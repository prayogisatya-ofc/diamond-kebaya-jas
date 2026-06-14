<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalAvailabilityController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\RentalInvoiceController;
use App\Http\Controllers\RentalItemController;
use App\Http\Controllers\RentalPackageController;
use App\Http\Controllers\RentalPaymentController;
use App\Http\Controllers\RentalStatusController;
use App\Http\Controllers\RentalThermalReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\UserPasswordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('orders/{rental}/invoice', RentalInvoiceController::class)
    ->middleware('signed')
    ->name('public.rentals.invoice');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::prefix('reports')->name('reports.')->group(function (): void {
        Route::get('transactions', [ReportController::class, 'transactions'])->name('transactions');
        Route::get('payments', [ReportController::class, 'payments'])->name('payments');
        Route::get('rented-products', [ReportController::class, 'rentedProducts'])->name('rented-products');
    });

    Route::resource('product-categories', ProductCategoryController::class);
    Route::resource('products', ProductController::class);
    Route::resource('rental-packages', RentalPackageController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('rentals', RentalController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('rentals/{rental}/invoice', RentalInvoiceController::class)
        ->name('rentals.invoice');
    Route::get('rentals/{rental}/thermal-receipt', RentalThermalReceiptController::class)
        ->name('rentals.thermal-receipt');
    Route::post('rentals/{rental}/items', [RentalItemController::class, 'store'])
        ->name('rentals.items.store');
    Route::put('rentals/{rental}/items/{rentalItem}', [RentalItemController::class, 'update'])
        ->name('rentals.items.update');
    Route::delete('rentals/{rental}/items/{rentalItem}', [RentalItemController::class, 'destroy'])
        ->name('rentals.items.destroy');
    Route::post('rentals/{rental}/payments', [RentalPaymentController::class, 'store'])
        ->name('rentals.payments.store');
    Route::delete('rentals/{rental}/payments/{rentalPayment}', [RentalPaymentController::class, 'destroy'])
        ->name('rentals.payments.destroy');
    Route::post('rentals/{rental}/pick-up', [RentalStatusController::class, 'pickUp'])
        ->name('rentals.pick-up');
    Route::post('rentals/{rental}/return', [RentalStatusController::class, 'returnRental'])
        ->name('rentals.return');
    Route::post('rentals/{rental}/complete', [RentalStatusController::class, 'complete'])
        ->name('rentals.complete');
    Route::post('rentals/{rental}/cancel', [RentalStatusController::class, 'cancel'])
        ->name('rentals.cancel');
    Route::get('rental-availability', RentalAvailabilityController::class)
        ->name('rental-availability');

    Route::middleware('owner')->group(function (): void {
        Route::get('settings', [StoreSettingController::class, 'edit'])
            ->name('settings.edit');
        Route::post('settings', [StoreSettingController::class, 'update'])
            ->name('settings.update');
        Route::resource('users', UserManagementController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);
        Route::post('users/{user}/password', UserPasswordController::class)
            ->name('users.password.update');
    });

    Route::get('products/{product}/variants/create', [ProductVariantController::class, 'create'])
        ->name('products.variants.create');
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])
        ->name('products.variants.store');
    Route::get('product-variants/{product_variant}/edit', [ProductVariantController::class, 'edit'])
        ->name('product-variants.edit');
    Route::put('product-variants/{product_variant}', [ProductVariantController::class, 'update'])
        ->name('product-variants.update');
    Route::delete('product-variants/{product_variant}', [ProductVariantController::class, 'destroy'])
        ->name('product-variants.destroy');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
