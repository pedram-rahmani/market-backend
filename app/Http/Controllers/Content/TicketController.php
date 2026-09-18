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

        $notifications->notifyAdmins(
            type: 'support',
            title: 'تیکت پشتیبانی جدید',
            message: "تیکت جدیدی از طرف {$request->user()->name} ثبت شد.",
            targetLink: '/my-account/support',
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
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        if (!$request->user()->isAdmin()) {
            $notifications->notifyAdmins(
                type: 'support',
                title: 'پاسخ جدید به تیکت',
                message: "پاسخ جدیدی از طرف {$request->user()->name} در یک تیکت ثبت شد.",
                targetLink: '/my-account/support',
                exceptUserId: $request->user()->id,
            );
        } else {
            $notifications->notifyUser(
                userId: $ticket->user_id,
                type: 'support',
                title: 'پاسخ جدید به تیکت شما',
                message: 'پاسخ جدیدی از طرف پشتیبانی برای تیکت شما ثبت شده است.',
                targetLink: '/my-account/support',
            );
        }

        return response()->json([
            'message' => 'پاسخ با موفقیت ارسال شد.',
            'data' => $message
        ], 201);
    }
}
