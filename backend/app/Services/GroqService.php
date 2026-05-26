<?php

namespace App\Services;

use App\Models\SoilAnalysis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.groq.key');
        $this->model = config('services.groq.model', 'llama3-8b-8192');
    }

    /**
     * Tuproq tahlili asosida AI tavsiyalarini olish.
     */
    public function getSoilRecommendation(SoilAnalysis $analysis): array
    {
        if (empty($this->apiKey)) {
            Log::warning("Groq API key is missing. Using mock advisor response.");
            return $this->getMockRecommendation($analysis);
        }

        try {
            $prompt = $this->buildPrompt($analysis);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "Siz O'zbekistondagi professional agronom va qishloq xo'jaligi mutaxassisiz. Foydalanuvchi taqdim etgan tuproq parametrlari (pH, unumdorlik, namlik, harorat, quyosh nuri, namlik ko'rsatkichlari) va u yetishtirmoqchi bo'lgan ekin turi asosida tahlil qiling. Javobni quyidagi aniq JSON formatida, faqat JSON o'zini qaytaring. Hech qanday qo'shimcha matn qo'shmang. JSON formati:
{
  \"content\": \"Batafsil agronomik tavsiyalar o'zbek tilida (muammolar, parvarishlash choralari va maslahatlar)...\",
  \"recommended_crops\": [\"ekin1\", \"ekin2\", \"ekin3\"],
  \"fertilizer_plan\": {
    \"bahor\": \"Bahorgi mineral o'g'itlar rejasi...\",
    \"yoz\": \"Yozgi sug'orish va mineral o'g'itlar rejasi...\",
    \"kuz\": \"Kuzgi tayyorgarlik rejasi...\"
  }
}"
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'], // JSON output rejimini faollashtirish
                'temperature' => 0.3,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $messageContent = $result['choices'][0]['message']['content'] ?? '{}';
                $parsedData = json_decode($messageContent, true);

                return [
                    'content' => $parsedData['content'] ?? "Tuproq tahlili yakunlandi. Parametrlar me'yorida.",
                    'recommended_crops' => $parsedData['recommended_crops'] ?? [$analysis->target_crop],
                    'fertilizer_plan' => $parsedData['fertilizer_plan'] ?? [],
                    'ai_model' => $this->model,
                    'tokens_used' => $result['usage']['total_tokens'] ?? 0,
                ];
            }

            Log::error("Groq API error: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Groq Service failed: " . $e->getMessage());
        }

        return $this->getMockRecommendation($analysis);
    }

    /**
     * AI uchun prompt tayyorlash.
     */
    protected function buildPrompt(SoilAnalysis $analysis): string
    {
        return "Ekin turi: {$analysis->target_crop}
Tuproq ko'rsatkichlari:
- pH darajasi: {$analysis->ph}
- Unumdorlik ko'rsatkichi (N, P, K mineral darajasi): {$analysis->fertility}%
- Namlik: {$analysis->moisture}%
- Harorat: {$analysis->temperature}°C
- Quyosh nuri (yorug'lik): {$analysis->sunlight} lux
- Havo namligi: {$analysis->humidity}%
- Tahlil sanasi: {$analysis->analysis_date->format('Y-m-d')}

Iltimos, ushbu parametrlar asosida yetishtirilayotgan ekinning o'sish ehtimoli, tuproqdagi yetishmovchiliklar va ularni bartaraf etish choralarini tavsiya qiling. Shuningdek muqobil ravishda ekish mumkin bo'lgan 3 ta ekin turi va yillik o'g'itlash rejasini tuzib bering.";
    }

    /**
     * Soxta (Fallback Mock) tavsiyalar olish. API kaliti bo'lmaganda yoki xatolik yuz berganda ishlatiladi.
     */
    protected function getMockRecommendation(SoilAnalysis $analysis): array
    {
        $target = $analysis->target_crop;
        $ph = $analysis->ph;
        $fertility = $analysis->fertility;

        $content = "Tahlil natijalariga ko'ra, siz tanlagan ({$target}) ekini uchun ";
        $recommendedCrops = [];
        $fertilizerPlan = [];

        // Oddiy qoidalar asosida soxta, ammo mantiqiy ma'lumot generatsiyasi
        if ($ph < 6.0) {
            $content .= "tuproq kislotaliligi yuqori (pH: {$ph}). Bu holatda ohakli o'g'itlar solish tavsiya etiladi. Ekin ildiz tizimining rivojlanishi qiyinlashishi mumkin.";
            $recommendedCrops = ["Kartoshka", "Suli", "Javdar"];
            $fertilizerPlan = [
                "bahor" => "Karbamid va ohak aralashmasini tuproqqa aralashtirish. Har gektarga 150 kg.",
                "yoz" => "Mikroelementlar bilan barg orqali oziqlantirish (bor, rux).",
                "kuz" => "Fosforli o'g'itlar (superfosfat) 200 kg/ga chuqur haydash bilan."
            ];
        } elseif ($ph > 7.5) {
            $content .= "tuproq ishqoriyligi yuqori (pH: {$ph}). Tuproqni neytrallash uchun gips yoki oltingugurt kukunidan foydalanish zarur. {$target} ekini uchun ozuqa moddalarining so'rilishi sekinlashishi mumkin.";
            $recommendedCrops = ["Arpa", "Qand lavlagi", "Bedalar"];
            $fertilizerPlan = [
                "bahor" => "Ammoniy sulfat o'g'itini qo'llash (tuproqni nordonlashtiradi). 180 kg/ga.",
                "yoz" => "Kaliy o'g'itlari va muntazam sug'orish tizimi (tomchilatib).",
                "kuz" => "Go'ng va chirindi solish, 20 tonna/ga, tuproq strukturasini yaxshilash uchun."
            ];
        } else {
            $content .= "tuproq kislotalilik darajasi neytral va juda mos keladi (pH: {$ph}). ";
            if ($fertility < 40.0) {
                $content .= "Biroq unumdorlik darajasi past ({$fertility}%). NPK (azot, fosfor, kaliy) o'g'itlarini ko'paytirish lozim.";
                $recommendedCrops = ["Mosh", "Noxot", "Soya (tuproqni azot bilan boyitish uchun)"];
                $fertilizerPlan = [
                    "bahor" => "Azotli o'g'itlar (Karbamid yoki Ammiakli selitra) 200 kg/ga.",
                    "yoz" => "N-P-K kompleks o'g'itlarni sug'orish suvi bilan birga berish.",
                    "kuz" => "Kaliy sulfat va go'ng aralashmasini solish."
                ];
            } else {
                $content .= "Unumdorlik darajasi yaxshi ({$fertility}%). Tuproq hozirgi holatda sog'lom va {$target} yetishtirish uchun to'liq yaroqli.";
                $recommendedCrops = ["G'o'za (Paxta)", "Bug'doy", "Makkajo'xori"];
                $fertilizerPlan = [
                    "bahor" => "Profilaktik mineral oziqlantirish (NPK 15:15:15) 100 kg/ga.",
                    "yoz" => "Namlikni saqlash va organik o'g'itlar (biogumus) bilan bargdan oziqlantirish.",
                    "kuz" => "Kuzgi shudgorlash oldidan superfosfat 120 kg/ga."
                ];
            }
        }

        return [
            'content' => $content . " Umumiy hisobda tuproq namligi ({$analysis->moisture}%) va harorati ({$analysis->temperature}°C) ekin rivojlanishi uchun me'yordadir.",
            'recommended_crops' => $recommendedCrops,
            'fertilizer_plan' => $fertilizerPlan,
            'ai_model' => 'llama3-8b-8192 (mocked)',
            'tokens_used' => 240,
        ];
    }

    /**
     * Chat orqali foydalanuvchi savoliga agronom sifatida javob berish.
     */
    public function ask(string $message, array $history = []): string
    {
        if (empty($this->apiKey)) {
            Log::warning("Groq API key is missing. Using mock chat response.");
            return $this->getMockChatResponse($message);
        }

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => "Siz O'zbekistondagi professional agronom va aqlli qishloq xo'jaligi bo'yicha maslahatchisiz (AgroMind loyihasida). Fermerlarga tuproq parvarishi, o'g'itlash, ekin ekish, sug'orish va kasalliklarga qarshi kurash bo'yicha mukammal, amaliy va faqat agronomik maslahatlar bering. Maslahatlarni o'zbek tilida, do'stona va sodda tilda yozing."
                ]
            ];

            // Chat tarixini qo'shish
            foreach ($history as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = [
                        'role' => $msg['role'],
                        'content' => $msg['content']
                    ];
                }
            }

            // Joriy foydalanuvchi xabarini qo'shish
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['choices'][0]['message']['content'] ?? "Kechirasiz, tizimda xatolik yuz berdi. Iltimos qaytadan urinib ko'ring.";
            }

            Log::error("Groq Chat API error: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Groq Chat failed: " . $e->getMessage());
        }

        return $this->getMockChatResponse($message);
    }

    /**
     * Soxta (Fallback Mock) chat javobini olish. API kaliti bo'lmaganda ishlatiladi.
     */
    protected function getMockChatResponse(string $message): string
    {
        $msg = strtolower($message);
        
        if (str_contains($msg, 'o\'g\'it') || str_contains($msg, 'ogit') || str_contains($msg, 'o`g`it')) {
            return "Aziz dehqon, tuproq unumdorligini oshirish uchun bahorda azotli o'g'itlar (karbamid, selitra), kuzda esa fosforli va kaliyli o'g'itlar solishni tavsiya etaman. Shuningdek, biogumus kabi organik chirindilar tuproq tuzilishini ancha yaxshilaydi.";
        }
        
        if (str_contains($msg, 'sug\'or') || str_contains($msg, 'suv') || str_contains($msg, 'sug`or')) {
            return "Ekinlarni sug'orishda tomchilatib sug'orish tizimidan foydalanishni maslahat beraman. Bu suvni 40-50% gacha tejaydi va o'g'itlarni bevosita o'simlik ildiziga yetkazib berish imkonini beradi. Sug'orishni quyosh botgandan keyin yoki ertalab barvaqt amalga oshirgan ma'qul.";
        }
        
        if (str_contains($msg, 'kasallik') || str_contains($msg, 'zararkunanda') || str_contains($msg, 'hasharot')) {
            return "Ekinlardagi kasalliklarga qarshi kurashish uchun birinchi navbatda fungitsidlar yoki biologik kurash usullaridan foydalanish lozim. Zararkunandalar ko'p bo'lsa, maxsus insektitsidlarni me'yorida seping.";
        }

        return "Salom dehqon! Men AgroMind aqlli agronom assistentiman. Menga tuproq tahlili, ekin ekish, sug'orish va o'g'itlar bo'yicha savollaringizni yozishingiz mumkin. Sizga maslahat berishdan xursandman!";
    }
}
