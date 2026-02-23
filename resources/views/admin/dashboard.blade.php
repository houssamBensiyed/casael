<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Administration
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="p-4 bg-green-100 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 text-red-700 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateurs</p>
                    <p class="mt-2 text-3xl font-bold text-gray-800">{{ $stats['total_users'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bannis</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ $stats['banned_users'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Colocations actives</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $stats['active_colocations'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total dépenses</p>
                    <p class="mt-2 text-3xl font-bold text-gray-800">{{ number_format($stats['total_expenses_amount'], 2) }}€</p>
                </div>
            </div>

            {{-- Users Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Utilisateurs ({{ $stats['total_users'] }})</h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-gray-500">
                                    <th class="pb-3 font-medium">Nom</th>
                                    <th class="pb-3 font-medium">Email</th>
                                    <th class="pb-3 font-medium">Rôle</th>
                                    <th class="pb-3 font-medium">Réputation</th>
                                    <th class="pb-3 font-medium">Statut</th>
                                    <th class="pb-3 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($users as $user)
                                    <tr>
                                        <td class="py-3 font-medium text-gray-800">{{ $user->name }}</td>
                                        <td class="py-3 text-gray-600">{{ $user->email }}</td>
                                        <td class="py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $user->role }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <span class="{{ $user->reputation >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                                {{ $user->reputation >= 0 ? '+' : '' }}{{ $user->reputation }}
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            @if($user->is_banned)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Banni</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Actif</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            @if(!$user->isAdmin())
                                                @if($user->is_banned)
                                                    <form method="POST" action="{{ route('admin.unban', $user) }}" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">Débannir</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.ban', $user) }}" class="inline"
                                                          onsubmit="return confirm('Bannir {{ $user->name }} ?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Bannir</button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
