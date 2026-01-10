@extends('layouts.app')

@section('title', 'Transações Recorrentes')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-arrow-repeat"></i> Transações Recorrentes</h2>
        <div>
            <form action="{{ route('recurring-transactions.generate') }}" method="POST" class="d-inline me-2">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-play-circle"></i> Gerar Transações Agora
                </button>
            </form>
            <a href="{{ route('recurring-transactions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nova Transação Recorrente
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('recurring-transactions.index') }}" class="row g-3">
            <div class="col-md-10">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Buscar por descrição, categoria, cartão..." 
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
            @if(request('search'))
                <div class="col-12">
                    <a href="{{ route('recurring-transactions.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpar busca
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($recurringTransactions->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">Nenhuma transação recorrente cadastrada.</p>
                <a href="{{ route('recurring-transactions.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Criar Primeira Transação Recorrente
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Frequência</th>
                            <th>Próxima Execução</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recurringTransactions as $recurring)
                            <tr>
                                <td>
                                    <strong>{{ $recurring->description ?: $recurring->card_description ?: 'Sem descrição' }}</strong>
                                    @if($recurring->card)
                                        <br><small class="text-muted"><i class="bi bi-credit-card"></i> {{ $recurring->card->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge" style="background-color: {{ $recurring->category->color ?? '#0d6efd' }};">
                                        {{ $recurring->category->name }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-{{ $recurring->type === 'INCOME' ? 'success' : 'danger' }}">
                                        {{ $recurring->type === 'INCOME' ? '+' : '-' }} R$ {{ number_format($recurring->amount, 2, ',', '.') }}
                                    </strong>
                                </td>
                                <td>
                                    @if($recurring->frequency === 'MONTHLY')
                                        Mensal (dia {{ $recurring->day_of_month }})
                                    @elseif($recurring->frequency === 'WEEKLY')
                                        Semanal
                                    @else
                                        Anual
                                    @endif
                                </td>
                                <td>
                                    {{ $recurring->next_execution_date->format('d/m/Y') }}
                                </td>
                                <td>
                                    @if($recurring->is_active)
                                        <span class="badge bg-success">Ativa</span>
                                    @else
                                        <span class="badge bg-secondary">Inativa</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('recurring-transactions.edit', $recurring->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('recurring-transactions.destroy', $recurring->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta transação recorrente?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
