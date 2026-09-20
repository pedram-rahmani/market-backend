<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|max:72',
            'role' => 'nullable|in:user,co-admin,admin',
            'phone' => 'nullable|string|max:20',
        ]);

        $role = $validated['role'] ?? 'user';
        if ($role !== 'user' && Auth::user()?->role !== 'admin') {
            abort(403, 'فقط مدیر کل می‌تواند کاربر مدیریتی ایجاد کند.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'phone' => $validated['phone'] ?? null,
            'status' => 'active',
            'permissions' => [],
        ]);

        return response()->json(['user' => $user], 201);
    }

    public function index()
    {
        $this->authorize('viewAny', User::class);

        $currentUser = User::with('addresses')->find(Auth::id());

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $permissions = [];

        if ($currentUser->isAdmin()) {
            foreach (config('permissionList', []) as $key => $description) {
                $permissions[$key] = true;
            }
        } else {
            foreach (config('permissionList', []) as $key => $description) {
                $permissions[$key] = $currentUser->hasPermission($key);
            }
        }

        return response()->json([
            'users' => User::with('addresses')->get(),
            'user' => $currentUser,
            'permissions' => $permissions
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        if ($request->has('role') && Auth::user()?->role !== 'admin') {
            abort(403, 'فقط مدیر کل می‌تواند نقش کاربران را تغییر دهد.');
        }

        $allowedPermissions = array_keys(config('permissionList', []));

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|unique:users,username,' . $id,
            'role' => 'sometimes|string',
            'status' => 'sometimes|string|in:active,banned',
            'phone' => 'nullable|string|max:20',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'postal_address' => 'nullable|string|max:2000',
            'admin_notes' => 'nullable|string',
            'password' => 'nullable|string|min:8|max:72',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ]);

        // new avatar management
        $avatarPath = $user->avatar;
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        // user basic info
        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'username' => $validated['username'] ?? $user->username,
            'email' => $validated['email'] ?? $user->email,
            'role' => $validated['role'] ?? $user->role,
            'status' => $validated['status'] ?? $user->status,
            'phone' => $validated['phone'] ?? $user->phone,
            'avatar' => $avatarPath,
            'admin_notes' => $validated['admin_notes'] ?? $user->admin_notes,
            'permissions' => $validated['permissions'] ?? $user->permissions,
            'password' => !empty($validated['password'])
                ? Hash::make($validated['password'])
                : $user->password,
        ]);

        // address management
        if ($request->has('postal_address') || $request->has('phone')) {
            $defaultAddress = $user->addresses()->where('is_default', true)->first();
            $addressPhone = $validated['phone'] ?? $user->phone ?? $defaultAddress?->phone;
            $postalAddress = $request->input(
                'postal_address',
                $defaultAddress?->postal_address
            );

            if ($addressPhone !== null && $postalAddress !== null) {
                $user->addresses()->updateOrCreate(
                    ['user_id' => $user->id, 'is_default' => true],
                    [
                        'phone' => $addressPhone,
                        'postal_address' => $postalAddress,
                        'is_default' => true,
                    ]
                );
            }
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh()->load('addresses')
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        $user->delete();
        return response()->json(['message' => 'کاربر به سطل زباله منتقل شد']);
    }

    public function deletedUsers()
    {
        $this->authorize('viewDeleted', User::class);
        return response()->json(['users' => User::onlyTrashed()->with('addresses')->get()]);
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $user);
        $user->restore();
        return response()->json(['message' => 'کاربر با موفقیت بازیابی شد']);
    }

    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $user);

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->forceDelete();
        return response()->json(['message' => 'کاربر برای همیشه حذف شد']);
    }

    public function promote(User $user)
    {
        $this->authorize('promote', $user);
        $user->update(['role' => 'co-admin']);
        return response()->json(['message' => 'کاربر ارتقا یافت']);
    }

    public function demote(User $user)
    {
        $this->authorize('demote', $user);

        $user->update([
            'role' => 'user',
            'permissions' => []
        ]);

        return response()->json(['message' => 'کاربر تنزل یافت و تمام دسترسی‌های او حذف شد.']);
    }

    public function updatePermissions(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->authorize('updatePermissions', $user);

        // دریافت لیست مجاز پرمیشن‌ها از فایل کانفیگ
        $allowedPermissions = array_keys(config('permissionList', []));

        // اعتبارسنجی پرمیشن‌های ارسالی
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => ['string', Rule::in($allowedPermissions)],
        ]);

        // آپدیت پرمیشن‌های کاربر
        $user->update([
            'permissions' => $validated['permissions'],
        ]);

        return response()->json([
            'message' => 'دسترسی‌ها با موفقیت به‌روزرسانی شدند.',
            'user' => $user->fresh()
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'postal_address' => 'nullable|string|max:2000',
        ]);

        // avatar management
        $avatarPath = $user->avatar;
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => $validated['phone'] ?? $user->phone,
            'avatar' => $avatarPath,
        ]);

        if ($request->has('postal_address') || $request->has('phone')) {
            $defaultAddress = $user->addresses()->where('is_default', true)->first();
            $addressPhone = $validated['phone'] ?? $user->phone ?? $defaultAddress?->phone;
            $postalAddress = $request->input(
                'postal_address',
                $defaultAddress?->postal_address
            );

            if ($addressPhone !== null && $postalAddress !== null) {
                $user->addresses()->updateOrCreate(
                    ['user_id' => $user->id, 'is_default' => true],
                    [
                        'phone' => $addressPhone,
                        'postal_address' => $postalAddress,
                        'is_default' => true,
                    ]
                );
            }
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()->load('addresses')
        ]);
    }


    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:16', 'confirmed'],
        ]);

        // new pass validation
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'error_code' => 'WRONG_CURRENT_PASSWORD',
                'message' => 'رمز عبور فعلی اشتباه است.'
            ], 422);
        }

        // update new pass
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'رمز عبور با موفقیت تغییر کرد.'
        ]);
    }
}
