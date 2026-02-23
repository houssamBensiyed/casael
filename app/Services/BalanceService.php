<?php

namespace App\Services;

use App\Models\Colocation;

class BalanceService
{
    /**
     * Calculate per-user balances from all expenses in a colocation.
     *
     * @return array<int, array{paid: float, share: float, balance: float}>
     */
    public function calculateBalances(Colocation $colocation): array
    {
        $activeMembers = $colocation->activeMembers()->pluck('users.id')->toArray();

        if (empty($activeMembers)) {
            return [];
        }

        // Sum total paid by each member
        $expenses = $colocation->expenses()->get();
        $totalExpenses = 0;
        $paidByUser = [];

        foreach ($activeMembers as $userId) {
            $paidByUser[$userId] = 0;
        }

        foreach ($expenses as $expense) {
            $totalExpenses += (float) $expense->amount;
            if (isset($paidByUser[$expense->payer_id])) {
                $paidByUser[$expense->payer_id] += (float) $expense->amount;
            }
        }

        $memberCount = count($activeMembers);
        $fairShare = $memberCount > 0 ? $totalExpenses / $memberCount : 0;

        $balances = [];
        foreach ($activeMembers as $userId) {
            $paid = $paidByUser[$userId] ?? 0;
            $balances[$userId] = [
                'paid' => round($paid, 2),
                'share' => round($fairShare, 2),
                'balance' => round($paid - $fairShare, 2),
            ];
        }

        return $balances;
    }

    /**
     * Generate minimal settlement transfers using a greedy algorithm.
     *
     * @return array<int, array{from: int, to: int, amount: float}>
     */
    public function generateSettlements(Colocation $colocation): array
    {
        $balances = $this->calculateBalances($colocation);

        $debtors = [];  // negative balance → owes money
        $creditors = []; // positive balance → owed money

        foreach ($balances as $userId => $data) {
            if ($data['balance'] < -0.01) {
                $debtors[] = ['user_id' => $userId, 'amount' => abs($data['balance'])];
            } elseif ($data['balance'] > 0.01) {
                $creditors[] = ['user_id' => $userId, 'amount' => $data['balance']];
            }
        }

        // Sort descending by amount for greedy matching
        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $settlements = [];
        $di = 0;
        $ci = 0;

        while ($di < count($debtors) && $ci < count($creditors)) {
            $transfer = min($debtors[$di]['amount'], $creditors[$ci]['amount']);

            if ($transfer > 0.01) {
                $settlements[] = [
                    'from' => $debtors[$di]['user_id'],
                    'to' => $creditors[$ci]['user_id'],
                    'amount' => round($transfer, 2),
                ];
            }

            $debtors[$di]['amount'] -= $transfer;
            $creditors[$ci]['amount'] -= $transfer;

            if ($debtors[$di]['amount'] < 0.01) {
                $di++;
            }
            if ($creditors[$ci]['amount'] < 0.01) {
                $ci++;
            }
        }

        return $settlements;
    }
}
