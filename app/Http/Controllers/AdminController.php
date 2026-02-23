<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Admin dashboard with global statistics.
     */
    public function dashboard(): View
    {
        $stats = [
            'total_users' => User::count(),
            'banned_users' => User::where('is_banned', true)->count(),
            'admin_users' => User::where('role', 'admin')->count(),
            'total_colocations' => Colocation::count(),
            'active_colocations' => Colocation::where('status', 'active')->count(),
            'cancelled_colocations' => Colocation::where('status', 'cancelled')->count(),
            'total_expenses' => Expense::count(),
            'total_expenses_amount' => Expense::sum('amount'),
        ];

        $users = User::orderBy('created_at', 'desc')->get();

        return view('admin.dashboard', compact('stats', 'users'));
    }

    /**
     * Ban a user.
     */
    public function ban(User $user): RedirectResponse
    {
        // Cannot ban yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Vous ne pouvez pas vous bannir vous-même.');
        }

        // Cannot ban another admin
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Impossible de bannir un administrateur.');
        }

        $user->update(['is_banned' => true]);

        return redirect()->route('admin.dashboard')
            ->with('success', $user->name . ' a été banni.');
    }

    /**
     * Unban a user.
     */
    public function unban(User $user): RedirectResponse
    {
        $user->update(['is_banned' => false]);

        return redirect()->route('admin.dashboard')
            ->with('success', $user->name . ' a été débanni.');
    }
}
