<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected ?string $provider;
    protected ?string $eskizEmail;
    protected ?string $eskizPassword;
    protected ?string $playMobileUsername;
    protected ?string $playMobilePassword;
    protected ?string $senderName;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'mock'); // mock | eskiz | playmobile
        $this->eskizEmail = config('services.sms.eskiz_email');
        $this->eskizPassword = config('services.sms.eskiz_password');
        $this->playMobileUsername = config('services.sms.play_username');
        $this->playMobilePassword = config('services.sms.play_password');
        $this->senderName = config('services.sms.sender_name', 'AgroMind');
    }

    /**
     * SMS yuborish asosiy metodi.
     */
    public function sendSms(string $phone, string $message): bool
    {
        // Telefon raqam formatini to'g'irlash (masalan, +998901234567 -> 998901234567)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Agar raqam 998 bilan boshlanmasa va uzunligi 9 ta bo'lsa 998 qo'shamiz
        if (strlen($cleanPhone) === 9) {
            $cleanPhone = '998' . $cleanPhone;
        }

        Log::info("SMS jo'natilmoqda -> {$cleanPhone}: \"{$message}\" via provider: {$this->provider}");

        switch ($this->provider) {
            case 'eskiz':
                return $this->sendViaEskiz($cleanPhone, $message);
            case 'playmobile':
                return $this->sendViaPlayMobile($cleanPhone, $message);
            case 'mock':
            default:
                // Simulyatsiya qilingan rejim
                Log::info("SMS MOCK: Message successfully processed for {$cleanPhone}");
                return true;
        }
    }

    /**
     * Eskiz.uz API integratsiyasi.
     */
    protected function sendViaEskiz(string $phone, string $message): bool
    {
        try {
            // 1. Eskiz API Token olish (keshlash tavsiya etiladi)
            $tokenResponse = Http::post('https://yildi.uz/api/auth/login', [
                'email' => $this->eskizEmail,
                'password' => $this->eskizPassword
            ]);

            if (!$tokenResponse->successful()) {
                Log::error("Eskiz Login API error: " . $tokenResponse->body());
                return false;
            }

            $token = $tokenResponse->json()['data']['token'] ?? null;
            if (!$token) {
                return false;
            }

            // 2. SMS yuborish
            $smsResponse = Http::withToken($token)->post('https://yildi.uz/api/message/sms/send', [
                'mobile_phone' => $phone,
                'message' => $message,
                'from' => $this->senderName,
            ]);

            if ($smsResponse->successful()) {
                Log::info("Eskiz SMS sent successfully to {$phone}");
                return true;
            }

            Log::error("Eskiz Send SMS API error: " . $smsResponse->body());
        } catch (\Exception $e) {
            Log::error("Eskiz SMS exception: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Play Mobile (Sms.uz / Play Mobile) API integratsiyasi.
     */
    protected function sendViaPlayMobile(string $phone, string $message): bool
    {
        try {
            // Play Mobile JSON rest API ishlatadi. (Odatda POST so'rov, Basic Auth yoki JSON parametrlar bilan)
            // Masalan: https://broker.playmobile.uz:8443/broker-api/send
            
            $payload = [
                'messages' => [
                    [
                        'recipient' => $phone,
                        'message-id' => 'agromind_' . uniqid(),
                        'sms' => [
                            'originator' => $this->senderName,
                            'content' => [
                                'text' => $message
                            ]
                        ]
                    ]
                ]
            ];

            $response = Http::withBasicAuth($this->playMobileUsername, $this->playMobilePassword)
                ->post('https://broker.playmobile.uz:8443/broker-api/send', $payload);

            if ($response->successful()) {
                Log::info("Play Mobile SMS sent successfully to {$phone}");
                return true;
            }

            Log::error("Play Mobile API error: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Play Mobile exception: " . $e->getMessage());
        }

        return false;
    }
}
