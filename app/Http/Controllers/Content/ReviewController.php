<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileUploaderService;
use App\Models\Content\Review;
use App\Models\Content\ReviewMedia;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->whereNull('parent_id')
            ->where('is_approved', true)
            ->with([
                'user:id,name,role',
                'media' => function ($query) {
                    $query->where('is_approved', true);
                },
                'replies' => function ($query) {
                    $query->where('is_approved', true)->with([
                        'user:id,name,role',
                        'media' => function ($q) {
                            $q->where('is_approved', true);
                        }
                    ]);
                }
            ])
            ->withCount([
                'reactions as likes_count' => function ($query) {
                    $query->where('type', 'like');
                },
                'reactions as dislikes_count' => function ($query) {
                    $query->where('type', 'dislike');
                }
            ])
            ->latest()
            ->get();

        $user = Auth::guard('sanctum')->user();

        if ($user) {
            $userId = $user->id;
            $reviews->transform(function ($review) use ($userId) {
                $userReaction = $review->reactions()->where('user_id', $userId)->first();
                $review->user_reaction = $userReaction ? $userReaction->type : null;
                return $review;
            });
        }

        return response()->json($reviews);
    }

    public function store(
        Request $request,
        FileUploaderService $uploader,
        NotificationService $notifications
    )
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'parent_id' => 'nullable|exists:reviews,id',
            'rating' => 'nullable|required_if:parent_id,null|integer|between:1,5',
            'comment' => 'nullable|string',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,mp4|max:10240',
        ]);

        $user = $request->user();
        $isReply = !empty($validated['parent_id']);
        $isStaffAuthor = $user->isAdmin() ||
            ($user->isCoAdmin() && $user->hasPermission('comments.reply'));

        if ($isReply && $user->isCoAdmin() && !$user->hasPermission('comments.reply')) {
            return response()->json(['message' => 'شما اجازه پاسخ به دیدگاه‌ها را ندارید.'], 403);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $validated['product_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'is_approved' => $isStaffAuthor,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $uploader->upload($file);
                $review->media()->create([
                    'file_path' => $path,
                    'file_type' => str_contains($file->getClientMimeType(), 'video') ? 'video' : 'image',
                    'disk' => 'public',
                    'is_approved' => $isStaffAuthor,
                ]);
            }
        }

        if ($review->parent_id && $review->parent?->user_id) {
            $notifications->notifyUser(
                userId: $review->parent->user_id,
                type: 'user-interactions',
                title: 'پاسخ جدید به دیدگاه شما',
                message: 'ادمین به دیدگاه شما پاسخ داده است.',
                targetLink: '/my-account/user-interactions',
            );
        } elseif (!$user->isAdmin()) {
            $notifications->notifyAdmins(
                type: 'user-interactions',
                title: 'دیدگاه جدید',
                message: "دیدگاه جدیدی از طرف {$request->user()->name} برای بررسی ثبت شد.",
                targetLink: '/my-account/user-interactions',
                exceptUserId: Auth::id(),
            );
            $notifications->notifyUser(
                userId: Auth::id(),
                type: 'user-interactions',
                title: 'دیدگاه شما ثبت شد',
                message: 'دیدگاه شما با موفقیت ثبت شد و پس از بررسی نمایش داده خواهد شد.',
                targetLink: '/my-account/user-interactions',
            );
        }

        return response()->json([
            'message' => $isStaffAuthor
                ? 'پاسخ شما با موفقیت ثبت شد.'
                : 'نظر شما با موفقیت ثبت شد و پس از تأیید ادمین نمایش داده می‌شود.',
            'review' => $review->load(['user:id,name,role', 'media'])
        ], 201);
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'rating' => 'nullable|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        $review->update([
            'rating' => $validated['rating'] ?? $review->rating,
            'comment' => $validated['comment'] ?? $review->comment,
            'is_approved' => false,
        ]);

        return response()->json([
            'message' => 'دیدگاه با موفقیت ویرایش شد و پس از تأیید مجدد نمایش داده خواهد شد.',
            'review' => $review->load(['user:id,name', 'media'])
        ]);
    }

    public function destroy(Review $review)
    {
        foreach ($review->media as $media) {
            $media->delete();
        }

        $review->delete();

        return response()->json([
            'message' => 'دیدگاه با موفقیت حذف شد.'
        ]);
    }

    public function destroyAll()
    {
        DB::transaction(function (): void {
            ReviewMedia::query()->delete();
            Review::query()->delete();
        });

        return response()->json(['message' => 'تمام دیدگاه‌ها و پاسخ‌ها حذف شدند.']);
    }

    public function toggleApproval(Review $review, NotificationService $notifications)
    {
        $review->is_approved = !$review->is_approved;
        $review->save();
        $notifications->notifyUser(
            userId: $review->user_id,
            type: 'user-interactions',
            title: $review->is_approved ? 'دیدگاه شما تایید شد' : 'دیدگاه شما نیاز به بررسی دارد',
            message: $review->is_approved
                ? 'دیدگاه شما تایید شد و در سایت نمایش داده می‌شود.'
                : 'وضعیت تایید دیدگاه شما تغییر کرد؛ برای بررسی بیشتر به تعاملات کاربران بروید.',
            targetLink: '/my-account/user-interactions',
        );

        return response()->json([
            'message' => 'وضعیت تایید دیدگاه با موفقیت تغییر کرد.',
            'is_approved' => $review->is_approved,
            'review' => $review
        ]);
    }

    public function toggleMediaApproval(ReviewMedia $media)
    {
        $media->is_approved = !$media->is_approved;
        $media->save();

        return response()->json([
            'message' => 'وضعیت تایید فایل با موفقیت تغییر کرد.',
            'is_approved' => $media->is_approved,
            'media' => $media
        ]);
    }

    public function adminIndex(Request $request)
    {
        $reviews = Review::whereNull('parent_id')
            ->with([
                'user:id,name,role,avatar',
                'product:id,name',
                'media',
                'replies' => function ($query) {
                    $query->with(['user:id,name,role,avatar', 'media'])->latest();
                },
            ])
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    public function react(Request $request, Review $review)
    {
        $request->validate([
            'type' => 'required|in:like,dislike',
        ]);

        $userId = Auth::id();
        $type = $request->type;

        $existingReaction = $review->reactions()->where('user_id', $userId)->first();

        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                $existingReaction->delete();
                $userReaction = null;
            } else {
                $existingReaction->update(['type' => $type]);
                $userReaction = $type;
            }
        } else {
            $review->reactions()->create([
                'user_id' => $userId,
                'type' => $type,
            ]);
            $userReaction = $type;
        }

        return response()->json([
            'user_reaction' => $userReaction,
            'likes_count' => $review->reactions()->where('type', 'like')->count(),
            'dislikes_count' => $review->reactions()->where('type', 'dislike')->count(),
        ]);
    }

    public function userReviews()
    {
        $userId = Auth::id();

        $reviews = Review::where('user_id', $userId)
            ->with(['product:id,name,slug', 'media'])
            ->latest()
            ->get();

        return response()->json($reviews);
    }
}
