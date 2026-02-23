<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Créer une colocation
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('colocations.store') }}">
                @csrf
                <div class="mb-4">
                    <label for="name">Nom</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="border rounded p-2 w-full">
                    @error('name') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="border rounded p-2 w-full">{{ old('description') }}</textarea>
                    @error('description') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Créer</button>
            </form>
        </div>
    </div>
</x-app-layout>
