<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\ParkEvent;
use App\Models\ParkActivity;
use App\Models\ParkSetting;
use App\Models\ReservationGuest;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(WeatherService $weather): View
    {
        $activeGuestCount = ReservationGuest::query()
            ->whereNull('checked_out_at')
            ->whereHas('reservation', function ($query) {
                $query->where('status', 'Checked In');
            })
            ->count();

        $parkSettings = ParkSetting::first();

        $featuredFeedbacks = Feedback::visible()
            ->topRated()
            ->limit(10)
            ->get();

        $now = Carbon::now();
        $oneWeekAhead = $now->copy()->addDays(7)->toDateString();

        // Nearest upcoming event in range 1 week (from today to 7 days ahead)
        $nearEvent = ParkEvent::active()
            ->whereBetween('date', [$now->toDateString(), $oneWeekAhead])
            ->orderBy('date', 'asc')
            ->first();

        // All active events for the events section
        $allEvents = ParkEvent::active()
            ->orderBy('date', 'asc')
            ->get();

        $activities = ParkActivity::query()
            ->orderBy('id')
            ->get();

        // Gallery Images from public/storage/gallery_images
        $galleryDir = public_path('storage/gallery_images');
        $galleryImages = [];
        if (is_dir($galleryDir)) {
            $files = scandir($galleryDir);
            natsort($files);
            foreach ($files as $file) {
                if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $galleryImages[] = [
                        'filename' => $file,
                        'url' => asset('storage/gallery_images/' . $file),
                        'title' => '',
                    ];
                }
            }
        }

        // Featured Gallery Images (image_8 to image_14 - strictly 7 images)
        $featuredGalleryImages = array_values(array_filter($galleryImages, function ($img) {
            if (preg_match('/image_(\d+)/', $img['filename'], $matches)) {
                $num = (int)$matches[1];
                return $num >= 8 && $num <= 14;
            }
            return false;
        }));

        if (empty($featuredGalleryImages) && !empty($galleryImages)) {
            $featuredGalleryImages = array_slice($galleryImages, 0, 7);
        } else {
            $featuredGalleryImages = array_slice($featuredGalleryImages, 0, 7);
        }

        return view('homepage', [
            'weather' => $weather->getTodayWeather(),
            'activeGuestCount' => $activeGuestCount,
            'parkSettings' => $parkSettings,
            'featuredFeedbacks' => $featuredFeedbacks,
            'nearEvent' => $nearEvent,
            'allEvents' => $allEvents,
            'activities' => $activities,
            'galleryImages' => $galleryImages,
            'featuredGalleryImages' => $featuredGalleryImages,
        ]);
    }
}
