<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Coupon;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $coupons = Coupon::with('users')->latest()->paginate(10);
        return response()->json($coupons);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric',
            'min_order_price' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'usage_limit' => 'nullable|integer',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $couponData = $request->except('user_id');
        $coupon = Coupon::create($couponData);

        if ($request->filled('user_id')) {
            $coupon->users()->attach($request->user_id);
        }

        return response()->json([
            'message' => 'کد تخفیف با موفقیت ایجاد شد.',
            'coupon' => $coupon->load('users')
        ], 201);
    }

    public function show($id)
    {
        $coupon = Coupon::with('users')->findOrFail($id);
        return response()->json($coupon);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $request->validate([
            'code' => 'required|string|unique:coupons,code,' . $id,
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric',
            'min_order_price' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'usage_limit' => 'nullable|integer',
            'expires_at' => 'nullable',
            'is_active' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $couponData = $request->except('user_id');

        if (!empty($couponData['expires_at'])) {
            try {
                Carbon::parse($couponData['expires_at']);
            } catch (\Exception $e) {
                unset($couponData['expires_at']);
            }
        } else {
            $couponData['expires_at'] = null;
        }

        $coupon->update($couponData);

        if ($request->filled('user_id')) {
            $coupon->users()->sync([$request->user_id]);
        } else {
            $coupon->users()->detach();
        }

        return response()->json([
            'message' => 'کد تخفیف با موفقیت ویرایش شد.',
            'coupon' => $coupon->load('users')
        ]);
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'message' => 'کد تخفیف با موفقیت حذف شد.'
        ]);
    }

    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'total_price' => 'required|numeric',
        ]);

        $user = $request->user();

        $coupon = Coupon::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json(['message' => 'کد تخفیف نامعتبر است.'], 422);
        }

        if ($coupon->expires_at && now()->greaterThan($coupon->expires_at)) {
            return response()->json(['message' => 'کد تخفیف منقضی شده است.'], 422);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json(['message' => 'ظرفیت استفاده از این کد به پایان رسیده است.'], 422);
        }

        if ($coupon->min_order_price && $request->total_price < $coupon->min_order_price) {
            return response()->json(['message' => "حداقل مبلغ سفارش برای این کد {$coupon->min_order_price} تومان است."], 422);
        }

        $assignedUsers = $coupon->users;
        if ($assignedUsers->isNotEmpty()) {
            if (!$user || !$assignedUsers->contains('id', $user->id)) {
                return response()->json(['message' => 'این کد تخفیف متعلق به شما نیست.'], 422);
            }
        }

        if ($user) {
            $alreadyUsed = $coupon->users()->where('user_id', $user->id)->wherePivot('used_at', '!=', null)->exists();
            if ($alreadyUsed) {
                return response()->json(['message' => 'شما قبلاً از این کد تخفیف استفاده کرده‌اید.'], 422);
            }
        }

        $discountAmount = 0;
        if ($coupon->type === 'percent') {
            $discountAmount = ($request->total_price * $coupon->value) / 100;
            if ($coupon->max_discount && $discountAmount > $coupon->max_discount) {
                $discountAmount = $coupon->max_discount;
            }
        } else {
            $discountAmount = $coupon->value;
        }

        return response()->json([
            'message' => 'کد تخفیف با موفقیت اعمال شد.',
            'coupon_id' => $coupon->id,
            'discount_amount' => $discountAmount,
        ]);
    }

    public function userCoupons(Request $request)
    {
        $user = $request->user();

        $coupons = \App\Models\General\Coupon::whereHas('users', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('is_active', true)
        ->get()
        ->map(function ($coupon) {
            $expireDate = null;
            if ($coupon->expires_at) {
                $expireDate = is_string($coupon->expires_at)
                    ? Carbon::parse($coupon->expires_at)->format('Y/m/d')
                    : $coupon->expires_at->format('Y/m/d');
            }

            return [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'title' => 'تخفیف اختصاصی',
                'discountValue' => $coupon->type === 'percent'
                    ? (int)$coupon->value . ' درصد'
                    : number_format($coupon->value) . ' تومان',
                'expireDate' => $expireDate,
            ];
        });

        return response()->json([
            'data' => $coupons
        ]);
    }
}
