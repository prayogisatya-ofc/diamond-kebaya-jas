<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RentalReturnReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_return_reminders_for_tomorrow_today_and_overdue_once_per_day(): void
    {
        config([
            'services.fonnte.enabled' => true,
            'services.fonnte.token' => 'test-fonnte-token',
            'services.fonnte.base_url' => 'https://api.fonnte.com',
            'services.fonnte.country_code' => '62',
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $user = User::factory()->owner()->create();
        $customer = Customer::factory()->create([
            'name' => 'Sari Wijaya',
            'whatsapp_number' => '0812-9999-8888',
        ]);
        $tomorrowRental = $this->pickedUpRental($customer, $user, 'INV-20260613-0001', '2026-06-14 17:00:00');
        $todayRental = $this->pickedUpRental($customer, $user, 'INV-20260613-0002', '2026-06-13 17:00:00');
        $overdueRental = $this->pickedUpRental($customer, $user, 'INV-20260613-0003', '2026-06-12 17:00:00');
        $returnedRental = Rental::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'invoice_number' => 'INV-20260613-0004',
            'status' => 'returned',
            'return_due_at' => '2026-06-13 17:00:00',
        ]);
        RentalItem::factory()->create([
            'rental_id' => $tomorrowRental->id,
            'item_name_snapshot' => 'Kebaya Merah',
            'quantity' => 1,
        ]);
        RentalItem::factory()->create([
            'rental_id' => $todayRental->id,
            'item_name_snapshot' => 'Jas Hitam',
            'quantity' => 1,
        ]);
        RentalItem::factory()->create([
            'rental_id' => $overdueRental->id,
            'item_name_snapshot' => 'Beskap Coklat',
            'quantity' => 1,
        ]);
        RentalItem::factory()->create([
            'rental_id' => $returnedRental->id,
            'item_name_snapshot' => 'Dasi',
            'quantity' => 1,
        ]);

        $this->artisan('rentals:send-return-reminders', ['--date' => '2026-06-13'])
            ->expectsOutput('Reminder pengembalian terkirim: 3')
            ->assertSuccessful();

        Http::assertSentCount(3);
        Http::assertSent(function ($request): bool {
            return $request['target'] === '6281299998888'
                && str_contains($request['message'], 'harus dikembalikan besok')
                && str_contains($request['message'], 'INV-20260613-0001')
                && str_contains($request['message'], 'Kebaya Merah');
        });
        Http::assertSent(function ($request): bool {
            return $request['target'] === '6281299998888'
                && str_contains($request['message'], 'harus dikembalikan hari ini')
                && str_contains($request['message'], 'INV-20260613-0002')
                && str_contains($request['message'], 'Jas Hitam');
        });
        Http::assertSent(function ($request): bool {
            return $request['target'] === '6281299998888'
                && str_contains($request['message'], 'sudah melewati jadwal pengembalian')
                && str_contains($request['message'], 'INV-20260613-0003')
                && str_contains($request['message'], 'Beskap Coklat');
        });

        $this->assertDatabaseHas('rental_whatsapp_notifications', [
            'rental_id' => $tomorrowRental->id,
            'type' => 'return_reminder_tomorrow',
        ]);
        $this->assertDatabaseHas('rental_whatsapp_notifications', [
            'rental_id' => $todayRental->id,
            'type' => 'return_reminder_today',
        ]);
        $this->assertDatabaseHas('rental_whatsapp_notifications', [
            'rental_id' => $overdueRental->id,
            'type' => 'return_reminder_overdue',
            'scheduled_for' => '2026-06-13 00:00:00',
        ]);
        $this->assertDatabaseMissing('rental_whatsapp_notifications', [
            'rental_id' => $returnedRental->id,
        ]);

        $this->artisan('rentals:send-return-reminders', ['--date' => '2026-06-13'])
            ->expectsOutput('Reminder pengembalian terkirim: 0')
            ->assertSuccessful();

        Http::assertSentCount(3);

        $this->artisan('rentals:send-return-reminders', ['--date' => '2026-06-14'])
            ->expectsOutput('Reminder pengembalian terkirim: 3')
            ->assertSuccessful();

        Http::assertSentCount(6);
        $this->assertDatabaseHas('rental_whatsapp_notifications', [
            'rental_id' => $todayRental->id,
            'type' => 'return_reminder_overdue',
            'scheduled_for' => '2026-06-14 00:00:00',
        ]);
        $this->assertDatabaseHas('rental_whatsapp_notifications', [
            'rental_id' => $overdueRental->id,
            'type' => 'return_reminder_overdue',
            'scheduled_for' => '2026-06-14 00:00:00',
        ]);
    }

    public function test_command_does_not_send_return_reminders_when_whatsapp_setting_is_disabled(): void
    {
        config([
            'services.fonnte.enabled' => true,
            'services.fonnte.token' => 'test-fonnte-token',
            'services.fonnte.base_url' => 'https://api.fonnte.com',
            'services.fonnte.country_code' => '62',
        ]);

        Setting::updateStoreProfile([
            'whatsapp_notifications_enabled' => false,
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $user = User::factory()->owner()->create();
        $customer = Customer::factory()->create([
            'whatsapp_number' => '0812-9999-8888',
        ]);
        $rental = $this->pickedUpRental($customer, $user, 'INV-20260613-OFF', '2026-06-13 17:00:00');
        RentalItem::factory()->create([
            'rental_id' => $rental->id,
            'item_name_snapshot' => 'Kebaya Merah',
            'quantity' => 1,
        ]);

        $this->artisan('rentals:send-return-reminders', ['--date' => '2026-06-13'])
            ->expectsOutput('Reminder pengembalian terkirim: 0')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseMissing('rental_whatsapp_notifications', [
            'rental_id' => $rental->id,
        ]);
    }

    private function pickedUpRental(Customer $customer, User $user, string $invoiceNumber, string $returnDueAt): Rental
    {
        return Rental::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
            'invoice_number' => $invoiceNumber,
            'status' => 'picked_up',
            'pickup_at' => '2026-06-12 10:00:00',
            'return_due_at' => $returnDueAt,
        ]);
    }
}
