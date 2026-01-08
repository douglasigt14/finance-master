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


<!-- Two Column Layout -->
<div class="row">

    <!-- Left Column: Invoice List (4/12) -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Faturas</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="showPaidInvoices" onchange="togglePaidInvoices()">
                    <label class="form-check-label" for="showPaidInvoices" style="font-size: 0.875rem;">
                        Mostrar pagas
                    </label>
                </div>
            </div>
            <div class="card-body p-0">
                @if($invoices->isEmpty())
                    <p class="text-muted p-3">Nenhuma fatura encontrada.</p>
                @else
                    <div class="list-group list-group-flush" style="max-height: 80vh; overflow-y: auto;">
                        @foreach($invoices as $invoice)
                            @php
                                $closingDate = \Carbon\Carbon::parse($invoice->closing_date);
                                $isFuture = $closingDate->isFuture();
                                $isSelected = $invoice->cycle_month == $selectedInvoice->cycle_month && 
                                            $invoice->cycle_year == $selectedInvoice->cycle_year;
                            @endphp
                            <div class="list-group-item invoice-item {{ $invoice->is_paid ? 'invoice-paid' : '' }} {{ $isSelected ? 'active' : '' }}" 
                                 style="{{ $invoice->is_paid ? 'display: none;' : '' }}"
                                 data-month="{{ $invoice->cycle_month }}" 
                                 data-year="{{ $invoice->cycle_year }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2">
                                                {{ $invoice->cycle_month }}/{{ $invoice->cycle_year }}
                                            </h6>
                                            @if($isFuture)
                                                <span class="badge bg-info">Futura</span>
                                            @endif
                                            @if($invoice->is_paid)
                                                <span class="badge bg-success ms-1">Paga</span>
                                            @else
                                                <span class="badge bg-warning ms-1">Não Paga</span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block">
                                            Fechamento: {{ \Carbon\Carbon::parse($invoice->closing_date)->format('d/m/Y') }}
                                        </small>
                                        <small class="text-muted d-block">
                                            Vencimento: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                                        </small>
                                        <div class="mt-2">
                                            <strong>R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                    <div class="btn-group-vertical ms-2">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary view-invoice-btn"
                                                data-month="{{ $invoice->cycle_month }}"
                                                data-year="{{ $invoice->cycle_year }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        @if(!$invoice->is_paid)
                                            <form action="{{ route('invoices.mark-paid', [$selectedCard->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                                                  method="POST" class="d-inline" onclick="event.stopPropagation()">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" 
                                                        title="Marcar como paga"
                                                        onclick="event.stopPropagation()">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('invoices.mark-unpaid', [$selectedCard->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                                                  method="POST" class="d-inline" onclick="event.stopPropagation()">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning" 
                                                        title="Marcar como não paga"
                                                        onclick="event.stopPropagation()">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

     <!-- Right Column: Invoice Details (8/12) -->
     <div class="col-md-8 h-100">
        <div id="invoiceDetails">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt"></i> Fatura - {{ $selectedCard->name }} ({{ $selectedInvoice->cycle_month }}/{{ $selectedInvoice->cycle_year }})
                    </h5>
                </div>
                <div class="card-body">
                    @include('invoices.partials.details', [
                        'card' => $selectedCard,
                        'invoice' => $selectedInvoice,
                        'transactions' => $transactions,
                        'cycleDates' => $cycleDates
                    ])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.invoice-item {
    cursor: pointer;
}
.invoice-item.active {
    background-color: #e7f3ff;
    border-left: 3px solid #0d6efd;
    color: #000 !important;
}
.invoice-item.active h6,
.invoice-item.active small,
.invoice-item.active strong {
    color: #000 !important;
}
.invoice-item:hover {
    background-color: #f8f9fa;
}
.invoice-item.active:hover {
    background-color: #d0e7ff;
}
.invoice-item .btn-group-vertical {
    pointer-events: auto;
}
.invoice-item .btn-group-vertical button,
.invoice-item .btn-group-vertical form {
    pointer-events: auto;
}
</style>
@endpush

@push('scripts')
<script>
function togglePaidInvoices() {
    const checkbox = document.getElementById('showPaidInvoices');
    const paidInvoices = document.querySelectorAll('.invoice-paid');
    
    paidInvoices.forEach(function(item) {
        if (checkbox.checked) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Load invoice details via AJAX
function loadInvoiceDetails(month, year) {
    const cardId = {{ $selectedCard->id }};
    const url = `/invoices/card/${cardId}/${month}/${year}`;
    
    // Show loading state
    const detailsContainer = document.getElementById('invoiceDetails');
    detailsContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
    
    // Update active state
    document.querySelectorAll('.invoice-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.month == month && item.dataset.year == year) {
            item.classList.add('active');
        }
    });
    
    // Fetch invoice details
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(html => {
        const cardName = '{{ $selectedCard->name }}';
        detailsContainer.innerHTML = '<div class="card mb-4"><div class="card-header"><h5 class="mb-0"><i class="bi bi-receipt"></i> Fatura - ' + cardName + ' (' + month + '/' + year + ')</h5></div><div class="card-body">' + html + '</div></div>';
    })
    .catch(error => {
        console.error('Error:', error);
        detailsContainer.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes da fatura. Tente recarregar a página.</div>';
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Hide paid invoices by default
    const checkbox = document.getElementById('showPaidInvoices');
    if (checkbox && !checkbox.checked) {
        togglePaidInvoices();
    }
    
    // Add click handlers to invoice items (entire item is clickable)
    document.querySelectorAll('.invoice-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking on action buttons
            if (e.target.closest('.btn-group-vertical') || 
                e.target.closest('button') || 
                e.target.closest('form')) {
                return;
            }
            
            const month = this.dataset.month;
            const year = this.dataset.year;
            loadInvoiceDetails(month, year);
        });
    });
    
    // Keep view button working too
    document.querySelectorAll('.view-invoice-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const month = this.dataset.month;
            const year = this.dataset.year;
            loadInvoiceDetails(month, year);
        });
    });
    
    // Handle form submissions for mark paid/unpaid - reload after success
    document.querySelectorAll('form[action*="mark-paid"], form[action*="mark-unpaid"]').forEach(form => {
        form.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering invoice item click
        });
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent triggering invoice item click
            const formData = new FormData(this);
            const action = this.action;
            
            fetch(action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(() => {
                // Reload page to update status
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback to normal form submission
                this.submit();
            });
        });
    });
});
</script>
@endpush
