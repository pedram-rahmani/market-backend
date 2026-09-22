<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User\User $user */
        $user = Auth::user();

        $orderCount = $user->orders()->count();
        $unreadChatCount = $user->chatConversation
            ? $user->chatConversation->messages()->where('user_id', '!=', $user->id)->where('is_read', false)->count()
            : 0;
        $walletBalance = $user->wallet ? $user->wallet->balance : 0;

        // get last ( 5 ) transactions
        $latestTransactions = $user->wallet
            ? $user->wallet->transactions()->latest()->take(5)->get()
            : [];

        return response()->json([
            'order_count'         => $orderCount,
            'unread_chat_count'   => $unreadChatCount,
            'wallet_balance'      => $walletBalance,
            'latest_transactions' => $latestTransactions,
        ]);
    }
}
