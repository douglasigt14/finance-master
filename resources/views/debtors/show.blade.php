@extends('layouts.app')

@section('title', 'Detalhes do Devedor')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-person-badge"></i> {{ $debtor->name }}</h2>
        <div>
            <a href="{{ route('debtors.edit', $debtor->id) }}" class="btn btn-secondary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('debtors.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações do Devedor</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Nome:</th>
                        <td>{{ $debtor->name }}</td>
                    </tr>
                    <tr>
                        <th>Total de Transações:</th>
                        <td>{{ $debtor->transactions->count() }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
