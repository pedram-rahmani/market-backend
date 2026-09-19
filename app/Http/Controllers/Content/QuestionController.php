<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Content\Question;
use App\Models\Content\QuestionReaction;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class QuestionController extends Controller
{
    // Fetch all main questions with replies, counts, and user reaction state (Public)
    public function index()
    {
        $questions = Question::whereNull('parent_id')
            ->where('is_approved', true)
            ->with([
                'user:id,name',
                'product:id,name',
                'replies' => function ($query) {
                    $query->where('is_approved', true)
                        ->with('user:id,name')->withCount([
                        'reactions as likes_count' => fn($q) => $q->where('type', 'like'),
                        'reactions as dislikes_count' => fn($q) => $q->where('type', 'dislike'),
                    ]);
                }
            ])
            ->withCount([
                'reactions as likes_count' => fn($q) => $q->where('type', 'like'),
                'reactions as dislikes_count' => fn($q) => $q->where('type', 'dislike'),
            ])
            ->latest()
            ->get();

        // Attach current authenticated user's reaction if logged in
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $userId = $user->id;
            $questions->transform(function ($question) use ($userId) {
                $userReaction = $question->reactions()->where('user_id', $userId)->first();
                $question->user_reaction = $userReaction ? $userReaction->type : null;
                return $question;
            });
        }

        return response()->json($questions);
    }

    // Fetch all questions for the Admin panel
    public function adminIndex()
    {
        $questions = Question::whereNull('parent_id')
            ->with([
                'user:id,name',
                'product:id,name',
                'replies' => function ($query) {
                    $query->with(['user:id,name', 'product:id,name'])
                        ->latest();
                }
            ])
            ->latest()
            ->get();

        return response()->json($questions);
    }

    // Fetch questions created by the authenticated user along with their replies
    public function userQuestions()
    {
        $userId = Auth::id();

        $questions = Question::where('user_id', $userId)
            ->whereNull('parent_id')
            ->with([
                'product:id,name',
                'replies' => function ($query) {
                    $query->with(['user:id,name', 'product:id,name'])->latest();
                }
            ])
            ->latest()
            ->get();

        return response()->json($questions);
    }

    // Store a new question or reply
    public function store(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'parent_id' => 'nullable|exists:questions,id',
            'body' => 'required|string',
        ]);

        $user = Auth::user();
        $isAdmin = method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;

        $question = Question::create([
            'user_id' => $user->id,
            'product_id' => $validated['product_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
            'is_approved' => $isAdmin ? true : false,
            'is_admin_answer' => $isAdmin && isset($validated['parent_id']),
        ]);

        if (!$isAdmin) {
            $notifications->notifyAdmins(
                type: 'user-interactions',
                title: 'پرسش جدید',
                message: "پرسش جدیدی از طرف {$user->name} برای بررسی ثبت شد.",
                targetLink: '/my-account/user-interactions',
                exceptUserId: $user->id,
            );
        }
        if ($question->parent_id && $question->parent?->user_id) {
            $notifications->notifyUser(
                userId: $question->parent->user_id,
                type: 'user-interactions',
                title: 'پاسخ جدید به پرسش شما',
                message: 'پاسخ جدیدی برای پرسش شما ثبت شده است.',
                targetLink: '/my-account/user-interactions',
            );
        } elseif (!$isAdmin) {
            $notifications->notifyUser(
                userId: $user->id,
                type: 'user-interactions',
                title: 'پرسش شما ثبت شد',
                message: 'پرسش شما با موفقیت ثبت شد و پس از بررسی نمایش داده خواهد شد.',
                targetLink: '/my-account/user-interactions',
            );
        }

        return response()->json([
            'message' => 'پرسش/پاسخ شما با موفقیت ثبت شد.',
            'question' => $question->load(['user:id,name'])
        ], 201);
    }

    // Update an existing question or reply
    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $question->update([
            'body' => $validated['body'],
            'is_approved' => false,
        ]);

        return response()->json([
            'message' => 'مورد با موفقیت ویرایش شد.',
            'question' => $question->load(['user:id,name'])
        ]);
    }

    // Delete a question or reply
    public function destroy(Question $question)
    {
        $question->delete();

        return response()->json([
            'message' => 'مورد با موفقیت حذف شد.'
        ]);
    }

    // Toggle approval status for admin panel
    public function toggleApproval(Question $question, NotificationService $notifications)
    {
        $question->is_approved = !$question->is_approved;
        $question->save();
        $notifications->notifyUser(
            userId: $question->user_id,
            type: 'user-interactions',
            title: $question->is_approved ? 'پرسش شما تایید شد' : 'پرسش شما نیاز به بررسی دارد',
            message: $question->is_approved
                ? 'پرسش شما تایید شد و در سایت نمایش داده می‌شود.'
                : 'وضعیت تایید پرسش شما تغییر کرد؛ برای بررسی بیشتر به تعاملات کاربران بروید.',
            targetLink: '/my-account/user-interactions',
        );

        return response()->json([
            'message' => 'وضعیت تایید تغییر کرد.',
            'is_approved' => $question->is_approved,
            'question' => $question
        ]);
    }

    // Handle like or dislike reactions for questions
    public function react(Request $request, Question $question)
    {
        $request->validate([
            'type' => 'required|in:like,dislike',
        ]);

        $userId = Auth::id();
        $type = $request->type;

        $existingReaction = QuestionReaction::where('user_id', $userId)
            ->where('question_id', $question->id)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                $existingReaction->delete();
                $userReaction = null;
            } else {
                $existingReaction->update(['type' => $type]);
                $userReaction = $type;
            }
        } else {
            QuestionReaction::create([
                'user_id' => $userId,
                'question_id' => $question->id,
                'type' => $type,
            ]);
            $userReaction = $type;
        }

        return response()->json([
            'user_reaction' => $userReaction,
            'likes_count' => $question->reactions()->where('type', 'like')->count(),
            'dislikes_count' => $question->reactions()->where('type', 'dislike')->count(),
        ]);
    }
}
