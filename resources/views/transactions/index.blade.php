@extends('layouts.app')

@section('title', 'Transações')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-arrow-left-right"></i> Transações</h2>
        <a href="{{ route('transactions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nova Transação
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('transactions.index') }}" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select name="type" class="form-select">
                    <option value="">Todos</option>
                    <option value="INCOME" {{ request('type') === 'INCOME' ? 'selected' : '' }}>Entrada</option>
                    <option value="EXPENSE" {{ request('type') === 'EXPENSE' ? 'selected' : '' }}>Saída</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Categoria</label>
                <select name="category_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Cartão</label>
                <select name="card_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($cards as $card)
                        <option value="{{ $card->id }}" {{ request('card_id') == $card->id ? 'selected' : '' }}>
                            {{ $card->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Forma de Pagamento</label>
                <select name="payment_method" class="form-select">
                    <option value="">Todas</option>
                    <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>Dinheiro</option>
                    <option value="PIX" {{ request('payment_method') === 'PIX' ? 'selected' : '' }}>PIX</option>
                    <option value="DEBIT" {{ request('payment_method') === 'DEBIT' ? 'selected' : '' }}>Débito</option>
                    <option value="CREDIT" {{ request('payment_method') === 'CREDIT' ? 'selected' : '' }}>Crédito</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Data De</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Data Até</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpar
                </a>
            </div>
        </form>
    </div>
</div>

@if($transactions->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Nenhuma transação encontrada. 
        <a href="{{ route('transactions.create') }}">Crie sua primeira transação</a>
    </div>
@else
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Forma de Pagamento</th>
                            <th>Valor</th>
                            <th>Parcelas</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}</td>
                                <td>{{ $transaction->description ?? '-' }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $transaction->category->color ?? '#6c757d' }}">
                                        {{ $transaction->category->name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $transaction->type === 'INCOME' ? 'success' : 'danger' }}">
                                        {{ $transaction->type === 'INCOME' ? 'ENTRADA' : 'SAÍDA' }}
                                    </span>
                                </td>
                                <td>
                                    @if($transaction->payment_method)
                                        <span class="badge bg-info">
                                            @if($transaction->payment_method === 'CASH') Dinheiro
                                            @elseif($transaction->payment_method === 'PIX') PIX
                                            @elseif($transaction->payment_method === 'DEBIT') Débito
                                            @elseif($transaction->payment_method === 'CREDIT') Crédito
                                            @else {{ $transaction->payment_method }}
                                            @endif
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="{{ $transaction->type === 'INCOME' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->type === 'INCOME' ? '+' : '-' }}R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                </td>
                                <td>
                                    @if($transaction->installments_total > 1)
                                        <span class="badge bg-secondary">
                                            {{ $transaction->installment_number }}/{{ $transaction->installments_total }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->is_paid)
                                        <span class="badge bg-success">Pago</span>
                                    @else
                                        <span class="badge bg-secondary">Não Pago</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('transactions.show', $transaction->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if(!$transaction->is_paid)
                                            <form action="{{ route('transactions.mark-paid', $transaction->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como pago">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('transactions.mark-unpaid', $transaction->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Marcar como não pago">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection
