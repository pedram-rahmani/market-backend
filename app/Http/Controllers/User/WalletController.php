<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $wallet = $request->user()->wallet;

        return response()->json([
            'balance' => $wallet ? $wallet->balance : 0,
            'status' => $wallet ? $wallet->status : 'inactive'
        ]);
    }
}
