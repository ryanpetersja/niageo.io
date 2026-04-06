<?php

namespace App\Http\Controllers;

use App\Models\BrandingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingSettingsController extends Controller
{
    public function edit()
    {
        $branding = BrandingSetting::getSettings();
        return view('settings.branding', compact('branding'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'footer_text' => 'nullable|string',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $branding = BrandingSetting::getSettings();

        if ($request->hasFile('logo')) {
            if ($branding->logo_path) {
                Storage::disk('public')->delete($branding->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('branding', 'public');
        }

        unset($validated['logo']);
        $branding->update($validated);

        return redirect()->route('settings.branding')->with('success', 'Branding settings updated.');
    }
}
