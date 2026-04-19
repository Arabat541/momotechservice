<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function envoyerNotificationReparation(string $telephone, string $numeroReparation, string $shopId): bool
    {
        $config = $this->config($shopId);

        if (!$config || !($config['enabled'] ?? false)) {
            return false;
        }

        $message = "Bonjour, votre réparation N° {$numeroReparation} est terminée. Vous pouvez venir récupérer votre appareil. Merci.";

        return match ($config['provider'] ?? '') {
            'twilio'   => $this->envoyerTwilio($config, $telephone, $message),
            'orange'   => $this->envoyerOrange($config, $telephone, $message),
            default    => false,
        };
    }

    public function envoyerRelance(string $telephone, string $numeroReparation, int $relanceCount, string $shopId): bool
    {
        $config = $this->config($shopId);

        if (!$config || !($config['enabled'] ?? false)) {
            return false;
        }

        $jours = $relanceCount === 0 ? 7 : 14;
        $message = "Rappel : votre réparation N° {$numeroReparation} est terminée depuis {$jours} jours. Merci de venir récupérer votre appareil dès que possible.";

        return match ($config['provider'] ?? '') {
            'twilio'   => $this->envoyerTwilio($config, $telephone, $message),
            'orange'   => $this->envoyerOrange($config, $telephone, $message),
            default    => false,
        };
    }

    private function envoyerTwilio(array $config, string $telephone, string $message): bool
    {
        try {
            $sid   = $config['twilio_sid'] ?? '';
            $token = $config['api_key'] ?? '';
            $from  = $config['sender'] ?? '';

            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To'   => $telephone,
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('SMS Twilio échoué : ' . $e->getMessage());
            return false;
        }
    }

    private function envoyerOrange(array $config, string $telephone, string $message): bool
    {
        try {
            $response = Http::withToken($config['api_key'] ?? '')
                ->post('https://api.orange.com/smsmessaging/v1/outbound/tel:' . ($config['sender'] ?? '') . '/requests', [
                    'outboundSMSMessageRequest' => [
                        'address'            => "tel:{$telephone}",
                        'senderAddress'      => 'tel:' . ($config['sender'] ?? ''),
                        'outboundSMSTextMessage' => ['message' => $message],
                    ],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('SMS Orange échoué : ' . $e->getMessage());
            return false;
        }
    }

    private function config(string $shopId): ?array
    {
        $settings = Settings::withoutGlobalScopes()->where('shopId', $shopId)->first();
        return $settings?->sms_config;
    }
}
