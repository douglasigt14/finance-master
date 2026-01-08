@extends('layouts.app')

@section('title', 'Faturas')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-receipt"></i> Faturas de Cartão de Crédito</h2>
    </div>
</div>

<!-- Card Selector -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('invoices.index') }}" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Selecione o Cartão</label>
                <select name="card_id" class="form-select" onchange="this.form.submit()">
                    @foreach($cards as $card)
                        <option value="{{ $card->id }}" {{ $selectedCard->id == $card->id ? 'selected' : '' }}>
                            {{ $card->name }} - Limite: R$ {{ number_format($card->credit_limit, 2, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Current Invoice Summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Limite de Crédito</h5>
                <h3 class="mb-0">R$ {{ number_format($selectedCard->credit_limit, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">Usado</h5>
                <h3 class="mb-0">R$ {{ number_format($currentInvoice->total_amount, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white {{ $availableCredit > 0 ? 'bg-success' : 'bg-warning' }}">
            <div class="card-body">
                <h5 class="card-title">Disponível</h5>
                <h3 class="mb-0">R$ {{ number_format($availableCredit, 2, ',', '.') }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Current Invoice Details -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Fatura Atual ({{ $currentInvoice->cycle_month }}/{{ $currentInvoice->cycle_year }})</h5>
        <div>
            <a href="{{ route('invoices.show', [$selectedCard->id, $currentInvoice->cycle_month, $currentInvoice->cycle_year]) }}" 
               class="btn btn-sm btn-primary">
                <i class="bi bi-eye"></i> Ver Detalhes
            </a>
            @if(!$currentInvoice->is_paid)
                <form action="{{ route('invoices.mark-paid', [$selectedCard->id, $currentInvoice->cycle_month, $currentInvoice->cycle_year]) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-check-circle"></i> Marcar como Paga
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Data de Fechamento:</strong><br>
                {{ \Carbon\Carbon::parse($currentInvoice->closing_date)->format('d/m/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Data de Vencimento:</strong><br>
                {{ \Carbon\Carbon::parse($currentInvoice->due_date)->format('d/m/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Valor Total:</strong><br>
                <span class="h5">R$ {{ number_format($currentInvoice->total_amount, 2, ',', '.') }}</span>
            </div>
            <div class="col-md-3">
                <strong>Status:</strong><br>
                @if($currentInvoice->is_paid)
                    <span class="badge bg-success">Paga</span>
                @else
                    <span class="badge bg-warning">Não Paga</span>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Invoice History -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Histórico de Faturas</h5>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="showPaidInvoices" onchange="togglePaidInvoices()">
            <label class="form-check-label" for="showPaidInvoices">
                Mostrar faturas pagas
            </label>
        </div>
    </div>
    <div class="card-body">
        @if($invoices->isEmpty())
            <p class="text-muted">Nenhuma fatura encontrada.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ciclo</th>
                            <th>Data de Fechamento</th>
                            <th>Data de Vencimento</th>
                            <th>Valor Total</th>
                            <th>Valor Pago</th>
                            <th>Status</th>
                            <th colspan="2" style="text-align: center;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                            @php
                                $closingDate = \Carbon\Carbon::parse($invoice->closing_date);
                                $isFuture = $closingDate->isFuture();
                            @endphp
                            <tr class="{{ $isFuture ? 'table-info' : '' }} {{ $invoice->is_paid ? 'invoice-paid' : '' }}" 
                                style="{{ $invoice->is_paid ? 'display: none;' : '' }}">
                                <td>
                                    {{ $invoice->cycle_month }}/{{ $invoice->cycle_year }}
                                    @if($isFuture)
                                        <span class="badge bg-info ms-1">Futura</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($invoice->closing_date)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td>
                                <td>R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</td>
                                <td>R$ {{ number_format($invoice->paid_amount, 2, ',', '.') }}</td>
                                <td>
                                    @if($invoice->is_paid)
                                        <span class="badge bg-success">Paga</span>
                                    @else
                                        <span class="badge bg-warning">Não Paga</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('invoices.show', [$selectedCard->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                </td>
                                <td>
                                    @if(!$invoice->is_paid)
                                        <form action="{{ route('invoices.mark-paid', [$selectedCard->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" 
                                                    >
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('invoices.mark-unpaid', [$selectedCard->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                    >
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
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
@endsection

@push('scripts')
<script>
function togglePaidInvoices() {
    const checkbox = document.getElementById('showPaidInvoices');
    const paidInvoices = document.querySelectorAll('.invoice-paid');
    
    paidInvoices.forEach(function(row) {
        if (checkbox.checked) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Initialize: hide paid invoices by default
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('showPaidInvoices');
    if (checkbox && !checkbox.checked) {
        togglePaidInvoices();
    }
});
</script>
@endpush
