<div class="p-6 max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold mb-4">Gerenciar Clientes</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 text-green-800 p-2 mb-4 rounded">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-2 mb-4">
        <input type="text" wire:model="nome" placeholder="Nome" class="w-full border p-2 rounded">
        <input type="email" wire:model="email" placeholder="Email" class="w-full border p-2 rounded">
        <input type="text" wire:model="telefone" placeholder="Telefone" class="w-full border p-2 rounded">
        <button class="bg-blue-500 text-white px-4 py-2 rounded">
            {{ $isEdit ? 'Atualizar' : 'Adicionar' }}
        </button>
        @if ($isEdit)
            <button type="button" wire:click="resetInput" class="bg-gray-500 text-white px-4 py-2 rounded">
                Cancelar
            </button>
        @endif
    </form>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2">ID</th>
                <th class="p-2">Nome</th>
                <th class="p-2">Email</th>
                <th class="p-2">Telefone</th>
                <th class="p-2">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clientes as $cliente)
                <tr class="border-b">
                    <td class="p-2">{{ $cliente->id }}</td>
                    <td class="p-2">{{ $cliente->nome }}</td>
                    <td class="p-2">{{ $cliente->email }}</td>
                    <td class="p-2">{{ $cliente->telefone }}</td>
                    <td class="p-2 space-x-2">
                        <button wire:click="edit({{ $cliente->id }})" class="bg-yellow-400 px-2 py-1 rounded">Editar</button>
                        <button wire:click="destroy({{ $cliente->id }})" class="bg-red-500 text-white px-2 py-1 rounded">Excluir</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
