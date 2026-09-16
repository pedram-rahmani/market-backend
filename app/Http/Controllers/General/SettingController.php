<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        if (isset($settings['site_logo']) && $settings['site_logo']) {
            $settings['site_logo'] = asset('storage/' . $settings['site_logo']);
        }

        foreach (['social_links', 'contact_info', 'footer_links'] as $key) {
            if (isset($settings[$key])) {
                $decoded = json_decode($settings[$key], true);
                $settings[$key] = is_array($decoded) ? $decoded : [];
            }
        }

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name'    => 'nullable|string|max:255',
            'site_logo'    => 'nullable|image|max:2048',
            'footer_text'  => 'nullable|string',
            'social_links' => 'nullable',
            'contact_info' => 'nullable',
            'footer_links' => 'nullable',
            'trust_badges' => 'nullable|string',
        ]);

        if ($request->hasFile('site_logo')) {
            $file = $request->file('site_logo');
            $oldLogo = Setting::where('key', 'site_logo')->value('value');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $file->store('settings', 'public');

            Setting::updateOrCreate(
                ['key' => 'site_logo'],
                ['value' => $path]
            );
        }

        $allowedKeys = [
            'site_name',
            'footer_text',
            'social_links',
            'contact_info',
            'footer_links',
            'trust_badges'
        ];

        foreach ($allowedKeys as $key) {
            if (!$request->has($key)) {
                continue;
            }

            $value = $request->input($key);

            if ($value === 'null' || $value === 'undefined') {
                $value = null;
            }

            $isJsonField = in_array($key, ['social_links', 'contact_info', 'footer_links']);
            if ($isJsonField) {
                if (!is_array($value)) {
                    $value = json_decode($value, true);
                }
                $value = json_encode($value ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            if ($isJsonField || $value !== null) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        return response()->json([
            'message' => 'تنظیمات سایت با موفقیت به‌روزرسانی شدند.'
        ]);
    }
}
