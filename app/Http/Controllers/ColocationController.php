<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ColocationController extends Controller
{
    /**
     * Display a listing of the user's colocations.
     */
    public function index(Request $request): View
    {
        $colocations = $request->user()->colocations()->latest()->get();

        return view('colocations.index', compact('colocations'));
    }

    /**
     * Show the form for creating a new colocation.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasActiveColocation()) {
            return redirect()->route('colocations.index')
                ->with('error', 'Vous avez déjà une colocation active.');
        }

        return view('colocations.create');
    }

    /**
     * Store a newly created colocation.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasActiveColocation()) {
            return redirect()->route('colocations.index')
                ->with('error', 'Vous avez déjà une colocation active.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $colocation = Colocation::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'owner_id' => $request->user()->id,
        ]);

        // Auto-add the creator as owner in the pivot table
        $colocation->members()->attach($request->user()->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Colocation créée avec succès.');
    }

    /**
     * Display the specified colocation.
     */
    public function show(Colocation $colocation): View
    {
        Gate::authorize('view', $colocation);

        $colocation->load(['activeMembers', 'owner', 'categories', 'expenses.payer', 'expenses.category']);

        return view('colocations.show', compact('colocation'));
    }

    /**
     * Show the form for editing the specified colocation.
     */
    public function edit(Colocation $colocation): View
    {
        Gate::authorize('update', $colocation);

        return view('colocations.edit', compact('colocation'));
    }

    /**
     * Update the specified colocation.
     */
    public function update(Request $request, Colocation $colocation): RedirectResponse
    {
        Gate::authorize('update', $colocation);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $colocation->update($validated);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Colocation mise à jour.');
    }

    /**
     * Cancel the colocation (soft delete via status).
     */
    public function destroy(Colocation $colocation): RedirectResponse
    {
        Gate::authorize('delete', $colocation);

        // Set status to cancelled
        $colocation->update(['status' => 'cancelled']);

        // Mark all active members as left
        $colocation->activeMembers()->each(function ($member) use ($colocation) {
            $colocation->members()->updateExistingPivot($member->id, [
                'left_at' => now(),
            ]);
        });

        return redirect()->route('colocations.index')
            ->with('success', 'Colocation annulée.');
    }

    /**
     * Member leaves the colocation.
     */
    public function leave(Colocation $colocation): RedirectResponse
    {
        Gate::authorize('leave', $colocation);

        $user = auth()->user();

        // Set left_at on the pivot
        $colocation->members()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);

        return redirect()->route('colocations.index')
            ->with('success', 'Vous avez quitté la colocation.');
    }

    /**
     * Owner removes a member from the colocation.
     */
    public function removeMember(Colocation $colocation, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $colocation);

        // Owner cannot remove themselves
        if ($user->id === $colocation->owner_id) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Le propriétaire ne peut pas se retirer.');
        }

        // Verify user is an active member
        if (!$colocation->activeMembers()->where('user_id', $user->id)->exists()) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Cet utilisateur n\'est pas un membre actif.');
        }

        // Set left_at on the pivot
        $colocation->members()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Membre retiré de la colocation.');
    }
}
