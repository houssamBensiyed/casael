<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Mes colocations
            </h2>
            @unless(auth()->user()->hasActiveColocation())
                <a href="{{ route('colocations.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    + Nouvelle colocation
                </a>
            @endunless
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">{{ session('error') }}</div>
            @endif

            @if($colocations->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <p class="text-gray-500 text-lg">Vous n'avez rejoint aucune colocation.</p>
                        <a href="{{ route('colocations.create') }}"
                           class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                            Créer ma première colocation
                        </a>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($colocations as $colocation)
                        <a href="{{ route('colocations.show', $colocation) }}"
                           class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition block">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-bold text-gray-800">{{ $colocation->name }}</h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $colocation->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $colocation->status === 'active' ? 'Active' : 'Annulée' }}
                                    </span>
                                </div>
                                @if($colocation->description)
                                    <p class="text-gray-500 text-sm mb-3">{{ Str::limit($colocation->description, 80) }}</p>
                                @endif
                                <div class="flex items-center justify-between text-sm text-gray-400">
                                    <span>{{ $colocation->activeMembers->count() }} membre(s)</span>
                                    <span>{{ $colocation->owner->name }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
