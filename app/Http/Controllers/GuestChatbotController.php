<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class GuestChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = $request->input('message');
        
        // Check if message is about the resort
        $forbiddenTopics = ['coding', 'politics', 'games', 'school', 'religion', 'celebrity', 'programming', 'developer', 'staff', 'admin', 'revenue', 'financial', 'payment status', 'occupancy', 'check-in', 'check-out', 'guest count', 'reservation count'];
        $isForbidden = false;
        
        foreach ($forbiddenTopics as $topic) {
            if (stripos($userMessage, $topic) !== false) {
                $isForbidden = true;
                break;
            }
        }
        
        if ($isForbidden) {
            return response()->json([
                'reply' => "I'm sorry, I can only assist with questions about our amenities and booking information. For other inquiries, please contact park staff."
            ]);
        }

        $apiKey = env('OPENROUTER_API_KEY');
        $model = $request->input('model', 'openrouter/free');
        
        if (!$apiKey) {
            \Log::error('OpenRouter API Key is missing');
            return response()->json([
                'error' => 'API Key Missing',
                'reply' => 'I apologize, but the chatbot service is currently unavailable. Please contact park staff for assistance.'
            ], 500);
        }

        // Fetch amenities data for guest context
        $amenitiesContext = $this->getAmenitiesContext($userMessage);

        $systemPrompt = "You are HinaguanBot, a helpful AI assistant for guests at Hinaguan Nature Park. Keep responses short and direct (under 50 words). Answer only what was asked. No filler, no suggestions to check elsewhere, no follow-up questions unless necessary.\n\nYou can ONLY help with:\n1. Information about park amenities (cottages, facilities, activities)\n2. Booking process and how to make reservations\n3. Rates and pricing information\n4. Park hours and location\n\nYou CANNOT provide information about:\n- Staff operations\n- Reservation counts or guest statistics\n- Financial data\n- Occupancy rates\n- Check-in/check-out status of other guests\n\nPark Hours: 6:00 AM - 6:00 PM (daytime), Overnight check-in from 6:00 PM\nLocation: Hinaguan, Jasaan, Misamis Oriental\nContact: 0917 861 8383\n\n{$amenitiesContext}";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => request()->getHttpHost(),
                'X-Title' => 'Hinaguan Nature Park',
            ])->post("https://openrouter.ai/api/v1/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage
                    ]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'I apologize, but I could not process your request. Please contact park staff.';
                
                // Clean up the response
                $reply = str_replace(['HinaguanBot:', 'Bot:'], '', $reply);
                $reply = trim($reply);
                
                return response()->json([
                    'reply' => $reply
                ]);
            } else {
                \Log::error('OpenRouter API Error: ' . $response->body());
            
                // Return actual error details for debugging
                return response()->json([
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'reply' => 'I apologize, but I encountered an error. Please contact park staff for assistance.'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Chatbot Exception: ' . $e->getMessage());
            return response()->json([
                'reply' => 'I apologize, but the service is temporarily unavailable. Please contact park staff for assistance.'
            ], 500);
        }
    }

    private function getAmenitiesContext($message)
    {
        $context = '';
        $messageLower = strtolower($message);
        
        // Fetch all amenities
        $amenities = DB::table('amenities')->get();
        
        if ($amenities->isEmpty()) {
            return '';
        }
        
        // Always provide amenities data for better recommendations
        $context .= "\n\nAvailable Amenities:\n";
        
        // Sort amenities by capacity and price for easier analysis
        $amenitiesByCapacity = $amenities->sortByDesc('maximum_capacity');
        $amenitiesByPrice = $amenities->sortBy('daytime_price');
        
        // Detailed amenities list
        foreach ($amenities as $amenity) {
            $context .= "- {$amenity->amenities_name}: ";
            if ($amenity->daytime_price) {
                $context .= "Daytime ₱" . number_format($amenity->daytime_price, 2);
            }
            if ($amenity->nighttime_price) {
                $context .= ($amenity->daytime_price ? ', ' : '') . "Nighttime ₱" . number_format($amenity->nighttime_price, 2);
            }
            if ($amenity->minimum_capacity && $amenity->maximum_capacity) {
                $context .= ", Capacity: {$amenity->minimum_capacity}-{$amenity->maximum_capacity} guests";
            }
            if ($amenity->description) {
                $context .= ". {$amenity->description}";
            }
            $context .= "\n";
        }
        
        // Add recommendations based on query
        // Capacity-based recommendations
        if (preg_match('/(\d+)\s*(people|guests|person|pax)/', $messageLower, $matches)) {
            $guestCount = (int)$matches[1];
            $suitableAmenities = $amenities->filter(function($amenity) use ($guestCount) {
                return $amenity->minimum_capacity <= $guestCount && $amenity->maximum_capacity >= $guestCount;
            });
            
            if ($suitableAmenities->isNotEmpty()) {
                $context .= "\n\nSuitable for {$guestCount} guests:\n";
                foreach ($suitableAmenities as $amenity) {
                    $context .= "- {$amenity->amenities_name} (Capacity: {$amenity->minimum_capacity}-{$amenity->maximum_capacity})\n";
                }
            }
        }
        
        // Price-based recommendations
        if (str_contains($messageLower, 'cheap') || str_contains($messageLower, 'cheapest') || str_contains($messageLower, 'affordable') || str_contains($messageLower, 'budget')) {
            $context .= "\n\nMost Affordable Options:\n";
            $cheapest = $amenitiesByPrice->take(3);
            foreach ($cheapest as $amenity) {
                $context .= "- {$amenity->amenities_name}: Daytime ₱" . number_format($amenity->daytime_price, 2) . "\n";
            }
        }
        
        // Large capacity recommendations
        if (str_contains($messageLower, 'large') || str_contains($messageLower, 'big') || str_contains($messageLower, 'many people') || str_contains($messageLower, 'group')) {
            $context .= "\n\nLarge Capacity Options:\n";
            $largest = $amenitiesByCapacity->take(3);
            foreach ($largest as $amenity) {
                $context .= "- {$amenity->amenities_name}: Up to {$amenity->maximum_capacity} guests\n";
            }
        }
        
        // Sleeping/overnight recommendations
        if (str_contains($messageLower, 'sleep') || str_contains($messageLower, 'overnight') || str_contains($messageLower, 'stay')) {
            $context .= "\n\nOvernight Options:\n";
            $overnightAmenities = $amenities->filter(function($amenity) {
                return $amenity->nighttime_price && $amenity->nighttime_price > 0;
            });
            foreach ($overnightAmenities as $amenity) {
                $context .= "- {$amenity->amenities_name}: Nighttime ₱" . number_format($amenity->nighttime_price, 2) . "\n";
            }
        }
        
        // Booking flow information
        if (str_contains($messageLower, 'book') || str_contains($messageLower, 'reserve') || str_contains($messageLower, 'how to')) {
            $context .= "\n\nBooking Process:\n";
            $context .= "1. Visit the reservation page\n";
            $context .= "2. Select your preferred date and amenities\n";
            $context .= "3. Fill in your personal details\n";
            $context .= "4. Pay 10% downpayment to confirm\n";
            $context .= "5. Receive confirmation via email\n";
            $context .= "6. Check-in at the park on your scheduled date\n";
        }
        
        // Rates information
        if (str_contains($messageLower, 'rate') || str_contains($messageLower, 'price') || str_contains($messageLower, 'cost') || str_contains($messageLower, 'fee')) {
            $context .= "\n\nRates:\n";
            $context .= "Daytime Entrance: Adult ₱70, Child ₱50\n";
            $context .= "Overnight Entrance: Adult ₱100, Child ₱70\n";
            $context .= "Downpayment: 10% of total amount\n";
        }
        
        return $context;
    }
}
