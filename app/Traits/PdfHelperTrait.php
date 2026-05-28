<?php

namespace App\Traits;

use App\Models\Settings;

trait PdfHelperTrait
{
    protected function getCompanyInfo(?string $shopId): array
    {
        $settings = $shopId
            ? Settings::withoutGlobalScopes()->where('shopId', $shopId)->first()
            : Settings::withoutGlobalScopes()->first();

        $default = ['nom' => 'MOMO TECH SERVICE', 'adresse' => '', 'telephone' => '', 'email' => ''];

        return array_merge($default, $settings?->companyInfo ?? []);
    }

    protected function getLogoBase64(): ?string
    {
        foreach (['logo-receipt.png', 'logo-app.png'] as $file) {
            $path = public_path('images/' . $file);
            if (file_exists($path)) {
                return base64_encode(file_get_contents($path));
            }
        }

        return null;
    }
}
