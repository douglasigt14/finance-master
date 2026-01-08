@extends('layouts.app')

@section('title', 'Cartões de Crédito')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-credit-card"></i> Cartões de Crédito</h2>
        <a href="{{ route('cards.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Cartão
        </a>
    </div>
</div>

@if($cards->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Nenhum cartão de crédito cadastrado. 
        <a href="{{ route('cards.create') }}">Crie seu primeiro cartão</a>
    </div>
@else
    <div class="row">
        @foreach($cards as $card)
            <div class="col-md-3 mb-4">
                <div class="card h-100" style="border-top: 4px solid {{ $card->color ?? '#0d6efd' }};">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: {{ $card->color ?? '#0d6efd' }}20;">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card-2-front"></i> {{ $card->name }}
                        </h5>
                        <span class="badge bg-{{ $card->status === 'active' ? 'success' : 'secondary' }}">
                            {{ $card->status === 'active' ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Bandeira:</strong> {{ $card->brand ?? 'N/A' }}
                        </p>
                        @if($card->last_four)
                            <p class="mb-2">
                                <strong>Últimos 4 dígitos:</strong> **** {{ $card->last_four }}
                            </p>
                        @endif
                        <p class="mb-2">
                            <strong>Limite:</strong> R$ {{ number_format($card->credit_limit, 2, ',', '.') }}
                        </p>
                        <p class="mb-2">
                            <strong>Dia de Fechamento:</strong> {{ $card->closing_day }}
                        </p>
                        <p class="mb-2">
                            <strong>Dia de Vencimento:</strong> {{ $card->due_day }}
                        </p>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100" role="group">
                            <a href="{{ route('invoices.index', ['card_id' => $card->id]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                            <a href="{{ route('cards.edit', $card->id) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <form action="{{ route('cards.destroy', $card->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Tem certeza que deseja excluir este cartão?')">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
