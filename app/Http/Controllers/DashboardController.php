<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $activeColocation = $user->activeColocation();
        $pendingInvitations = \App\Models\Invitation::where('email', $user->email)
            ->where('status', 'pending')
            ->with('colocation')
            ->get();

        return view('dashboard', [
            'activeColocation' => $activeColocation,
            'pendingInvitations' => $pendingInvitations,
            'reputation' => $user->reputation,
        ]);
    }
}
