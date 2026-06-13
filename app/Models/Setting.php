<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['key', 'value', 'type', 'description'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array{store_name: string, store_address: string, store_whatsapp_number: string, invoice_footer_note: string, primary_color: string, store_logo_path: string|null, store_logo_url: string|null, store_favicon_path: string|null, store_favicon_url: string|null}
     */
    public static function storeProfile(): array
    {
        $settings = self::query()
            ->whereIn('key', array_keys(self::storeDefaults()))
            ->pluck('value', 'key');

        $profile = collect(self::storeDefaults())
            ->map(fn (?string $default, string $key): ?string => $settings->get($key) ?? $default)
            ->all();

        $logoPath = $profile['store_logo_path'];
        $faviconPath = $profile['store_favicon_path'];

        return [
            'store_name' => $profile['store_name'] ?: 'Diamond Kebaya & Jas',
            'store_address' => $profile['store_address'] ?: 'Alamat toko belum diatur',
            'store_whatsapp_number' => $profile['store_whatsapp_number'] ?: 'WhatsApp toko belum diatur',
            'invoice_footer_note' => $profile['invoice_footer_note'] ?: 'Terima kasih sudah menyewa di Diamond Kebaya & Jas.',
            'primary_color' => $profile['primary_color'] ?: '#615cf9',
            'store_logo_path' => $logoPath,
            'store_logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'store_favicon_path' => $faviconPath,
            'store_favicon_url' => $faviconPath ? Storage::disk('public')->url($faviconPath) : null,
        ];
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function updateStoreProfile(array $values): void
    {
        foreach ($values as $key => $value) {
            self::query()->updateOrCreate([
                'key' => $key,
            ], [
                'value' => $value,
                'type' => in_array($key, ['store_logo_path', 'store_favicon_path'], true) ? 'file' : 'string',
            ]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    public static function storeDefaults(): array
    {
        return [
            'store_name' => 'Diamond Kebaya & Jas',
            'store_address' => 'Alamat toko belum diatur',
            'store_whatsapp_number' => 'WhatsApp toko belum diatur',
            'store_logo_path' => null,
            'store_favicon_path' => null,
            'invoice_footer_note' => 'Terima kasih sudah menyewa di Diamond Kebaya & Jas.',
            'primary_color' => '#615cf9',
        ];
    }
}
