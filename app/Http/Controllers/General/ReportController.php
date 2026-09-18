<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Report;
use App\Models\Content\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class ReportController extends Controller
{
    public function storeReviewReport(
        Request $request,
        Review $review,
        NotificationService $notifications
    )
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();

        // prevent duplicate reports by the same user for the same review
        $exists = Report::where('user_id', $userId)
            ->where('reportable_type', Review::class)
            ->where('reportable_id', $review->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'شما قبلاً این دیدگاه را گزارش کرده‌اید.'
            ], 422);
        }

        // new report by morph relationship
        $review->reports()->create([
            'user_id' => $userId,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        $notifications->notifyAdmins(
            type: 'user-interactions',
            title: 'گزارش جدید برای دیدگاه',
            message: "یک دیدگاه توسط کاربر {$request->user()->name} گزارش شد.",
            targetLink: '/my-account/user-interactions',
            exceptUserId: $userId,
        );

        return response()->json([
            'message' => 'گزارش شما با موفقیت ثبت شد. با تشکر از همکاری شما.'
        ]);
    }
}
