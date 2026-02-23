<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Administration
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

            <h3 class="font-semibold text-lg mb-4">Statistiques globales</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="p-4 bg-white rounded shadow">
                    <p class="text-sm text-gray-500">Utilisateurs</p>
                    <p class="text-2xl font-bold">{{ $stats['total_users'] }}</p>
                </div>
                <div class="p-4 bg-white rounded shadow">
                    <p class="text-sm text-gray-500">Bannis</p>
                    <p class="text-2xl font-bold text-red-600">{{ $stats['banned_users'] }}</p>
                </div>
                <div class="p-4 bg-white rounded shadow">
                    <p class="text-sm text-gray-500">Colocations actives</p>
                    <p class="text-2xl font-bold">{{ $stats['active_colocations'] }}</p>
                </div>
                <div class="p-4 bg-white rounded shadow">
                    <p class="text-sm text-gray-500">Total dépenses</p>
                    <p class="text-2xl font-bold">{{ number_format($stats['total_expenses_amount'], 2) }}€</p>
                </div>
            </div>

            <h3 class="font-semibold text-lg mb-4">Utilisateurs</h3>
            <table class="w-full bg-white rounded shadow">
                <thead>
                    <tr class="border-b">
                        <th class="p-3 text-left">Nom</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Rôle</th>
                        <th class="p-3 text-left">Réputation</th>
                        <th class="p-3 text-left">Statut</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr class="border-b">
                            <td class="p-3">{{ $user->name }}</td>
                            <td class="p-3">{{ $user->email }}</td>
                            <td class="p-3">{{ $user->role }}</td>
                            <td class="p-3">{{ $user->reputation }}</td>
                            <td class="p-3">
                                @if($user->is_banned)
                                    <span class="text-red-600 font-semibold">Banni</span>
                                @else
                                    <span class="text-green-600">Actif</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if(!$user->isAdmin())
                                    @if($user->is_banned)
                                        <form method="POST" action="{{ route('admin.unban', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-green-600 hover:underline">Débannir</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.ban', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-red-600 hover:underline">Bannir</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
