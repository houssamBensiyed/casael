<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $colocation->name }}
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

            <p>{{ $colocation->description }}</p>
            <p>Statut: {{ $colocation->status }}</p>
            <p>Propriétaire: {{ $colocation->owner->name }}</p>

            <h3 class="mt-6 font-semibold">Membres actifs</h3>
            @foreach($colocation->activeMembers as $member)
                <div class="mt-2">{{ $member->name }} ({{ $member->pivot->role }})</div>
            @endforeach
        </div>
    </div>
</x-app-layout>
