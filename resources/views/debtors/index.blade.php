@extends('layouts.app')

@section('title', 'Devedores')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-person-badge"></i> Devedores</h2>
        <a href="{{ route('debtors.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Devedor
        </a>
    </div>
</div>

@if(isset($cycleInfo) && $cycleInfo->isNotEmpty())
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i> 
        <strong>Próximo Ciclo:</strong>
        @foreach($cycleInfo as $info)
            <span class="me-3">
                <strong>{{ $info['card'] }}:</strong> {{ $info['start'] }} até {{ $info['end'] }}
            </span>
        @endforeach
    </div>
@endif

@if($debtors->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Nenhum devedor cadastrado. 
        <a href="{{ route('debtors.create') }}">Crie seu primeiro devedor</a>
    </div>
@else
    @php
        // Get all debtors including those without transactions and "Meu" (transactions without debtor)
        $allDebtorsWithTransactions = collect();
        
        // Add ALL debtors (with or without transactions)
        foreach ($debtors as $debtor) {
            $debtorTransactions = $transactionsByDebtor->get($debtor->id, collect());
            $allDebtorsWithTransactions->push([
                'id' => $debtor->id,
                'name' => $debtor->name,
                'transactions' => $debtorTransactions
            ]);
        }
        
        // Add "Meu" (transactions without debtor) if it has transactions
        $meuTransactions = $transactionsByDebtor->get('sem_devedor', collect());
        if ($meuTransactions->isNotEmpty()) {
            $allDebtorsWithTransactions->push([
                'id' => null,
                'name' => 'Meu',
                'transactions' => $meuTransactions
            ]);
        }
    @endphp

    @if($allDebtorsWithTransactions->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Nenhum devedor cadastrado.
        </div>
    @else
        @foreach($allDebtorsWithTransactions as $debtorData)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person"></i> {{ $debtorData['name'] }}
                        @if($debtorData['transactions']->isNotEmpty())
                            <small class="text-muted">({{ $debtorData['transactions']->count() }})</small>
                        @endif
                    </h5>
                    @if($debtorData['id'] !== null)
                        <div class="btn-group">
                            <a href="{{ route('debtors.edit', $debtorData['id']) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('debtors.destroy', $debtorData['id']) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Tem certeza?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @if($debtorData['transactions']->isEmpty())
                        <p class="text-muted mb-0">Nenhuma transação no ciclo atual.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Nome na Fatura</th>
                                        <th>Descrição</th>
                                        <th>Categoria</th>
                                        <th>Cartão</th>
                                        <th>Valor</th>
                                        <th>Parcelas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($debtorData['transactions'] as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                        <td>{{ $transaction->card_description ?? '-' }}</td>
                                        <td>{{ $transaction->description ?? '-' }}</td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $transaction->category->color ?? '#6c757d' }}">
                                                {{ $transaction->category->name }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $transaction->card->color ?? '#0d6efd' }}20; color: {{ $transaction->card->color ?? '#0d6efd' }}">
                                                {{ $transaction->card->name }}
                                            </span>
                                        </td>
                                        <td>R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($transaction->installments_total > 1)
                                                <small class="fst-italic">{{ $transaction->installment_number }}/{{ $transaction->installments_total }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->group_uuid)
                                                <div class="btn-group">
                                                    <a href="{{ route('transactions.edit-group', $transaction->id) }}" class="btn btn-sm btn-outline-info" title="Editar Grupo">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a> 
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total:</th>
                                    <th>R$ {{ number_format($debtorData['transactions']->sum('amount'), 2, ',', '.') }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
@endif
@endsection
