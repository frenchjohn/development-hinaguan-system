<?php

namespace Database\Seeders;

use App\Models\ParkRule;
use Illuminate\Database\Seeder;

class ParkRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'rule_name' => 'Proper Swimming Pool Attire',
                'rule_descriptions' => 'Proper swimwear (rash guards, swim trunks, bathing suits) is required when entering swimming pools. Cotton t-shirts, denim pants, and undergarments are strictly prohibited in the water.',
            ],
            [
                'rule_name' => 'Outside Food & Corkage Policy',
                'rule_descriptions' => 'Guests may bring their own food and non-alcoholic beverages with zero corkage fee. Free outdoor grilling stations are provided for guest use. Please bring your own charcoal and cooking utensils.',
            ],
            [
                'rule_name' => 'Quiet Hours & Respect',
                'rule_descriptions' => 'Quiet hours are observed from 10:00 PM to 6:00 AM to ensure comfort for overnight guests and nearby nature surroundings. High-volume sound systems must be lowered.',
            ],
            [
                'rule_name' => 'Clean As You Go (CLAYGO)',
                'rule_descriptions' => 'Help maintain the pristine beauty of our park. Please clean up your cottage area before departure and place all trash into labeled waste segregation bins.',
            ],
            [
                'rule_name' => 'Pet Policy & Guidelines',
                'rule_descriptions' => 'Pets are warmly welcome inside the park premises but must be kept on a leash at all times. Pet owners are solely responsible for cleaning up after their pets.',
            ],
            [
                'rule_name' => 'Designated Smoking Areas',
                'rule_descriptions' => 'Smoking and vaping are only permitted in designated smoking zones away from cottages, dining pavilions, and children swimming areas.',
            ],
        ];

        foreach ($rules as $rule) {
            ParkRule::firstOrCreate(
                ['rule_name' => $rule['rule_name']],
                ['rule_descriptions' => $rule['rule_descriptions']]
            );
        }
    }
}
