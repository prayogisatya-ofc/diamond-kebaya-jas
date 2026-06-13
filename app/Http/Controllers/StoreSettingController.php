<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStoreSettingRequest;
use App\Models\Setting;
use App\Support\StoreFaviconGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StoreSettingController extends Controller
{
    public function __construct(private readonly StoreFaviconGenerator $faviconGenerator) {}

    public function edit(): Response
    {
        return Inertia::render('Settings/Edit', [
            'settings' => Setting::storeProfile(),
        ]);
    }

    public function update(UpdateStoreSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $profile = Setting::storeProfile();
        $logoPath = $profile['store_logo_path'];
        $faviconPath = $profile['store_favicon_path'];

        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            if ($faviconPath) {
                Storage::disk('public')->delete($faviconPath);
            }

            $logoPath = $request->file('logo')->store('store-settings', 'public');
            $faviconPath = $this->faviconGenerator->generate($request->file('logo'));
        }

        Setting::updateStoreProfile([
            'store_name' => $validated['store_name'],
            'store_address' => $validated['store_address'],
            'store_whatsapp_number' => $validated['store_whatsapp_number'],
            'invoice_footer_note' => $validated['invoice_footer_note'] ?? null,
            'primary_color' => $validated['primary_color'],
            'store_logo_path' => $logoPath,
            'store_favicon_path' => $faviconPath,
        ]);

        return redirect()->route('settings.edit')->with('success', 'Setting toko berhasil disimpan.');
    }
}
