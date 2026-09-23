<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    // wallet info
    public function index(Request $request)
    {
        $wallet = $request->user()->wallet;

        return response()->json([
            'balance' => $wallet ? $wallet->balance : 0,
            'status'  => $wallet ? $wallet->status : 'inactive',
            'last_transaction' => $wallet && $wallet->amount ? [
                'amount'      => $wallet->amount,
                'type'        => $wallet->type,
                'status'      => $wallet->status_transaction,
                'description' => $wallet->description,
                'ref_id'      => $wallet->ref_id,
                'date'        => $wallet->updated_at
            ] : null
        ]);
    }

    // deposit
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'status' => 'active']
            );

            $amount = $request->input('amount');
            $wallet->balance += $amount;

            $wallet->amount = $amount;
            $wallet->type = 'deposit';
            $wallet->status_transaction = 'success';
            $wallet->description = $request->input('description', 'شارژ کیف پول');
            $wallet->ref_id = 'DEP-' . strtoupper(Str::random(12));

            $wallet->save();

            return response()->json([
                'message' => 'موجودی با موفقیت افزایش یافت.',
                'balance' => $wallet->balance,
                'ref_id' => $wallet->ref_id
            ], 200);
        });
    }

    // withdraw
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$wallet || $wallet->balance < $request->input('amount')) {
                return response()->json([
                    'message' => 'موجودی کیف پول کافی نیست.'
                ], 422);
            }

            $amount = $request->input('amount');
            $wallet->balance -= $amount;

            // record last transaction information
            $wallet->amount = $amount;
            $wallet->type = 'withdraw';
            $wallet->status_transaction = 'success';
            $wallet->description = $request->input('description', 'برداشت وجه');
            $wallet->ref_id = 'WTH-' . strtoupper(Str::random(12));

            $wallet->save();

            return response()->json([
                'message' => 'برداشت وجه با موفقیت انجام شد.',
                'balance' => $wallet->balance,
                'ref_id' => $wallet->ref_id
            ], 200);
        });
    }
}