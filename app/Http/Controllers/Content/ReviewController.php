<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileUploaderService;
use App\Models\Content\Review;
use App\Models\Content\ReviewMedia;
use App\Models\General\Notification;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->whereNull('parent_id')
            ->where('is_approved', true)
            ->with([
                'user:id,name',
                'media' => function ($query) {
                    $query->where('is_approved', true);
                },
                'replies' => function ($query) {
                    $query->where('is_approved', true)->with([
                        'user:id,name',
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

    public function store(Request $request, FileUploaderService $uploader)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'parent_id' => 'nullable|exists:reviews,id',
            'rating' => 'nullable|required_if:parent_id,null|integer|between:1,5',
            'comment' => 'nullable|string',
            'files.*' => 'nullable|file|mimes:jpeg,png,jpg,mp4|max:10240',
        ]);

        $review = Review::create([
            'user_id' => Auth::id(),
            'product_id' => $validated['product_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'comment' => $validated['comment'] ?? null,
            'is_approved' => false,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $uploader->upload($file);
                $review->media()->create([
                    'file_path' => $path,
                    'file_type' => str_contains($file->getClientMimeType(), 'video') ? 'video' : 'image',
                    'disk' => 'public',
                    'is_approved' => false,
                ]);
            }
        }

        // **ساخت نوتیفیکیشن برای ثبت نظر**
        Notification::create([
            'user_id' => Auth::id(),
            'type' => 'user-interactions',
            'title' => 'دیدگاه جدید',
            'message' => 'دیدگاه شما با موفقیت ثبت شد و در انتظار تایید است.',
            'target_link' => '/my-account/user-interactions',
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'نظر شما با موفقیت ثبت شد و پس از تأیید ادمین نمایش داده می‌شود.',
            'review' => $review->load(['user:id,name', 'media'])
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

    public function toggleApproval(Review $review)
    {
        $review->is_approved = !$review->is_approved;
        $review->save();

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
        $reviews = Review::with(['user:id,name', 'product:id,name', 'media'])
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
