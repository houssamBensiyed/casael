<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Colocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    /**
     * Store a new category for a colocation.
     */
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        Gate::authorize('create', [Category::class, $colocation]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $colocation->categories()->create($validated);

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Catégorie ajoutée.');
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update($validated);

        return redirect()->route('colocations.show', $category->colocation)
            ->with('success', 'Catégorie mise à jour.');
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $colocation = $category->colocation;
        $category->delete();

        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Catégorie supprimée.');
    }
}
