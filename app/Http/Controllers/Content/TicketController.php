<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\Ticket;
use App\Models\Content\TicketMessage;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    // دریافت لیست تیکت‌های کاربر جاری
    public function index(Request $request)
    {
        $tickets = $request->user()->tickets()->with(['messages'])->latest()->get();
        return response()->json($tickets);
    }

    // ثبت تیکت جدید به همراه اولین پیام
    public function store(Request $request)
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

        return response()->json([
            'message' => 'تیکت با موفقیت ثبت شد.',
            'ticket' => $ticket->load('messages')
        ], 201);
    }

    // ارسال پیام جدید (پاسخ) به یک تیکت موجود
    public function reply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return response()->json([
            'message' => 'پاسخ با موفقیت ارسال شد.',
            'data' => $message
        ], 201);
    }
}
