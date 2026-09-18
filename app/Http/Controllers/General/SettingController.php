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

        foreach (['site_logo', 'site_favicon'] as $imageKey) {
            if (isset($settings[$imageKey]) && $settings[$imageKey]) {
                $settings[$imageKey] = asset('storage/' . $settings[$imageKey]);
            }
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
        abort_unless($request->user()?->isAdmin() || $request->user()?->hasPermission('settings.edit'), 403);

        $request->validate([
            'site_name'    => 'nullable|string|max:255',
            'site_logo'    => 'nullable|image|max:2048',
            'site_favicon' => 'nullable|image|mimes:ico,png,jpg,jpeg,svg,webp|max:1024',
            'footer_text'  => 'nullable|string',
            'social_links' => 'nullable',
            'contact_info' => 'nullable',
            'footer_links' => 'nullable',
            'trust_badges' => 'nullable|string',
        ]);

        if ($request->hasFile('site_logo')) {
            $this->storeImageSetting('site_logo', $request->file('site_logo'));
        }

        if ($request->hasFile('site_favicon')) {
            $this->storeImageSetting('site_favicon', $request->file('site_favicon'));
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

    private function storeImageSetting(string $key, \Illuminate\Http\UploadedFile $file): void
    {
        $oldPath = Setting::where('key', $key)->value('value');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $file->store('settings', 'public')]
        );
    }
}
