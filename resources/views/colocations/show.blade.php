<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $colocation->name }}
                </h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1
                    {{ $colocation->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                    {{ $colocation->status === 'active' ? 'Active' : 'Annulée' }}
                </span>
            </div>
            @if(auth()->user()->isOwnerOf($colocation) && $colocation->status === 'active')
                <div class="flex gap-2">
                    <a href="{{ route('colocations.edit', $colocation) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                        Modifier
                    </a>
                    <form method="POST" action="{{ route('colocations.destroy', $colocation) }}"
                          onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette colocation ?')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">Annuler la colocation</x-danger-button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-700 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Description --}}
            @if($colocation->description)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-gray-600">{{ $colocation->description }}</p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- MEMBERS --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Membres ({{ $colocation->activeMembers->count() }})</h3>

                        <div class="space-y-3">
                            @foreach($colocation->activeMembers as $member)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="font-medium text-gray-800">{{ $member->name }}</span>
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $member->pivot->role === 'owner' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $member->pivot->role === 'owner' ? 'Propriétaire' : 'Membre' }}
                                        </span>
                                    </div>
                                    @if($colocation->status === 'active')
                                        @if(auth()->user()->isOwnerOf($colocation) && $member->id !== $colocation->owner_id)
                                            <form method="POST" action="{{ route('colocations.removeMember', [$colocation, $member]) }}"
                                                  onsubmit="return confirm('Retirer {{ $member->name }} ?')">
                                                @csrf
                                                <button type="submit" class="text-xs text-red-600 hover:text-red-800">Retirer</button>
                                            </form>
                                        @elseif(auth()->id() === $member->id && !auth()->user()->isOwnerOf($colocation))
                                            <form method="POST" action="{{ route('colocations.leave', $colocation) }}"
                                                  onsubmit="return confirm('Quitter cette colocation ?')">
                                                @csrf
                                                <button type="submit" class="text-xs text-orange-600 hover:text-orange-800">Quitter</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        {{-- Invite Form (Owner only) --}}
                        @if(auth()->user()->isOwnerOf($colocation) && $colocation->status === 'active')
                            <div class="mt-6 pt-4 border-t">
                                <h4 class="text-sm font-semibold text-gray-700 mb-2">Inviter un membre</h4>
                                <form method="POST" action="{{ route('invitations.store', $colocation) }}" class="flex gap-2">
                                    @csrf
                                    <x-text-input name="email" type="email" placeholder="email@exemple.com" class="flex-1 text-sm" required />
                                    <x-primary-button class="text-xs">Inviter</x-primary-button>
                                </form>
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>

                            {{-- Pending Invitations --}}
                            @php $pendingInvitations = $colocation->invitations()->where('status', 'pending')->get(); @endphp
                            @if($pendingInvitations->isNotEmpty())
                                <div class="mt-4">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Invitations en attente</h4>
                                    @foreach($pendingInvitations as $invitation)
                                        <div class="flex items-center justify-between py-1">
                                            <span class="text-sm text-gray-600">{{ $invitation->email }}</span>
                                            <form method="POST" action="{{ route('invitations.destroy', $invitation) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Annuler</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        {{-- Settlements Link --}}
                        @if($colocation->status === 'active')
                            <div class="mt-4 pt-4 border-t">
                                <a href="{{ route('settlements.index', $colocation) }}"
                                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                    Voir les règlements →
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- CATEGORIES & EXPENSES (Takes 2 columns) --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Categories --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Catégories</h3>

                            <div class="flex flex-wrap gap-2 mb-4">
                                @forelse($colocation->categories as $category)
                                    <div class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-50 text-indigo-800 rounded-full text-sm">
                                        {{ $category->name }}
                                        @if(auth()->user()->isOwnerOf($colocation))
                                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-1 text-indigo-400 hover:text-red-600">&times;</button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400">Aucune catégorie.</p>
                                @endforelse
                            </div>

                            @if($colocation->status === 'active' && $colocation->activeMembers->contains(auth()->user()))
                                <form method="POST" action="{{ route('categories.store', $colocation) }}" class="flex gap-2">
                                    @csrf
                                    <x-text-input name="name" type="text" placeholder="Nouvelle catégorie" class="text-sm" required />
                                    <x-primary-button class="text-xs">Ajouter</x-primary-button>
                                </form>
                                <x-input-error :messages="$errors->get('name')" class="mt-1" />
                            @endif
                        </div>
                    </div>

                    {{-- Expenses --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                Dépenses
                                @if($colocation->expenses->isNotEmpty())
                                    <span class="text-sm font-normal text-gray-400">
                                        — Total : {{ number_format($colocation->expenses->sum('amount'), 2) }}€
                                    </span>
                                @endif
                            </h3>

                            {{-- Expense Table --}}
                            @if($colocation->expenses->isNotEmpty())
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b text-left text-gray-500">
                                                <th class="pb-2 font-medium">Titre</th>
                                                <th class="pb-2 font-medium">Montant</th>
                                                <th class="pb-2 font-medium">Date</th>
                                                <th class="pb-2 font-medium">Catégorie</th>
                                                <th class="pb-2 font-medium">Payé par</th>
                                                <th class="pb-2 font-medium"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @foreach($colocation->expenses->sortByDesc('date') as $expense)
                                                <tr>
                                                    <td class="py-2 font-medium text-gray-800">{{ $expense->title }}</td>
                                                    <td class="py-2 text-gray-600">{{ number_format($expense->amount, 2) }}€</td>
                                                    <td class="py-2 text-gray-500">{{ \Carbon\Carbon::parse($expense->date)->format('d/m/Y') }}</td>
                                                    <td class="py-2 text-gray-500">{{ $expense->category?->name ?? '—' }}</td>
                                                    <td class="py-2 text-gray-500">{{ $expense->payer->name }}</td>
                                                    <td class="py-2">
                                                        @if(auth()->id() === $expense->payer_id || auth()->user()->isOwnerOf($colocation))
                                                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                                                  onsubmit="return confirm('Supprimer cette dépense ?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Supprimer</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-gray-400 text-sm">Aucune dépense enregistrée.</p>
                            @endif

                            {{-- Add Expense Form --}}
                            @if($colocation->status === 'active' && $colocation->activeMembers->contains(auth()->user()))
                                <div class="mt-6 pt-4 border-t">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Ajouter une dépense</h4>
                                    <form method="POST" action="{{ route('expenses.store', $colocation) }}">
                                        @csrf
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                            <div>
                                                <x-text-input name="title" type="text" placeholder="Titre" class="w-full text-sm" required />
                                                <x-input-error :messages="$errors->get('title')" class="mt-1" />
                                            </div>
                                            <div>
                                                <x-text-input name="amount" type="number" step="0.01" min="0.01" placeholder="Montant" class="w-full text-sm" required />
                                                <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                                            </div>
                                            <div>
                                                <x-text-input name="date" type="date" class="w-full text-sm" :value="date('Y-m-d')" required />
                                                <x-input-error :messages="$errors->get('date')" class="mt-1" />
                                            </div>
                                            <div>
                                                <select name="category_id" class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Sans catégorie</option>
                                                    @foreach($colocation->categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <x-primary-button class="text-xs">Ajouter la dépense</x-primary-button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
