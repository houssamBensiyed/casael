<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Settlement;
use App\Services\BalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function __construct(
        protected BalanceService $balanceService
    ) {}

    /**
     * Show balances and unpaid settlements for a colocation.
     */
    public function index(Colocation $colocation): View
    {
        Gate::authorize('viewBalances', [Settlement::class, $colocation]);

        $balances = $this->balanceService->calculateBalances($colocation);
        $settlements = $colocation->settlements()->where('is_paid', false)->with(['fromUser', 'toUser'])->get();
        $paidSettlements = $colocation->settlements()->where('is_paid', true)->with(['fromUser', 'toUser'])->get();

        return view('settlements.index', compact('colocation', 'balances', 'settlements', 'paidSettlements'));
    }

    /**
     * Generate settlement records from current expenses.
     */
    public function generate(Colocation $colocation): RedirectResponse
    {
        Gate::authorize('generate', [Settlement::class, $colocation]);

        // Delete old unpaid settlements before regenerating
        $colocation->settlements()->where('is_paid', false)->delete();

        $transfers = $this->balanceService->generateSettlements($colocation);

        foreach ($transfers as $transfer) {
            Settlement::create([
                'from_user_id' => $transfer['from'],
                'to_user_id' => $transfer['to'],
                'amount' => $transfer['amount'],
                'colocation_id' => $colocation->id,
                'is_paid' => false,
            ]);
        }

        return redirect()->route('settlements.index', $colocation)
            ->with('success', count($transfers) . ' règlement(s) généré(s).');
    }

    /**
     * Mark a settlement as paid.
     */
    public function markPaid(Settlement $settlement): RedirectResponse
    {
        Gate::authorize('markPaid', $settlement);

        $settlement->update(['is_paid' => true]);

        return redirect()->route('settlements.index', $settlement->colocation)
            ->with('success', 'Règlement marqué comme payé.');
    }
}
