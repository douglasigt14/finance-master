@extends('layouts.app')

@section('title', 'Detalhes da Fatura')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-receipt"></i> Fatura - {{ $card->name }} ({{ $invoice->cycle_month }}/{{ $invoice->cycle_year }})</h2>
        <div>
            <a href="{{ route('invoices.index', ['card_id' => $card->id]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<!-- Invoice Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h6 class="card-title">Valor Total</h6>
                <h3 class="mb-0">R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h6 class="card-title">Valor Pago</h6>
                <h3 class="mb-0">R$ {{ number_format($invoice->paid_amount, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h6 class="card-title">Restante</h6>
                <h3 class="mb-0">R$ {{ number_format($invoice->remaining_amount, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Status</h6>
                @if($invoice->is_paid)
                    <span class="badge bg-success fs-6">Paga</span>
                @else
                    <span class="badge bg-warning fs-6">Não Paga</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Invoice Info -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Informações da Fatura</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Ciclo:</strong><br>
                {{ $invoice->cycle_month }}/{{ $invoice->cycle_year }}
            </div>
            <div class="col-md-3">
                <strong>Período do Ciclo:</strong><br>
                {{ $cycleDates['start']->format('d/m/Y') }} até {{ $cycleDates['end']->format('d/m/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Data de Fechamento:</strong><br>
                {{ \Carbon\Carbon::parse($invoice->closing_date)->format('d/m/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Data de Vencimento:</strong><br>
                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
            </div>
        </div>
    </div>
</div>

<!-- Transactions -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Transações</h5>
        <div>
            @if(!$invoice->is_paid)
                <form action="{{ route('invoices.mark-paid', [$card->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-check-circle"></i> Marcar como Paga
                    </button>
                </form>
            @else
                <form action="{{ route('invoices.mark-unpaid', [$card->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="bi bi-x-circle"></i> Marcar como Não Paga
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($transactions->isEmpty())
            <p class="text-muted">Nenhuma transação neste ciclo.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Parcelas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td>{{ $transaction->description ?? '-' }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $transaction->category->color ?? '#6c757d' }}">
                                        {{ $transaction->category->name }}
                                    </span>
                                </td>
                                <td>R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td>
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
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th>R$ {{ number_format($transactions->sum('amount'), 2, ',', '.') }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
