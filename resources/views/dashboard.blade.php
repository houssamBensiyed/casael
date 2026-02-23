<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tableau de bord
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Active Colocation --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Ma colocation</h3>

                    @if($activeColocation)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xl font-bold text-indigo-600">{{ $activeColocation->name }}</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $activeColocation->activeMembers->count() }} membre(s) actif(s)
                                    · Propriétaire : {{ $activeColocation->owner->name }}
                                </p>
                                @if($activeColocation->description)
                                    <p class="text-gray-600 mt-2">{{ $activeColocation->description }}</p>
                                @endif
                            </div>
                            <a href="{{ route('colocations.show', $activeColocation) }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                Voir
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-gray-500 mb-4">Vous n'avez pas de colocation active.</p>
                            <a href="{{ route('colocations.create') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                Créer une colocation
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Réputation</h3>
                        <p class="mt-2 text-3xl font-bold {{ $reputation >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $reputation >= 0 ? '+' : '' }}{{ $reputation }}
                        </p>
                        <p class="text-sm text-gray-400 mt-1">Basée sur vos départs et participations</p>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Invitations en attente</h3>
                        <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $pendingInvitations->count() }}</p>

                        @foreach($pendingInvitations as $invitation)
                            <div class="mt-3 flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                                <span class="text-sm font-medium text-indigo-800">{{ $invitation->colocation->name }}</span>
                                <div class="flex gap-2">
                                    <a href="{{ route('invitations.accept', $invitation->token) }}"
                                       class="text-xs px-3 py-1 bg-green-500 text-white rounded-md hover:bg-green-600 transition">
                                        Accepter
                                    </a>
                                    <a href="{{ route('invitations.refuse', $invitation->token) }}"
                                       class="text-xs px-3 py-1 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
                                        Refuser
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
