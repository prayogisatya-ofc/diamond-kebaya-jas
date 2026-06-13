<?php

namespace App\Console\Commands;

use App\Models\Rental;
use App\Models\RentalWhatsappNotification;
use App\Services\FonnteWhatsappService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('rentals:send-return-reminders {--date= : Tanggal acuan reminder dalam format Y-m-d}')]
#[Description('Kirim notifikasi WhatsApp H-1, hari H, dan keterlambatan pengembalian rental.')]
class SendRentalReturnReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FonnteWhatsappService $whatsappService): int
    {
        $baseDate = $this->baseDate();
        $sentCount = 0;

        $sentCount += $this->sendRemindersForDate(
            $whatsappService,
            FonnteWhatsappService::RETURN_REMINDER_TOMORROW,
            $baseDate->addDay()
        );
        $sentCount += $this->sendRemindersForDate(
            $whatsappService,
            FonnteWhatsappService::RETURN_REMINDER_TODAY,
            $baseDate
        );
        $sentCount += $this->sendOverdueReminders($whatsappService, $baseDate);

        $this->info("Reminder pengembalian terkirim: {$sentCount}");

        return self::SUCCESS;
    }

    private function baseDate(): CarbonImmutable
    {
        $date = $this->option('date');

        if (blank($date)) {
            return CarbonImmutable::today(config('app.timezone'));
        }

        return CarbonImmutable::createFromFormat('Y-m-d', (string) $date, config('app.timezone'))->startOfDay();
    }

    private function sendRemindersForDate(FonnteWhatsappService $whatsappService, string $type, CarbonImmutable $returnDate): int
    {
        $sentCount = 0;

        Rental::query()
            ->with(['customer:id,name,whatsapp_number', 'items:id,rental_id,item_name_snapshot,quantity'])
            ->whereIn('status', ['picked_up', 'overdue'])
            ->whereBetween('return_due_at', [$returnDate->startOfDay(), $returnDate->endOfDay()])
            ->orderBy('id')
            ->each(function (Rental $rental) use ($whatsappService, $type, $returnDate, &$sentCount): void {
                $notification = $this->createNotificationIfMissing($rental, $type, $returnDate);

                if (! $notification) {
                    return;
                }

                if (! $whatsappService->sendReturnReminder($rental, $type)) {
                    $notification->delete();

                    return;
                }

                $notification->update(['sent_at' => now()]);
                $sentCount++;
            });

        return $sentCount;
    }

    private function sendOverdueReminders(FonnteWhatsappService $whatsappService, CarbonImmutable $baseDate): int
    {
        $sentCount = 0;

        Rental::query()
            ->with(['customer:id,name,whatsapp_number', 'items:id,rental_id,item_name_snapshot,quantity'])
            ->whereIn('status', ['picked_up', 'overdue'])
            ->where('return_due_at', '<', $baseDate->startOfDay())
            ->orderBy('id')
            ->each(function (Rental $rental) use ($whatsappService, $baseDate, &$sentCount): void {
                $notification = $this->createNotificationIfMissing(
                    $rental,
                    FonnteWhatsappService::RETURN_REMINDER_OVERDUE,
                    $baseDate
                );

                if (! $notification) {
                    return;
                }

                if (! $whatsappService->sendReturnReminder($rental, FonnteWhatsappService::RETURN_REMINDER_OVERDUE)) {
                    $notification->delete();

                    return;
                }

                $notification->update(['sent_at' => now()]);
                $sentCount++;
            });

        return $sentCount;
    }

    private function createNotificationIfMissing(Rental $rental, string $type, CarbonImmutable $scheduledFor): ?RentalWhatsappNotification
    {
        return DB::transaction(function () use ($rental, $type, $scheduledFor): ?RentalWhatsappNotification {
            if (RentalWhatsappNotification::query()
                ->where('rental_id', $rental->id)
                ->where('type', $type)
                ->whereDate('scheduled_for', $scheduledFor->toDateString())
                ->lockForUpdate()
                ->exists()) {
                return null;
            }

            return RentalWhatsappNotification::query()->create([
                'rental_id' => $rental->id,
                'type' => $type,
                'scheduled_for' => $scheduledFor->startOfDay(),
            ]);
        });
    }
}
