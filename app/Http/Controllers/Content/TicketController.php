<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\Ticket;
use App\Models\Content\TicketMessage;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class TicketController extends Controller
{
    // دریافت لیست تیکت‌های کاربر جاری
    public function index(Request $request)
    {
        $tickets = $request->user()->tickets()->with(['messages'])->latest()->get();
        return response()->json($tickets);
    }

    // دریافت لیست تمام تیکت‌ها برای ادمین/co-admin دارای دسترسی tickets.view
    public function adminIndex(Request $request)
    {
        $query = Ticket::with(['user:id,name,username', 'messages.user:id,name,username'])->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->get());
    }

    // نمایش جزئیات یک تیکت (صاحب تیکت یا کارمند دارای دسترسی tickets.view)
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeAccess($request, $ticket, 'tickets.view');

        return response()->json(
            $ticket->load(['user:id,name,username', 'messages.user:id,name,username'])
        );
    }

    // تغییر وضعیت تیکت توسط ادمین/co-admin دارای دسترسی tickets.reply
    public function updateStatus(Request $request, Ticket $ticket)
    {
        if (!$request->user()->hasPermission('tickets.reply')) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $validated = $request->validate([
            'status' => 'required|in:open,pending,closed',
        ]);

        $ticket->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'وضعیت تیکت به‌روزرسانی شد.',
            'ticket' => $ticket->fresh(['user:id,name,username', 'messages.user:id,name,username']),
        ]);
    }

    // بررسی دسترسی کاربر به یک تیکت مشخص (مالک تیکت یا کارمند دارای دسترسی)
    private function authorizeAccess(Request $request, Ticket $ticket, string $staffPermission): void
    {
        $user = $request->user();
        $isOwner = $ticket->user_id === $user->id;

        if (!$isOwner && !$user->hasPermission($staffPermission)) {
            abort(403, 'دسترسی غیرمجاز');
        }
    }

    // حذف تیکت (توسط صاحب تیکت یا کارمند دارای دسترسی tickets.delete)
    public function destroy(Request $request, Ticket $ticket)
    {
        $this->authorizeAccess($request, $ticket, 'tickets.delete');

        $ticket->delete();

        return response()->json([
            'message' => 'تیکت با موفقیت حذف شد.',
        ]);
    }

    // ثبت تیکت جدید به همراه اولین پیام
    public function store(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|string',
            'priority' => 'required|string',
            'message' => 'required|string',
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'department' => $validated['department'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        $notifications->notifyStaff(
            permission: 'tickets.view',
            type: 'ticket-management',
            title: 'تیکت پشتیبانی جدید',
            message: "تیکت جدیدی از طرف {$request->user()->name} ثبت شد.",
            targetLink: '/my-account/ticket-management',
            exceptUserId: $request->user()->id,
        );

        return response()->json([
            'message' => 'تیکت با موفقیت ثبت شد.',
            'ticket' => $ticket->load('messages')
        ], 201);
    }

    // ارسال پیام جدید (پاسخ) به یک تیکت موجود
    public function reply(
        Request $request,
        Ticket $ticket,
        NotificationService $notifications
    )
    {
        $this->authorizeAccess($request, $ticket, 'tickets.reply');

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        $isStaffReply = $ticket->user_id !== $request->user()->id;

        if ($isStaffReply) {
            // اگر تیکت قبلا بسته شده بود، پاسخ کارمند آن را دوباره باز می‌کند
            if ($ticket->status === 'closed') {
                $ticket->update(['status' => 'open']);
            }

            $notifications->notifyUser(
                userId: $ticket->user_id,
                type: 'support',
                title: 'پاسخ جدید به تیکت شما',
                message: 'پاسخ جدیدی از طرف پشتیبانی برای تیکت شما ثبت شده است.',
                targetLink: '/my-account/support',
            );
        } else {
            $notifications->notifyStaff(
                permission: 'tickets.view',
                type: 'ticket-management',
                title: 'پاسخ جدید به تیکت',
                message: "پاسخ جدیدی از طرف {$request->user()->name} در یک تیکت ثبت شد.",
                targetLink: '/my-account/ticket-management',
                exceptUserId: $request->user()->id,
            );
        }

        return response()->json([
            'message' => 'پاسخ با موفقیت ارسال شد.',
            'data' => $message->load('user:id,name,username')
        ], 201);
    }
}
