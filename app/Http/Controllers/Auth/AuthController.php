<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function registerUser(Request $request)
    {
        try {
            $this->validateRegistration($request);
        } catch (ValidationException $e) {
            return response()->json(['error_code' => 'VALIDATION_ERROR', 'errors' => $e->errors()], 422);
        }

        $user = $this->createUser($request);
        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load('addresses');

        return response()->json(['user' => $user, 'token' => $token, 'message' => 'Success'], 201);
    }

    public function loginUser(Request $request)
    {
        $request->validate([
            'identifier' => 'required',
            'password' => 'required',
            'remember' => 'sometimes|boolean'
        ]);

        $field = filter_var($request->identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $request->identifier)->first();

        if (!$user) {
            return response()->json(['error_code' => 'USER_NOT_FOUND'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error_code' => 'WRONG_PASSWORD'], 401);
        }

        // update last login
        $user->update(['last_login_at' => Carbon::now('Asia/Tehran')]);

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // load addresses
        $user->load('addresses');

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Success',
            'remember' => $request->boolean('remember')
        ], 200);
    }

    // Forgot Password
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error_code' => 'USER_NOT_FOUND', 'message' => 'کاربری با این ایمیل یافت نشد.'], 404);
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now('Asia/Tehran')
            ]
        );

        return response()->json([
            'message' => 'لینک بازیابی رمز عبور با موفقیت ایجاد شد.',
            'token' => $token,
            'email' => $request->email
        ], 200);
    }

    // Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|string|min:8|max:16|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json(['error_code' => 'INVALID_TOKEN', 'message' => 'توکن بازیابی معتبر نیست یا منقضی شده است.'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error_code' => 'USER_NOT_FOUND'], 404);
        }

        // update password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // clear last token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'رمز عبور با موفقیت تغییر کرد. اکنون می‌توانید وارد شوید.'
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->load('addresses');
        }

        return response()->json(['user' => $user]);
    }

    private function validateRegistration(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|min:3|max:30',
            'username' => 'required|string|min:3|max:30|unique:users,username',
            'email' => 'required|email|max:38|unique:users,email',
            'password' => 'required|string|min:8|max:16|confirmed',
        ]);
    }

    private function createUser(Request $request)
    {
        $isFirstUser = User::count() === 0;

        return User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $isFirstUser ? 'admin' : 'user',
            'last_login_at' => Carbon::now('Asia/Tehran'),
        ]);
    }
}
