@extends('layouts.app')

@section('title', 'Detalhes do Cartão')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-credit-card"></i> {{ $card->name }}</h2>
        <div>
            <a href="{{ route('cards.edit', $card->id) }}" class="btn btn-secondary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('cards.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações do Cartão</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Nome:</th>
                        <td>{{ $card->name }}</td>
                    </tr>
                    <tr>
                        <th>Bandeira:</th>
                        <td>{{ $card->brand ?? 'N/A' }}</td>
                    </tr>
                    @if($card->last_four)
                    <tr>
                        <th>Últimos 4 Dígitos:</th>
                        <td>**** {{ $card->last_four }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Limite de Crédito:</th>
                        <td>R$ {{ number_format($card->credit_limit, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Dia de Fechamento:</th>
                        <td>{{ $card->closing_day }}</td>
                    </tr>
                    <tr>
                        <th>Dia de Vencimento:</th>
                        <td>{{ $card->due_day }}</td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge bg-{{ $card->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $card->status === 'active' ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ações Rápidas</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('invoices.index', ['card_id' => $card->id]) }}" class="btn btn-primary">
                        <i class="bi bi-receipt"></i> Ver Faturas
                    </a>
                    <a href="{{ route('transactions.create') }}?card_id={{ $card->id }}" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle"></i> Nova Transação
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
