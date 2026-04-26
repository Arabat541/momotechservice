<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnvoyerSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // secondes entre chaque tentative

    public function __construct(
        private readonly string $type,        // 'notification' | 'relance'
        private readonly string $telephone,
        private readonly string $numeroReparation,
        private readonly string $shopId,
        private readonly int    $relanceCount = 0,
    ) {}

    public function handle(SmsService $smsService): void
    {
        $sent = match ($this->type) {
            'notification' => $smsService->envoyerNotificationReparation(
                $this->telephone, $this->numeroReparation, $this->shopId
            ),
            'relance' => $smsService->envoyerRelance(
                $this->telephone, $this->numeroReparation, $this->relanceCount, $this->shopId
            ),
            default => false,
        };

        if (!$sent) {
            Log::warning("SMS {$this->type} non envoyé pour {$this->numeroReparation} (tentative {$this->attempts()}/{$this->tries})");

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SMS {$this->type} définitivement échoué pour {$this->numeroReparation} : {$e->getMessage()}");
    }
}
