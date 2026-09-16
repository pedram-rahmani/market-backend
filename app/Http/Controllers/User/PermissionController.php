<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'canDelete'      => $user->isAdmin(),
            'canPromote'     => $user->isAdmin(),
            'canDemote'      => $user->isAdmin(),
            'canViewDeleted' => $user->isAdmin(),
            'canEditUser'    => $user->isAdmin(),
        ]);
    }
}
