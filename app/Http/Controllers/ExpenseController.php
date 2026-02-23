<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    /**
     * Store a new expense for a colocation.
     */
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        Gate::authorize('create', [Expense::class, $colocation]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        // Verify category belongs to this colocation if provided
        if (!empty($validated['category_id'])) {
            $categoryBelongs = $colocation->categories()->where('id', $validated['category_id'])->exists();
            if (!$categoryBelongs) {
                return redirect()->route('colocations.show', $colocation)
                    ->with('error', 'Cette catégorie n\'appartient pas à cette colocation.');
            }
        }

        $colocation->expenses()->create([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'category_id' => $validated['category_id'] ?? null,
            'payer_id' => $request->user()->id,
        ]);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Dépense ajoutée.');
    }

    /**
     * Update an existing expense.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $expense);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        // Verify category belongs to this colocation if provided
        if (!empty($validated['category_id'])) {
            $categoryBelongs = $expense->colocation->categories()->where('id', $validated['category_id'])->exists();
            if (!$categoryBelongs) {
                return redirect()->route('colocations.show', $expense->colocation)
                    ->with('error', 'Cette catégorie n\'appartient pas à cette colocation.');
            }
        }

        $expense->update([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return redirect()->route('colocations.show', $expense->colocation)
            ->with('success', 'Dépense mise à jour.');
    }

    /**
     * Delete an expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        $colocation = $expense->colocation;
        $expense->delete();

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Dépense supprimée.');
    }
}
