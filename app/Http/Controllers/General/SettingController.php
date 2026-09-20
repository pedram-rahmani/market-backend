<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

        foreach (['social_links', 'contact_info', 'footer_links', 'trust_badges'] as $key) {
            if (isset($settings[$key])) {
                $decoded = json_decode($settings[$key], true);
                $settings[$key] = is_array($decoded) ? $decoded : ($key === 'trust_badges' ? [] : []);
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
            'footer_links' => 'nullable|json|max:20000',
            'trust_badges' => 'nullable|json|max:10000',
        ]);

        if ($request->filled('trust_badges')) {
            $trustBadge = json_decode($request->input('trust_badges'), true);
            if (!is_array($trustBadge)) {
                throw ValidationException::withMessages([
                    'trust_badges' => 'ساختار نشان اعتماد نامعتبر است.',
                ]);
            }

            $badgeValidator = Validator::make($trustBadge ?: [], [
                'image_url' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/'],
                'link_url' => ['nullable', 'string', 'max:2048', 'regex:/^https?:\/\//'],
                'alt' => 'nullable|string|max:255',
            ]);

            if ($badgeValidator->fails()) {
                throw new ValidationException($badgeValidator);
            }
        }

        if ($request->filled('footer_links')) {
            $footerLinks = json_decode($request->input('footer_links'), true);
            if (!is_array($footerLinks)) {
                throw ValidationException::withMessages([
                    'footer_links' => 'ساختار لینک‌های فوتر نامعتبر است.',
                ]);
            }

            $footerLinksValidator = Validator::make(
                $footerLinks,
                [
                    '*.title' => 'required|string|max:100',
                    '*.items' => 'required|array|max:20',
                    '*.items.*.label' => 'required|string|max:100',
                    '*.items.*.url' => [
                        'required',
                        'string',
                        'max:2048',
                        'regex:/^(\/|https?:\/\/)/',
                    ],
                ],
            );

            if ($footerLinksValidator->fails()) {
                throw new ValidationException($footerLinksValidator);
            }
        }

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
            'trust_badges',
        ];

        foreach ($allowedKeys as $key) {
            if (!$request->exists($key)) {
                continue;
            }

            $value = $request->input($key);

            if ($value === 'null' || $value === 'undefined') {
                $value = null;
            }

            $isJsonField = in_array($key, [
                'social_links',
                'contact_info',
                'footer_links',
                'trust_badges',
            ]);
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
