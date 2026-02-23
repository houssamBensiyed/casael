<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Règlements — {{ $colocation->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
            @endif

            <h3 class="font-semibold text-lg mb-4">Balances</h3>
            @foreach($balances as $userId => $data)
                <div class="p-2 border-b">
                    Utilisateur #{{ $userId }} — Payé: {{ $data['paid'] }}€ | Part: {{ $data['share'] }}€ | Solde: {{ $data['balance'] }}€
                </div>
            @endforeach

            <h3 class="font-semibold text-lg mt-6 mb-4">Règlements en attente</h3>
            @forelse($settlements as $settlement)
                <div class="p-2 border-b flex justify-between items-center">
                    <span>{{ $settlement->fromUser->name }} doit {{ $settlement->amount }}€ à {{ $settlement->toUser->name }}</span>
                    <form method="POST" action="{{ route('settlements.markPaid', $settlement) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded text-sm">Marquer payé</button>
                    </form>
                </div>
            @empty
                <p class="text-gray-500">Aucun règlement en attente.</p>
            @endforelse

            @if($paidSettlements->isNotEmpty())
                <h3 class="font-semibold text-lg mt-6 mb-4">Règlements payés</h3>
                @foreach($paidSettlements as $settlement)
                    <div class="p-2 border-b text-gray-400">
                        {{ $settlement->fromUser->name }} a payé {{ $settlement->amount }}€ à {{ $settlement->toUser->name }} ✓
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
