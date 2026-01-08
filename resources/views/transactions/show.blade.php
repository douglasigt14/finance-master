@extends('layouts.app')

@section('title', 'Detalhes da Transação')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-arrow-left-right"></i> Detalhes da Transação</h2>
        <div>
            <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn btn-secondary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações da Transação</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Data:</th>
                        <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <th>Tipo:</th>
                        <td>
                            <span class="badge bg-{{ $transaction->type === 'INCOME' ? 'success' : 'danger' }}">
                                {{ $transaction->type === 'INCOME' ? 'ENTRADA' : 'SAÍDA' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Categoria:</th>
                        <td>
                            <span class="badge" style="background-color: {{ $transaction->category->color ?? '#6c757d' }}">
                                {{ $transaction->category->name }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Valor:</th>
                        <td class="{{ $transaction->type === 'INCOME' ? 'text-success' : 'text-danger' }}">
                            <strong>{{ $transaction->type === 'INCOME' ? '+' : '-' }}R$ {{ number_format($transaction->amount, 2, ',', '.') }}</strong>
                        </td>
                    </tr>
                    @if($transaction->payment_method)
                    <tr>
                        <th>Forma de Pagamento:</th>
                        <td><span class="badge bg-info">
                            @if($transaction->payment_method === 'CASH') Dinheiro
                            @elseif($transaction->payment_method === 'PIX') PIX
                            @elseif($transaction->payment_method === 'DEBIT') Débito
                            @elseif($transaction->payment_method === 'CREDIT') Crédito
                            @else {{ $transaction->payment_method }}
                            @endif
                        </span></td>
                    </tr>
                    @endif
                    @if($transaction->card)
                    <tr>
                        <th>Cartão:</th>
                        <td>{{ $transaction->card->name }}</td>
                    </tr>
                    @endif
                    @if($transaction->installments_total > 1)
                    <tr>
                        <th>Parcelas:</th>
                        <td>
                            <span class="badge bg-secondary">
                                {{ $transaction->installment_number }}/{{ $transaction->installments_total }}
                            </span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th>Status:</th>
                        <td>
                            @if($transaction->is_paid)
                                <span class="badge bg-success">Pago</span>
                            @else
                                <span class="badge bg-secondary">Não Pago</span>
                            @endif
                        </td>
                    </tr>
                    @if($transaction->description)
                    <tr>
                        <th>Descrição:</th>
                        <td>{{ $transaction->description }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @if($installmentGroup && $installmentGroup->count() > 1)
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Grupo de Parcelas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($installmentGroup as $installment)
                                <tr class="{{ $installment->id === $transaction->id ? 'table-primary' : '' }}">
                                    <td>{{ $installment->installment_number }}/{{ $installment->installments_total }}</td>
                                    <td>{{ $installment->transaction_date->format('d/m/Y') }}</td>
                                    <td>R$ {{ number_format($installment->amount, 2, ',', '.') }}</td>
                                    <td>
                                        @if($installment->is_paid)
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
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="btn-group">
                    @if(!$transaction->is_paid)
                        <form action="{{ route('transactions.mark-paid', $transaction->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Marcar como Pago
                            </button>
                        </form>
                    @else
                        <form action="{{ route('transactions.mark-unpaid', $transaction->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-x-circle"></i> Marcar como Não Pago
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
