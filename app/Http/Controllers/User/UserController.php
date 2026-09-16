<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', User::class);

        $currentUser = User::with('addresses')->find(Auth::id());

        if (!$currentUser) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $permissions = [];

        if ($currentUser->isAdmin()) {
            foreach (User::$availablePermissions as $key => $description) {
                $permissions[$key] = true;
            }
        } else {
            foreach (User::$availablePermissions as $key => $description) {
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

        $allowedPermissions = array_keys(User::$availablePermissions);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|unique:users,username,' . $id,
            'role' => 'sometimes|string',
            'status' => 'sometimes|string|in:active,banned',
            'phone' => 'nullable|string|max:20',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'postal_address' => 'nullable|string',
            'admin_notes' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:' . implode(',', $allowedPermissions),
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
        ]);

        // address management
        if ($request->has('postal_address') || $request->has('phone')) {
            $user->addresses()->updateOrCreate(
                ['user_id' => $user->id, 'is_default' => true],
                [
                    'phone' => $validated['phone'] ?? $user->phone,
                    'postal_address' => $request->input('postal_address', optional($user->addresses()->where('is_default', true)->first())->postal_address),
                    'is_default' => true
                ]
            );
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

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:' . implode(',', array_keys(User::$availablePermissions)),
        ]);

        $user->update([
            'permissions' => $request->permissions,
        ]);

        return response()->json(['message' => 'پرمیشن‌ها با موفقیت بروزرسانی شدند']);
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
            'postal_address' => 'nullable|string',
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
            $user->addresses()->updateOrCreate(
                ['user_id' => $user->id, 'is_default' => true],
                [
                    'phone' => $validated['phone'] ?? $user->phone,
                    'postal_address' => $request->input('postal_address', ''),
                    'is_default' => true
                ]
            );
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
