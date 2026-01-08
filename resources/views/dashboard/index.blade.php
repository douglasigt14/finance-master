@extends('layouts.app')

@section('title', 'Painel')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-speedometer2"></i> Painel</h2>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-arrow-down-circle"></i> Total de Entradas</h5>
                <h3 class="mb-0">R$ {{ number_format($totalIncome, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-arrow-up-circle"></i> Total de Saídas</h5>
                <h3 class="mb-0">R$ {{ number_format($totalExpense, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card text-white {{ $balance >= 0 ? 'bg-primary' : 'bg-warning' }}">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-cash-stack"></i> Saldo</h5>
                <h3 class="mb-0">R$ {{ number_format($balance, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Expenses by Category -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Gastos por Categoria</h5>
            </div>
            <div class="card-body">
                @if($expensesByCategory->isEmpty())
                    <p class="text-muted">Nenhum gasto este mês.</p>
                @else
                    <div class="list-group">
                        @foreach($expensesByCategory as $item)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge rounded-pill" style="background-color: {{ $item['color'] ?? '#6c757d' }}">
                                        {{ $item['category'] }}
                                    </span>
                                </div>
                                <strong>R$ {{ number_format($item['total'], 2, ',', '.') }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Cards Summary -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-credit-card"></i> Resumo dos Cartões</h5>
            </div>
            <div class="card-body">
                @if($cardsSummary->isEmpty())
                    <p class="text-muted">Nenhum cartão de crédito cadastrado.</p>
                    <a href="{{ route('cards.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle"></i> Adicionar Cartão
                    </a>
                @else
                    @foreach($cardsSummary as $card)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <strong>{{ $card['name'] }}</strong>
                                <span class="text-muted">{{ number_format($card['percentage'], 1) }}% usado</span>
                            </div>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar {{ $card['percentage'] > 80 ? 'bg-danger' : ($card['percentage'] > 50 ? 'bg-warning' : 'bg-success') }}" 
                                     role="progressbar" 
                                     style="width: {{ min($card['percentage'], 100) }}%">
                                    {{ number_format($card['percentage'], 1) }}%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Usado: R$ {{ number_format($card['used'], 2, ',', '.') }}</span>
                                <span>Disponível: R$ {{ number_format($card['available'], 2, ',', '.') }}</span>
                                <span>Limite: R$ {{ number_format($card['credit_limit'], 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Transações Recentes</h5>
                <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle"></i> Nova Transação
                </a>
            </div>
            <div class="card-body">
                @if($recentTransactions->isEmpty())
                    <p class="text-muted">Nenhuma transação ainda.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Tipo</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTransactions as $transaction)
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
                                        <td class="{{ $transaction->type === 'INCOME' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === 'INCOME' ? '+' : '-' }}R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                        </td>
                                        <td>
                                            @if($transaction->is_paid)
                                                <span class="badge bg-success">Pago</span>
                                            @else
                                                <span class="badge bg-secondary">Não Pago</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
