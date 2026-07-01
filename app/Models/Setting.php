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
            ->map(fn (mixed $default, string $key): mixed => $settings->get($key) ?? $default)
            ->all();

        $logoPath = $profile['store_logo_path'];
        $faviconPath = $profile['store_favicon_path'];

        return [
            'store_name' => $profile['store_name'] ?: 'Diamond Kebaya & Jas',
            'store_address' => $profile['store_address'] ?: 'Alamat toko belum diatur',
            'store_whatsapp_number' => $profile['store_whatsapp_number'] ?: 'WhatsApp toko belum diatur',
            'invoice_footer_note' => $profile['invoice_footer_note'] ?: 'Terima kasih sudah menyewa di Diamond Kebaya & Jas.',
            'primary_color' => $profile['primary_color'] ?: '#615cf9',
            'whatsapp_notifications_enabled' => self::booleanValue($profile['whatsapp_notifications_enabled'] ?? true),
            'whatsapp_rental_message_template' => $profile['whatsapp_rental_message_template'] ?? "Halo {customer_name}, rental Anda di {store_name} sudah tercatat.\n\nInvoice: {invoice_number}\nJadwal ambil: {pickup_at}\nJadwal kembali: {return_due_at}\nTotal: {total_amount}\nSisa bayar: {remaining_amount}\n\nItem:\n{item_list}\n\nDetail order:\n{invoice_url}\n\nTerima kasih.",
            'store_logo_path' => $logoPath,
            'store_logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
            'store_favicon_path' => $faviconPath,
            'store_favicon_url' => $faviconPath ? Storage::disk('public')->url($faviconPath) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function updateStoreProfile(array $values): void
    {
        foreach ($values as $key => $value) {
            self::query()->updateOrCreate([
                'key' => $key,
            ], [
                'value' => self::serializeValue($key, $value),
                'type' => self::settingType($key),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
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
            'whatsapp_notifications_enabled' => true,
        ];
    }

    private static function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function serializeValue(string $key, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($key === 'whatsapp_notifications_enabled') {
            return self::booleanValue($value) ? '1' : '0';
        }

        return (string) $value;
    }

    private static function settingType(string $key): string
    {
        if (in_array($key, ['store_logo_path', 'store_favicon_path'], true)) {
            return 'file';
        }

        return 'string';
    }
}
