<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Mes Colocations
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
            @endif

            <a href="{{ route('colocations.create') }}">Créer une colocation</a>

            @forelse($colocations as $colocation)
                <div class="mt-4 p-4 bg-white rounded shadow">
                    <a href="{{ route('colocations.show', $colocation) }}">{{ $colocation->name }}</a>
                    <span>({{ $colocation->status }})</span>
                </div>
            @empty
                <p class="mt-4">Aucune colocation.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
