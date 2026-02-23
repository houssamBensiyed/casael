<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    /**
     * Send an invitation to join a colocation.
     */
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        Gate::authorize('create', [Invitation::class, $colocation]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = $validated['email'];

        // Check if user is already an active member
        $targetUser = User::where('email', $email)->first();
        if ($targetUser && $colocation->activeMembers()->where('user_id', $targetUser->id)->exists()) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Cet utilisateur est déjà membre de la colocation.');
        }

        // Check if user already has an active colocation
        if ($targetUser && $targetUser->hasActiveColocation()) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Cet utilisateur a déjà une colocation active.');
        }

        // Check if a pending invitation already exists for this email
        $existingInvitation = $colocation->invitations()
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', 'Une invitation est déjà en attente pour cet email.');
        }

        // Create the invitation
        Invitation::create([
            'colocation_id' => $colocation->id,
            'email' => $email,
            'token' => Str::random(32),
            'status' => 'pending',
        ]);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Invitation envoyée.');
    }

    /**
     * Accept an invitation via token.
     */
    public function accept(string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $user = auth()->user();

        // Ensure the logged-in user's email matches the invitation
        if ($user->email !== $invitation->email) {
            return redirect()->route('dashboard')
                ->with('error', 'Cette invitation ne vous est pas destinée.');
        }

        // Ensure user doesn't already have an active colocation
        if ($user->hasActiveColocation()) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous avez déjà une colocation active.');
        }

        // Ensure the colocation is still active
        if ($invitation->colocation->status !== 'active') {
            $invitation->update(['status' => 'refused']);
            return redirect()->route('dashboard')
                ->with('error', 'Cette colocation n\'est plus active.');
        }

        // Accept the invitation
        $invitation->update(['status' => 'accepted']);

        // Add the user to the colocation
        $invitation->colocation->members()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        return redirect()->route('colocations.show', $invitation->colocation)
            ->with('success', 'Vous avez rejoint la colocation !');
    }

    /**
     * Refuse an invitation via token.
     */
    public function refuse(string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        $user = auth()->user();

        // Ensure the logged-in user's email matches the invitation
        if ($user->email !== $invitation->email) {
            return redirect()->route('dashboard')
                ->with('error', 'Cette invitation ne vous est pas destinée.');
        }

        $invitation->update(['status' => 'refused']);

        return redirect()->route('dashboard')
            ->with('success', 'Invitation refusée.');
    }

    /**
     * Owner cancels a pending invitation.
     */
    public function destroy(Invitation $invitation): RedirectResponse
    {
        Gate::authorize('delete', $invitation);

        $colocation = $invitation->colocation;
        $invitation->delete();

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Invitation annulée.');
    }
}
