@extends('layouts.app')

@section('title', 'Faturas')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-receipt"></i> Faturas de Cartão de Crédito</h2>
        <div>
            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                <i class="bi bi-plus-circle"></i> Nova Transação
            </button>
            <a href="{{ route('cards.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
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

<!-- Modal Nova Transação -->
<div class="modal fade" id="newTransactionModal" tabindex="-1" aria-labelledby="newTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newTransactionModalLabel">
                    <i class="bi bi-plus-circle"></i> Nova Transação
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transactionModalForm" method="POST" action="{{ route('transactions.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_type" name="type" required>
                            <option value="">Selecione o tipo</option>
                            <option value="INCOME">Entrada</option>
                            <option value="EXPENSE" selected>Saída</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_category_id" name="category_id" required>
                            <option value="">Selecione a categoria</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" data-type="{{ $category->type }}">
                                    {{ $category->name }} ({{ $category->type }})
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3" id="modal_paymentMethodGroup" style="display: none;">
                        <label for="modal_payment_method" class="form-label">Forma de Pagamento <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_payment_method" name="payment_method">
                            <option value="">Selecione a forma de pagamento</option>
                            <option value="CASH">Dinheiro</option>
                            <option value="PIX">PIX</option>
                            <option value="DEBIT">Cartão de Débito</option>
                            <option value="CREDIT">Cartão de Crédito</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3" id="modal_cardGroup" style="display: none;">
                        <label for="modal_card_id" class="form-label">Cartão de Crédito <span class="text-danger">*</span></label>
                        <select class="form-select" id="modal_card_id" name="card_id">
                            <option value="">Selecione o cartão</option>
                            @foreach($allCards as $card)
                                <option value="{{ $card->id }}" {{ $selectedCard->id == $card->id ? 'selected' : '' }}>
                                    {{ $card->name }} (Limit: R$ {{ number_format($card->credit_limit, 2, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3" id="modal_installmentsGroup" style="display: none;">
                        <label for="modal_installments_total" class="form-label">Número de Parcelas <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="modal_installments_total" name="installments_total" value="1" min="1" max="24">
                        <small class="form-text text-muted">Número total de parcelas (1-24)</small>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3" id="modal_installmentsPreview" style="display: none;">
                        <div class="alert alert-info">
                            <strong>Prévia das Parcelas:</strong>
                            <div id="modal_previewContent"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_amount" class="form-label">Valor <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="modal_amount" name="amount" required>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_transaction_date" class="form-label">Data da Transação <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="modal_transaction_date" name="transaction_date" value="{{ date('Y-m-d') }}" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="modal_description" name="description" rows="3"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3" id="modal_cardDescriptionGroup" style="display: none;">
                        <label for="modal_card_description" class="form-label">Descrição no Cartão</label>
                        <input type="text" class="form-control" id="modal_card_description" name="card_description" placeholder="Ex: LOJA X JS">
                        <small class="form-text text-muted">Descrição exata como aparece no cartão de crédito</small>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="modal_debtor_id" class="form-label">Cobrar de (Devedor)</label>
                        <select class="form-select" id="modal_debtor_id" name="debtor_id">
                            <option value="">Nenhum</option>
                            @foreach($debtors as $debtor)
                                <option value="{{ $debtor->id }}">{{ $debtor->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Selecione se emprestou o cartão para alguém</small>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Criar Transação
                    </button>
                </div>
            </form>
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
    
    // Transaction Modal JavaScript
    const modalTypeSelect = document.getElementById('modal_type');
    const modalCategorySelect = document.getElementById('modal_category_id');
    const modalPaymentMethodGroup = document.getElementById('modal_paymentMethodGroup');
    const modalPaymentMethodSelect = document.getElementById('modal_payment_method');
    const modalCardGroup = document.getElementById('modal_cardGroup');
    const modalCardIdSelect = document.getElementById('modal_card_id');
    const modalCardDescriptionGroup = document.getElementById('modal_cardDescriptionGroup');
    const modalInstallmentsGroup = document.getElementById('modal_installmentsGroup');
    const modalInstallmentsPreview = document.getElementById('modal_installmentsPreview');
    const modalInstallmentsTotalInput = document.getElementById('modal_installments_total');
    const modalAmountInput = document.getElementById('modal_amount');
    const modalTransactionDateInput = document.getElementById('modal_transaction_date');
    const modalPreviewContent = document.getElementById('modal_previewContent');
    const transactionModalForm = document.getElementById('transactionModalForm');
    const newTransactionModal = new bootstrap.Modal(document.getElementById('newTransactionModal'));

    function updateModalFormVisibility() {
        const type = modalTypeSelect.value;
        const paymentMethod = modalPaymentMethodSelect.value;
        const isExpense = type === 'EXPENSE';
        const isCredit = paymentMethod === 'CREDIT';

        // Show payment method for expenses
        modalPaymentMethodGroup.style.display = isExpense ? 'block' : 'none';
        if (!isExpense) {
            modalPaymentMethodSelect.value = '';
        }

        // Show card, card description and installments for credit
        modalCardGroup.style.display = isCredit ? 'block' : 'none';
        modalCardDescriptionGroup.style.display = isCredit ? 'block' : 'none';
        modalInstallmentsGroup.style.display = isCredit ? 'block' : 'none';
        
        if (!isCredit) {
            modalInstallmentsTotalInput.value = 1;
            modalInstallmentsPreview.style.display = 'none';
        } else {
            updateModalInstallmentsPreview();
        }
    }

    function updateModalInstallmentsPreview() {
        const installments = parseInt(modalInstallmentsTotalInput.value) || 1;
        const amount = parseFloat(modalAmountInput.value) || 0;
        const date = modalTransactionDateInput.value;

        if (installments > 1 && amount > 0 && date) {
            const installmentAmount = amount / installments;
            
            // Parse date string (YYYY-MM-DD) to avoid timezone issues
            const [year, month, day] = date.split('-').map(Number);
            const startDate = new Date(year, month - 1, day);
            
            let preview = '<ul class="mb-0">';
            for (let i = 1; i <= installments; i++) {
                const installmentDate = new Date(startDate);
                installmentDate.setMonth(startDate.getMonth() + (i - 1));
                
                const formattedDate = installmentDate.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                preview += `<li>Parcela ${i}/${installments}: R$ ${installmentAmount.toFixed(2)} - ${formattedDate}</li>`;
            }
            preview += '</ul>';
            
            modalPreviewContent.innerHTML = preview;
            modalInstallmentsPreview.style.display = 'block';
        } else {
            modalInstallmentsPreview.style.display = 'none';
        }
    }

    if (modalTypeSelect) {
        modalTypeSelect.addEventListener('change', updateModalFormVisibility);
    }
    if (modalPaymentMethodSelect) {
        modalPaymentMethodSelect.addEventListener('change', updateModalFormVisibility);
    }
    if (modalInstallmentsTotalInput) {
        modalInstallmentsTotalInput.addEventListener('input', updateModalInstallmentsPreview);
    }
    if (modalAmountInput) {
        modalAmountInput.addEventListener('input', updateModalInstallmentsPreview);
    }
    if (modalTransactionDateInput) {
        modalTransactionDateInput.addEventListener('change', updateModalInstallmentsPreview);
    }

    // Initialize modal form visibility
    if (modalTypeSelect) {
        updateModalFormVisibility();
    }

    // Handle modal form submission
    if (transactionModalForm) {
        transactionModalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
            
            // Clear previous errors
            this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            this.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                return response.json().then(err => Promise.reject(err));
            })
            .then(data => {
                // Close modal
                newTransactionModal.hide();
                
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <strong>Sucesso!</strong> ${data.message || 'Transação criada com sucesso.'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.querySelector('.row.mb-4').insertAdjacentElement('afterend', alertDiv);
                
                // Reload page to update invoice details
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            })
            .catch(error => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
                
                if (error.errors) {
                    // Handle validation errors
                    Object.keys(error.errors).forEach(field => {
                        const input = this.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            const feedback = input.parentElement.querySelector('.invalid-feedback') || 
                                           input.closest('.mb-3').querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.textContent = error.errors[field][0];
                            }
                        }
                    });
                } else {
                    // Show general error
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <strong>Erro!</strong> ${error.message || 'Erro ao criar transação. Tente novamente.'}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('.modal-body').insertBefore(alertDiv, document.querySelector('.modal-body').firstChild);
                }
            });
        });
    }

    // Reset form when modal is closed
    document.getElementById('newTransactionModal').addEventListener('hidden.bs.modal', function() {
        transactionModalForm.reset();
        transactionModalForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        transactionModalForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        transactionModalForm.querySelectorAll('.alert').forEach(el => el.remove());
        modalTypeSelect.value = 'EXPENSE';
        modalCardIdSelect.value = '{{ $selectedCard->id }}';
        updateModalFormVisibility();
    });
});
</script>
@endpush
