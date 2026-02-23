<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Règlements — {{ $colocation->name }}
            </h2>
            @if(auth()->user()->isOwnerOf($colocation) && $colocation->status === 'active')
                <form method="POST" action="{{ route('settlements.generate', $colocation) }}">
                    @csrf
                    <x-primary-button onclick="return confirm('Régénérer les règlements ? Les anciens non payés seront remplacés.')">
                        Générer les règlements
                    </x-primary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Balances --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Balances</h3>

                    @if(empty($balances))
                        <p class="text-sm text-gray-400">Aucune donnée.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($balances as $userId => $data)
                                @php $member = $colocation->activeMembers->firstWhere('id', $userId); @endphp
                                <div class="p-4 rounded-lg border {{ $data['balance'] >= 0 ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                                    <p class="font-medium text-gray-800">{{ $member?->name ?? 'Utilisateur #'.$userId }}</p>
                                    <div class="mt-2 text-sm space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Payé :</span>
                                            <span class="font-medium">{{ number_format($data['paid'], 2) }}€</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Part :</span>
                                            <span>{{ number_format($data['share'], 2) }}€</span>
                                        </div>
                                        <div class="flex justify-between border-t pt-1">
                                            <span class="text-gray-500">Solde :</span>
                                            <span class="font-bold {{ $data['balance'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                                {{ $data['balance'] >= 0 ? '+' : '' }}{{ number_format($data['balance'], 2) }}€
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Unpaid Settlements --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Règlements en attente</h3>

                    @forelse($settlements as $settlement)
                        <div class="flex items-center justify-between p-4 border rounded-lg mb-3 bg-amber-50 border-amber-200">
                            <div>
                                <span class="font-medium text-gray-800">{{ $settlement->fromUser->name }}</span>
                                <span class="text-gray-500">doit</span>
                                <span class="font-bold text-amber-700">{{ number_format($settlement->amount, 2) }}€</span>
                                <span class="text-gray-500">à</span>
                                <span class="font-medium text-gray-800">{{ $settlement->toUser->name }}</span>
                            </div>
                            @if(auth()->id() === $settlement->to_user_id || auth()->user()->isOwnerOf($colocation))
                                <form method="POST" action="{{ route('settlements.markPaid', $settlement) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-md hover:bg-green-700 transition">
                                        ✓ Marquer payé
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Aucun règlement en attente.</p>
                    @endforelse
                </div>
            </div>

            {{-- Paid Settlements --}}
            @if($paidSettlements->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Règlements payés</h3>

                        @foreach($paidSettlements as $settlement)
                            <div class="flex items-center justify-between p-3 border rounded-lg mb-2 bg-gray-50 border-gray-200">
                                <div class="text-gray-400">
                                    <span>{{ $settlement->fromUser->name }}</span>
                                    <span>→</span>
                                    <span>{{ number_format($settlement->amount, 2) }}€</span>
                                    <span>→</span>
                                    <span>{{ $settlement->toUser->name }}</span>
                                </div>
                                <span class="text-xs text-green-600 font-medium">✓ Payé</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="text-center">
                <a href="{{ route('colocations.show', $colocation) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    ← Retour à la colocation
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
