<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

class FonnteWhatsappService
{
    public const RETURN_REMINDER_TOMORROW = 'return_reminder_tomorrow';

    public const RETURN_REMINDER_TODAY = 'return_reminder_today';

    public const RETURN_REMINDER_OVERDUE = 'return_reminder_overdue';

    public function sendRentalCreated(Rental $rental): bool
    {
        $rental->loadMissing(['customer:id,name,whatsapp_number', 'items:id,rental_id,item_name_snapshot,quantity']);

        $detailUrl = $this->rentalDetailUrl($rental);

        return $this->sendRentalMessage($rental, $this->rentalCreatedMessage($rental, $detailUrl));
    }

    public function sendReturnReminder(Rental $rental, string $type): bool
    {
        $rental->loadMissing(['customer:id,name,whatsapp_number', 'items:id,rental_id,item_name_snapshot,quantity']);

        return $this->sendRentalMessage($rental, $this->returnReminderMessage($rental, $type, $this->rentalDetailUrl($rental)));
    }

    public function isEnabled(): bool
    {
        return Setting::whatsappNotificationsEnabled()
            && config('services.fonnte.enabled')
            && filled(config('services.fonnte.token'));
    }

    private function sendRentalMessage(Rental $rental, string $message): bool
    {
        $token = config('services.fonnte.token');

        if (! $this->isEnabled()) {
            return false;
        }

        if (! $rental->customer || blank($rental->customer->whatsapp_number)) {
            return false;
        }

        $target = $this->normalizeTarget($rental->customer->whatsapp_number);

        if ($target === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['Authorization' => $token])
                ->timeout(10)
                ->connectTimeout(5)
                ->retry([500, 1000], throw: false)
                ->post(rtrim((string) config('services.fonnte.base_url'), '/').'/send', [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => (string) config('services.fonnte.country_code', '62'),
                    'preview' => true,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Gagal mengirim notifikasi WhatsApp Fonnte.', [
                'rental_id' => $rental->id,
                'invoice_number' => $rental->invoice_number,
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        if ($response->failed() || $response->json('status') === false) {
            Log::warning('Fonnte mengembalikan respons gagal.', [
                'rental_id' => $rental->id,
                'invoice_number' => $rental->invoice_number,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function rentalDetailUrl(Rental $rental): string
    {
        return URL::temporarySignedRoute(
            'public.rentals.invoice',
            now()->addDays(30),
            ['rental' => $rental],
        );
    }

    private function normalizeTarget(string $whatsappNumber): string
    {
        $number = preg_replace('/\D+/', '', $whatsappNumber) ?? '';
        $countryCode = (string) config('services.fonnte.country_code', '62');

        if ($number === '') {
            return '';
        }

        if (str_starts_with($number, '0')) {
            return $countryCode.substr($number, 1);
        }

        if (str_starts_with($number, '8')) {
            return $countryCode.$number;
        }

        return $number;
    }

    private function rentalCreatedMessage(Rental $rental, string $detailUrl): string
    {
        $store = Setting::storeProfile();
        $itemSummary = $rental->items
            ->take(3)
            ->map(fn ($item): string => "- {$item->quantity}x {$item->item_name_snapshot}")
            ->implode("\n");

        if ($rental->items->count() > 3) {
            $itemSummary .= "\n- dan item lainnya";
        }

        return trim(
            "Halo {$rental->customer->name}, rental Anda di {$store['store_name']} sudah tercatat.\n\n".
            "Invoice: {$rental->invoice_number}\n".
            "Jadwal ambil: {$this->formatDateTime($rental->pickup_at)}\n".
            "Jadwal kembali: {$this->formatDateTime($rental->return_due_at)}\n".
            "Total: {$this->formatMoney($rental->total_amount)}\n".
            "Sisa bayar: {$this->formatMoney($rental->remaining_amount)}\n\n".
            "Item:\n{$itemSummary}\n\n".
            "Detail order:\n{$detailUrl}\n\n".
            'Terima kasih.'
        );
    }

    private function returnReminderMessage(Rental $rental, string $type, string $detailUrl): string
    {
        $store = Setting::storeProfile();
        $headline = match ($type) {
            self::RETURN_REMINDER_TOMORROW => 'Ini pengingat bahwa barang rental Anda harus dikembalikan besok.',
            self::RETURN_REMINDER_OVERDUE => 'Barang rental Anda sudah melewati jadwal pengembalian.',
            default => 'Ini pengingat bahwa barang rental Anda harus dikembalikan hari ini.',
        };
        $closing = $type === self::RETURN_REMINDER_OVERDUE
            ? 'Mohon segera kembalikan barang ke toko. Jika ada keterlambatan, denda akan dikonfirmasi oleh staff.'
            : 'Mohon kembalikan sesuai jadwal.';

        $itemSummary = $rental->items
            ->take(3)
            ->map(fn ($item): string => "- {$item->quantity}x {$item->item_name_snapshot}")
            ->implode("\n");

        if ($rental->items->count() > 3) {
            $itemSummary .= "\n- dan item lainnya";
        }

        return trim(
            "Halo {$rental->customer->name}, {$headline}\n\n".
            "Toko: {$store['store_name']}\n".
            "Invoice: {$rental->invoice_number}\n".
            "Batas kembali: {$this->formatDateTime($rental->return_due_at)}\n".
            "Sisa bayar: {$this->formatMoney($rental->remaining_amount)}\n\n".
            "Item:\n{$itemSummary}\n\n".
            "Detail order:\n{$detailUrl}\n\n".
            "{$closing} Terima kasih."
        );
    }

    private function formatDateTime(mixed $value): string
    {
        if (! $value) {
            return '-';
        }

        return $value->timezone(config('app.timezone'))->format('d/m/Y H:i');
    }

    private function formatMoney(mixed $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
