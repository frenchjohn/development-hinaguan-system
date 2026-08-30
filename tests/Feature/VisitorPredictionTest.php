<?php

namespace Tests\Feature;

use App\Models\DailyWeatherShiftLog;
use App\Services\VisitorPredictionService;
use App\Services\WeatherForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisitorPredictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_prediction_calculates_daytime_and_nighttime_separately()
    {
        $predictionService = new VisitorPredictionService();
        $report = $predictionService->predictForDate(Carbon::today()->toDateString());

        $this->assertArrayHasKey('daytime', $report);
        $this->assertArrayHasKey('nighttime', $report);
        $this->assertArrayHasKey('outlook', $report);

        $this->assertEquals('Daytime', $report['daytime']['shift']);
        $this->assertEquals('Nighttime', $report['nighttime']['shift']);

        $this->assertGreaterThan(0, $report['daytime']['predicted_guests']);
        $this->assertGreaterThan(0, $report['nighttime']['predicted_guests']);
        $this->assertNotEmpty($report['daytime']['earliest_arrival']);
        $this->assertNotEmpty($report['daytime']['peak_arrival_window']);
    }

    public function test_rainy_weather_reduces_predicted_visitor_count()
    {
        $predictionService = new VisitorPredictionService();
        $date = Carbon::parse('2026-08-30'); // Sunday

        $sunnyPrediction = $predictionService->predictShift($date, 'Daytime', 'Sunny', ['icon' => '☀️', 'temp' => 31, 'precip_prob' => 10]);
        $rainyPrediction = $predictionService->predictShift($date, 'Daytime', 'Rainy', ['icon' => '🌧️', 'temp' => 25, 'precip_prob' => 85]);

        // Rain prediction should be significantly less than sunny prediction
        $this->assertLessThan($sunnyPrediction['predicted_guests'], $rainyPrediction['predicted_guests']);
        $this->assertLessThan(0, $rainyPrediction['weather_impact_percent']);
    }

    public function test_admin_dashboard_renders_with_prediction_widget()
    {
        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'parkhinaguan@gmail.com',
                'role' => 'admin',
            ],
        ])->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Visitor Traffic Prediction');
        $response->assertSee('Daytime (8:00 AM');
        $response->assertSee('Nighttime (6:00 PM');
    }

    public function test_staff_dashboard_renders_with_prediction_widget()
    {
        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Staff User',
                'email' => 'staff@example.com',
                'role' => 'staff',
            ],
        ])->get(route('staff.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Visitor Traffic Prediction');
        $response->assertSee('Daytime (8:00 AM');
        $response->assertSee('Nighttime (6:00 PM');
    }

    public function test_knows_day_of_week_and_detects_holidays()
    {
        $predictionService = new VisitorPredictionService();

        // Christmas Day holiday
        $christmas = Carbon::parse('2026-12-25');
        $report = $predictionService->predictForDate($christmas->toDateString());

        $this->assertEquals('Friday', $report['day_name']);
        $this->assertTrue($report['is_holiday']);
        $this->assertEquals('Christmas Day', $report['holiday_name']);

        // Normal Tuesday
        $tuesday = Carbon::parse('2026-09-01'); // Tuesday
        $tueReport = $predictionService->predictForDate($tuesday->toDateString());
        $this->assertEquals('Tuesday', $tueReport['day_name']);
    }

    public function test_handles_insufficient_historical_data_gracefully()
    {
        $predictionService = new VisitorPredictionService();

        // Empty database: no logs exist for Tuesday
        $tuesday = Carbon::parse('2026-09-01');
        $shiftReport = $predictionService->predictShift($tuesday, 'Daytime', 'Sunny');

        $this->assertFalse($shiftReport['has_sufficient_data']);
        $this->assertStringContainsString('Baseline', $shiftReport['confidence']);
        $this->assertGreaterThan(0, $shiftReport['predicted_guests']);
    }

    public function test_records_daily_shift_log_and_runs_sync_command()
    {
        $predictionService = new VisitorPredictionService();
        $log = $predictionService->recordShiftLog('2026-08-28', 'Daytime', 'Sunny', 31.0);

        $this->assertNotNull($log);
        $this->assertEquals('2026-08-28', $log->log_date->toDateString());
        $this->assertEquals('Daytime', $log->shift);
        $this->assertEquals('Sunny', $log->weather_condition);

        $this->artisan('weather:log-shifts --days=2')
            ->assertExitCode(0);
    }
}


