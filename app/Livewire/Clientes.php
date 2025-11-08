<?php

namespace App\Livewire;

use App\Models\Cliente;
use Livewire\Component;

class Clientes extends Component
{
    public $clientes;

    public $nome;

    public $email;

    public $telefone;

    public $cliente_id;

    public $isEdit = false;

    public function render()
    {
        $this->clientes = Cliente::all();

        return view('livewire.clientes');
    }

    public function resetInput()
    {
        $this->nome = $this->email = $this->telefone = '';
        $this->isEdit = false;
        $this->cliente_id = null;
    }

    public function store()
    {
        $this->validate([
            'nome' => 'required',
            'email' => 'required|email',
        ]);

        Cliente::create([
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
        ]);

        session()->flash('message', 'Cliente criado com sucesso!');
        $this->resetInput();
    }

    public function edit($id)
    {
        $cliente = Cliente::findOrFail($id);
        $this->cliente_id = $id;
        $this->nome = $cliente->nome;
        $this->email = $cliente->email;
        $this->telefone = $cliente->telefone;
        $this->isEdit = true;
    }

    public function update()
    {
        $this->validate([
            'nome' => 'required',
            'email' => 'required|email',
        ]);

        $cliente = Cliente::findOrFail($this->cliente_id);
        $cliente->update([
            'nome' => $this->nome,
            'email' => $this->email,
            'telefone' => $this->telefone,
        ]);

        session()->flash('message', 'Cliente atualizado com sucesso!');
        $this->resetInput();
    }

    public function destroy($id)
    {
        Cliente::findOrFail($id)->delete();
        session()->flash('message', 'Cliente deletado com sucesso!');
    }
}
