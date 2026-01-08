@extends('layouts.app')

@section('title', 'Criar Transação')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-plus-circle"></i> Criar Transação</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('transactions.store') }}" method="POST" id="transactionForm">
                    @csrf

                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="">Selecione o tipo</option>
                            <option value="INCOME" {{ old('type') === 'INCOME' ? 'selected' : '' }}>Entrada</option>
                            <option value="EXPENSE" {{ old('type') === 'EXPENSE' ? 'selected' : '' }}>Saída</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                            <option value="">Selecione a categoria</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        data-type="{{ $category->type }}"
                                        {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} ({{ $category->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="paymentMethodGroup" style="display: none;">
                        <label for="payment_method" class="form-label">Forma de Pagamento <span class="text-danger">*</span></label>
                        <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                            <option value="">Selecione a forma de pagamento</option>
                            <option value="CASH" {{ old('payment_method') === 'CASH' ? 'selected' : '' }}>Dinheiro</option>
                            <option value="PIX" {{ old('payment_method') === 'PIX' ? 'selected' : '' }}>PIX</option>
                            <option value="DEBIT" {{ old('payment_method') === 'DEBIT' ? 'selected' : '' }}>Cartão de Débito</option>
                            <option value="CREDIT" {{ old('payment_method') === 'CREDIT' ? 'selected' : '' }}>Cartão de Crédito</option>
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="cardGroup" style="display: none;">
                        <label for="card_id" class="form-label">Cartão de Crédito <span class="text-danger">*</span></label>
                        <select class="form-select @error('card_id') is-invalid @enderror" id="card_id" name="card_id">
                            <option value="">Selecione o cartão</option>
                            @foreach($cards as $card)
                                <option value="{{ $card->id }}" {{ old('card_id', request('card_id')) == $card->id ? 'selected' : '' }}>
                                    {{ $card->name }} (Limit: R$ {{ number_format($card->credit_limit, 2, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        @error('card_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="installmentsGroup" style="display: none;">
                        <label for="installments_total" class="form-label">Número de Parcelas <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('installments_total') is-invalid @enderror" 
                               id="installments_total" name="installments_total" 
                               value="{{ old('installments_total', 1) }}" min="1" max="24">
                        <small class="form-text text-muted">Número total de parcelas (1-24)</small>
                        @error('installments_total')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="installmentsPreview" style="display: none;">
                        <div class="alert alert-info">
                            <strong>Prévia das Parcelas:</strong>
                            <div id="previewContent"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Valor <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0.01" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" name="amount" value="{{ old('amount') }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Data da Transação <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" 
                               id="transaction_date" name="transaction_date" 
                               value="{{ old('transaction_date', date('Y-m-d')) }}" required>
                        @error('transaction_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Criar Transação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const categorySelect = document.getElementById('category_id');
    const paymentMethodGroup = document.getElementById('paymentMethodGroup');
    const paymentMethodSelect = document.getElementById('payment_method');
    const cardGroup = document.getElementById('cardGroup');
    const installmentsGroup = document.getElementById('installmentsGroup');
    const installmentsPreview = document.getElementById('installmentsPreview');
    const installmentsTotalInput = document.getElementById('installments_total');
    const amountInput = document.getElementById('amount');
    const transactionDateInput = document.getElementById('transaction_date');
    const previewContent = document.getElementById('previewContent');

    function updateFormVisibility() {
        const type = typeSelect.value;
        const paymentMethod = paymentMethodSelect.value;
        const isExpense = type === 'EXPENSE';
        const isCredit = paymentMethod === 'CREDIT';

        // Show payment method for expenses
        paymentMethodGroup.style.display = isExpense ? 'block' : 'none';
        if (!isExpense) {
            paymentMethodSelect.value = '';
        }

        // Show card and installments for credit
        cardGroup.style.display = isCredit ? 'block' : 'none';
        installmentsGroup.style.display = isCredit ? 'block' : 'none';
        
        if (!isCredit) {
            installmentsTotalInput.value = 1;
            installmentsPreview.style.display = 'none';
        } else {
            updateInstallmentsPreview();
        }
    }

    function updateInstallmentsPreview() {
        const installments = parseInt(installmentsTotalInput.value) || 1;
        const amount = parseFloat(amountInput.value) || 0;
        const date = transactionDateInput.value;

        if (installments > 1 && amount > 0 && date) {
            const installmentAmount = amount / installments;
            const startDate = new Date(date);
            
            let preview = '<ul class="mb-0">';
            for (let i = 1; i <= installments; i++) {
                const installmentDate = new Date(startDate);
                installmentDate.setMonth(startDate.getMonth() + (i - 1));
                
                const formattedDate = installmentDate.toLocaleDateString('pt-BR');
                preview += `<li>Parcela ${i}/${installments}: R$ ${installmentAmount.toFixed(2)} - ${formattedDate}</li>`;
            }
            preview += '</ul>';
            
            previewContent.innerHTML = preview;
            installmentsPreview.style.display = 'block';
        } else {
            installmentsPreview.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', updateFormVisibility);
    paymentMethodSelect.addEventListener('change', updateFormVisibility);
    installmentsTotalInput.addEventListener('input', updateInstallmentsPreview);
    amountInput.addEventListener('input', updateInstallmentsPreview);
    transactionDateInput.addEventListener('change', updateInstallmentsPreview);

    // Initial visibility update
    updateFormVisibility();
});
</script>
@endpush
