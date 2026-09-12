<?php

namespace App\Services;

use App\Models\Feedback;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FeedbackAiService
{
    /**
     * Profanity, insults, derogatory terms, shortcuts, and wrong spellings in Tagalog, Bisaya, and English.
     */
    protected const PROFANITY_KEYWORDS = [
        // Tagalog / Filipino phrases and curses
        'putangina' => 'Severely offensive Tagalog curse word',
        'putang ina' => 'Severely offensive Tagalog curse phrase',
        'ptangina' => 'Severely offensive Tagalog curse shortcut',
        'ptang ina' => 'Severely offensive Tagalog curse phrase shortcut',
        'tanginamo' => 'Direct offensive insult to recipient',
        'tang ina mo' => 'Direct offensive insult phrase',
        'tnginamo' => 'Direct offensive insult shortcut',
        'tng ina mo' => 'Direct offensive insult phrase shortcut',
        'tanginang' => 'Offensive Tagalog curse expression',
        'tnginang' => 'Offensive Tagalog curse expression shortcut',
        'tangina' => 'Offensive Tagalog curse word',
        'tang ina' => 'Offensive Tagalog curse phrase',
        'tngina' => 'Offensive Tagalog curse shortcut (tangina)',
        'tng ina' => 'Offensive Tagalog curse phrase shortcut',
        'tangena' => 'Offensive Tagalog curse word variant',
        'tngena' => 'Offensive Tagalog curse shortcut variant',
        'ptngna' => 'Offensive Tagalog curse abbreviation',
        'putang' => 'Offensive profanity',
        'potang' => 'Offensive profanity variant',
        'puta' => 'Offensive profanity',
        'pota' => 'Offensive curse word',
        'pta' => 'Offensive curse shortcut',
        'gago' => 'Offensive Tagalog profanity',
        'ggo' => 'Offensive Tagalog profanity shortcut (gago)',
        'gaga' => 'Offensive Tagalog profanity',
        'gga' => 'Offensive Tagalog profanity shortcut (gaga)',
        'gagsti' => 'Offensive Tagalog slang',
        'tanga' => 'Derogatory Tagalog insult meaning stupid',
        'tnga' => 'Derogatory Tagalog insult shortcut (tanga)',
        'bobo' => 'Derogatory insult meaning dumb/foolish',
        'bbo' => 'Derogatory insult shortcut (bobo)',
        'inutil' => 'Insult meaning useless/incompetent',
        'kupal' => 'Vulgar Filipino profanity',
        'kpal' => 'Vulgar Filipino profanity shortcut',
        'kpl' => 'Vulgar Filipino profanity shortcut',
        'tarantado' => 'Offensive Tagalog curse word',
        'tarntado' => 'Offensive Tagalog curse word misspelling',
        'trntdo' => 'Offensive Tagalog curse shortcut',
        'trantado' => 'Offensive Tagalog curse variant',
        'pakshet' => 'Filipino profanity slang',
        'paksht' => 'Filipino profanity slang shortcut',
        'pkshet' => 'Filipino profanity slang shortcut',
        'pksht' => 'Filipino profanity slang shortcut',
        'pakshit' => 'Filipino profanity slang variant',
        'pkshit' => 'Filipino profanity slang shortcut',
        'pakyu' => 'Offensive phonetic insult (fuck you)',
        'pak yu' => 'Offensive phonetic insult phrase',
        'pakyow' => 'Offensive phonetic insult variant',
        'fakyu' => 'Offensive phonetic insult variant',
        'fak yu' => 'Offensive phonetic insult phrase',
        'fckyu' => 'Offensive phonetic insult shortcut',
        'fck yu' => 'Offensive phonetic insult shortcut phrase',
        'fukyu' => 'Offensive phonetic insult variant',
        'fuk yu' => 'Offensive phonetic insult variant phrase',
        'fck u' => 'Offensive curse shortcut phrase',
        'fak u' => 'Offensive curse variant phrase',
        'fuk u' => 'Offensive curse variant phrase',
        'hudas' => 'Offensive traitor/religious slur',
        'puki' => 'Vulgar anatomical term',
        'puke' => 'Vulgar anatomical term',
        'leche' => 'Offensive Spanish/Filipino curse',
        'letse' => 'Offensive curse expression',
        'lche' => 'Offensive curse shortcut',
        'ltse' => 'Offensive curse shortcut',
        'bwisit' => 'Offensive curse meaning annoyance/jinx',
        'bwesit' => 'Offensive curse meaning annoyance/jinx',
        'bwsit' => 'Offensive curse shortcut',
        'bwst' => 'Offensive curse shortcut',
        'bwiset' => 'Offensive curse variant',
        'bwset' => 'Offensive curse shortcut variant',
        'ulol' => 'Derogatory Filipino insult meaning fool/idiot',
        'olol' => 'Derogatory Filipino insult meaning fool/idiot',
        'ulul' => 'Derogatory Filipino insult variant',

        // Bisaya curses, shortcuts, and vulgarities
        'piste' => 'Bisaya curse word (pest/annoyance)',
        'pisti' => 'Bisaya curse word variant',
        'psti' => 'Bisaya curse word shortcut (pisti)',
        'pste' => 'Bisaya curse word shortcut (peste)',
        'psteng' => 'Bisaya curse expression (pesteng)',
        'pesteng' => 'Bisaya curse expression',
        'pisteng' => 'Bisaya curse expression',
        'pistet' => 'Bisaya curse expression',
        'pistiha' => 'Bisaya curse expression',
        'pesteha' => 'Bisaya curse expression',
        'pestea' => 'Bisaya curse expression',
        'pinisti' => 'Bisaya curse word variant used as an intensified expression',
        'peste' => 'Curse word meaning pest/nuisance',
        'yawa' => 'Bisaya curse word (devil/demon)',
        'yawaa' => 'Bisaya curse word expression',
        'ywa' => 'Bisaya curse word shortcut (yawa)',
        'ywaa' => 'Bisaya curse word shortcut (yawaa)',
        'giatay' => 'Bisaya curse word (giatay)',
        'gyatay' => 'Bisaya curse word shortcut (giatay)',
        'iatay' => 'Bisaya curse word variant',
        'atay' => 'Bisaya curse word/vulgar exclamation',
        'aty' => 'Bisaya curse word shortcut (atay)',
        'atya' => 'Bisaya curse word shortcut (atya)',
        'buang' => 'Bisaya insult meaning crazy/insane',
        'boang' => 'Bisaya insult meaning crazy/insane',
        'bwang' => 'Bisaya insult shortcut (buang)',
        'bwng' => 'Bisaya insult shortcut (buang)',
        'bwoang' => 'Bisaya insult variant',
        'bilat' => 'Vulgar Bisaya anatomical term',
        'blat' => 'Vulgar Bisaya anatomical shortcut (bilat)',
        'bilatibay' => 'Vulgar Bisaya curse word',
        'blatibay' => 'Vulgar Bisaya curse shortcut',
        'bilatsina' => 'Vulgar Bisaya curse word',
        'otin' => 'Vulgar Bisaya anatomical term',
        'otn' => 'Vulgar Bisaya anatomical shortcut (otin)',
        'amaw' => 'Bisaya insult meaning foolish/idiot',

        // English profanity, misspellings, and shortcuts
        'motherfucker' => 'Severe English profanity',
        'mother fucker' => 'Severe English profanity phrase',
        'mfer' => 'Severe English profanity shortcut (motherfucker)',
        'fuck' => 'Severe English profanity',
        'fck' => 'Severe English profanity shortcut (fuck)',
        'fack' => 'Severe English profanity phonetic misspelling',
        'fuk' => 'Severe English profanity variant',
        'fak' => 'Severe English profanity phonetic variant',
        'fucking' => 'Severe English profanity',
        'fckin' => 'Severe English profanity shortcut',
        'fckng' => 'Severe English profanity shortcut',
        'fcking' => 'Severe English profanity shortcut',
        'fukin' => 'Severe English profanity variant',
        'fuking' => 'Severe English profanity variant',
        'fucker' => 'Severe English profanity',
        'fckr' => 'Severe English profanity shortcut',
        'shit' => 'English profanity',
        'sht' => 'English profanity shortcut (shit)',
        'shyt' => 'English profanity variant',
        'shite' => 'English profanity variant',
        'bullshit' => 'English profanity',
        'bull shit' => 'English profanity phrase',
        'bllsht' => 'English profanity shortcut',
        'bullsht' => 'English profanity shortcut',
        'bitch' => 'Offensive English derogatory term',
        'btch' => 'Offensive English derogatory shortcut (bitch)',
        'bitches' => 'Offensive English derogatory term',
        'btches' => 'Offensive English derogatory shortcut',
        'betch' => 'Offensive English derogatory variant',
        'asshole' => 'Offensive English derogatory insult',
        'ashole' => 'Offensive English derogatory misspelling',
        'ahole' => 'Offensive English derogatory shortcut',
        'a-hole' => 'Offensive English derogatory shortcut',
        'azzhole' => 'Offensive English derogatory variant',
        'bastard' => 'Offensive English insult',
        'bastrd' => 'Offensive English insult shortcut',
        'bstrd' => 'Offensive English insult shortcut',
        'dick' => 'Vulgar anatomical term',
        'dck' => 'Vulgar anatomical shortcut',
        'dik' => 'Vulgar anatomical variant',
        'cunt' => 'Severe English vulgar term',
        'slut' => 'Offensive English derogatory term',
        'whore' => 'Offensive English derogatory term',
        'nigger' => 'Severely offensive racial slur',
        'nigga' => 'Offensive racial slur variant',
        'scam' => 'Accusation of fraudulent practice',
    ];

    /**
     * Negative keywords and their operational context.
     */
    protected const NEGATIVE_MAPPINGS = [
        'hugaw' => ['topic' => 'Cleanliness', 'reason' => 'Noted dirty or unsanitary conditions'],
        'marumi' => ['topic' => 'Cleanliness', 'reason' => 'Noted dirty or unkempt park grounds'],
        'dirty' => ['topic' => 'Cleanliness', 'reason' => 'Reported dirty facilities or environment'],
        'filthy' => ['topic' => 'Cleanliness', 'reason' => 'Reported severe cleanliness issue'],
        'baho' => ['topic' => 'Hygiene', 'reason' => 'Reported foul odor or unhygienic smell'],
        'mabaho' => ['topic' => 'Hygiene', 'reason' => 'Complained about unpleasant smell/odor'],
        'smelly' => ['topic' => 'Hygiene', 'reason' => 'Reported bad smell around the area'],
        'stinky' => ['topic' => 'Hygiene', 'reason' => 'Reported foul odors'],
        'kasilyas' => ['topic' => 'Restrooms', 'reason' => 'Mentioned restroom / toilet concerns'],
        'banyo' => ['topic' => 'Restrooms', 'reason' => 'Mentioned restroom / bathroom facilities'],
        'toilet' => ['topic' => 'Restrooms', 'reason' => 'Reported issue with toilet facilities'],
        'restroom' => ['topic' => 'Restrooms', 'reason' => 'Reported restroom maintenance issue'],
        'dugay' => ['topic' => 'Service Speed', 'reason' => 'Complained of slow response or long waiting times'],
        'matagal' => ['topic' => 'Service Speed', 'reason' => 'Noted excessive delays or slow service'],
        'slow' => ['topic' => 'Service Speed', 'reason' => 'Complained about sluggish service'],
        'mahal' => ['topic' => 'Pricing', 'reason' => 'Expressed that entrance fees or amenities are expensive'],
        'expensive' => ['topic' => 'Pricing', 'reason' => 'Expressed dissatisfaction with high pricing'],
        'overpriced' => ['topic' => 'Pricing', 'reason' => 'Felt rates were too high for the experience'],
        'guba' => ['topic' => 'Maintenance', 'reason' => 'Reported broken or damaged park amenities'],
        'broken' => ['topic' => 'Maintenance', 'reason' => 'Reported broken facility or fixture'],
        'samok' => ['topic' => 'Crowd / Noise', 'reason' => 'Expressed frustration with chaotic crowding or disturbances'],
        'saba' => ['topic' => 'Noise', 'reason' => 'Complained of excessive noise disturbing the peace'],
        'noisy' => ['topic' => 'Noise', 'reason' => 'Reported disturbance from loud surroundings'],
        'crowded' => ['topic' => 'Crowding', 'reason' => 'Expressed dissatisfaction with overcrowding'],
        'rude' => ['topic' => 'Staff Behavior', 'reason' => 'Reported disrespectful or impolite staff behavior'],
        'bastos' => ['topic' => 'Staff Behavior', 'reason' => 'Complained about discourteous behavior'],
        'bad' => ['topic' => 'General Experience', 'reason' => 'Expressed negative sentiment about experience'],
        'terrible' => ['topic' => 'General Experience', 'reason' => 'Strong dissatisfaction with visit'],
        'horrible' => ['topic' => 'General Experience', 'reason' => 'Severe negative impression'],
        'worst' => ['topic' => 'General Experience', 'reason' => 'Extreme negative evaluation'],
        'poor' => ['topic' => 'General Quality', 'reason' => 'Felt quality was below expectations'],
        'disappointed' => ['topic' => 'Expectations', 'reason' => 'Stated expectations were not met'],
        'disappointing' => ['topic' => 'Expectations', 'reason' => 'Felt the visit was unsatisfactory'],
        'walang kwenta' => ['topic' => 'Value', 'reason' => 'Felt the experience had poor value'],
        'walay ayo' => ['topic' => 'Service', 'reason' => 'Expressed that service was unacceptable'],
        'sayang' => ['topic' => 'Value', 'reason' => 'Felt money or time spent was wasted'],
    ];

    /**
     * Positive keywords and their operational context.
     */
    protected const POSITIVE_MAPPINGS = [
        'nindot' => ['topic' => 'Scenery & Aesthetics', 'reason' => 'Admired the beauty and scenic views of the park'],
        'maganda' => ['topic' => 'Scenery & Aesthetics', 'reason' => 'Praised the pleasant and attractive environment'],
        'beautiful' => ['topic' => 'Scenery', 'reason' => 'Complimented the park aesthetics and natural visual appeal'],
        'scenic' => ['topic' => 'Scenery', 'reason' => 'Loved the picturesque natural landscapes'],
        'gwapa' => ['topic' => 'Aesthetics', 'reason' => 'Appreciated the attractive surroundings'],
        'gwapo' => ['topic' => 'Aesthetics', 'reason' => 'Appreciated the park layout and beauty'],
        'limpyo' => ['topic' => 'Cleanliness', 'reason' => 'Commended the clean and well-kept surroundings'],
        'clean' => ['topic' => 'Cleanliness', 'reason' => 'Praised the high hygiene standards of grounds and amenities'],
        'presko' => ['topic' => 'Atmosphere', 'reason' => 'Enjoyed the fresh, cool, and invigorating air'],
        'refreshing' => ['topic' => 'Atmosphere', 'reason' => 'Felt rejuvenated by the natural riverside environment'],
        'bugnaw' => ['topic' => 'River / Cold Spring', 'reason' => 'Enjoyed the cool and refreshing natural river water'],
        'cold' => ['topic' => 'River Water', 'reason' => 'Appreciated the natural cold river streams'],
        'relaxing' => ['topic' => 'Ambiance', 'reason' => 'Found the park tranquil and great for relaxation'],
        'relax' => ['topic' => 'Relaxation', 'reason' => 'Found the park relaxing and peaceful'],
        'maka relax' => ['topic' => 'Relaxation', 'reason' => 'Able to unwind and relax properly'],
        'tarong' => ['topic' => 'Quality Experience', 'reason' => 'Expressed that they were able to enjoy the park properly and peacefully'],
        'peaceful' => ['topic' => 'Ambiance', 'reason' => 'Praised the calm, serene, and stress-free environment'],
        'tahimik' => ['topic' => 'Ambiance', 'reason' => 'Appreciated the quiet and serene natural setting'],
        'chill' => ['topic' => 'Ambiance', 'reason' => 'Enjoyed the laid-back and chill vibe'],
        'lingaw' => ['topic' => 'Entertainment / Fun', 'reason' => 'Had great fun and enjoyed recreational activities with family/friends'],
        'fun' => ['topic' => 'Enjoyment', 'reason' => 'Had an enjoyable and memorable visit'],
        'enjoy' => ['topic' => 'Enjoyment', 'reason' => 'Expressed overall joy and satisfaction'],
        'enjoyed' => ['topic' => 'Enjoyment', 'reason' => 'Had a positive and fulfilling visit'],
        'buotan' => ['topic' => 'Staff Hospitality', 'reason' => 'Praised the kind and warm hospitality of park staff'],
        'mababait' => ['topic' => 'Staff Hospitality', 'reason' => 'Commended the polite and welcoming staff members'],
        'friendly' => ['topic' => 'Staff Hospitality', 'reason' => 'Appreciated courteous and approachable staff assistance'],
        'helpful' => ['topic' => 'Staff Hospitality', 'reason' => 'Commended staff for going out of their way to assist'],
        'barato' => ['topic' => 'Affordability', 'reason' => 'Found entrance and amenity fees affordable'],
        'mura' => ['topic' => 'Affordability', 'reason' => 'Considered the park budget-friendly'],
        'affordable' => ['topic' => 'Affordability', 'reason' => 'Appreciated reasonable and fair pricing'],
        'sulit' => ['topic' => 'Value for Money', 'reason' => 'Felt the experience provided outstanding value'],
        'chada' => ['topic' => 'General Quality', 'reason' => 'Expressed high satisfaction with the park experience'],
        'tsada' => ['topic' => 'General Quality', 'reason' => 'Expressed great delight with the resort facilities'],
        'lami' => ['topic' => 'Food & Drinks', 'reason' => 'Praised delicious food offerings and refreshments'],
        'masarap' => ['topic' => 'Food & Drinks', 'reason' => 'Complimented appetizing dishes and snacks'],
        'delicious' => ['topic' => 'Food & Drinks', 'reason' => 'Loved the quality and taste of available food'],
        'perpekto' => ['topic' => 'Excellence', 'reason' => 'Rated the visit as a flawless, perfect getaway'],
        'perfect' => ['topic' => 'Excellence', 'reason' => 'Felt the park met all criteria for an ideal stay'],
        'recommend' => ['topic' => 'Recommendation', 'reason' => 'Would actively recommend the park to other visitors'],
        'rekomenda' => ['topic' => 'Recommendation', 'reason' => 'Expressed strong recommendation to peers'],
        'excellent' => ['topic' => 'High Quality', 'reason' => 'Gave top marks for overall park standards'],
        'amazing' => ['topic' => 'High Quality', 'reason' => 'Thrilled with the overall visit experience'],
        'wonderful' => ['topic' => 'High Quality', 'reason' => 'Described the visit as truly delightful'],
        'good' => ['topic' => 'Satisfaction', 'reason' => 'Confirmed overall positive impression'],
        'great' => ['topic' => 'Satisfaction', 'reason' => 'Delighted with the park offering'],
        'love' => ['topic' => 'Affection', 'reason' => 'Expressed strong love for the park setting'],
        'loved' => ['topic' => 'Affection', 'reason' => 'Great fondness for the park experience'],
    ];

    /**
     * Generate normalized variants to catch leetspeak, repeated letters, and obfuscations.
     *
     * @return array<int, string>
     */
    public function normalizeVariants(string $text): array
    {
        $variants = [];

        // 1. Lowercase and collapse consecutive whitespace
        $clean = mb_strtolower(trim($text));
        $clean = preg_replace('/\s+/u', ' ', $clean);
        if ($clean === '') {
            return [];
        }
        $variants[] = $clean;

        // 2. Leetspeak mapping
        $leetMap = [
            '@' => 'a',
            '$' => 's',
            '0' => 'o',
            '1' => 'i',
            '!' => 'i',
            '3' => 'e',
            '5' => 's',
            '7' => 't',
        ];
        $deLeet = strtr($clean, $leetMap);
        if ($deLeet !== $clean) {
            $variants[] = $deLeet;
        }

        // 3. Collapse character repetitions (3+ identical characters collapsed to 1, and 2+ collapsed)
        foreach ([$clean, $deLeet] as $v) {
            $collapsed3 = preg_replace('/(.)\1{2,}/u', '$1', $v);
            if ($collapsed3 !== $v) {
                $variants[] = $collapsed3;
            }
            $collapsed2 = preg_replace('/(.)\1+/u', '$1', $v);
            if ($collapsed2 !== $v && $collapsed2 !== $collapsed3) {
                $variants[] = $collapsed2;
            }
        }

        // 4. Remove masking punctuation and intra-word separators (e.g. f*ck -> fck, p.s.t.i -> psti)
        foreach ([$clean, $deLeet] as $v) {
            $stripped = preg_replace('/(?<=\pL)[*._\-~]+(?=\pL)/u', '', $v);
            if ($stripped !== $v) {
                $variants[] = $stripped;
            }

            // Collapse spaced letters (e.g. "p s t i" -> "psti")
            $spacedLetters = $v;
            while (preg_match('/(\b\pL)\s+(\pL\b)/u', $spacedLetters)) {
                $spacedLetters = preg_replace('/(\b\pL)\s+(\pL\b)/u', '$1$2', $spacedLetters);
            }
            if ($spacedLetters !== $v) {
                $variants[] = $spacedLetters;
            }
        }

        return array_values(array_unique($variants));
    }

    public function detectInappropriateContent(?string $text): array
    {
        $originalText = trim((string) $text);
        if ($originalText === '') {
            return [];
        }

        $variants = $this->normalizeVariants($originalText);
        $matches = [];

        foreach (self::PROFANITY_KEYWORDS as $word => $description) {
            $pattern = '/(?<!\pL)' . preg_quote($word, '/') . '(?!\pL)/iu';
            foreach ($variants as $variant) {
                if (preg_match($pattern, $variant)) {
                    $matches[$word] = [
                        'term' => $word,
                        'sentence' => $this->extractSentenceWithWord($originalText, $word),
                        'description' => $description,
                    ];
                    break;
                }
            }
        }

        return $matches;
    }

    public function moderateImages(array $images): array
    {
        $apiKey = config('services.openrouter.key');
        if ($apiKey === '' || $apiKey === null || $images === []) {
            return ['available' => false, 'blocked' => false, 'matches' => []];
        }

        try {
            $content = [[
                'type' => 'text',
                'text' => 'Review each attached image for sexual, nude, pornographic, or otherwise lewd content. Return only valid JSON in this exact format: {"blocked":true,"matches":[{"image":"filename.jpg","reason":"brief reason"}]}. Set blocked to false and matches to [] when every image is safe. Do not block ordinary swimwear, family recreation, nature, food, or normal park photos.',
            ]];

            foreach ($images as $image) {
                $content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:' . $image->getMimeType() . ';base64,' . base64_encode($image->get()),
                    ],
                ];
            }

            $response = Http::timeout(25)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => request()->getHttpHost(),
                'X-Title' => 'Hinaguan Nature Park Feedback Moderation',
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => config('services.openrouter.vision_model', 'openrouter/free'),
                'messages' => [[
                    'role' => 'user',
                    'content' => $content,
                ]],
                'temperature' => 0,
                'max_tokens' => 300,
            ]);

            if (! $response->successful()) {
                Log::warning('Feedback image moderation request failed.', ['status' => $response->status()]);
                return ['available' => false, 'blocked' => false, 'matches' => []];
            }

            $rawContent = $response->json('choices.0.message.content');
            $rawContent = is_string($rawContent) ? $rawContent : json_encode($rawContent);
            if (! preg_match('/\{.*\}/s', (string) $rawContent, $jsonMatch)) {
                return ['available' => false, 'blocked' => false, 'matches' => []];
            }

            $result = json_decode($jsonMatch[0], true);
            if (! is_array($result)) {
                return ['available' => false, 'blocked' => false, 'matches' => []];
            }

            return [
                'available' => true,
                'blocked' => (bool) ($result['blocked'] ?? false),
                'matches' => array_values(array_filter((array) ($result['matches'] ?? []))),
            ];
        } catch (\Throwable $exception) {
            Log::warning('Feedback image moderation unavailable.', ['message' => $exception->getMessage()]);
            return ['available' => false, 'blocked' => false, 'matches' => []];
        }
    }

    /**
     * Analyze sentiment for a single feedback item with granular positive & negative phrase extraction.
     */
    public function analyzeSentiment(Feedback $feedback): array
    {
        $stars = (int) $feedback->stars;
        $name = trim($feedback->full_name ?? '');
        $text = trim($feedback->description ?? '');
        $lowerName = mb_strtolower($name);
        $lowerText = mb_strtolower($text);
        $combined = "{$lowerName} {$lowerText}";

        $points = [];
        $profanitiesFound = [];

        // 1. Detect Profanities
        $detectedProfanities = array_merge(
            $this->detectInappropriateContent($name),
            $this->detectInappropriateContent($text)
        );

        foreach ($detectedProfanities as $word => $meta) {
            $profanitiesFound[] = [
                'type' => 'flagged',
                'snippet' => $meta['term'],
                'topic' => 'Inappropriate Content',
                'reason' => $meta['description'],
                'how' => "Contains profane or abusive term '{$meta['term']}' ({$meta['description']}).",
                'emoji' => '🔴',
            ];
        }

        if (!empty($profanitiesFound)) {
            return [
                'sentiment' => 'negative',
                'label' => 'Flagged / Inappropriate',
                'emoji' => '🔴',
                'tone' => 'Inappropriate / Offensive Language',
                'summary' => 'Contains offensive words or inappropriate language in name/submission.',
                'explanation' => 'Flagged as Inappropriate because offensive terms were detected in the submission.',
                'points' => $profanitiesFound,
            ];
        }

        // 2. Detect Gibberish / Random keystroke spam
        if ($this->isGibberish($text)) {
            return [
                'sentiment' => 'neutral',
                'label' => 'Neutral (Gibberish)',
                'emoji' => '🟡',
                'tone' => 'Spam / Random Keystrokes',
                'summary' => 'Random keystrokes or non-meaningful text.',
                'explanation' => 'Classified as Gibberish because the review text consists of random keyboard smashing without coherent words.',
                'points' => [
                    [
                        'type' => 'neutral',
                        'snippet' => $text,
                        'topic' => 'Unintelligible Text',
                        'reason' => 'Contains random keystroke sequences without legible sentences',
                        'how' => 'Keyboard smashing detected; lacks coherent semantic structure.',
                        'emoji' => '🟡',
                    ]
                ],
            ];
        }

        // 3. Extract Granular Positive & Negative Clues from Text
        $posMatches = [];
        $negMatches = [];

        foreach (self::POSITIVE_MAPPINGS as $word => $meta) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $lowerText) || str_contains($lowerText, $word)) {
                $snippet = $this->extractSentenceWithWord($text, $word);
                $posMatches[] = [
                    'type' => 'positive',
                    'snippet' => $snippet,
                    'keyword' => $word,
                    'topic' => $meta['topic'],
                    'reason' => $meta['reason'],
                    'how' => "Positive because the guest highlighted {$meta['topic']}: \"{$meta['reason']}\".",
                    'emoji' => '🟢',
                ];
            }
        }

        foreach (self::NEGATIVE_MAPPINGS as $word => $meta) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $lowerText) || str_contains($lowerText, $word)) {
                $snippet = $this->extractSentenceWithWord($text, $word);
                $negMatches[] = [
                    'type' => 'negative',
                    'snippet' => $snippet,
                    'keyword' => $word,
                    'topic' => $meta['topic'],
                    'reason' => $meta['reason'],
                    'how' => "Negative because the guest raised concerns regarding {$meta['topic']}: \"{$meta['reason']}\".",
                    'emoji' => '🔴',
                ];
            }
        }

        // Deduplicate points by topic
        $posPoints = $this->deduplicatePoints($posMatches);
        $negPoints = $this->deduplicatePoints($negMatches);
        $allPoints = array_merge($posPoints, $negPoints);

        $numPos = count($posPoints);
        $numNeg = count($negPoints);

        // 4. Determine Overall Sentiment & Detailed Explanation (Driven STRICTLY by Name & Feedback Text)
        if ($numNeg > $numPos) {
            $sentiment = 'negative';
            $tone = 'Concerned / Report of issue';
            $reasons = array_values(array_unique(array_map(fn($p) => $p['topic'], $negPoints)));
            $snippets = array_values(array_unique(array_map(fn($p) => $p['snippet'], $negPoints)));
            $explanation = "Classified as Negative based on feedback highlighting concerns with: " . implode(', ', array_slice($reasons, 0, 3)) . ".";
            $allPoints = [
                [
                    'type' => 'negative',
                    'snippet' => implode(' | ', array_slice($snippets, 0, 2)) ?: $text,
                    'topic' => implode(', ', array_slice($reasons, 0, 2)),
                    'reason' => 'Guest expressed concerns regarding ' . implode(', ', $reasons) . '.',
                    'how' => 'Negative review: Identified operational issues and visitor dissatisfaction.',
                    'emoji' => '🔴',
                ]
            ];
        } elseif ($numPos > $numNeg) {
            $sentiment = 'positive';
            $tone = 'Satisfied & complimentary';
            $reasons = array_values(array_unique(array_map(fn($p) => $p['topic'], $posPoints)));
            $snippets = array_values(array_unique(array_map(fn($p) => $p['snippet'], $posPoints)));
            $explanation = "Classified as Positive based on feedback praising: " . implode(', ', array_slice($reasons, 0, 3)) . ".";
            $allPoints = [
                [
                    'type' => 'positive',
                    'snippet' => implode(' | ', array_slice($snippets, 0, 2)) ?: $text,
                    'topic' => implode(', ', array_slice($reasons, 0, 2)),
                    'reason' => 'Guest expressed positive compliments regarding ' . implode(', ', $reasons) . '.',
                    'how' => 'Positive review: The guest confirmed a pleasant, satisfying experience at the park.',
                    'emoji' => '🟢',
                ]
            ];
        } elseif ($numPos > 0 && $numNeg > 0 && $numPos === $numNeg) {
            $sentiment = 'neutral';
            $tone = 'Mixed feedback';
            $explanation = "Classified as Neutral due to mixed positive praises and concerns in the review text.";
            $allPoints = [
                [
                    'type' => 'neutral',
                    'snippet' => mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : ($text ?: 'Mixed review'),
                    'topic' => 'Mixed Feedback',
                    'reason' => 'Contains both positive remarks and constructive concerns.',
                    'how' => 'Neutral review: Balanced feedback with equal praise and complaints.',
                    'emoji' => '🟡',
                ]
            ];
        } else {
            $sentiment = 'neutral';
            $tone = 'General observation';
            $explanation = "Classified as Neutral because the review text contains no explicit positive or negative sentiment words.";
            $allPoints = [
                [
                    'type' => 'neutral',
                    'snippet' => mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : ($text ?: 'General submission'),
                    'topic' => 'General Feedback',
                    'reason' => 'Standard submission without extreme positive or negative sentiment words.',
                    'how' => 'Neutral review: General guest observation.',
                    'emoji' => '🟡',
                ]
            ];
        }

        $labels = [
            'positive' => 'Positive',
            'neutral' => 'Neutral',
            'negative' => 'Negative',
        ];

        $emojis = [
            'positive' => '🟢',
            'neutral' => '🟡',
            'negative' => '🔴',
        ];

        return [
            'sentiment' => $sentiment,
            'label' => $labels[$sentiment] ?? 'Neutral',
            'emoji' => $emojis[$sentiment] ?? '🟡',
            'tone' => $tone,
            'summary' => $this->generateOneSentenceSummary($feedback, $sentiment),
            'explanation' => $explanation,
            'points' => $allPoints,
        ];
    }

    /**
     * Deduplicate clues by topic.
     */
    protected function deduplicatePoints(array $points): array
    {
        $unique = [];
        $topicsSeen = [];

        foreach ($points as $p) {
            $topic = $p['topic'];
            if (!isset($topicsSeen[$topic])) {
                $topicsSeen[$topic] = true;
                $unique[] = $p;
            }
        }

        return array_slice($unique, 0, 4);
    }

    /**
     * Extract the sentence or phrase containing a specific keyword.
     */
    protected function extractSentenceWithWord(string $text, string $word): string
    {
        $sentences = preg_split('/(?<=[.?!\n])\s+/', $text);
        foreach ($sentences as $s) {
            if (stripos($s, $word) !== false) {
                return trim($s);
            }
        }

        // Fallback: check normalized variants of each sentence
        $pattern = '/(?<!\pL)' . preg_quote($word, '/') . '(?!\pL)/iu';
        foreach ($sentences as $s) {
            $variants = $this->normalizeVariants($s);
            foreach ($variants as $variant) {
                if (preg_match($pattern, $variant)) {
                    return trim($s);
                }
            }
        }

        return trim($text);
    }

    /**
     * Check if text contains profanities.
     */
    protected function containsProfanity(string $text): bool
    {
        return $this->detectInappropriateContent($text) !== [];
    }

    /**
     * Detect gibberish or spam keyboard smashing.
     */
    protected function isGibberish(string $text): bool
    {
        $clean = preg_replace('/\s+/', '', $text);
        if (mb_strlen($clean) === 0) {
            return true;
        }

        // Long single word without spaces (e.g. > 14 chars)
        if (!str_contains(trim($text), ' ') && mb_strlen($clean) >= 14) {
            // Check for home row keyboard smashing (adsad..., asdf..., hjkl..., etc.)
            if (preg_match('/(as|sa|sd|ds|ad|da|df|fd|fg|gf|gh|hg|hj|jh|jk|kj|kl|lk|qw|wq|we|ew|er|re|rt|tr|ty|yt|yu|uy|ui|iu|io|oi|op|po|zx|xz|xc|cx|cv|vc|vb|bv|bn|nb|nm|mn){3,}/i', $clean)) {
                return true;
            }

            // 5 or more consecutive consonants
            if (preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/i', $clean)) {
                return true;
            }
        }

        // Character repetition (e.g. "aaaaaa", "111111", "......")
        if (preg_match('/(.)\1{4,}/', $clean)) {
            return true;
        }

        return false;
    }

    /**
     * Generate a concise 1-sentence key takeaway for a review.
     */
    public function generateOneSentenceSummary(Feedback $feedback, string $sentiment): string
    {
        $desc = trim($feedback->description);
        $stars = $feedback->stars;

        if (mb_strlen($desc) <= 80) {
            return $desc ?: "Rated {$stars} out of 5 stars.";
        }

        $clean = preg_replace('/\s+/', ' ', $desc);
        $sentences = preg_split('/(?<=[.?!])\s+/', $clean, 2);
        $firstSentence = trim($sentences[0] ?? $clean);

        if (mb_strlen($firstSentence) > 120) {
            return mb_substr($firstSentence, 0, 117) . '...';
        }

        return $firstSentence;
    }

    /**
     * Generate Executive AI Insights summary for all reviews.
     */
    public function generateExecutiveInsights(?Collection $feedbacks = null, bool $forceFresh = false): array
    {
        $cacheKey = 'admin_feedback_ai_executive_insights';

        if (!$forceFresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        if ($feedbacks === null) {
            $feedbacks = Feedback::orderByDesc('created_at')->get();
        }

        $total = $feedbacks->count();

        if ($total === 0) {
            $emptyData = [
                'total_reviews' => 0,
                'positive_count' => 0,
                'positive_percent' => 0,
                'neutral_count' => 0,
                'neutral_percent' => 0,
                'negative_count' => 0,
                'negative_percent' => 0,
                'average_rating' => 0.0,
                'top_praises' => ['No reviews submitted yet.'],
                'top_issues' => ['No issues reported.'],
                'recommendation' => 'Encourage visitors to leave reviews during checkout to build park reputation.',
                'analyzed_at' => now()->format('M j, Y h:i A'),
            ];
            Cache::put($cacheKey, $emptyData, now()->addHours(6));
            return $emptyData;
        }

        $positive = 0;
        $neutral = 0;
        $negative = 0;
        $praises = [];
        $issues = [];

        foreach ($feedbacks as $fb) {
            $analysis = $this->analyzeSentiment($fb);
            if ($analysis['sentiment'] === 'positive') {
                $positive++;
                if (count($praises) < 4 && !$this->isGibberish($fb->description) && !$this->containsProfanity($fb->description)) {
                    $praises[] = $this->extractKeyPoint($fb->description, true);
                }
            } elseif ($analysis['sentiment'] === 'negative') {
                $negative++;
                if (count($issues) < 4 && !$this->isGibberish($fb->description) && !$this->containsProfanity($fb->description)) {
                    $issues[] = $this->extractKeyPoint($fb->description, false);
                }
            } else {
                $neutral++;
            }
        }

        $posPct = round(($positive / $total) * 100);
        $neuPct = round(($neutral / $total) * 100);
        $negPct = max(0, 100 - $posPct - $neuPct);
        $avgRating = round((float) $feedbacks->avg('stars'), 1);

        $praises = array_values(array_unique(array_filter($praises)));
        $issues = array_values(array_unique(array_filter($issues)));

        if (empty($praises)) {
            $praises = ['Guests appreciate the peaceful ambiance and natural park landscape.'];
        }

        if (empty($issues)) {
            $issues = ['No major grievances reported. General operations are running smoothly.'];
        }

        $recommendation = $this->formulateRecommendation($posPct, $negPct, $avgRating, $issues);

        $insights = [
            'total_reviews' => $total,
            'positive_count' => $positive,
            'positive_percent' => $posPct,
            'neutral_count' => $neutral,
            'neutral_percent' => $neuPct,
            'negative_count' => $negative,
            'negative_percent' => $negPct,
            'average_rating' => $avgRating,
            'top_praises' => array_slice($praises, 0, 3),
            'top_issues' => array_slice($issues, 0, 3),
            'recommendation' => $recommendation,
            'analyzed_at' => now()->format('M j, Y h:i A'),
        ];

        $enhanced = $this->attemptLlmInsightsEnhancement($feedbacks, $insights);
        $finalInsights = $enhanced ?: $insights;

        Cache::put($cacheKey, $finalInsights, now()->addHours(6));

        return $finalInsights;
    }

    /**
     * Extract a short human-readable key point from text.
     */
    protected function extractKeyPoint(string $text, bool $isPositive): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if (mb_strlen($text) <= 90) {
            return $text;
        }

        $sentences = preg_split('/(?<=[.?!])\s+/', $text);
        return trim($sentences[0] ?? mb_substr($text, 0, 87) . '...');
    }

    /**
     * Formulate operational recommendations based on sentiment distribution.
     */
    protected function formulateRecommendation(int $posPct, int $negPct, float $avgRating, array $issues): string
    {
        if ($negPct >= 20) {
            return 'High complaint volume detected. Prioritize immediate facility inspection, restroom hygiene checks, and staff service alignment.';
        }

        if ($avgRating >= 4.5 && $posPct >= 80) {
            return 'Overall guest satisfaction is exceptional. Maintain riverside cleanliness and feature guest photos in social media marketing.';
        }

        if ($posPct >= 65) {
            return 'Guest satisfaction is steady. Address recurring minor suggestions regarding weekend crowding and amenity maintenance.';
        }

        return 'Focus on staff responsiveness, clear signage along nature trails, and regular maintenance during peak weekend visiting hours.';
    }

    /**
     * Optional LLM-powered summary enhancement via OpenRouter API.
     */
    protected function attemptLlmInsightsEnhancement(Collection $feedbacks, array $baseInsights): ?array
    {
        $apiKey = env('OPENROUTER_API_KEY');
        if (!$apiKey) {
            return null;
        }

        try {
            $sampleReviews = $feedbacks->take(15)->map(function ($f) {
                return "- [{$f->stars}/5 stars] {$f->description}";
            })->implode("\n");

            $prompt = "You are the AI Executive Intelligence Analyst for Hinaguan Nature Park.\n"
                . "Analyze these recent visitor reviews and return a strictly valid JSON object with the following structure:\n"
                . "{\n"
                . "  \"top_praises\": [\"praise 1\", \"praise 2\", \"praise 3\"],\n"
                . "  \"top_issues\": [\"issue 1\", \"issue 2\"],\n"
                . "  \"recommendation\": \"1-2 sentence actionable operational recommendation for park management\"\n"
                . "}\n\n"
                . "Reviews:\n{$sampleReviews}";

            $response = Http::timeout(8)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => request()->getHttpHost(),
                'X-Title' => 'Hinaguan Nature Park Admin',
            ])->post("https://openrouter.ai/api/v1/chat/completions", [
                'model' => 'openrouter/free',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 450,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                if (preg_match('/\{.*\}/s', $content, $matches)) {
                    $parsed = json_decode($matches[0], true);
                    if (is_array($parsed)) {
                        $baseInsights['top_praises'] = !empty($parsed['top_praises']) ? (array) $parsed['top_praises'] : $baseInsights['top_praises'];
                        $baseInsights['top_issues'] = !empty($parsed['top_issues']) ? (array) $parsed['top_issues'] : $baseInsights['top_issues'];
                        $baseInsights['recommendation'] = !empty($parsed['recommendation']) ? (string) $parsed['recommendation'] : $baseInsights['recommendation'];
                        return $baseInsights;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::info('Feedback AI Service LLM fallback active: ' . $e->getMessage());
        }

        return null;
    }
}
