@extends('layouts.app')

@section('title', 'Editar Grupo de Transações')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-pencil-square"></i> Editar Grupo de Transações</h2>
        <p class="text-muted">Editando {{ $groupTransactions->count() }} transações do grupo</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('transactions.update-group', $transaction->id) }}" method="POST" id="editGroupForm">
                    @csrf
                    @method('PUT')

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Atenção:</strong> As alterações serão aplicadas a todas as {{ $groupTransactions->count() }} transações do grupo. 
                        A data e o valor serão ajustados automaticamente para cada parcela.
                    </div>

                    @php
                        $firstTransaction = $groupTransactions->first();
                        $baseDescription = preg_replace('/\s*-\s*Parcela\s+\d+\/\d+$/', '', $firstTransaction->description);
                    @endphp

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ old('category_id', $firstTransaction->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} ({{ $category->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Data da Primeira Parcela <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" 
                               id="transaction_date" name="transaction_date" 
                               value="{{ old('transaction_date', $firstTransaction->transaction_date->format('Y-m-d')) }}" required>
                        <small class="form-text text-muted">As datas das demais parcelas serão calculadas automaticamente (mês a mês a partir desta data)</small>
                        @error('transaction_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Valor da Parcela <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" 
                               id="amount" name="amount" 
                               value="{{ old('amount', number_format($firstTransaction->amount, 2, '.', '')) }}" required>
                        <small class="form-text text-muted">O valor será aplicado a todas as parcelas do grupo</small>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição Base</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $baseDescription) }}</textarea>
                        <small class="form-text text-muted">O sufixo "Parcela X/Y" será adicionado automaticamente a cada transação</small>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="card_id" class="form-label">Cartão de Crédito</label>
                        <select class="form-select @error('card_id') is-invalid @enderror" id="card_id" name="card_id">
                            <option value="">Sem cartão</option>
                            @foreach($cards as $card)
                                <option value="{{ $card->id }}" 
                                        {{ old('card_id', $firstTransaction->card_id) == $card->id ? 'selected' : '' }}>
                                    {{ $card->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('card_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="card_description" class="form-label">Descrição no Cartão</label>
                        <input type="text" class="form-control @error('card_description') is-invalid @enderror" 
                               id="card_description" name="card_description" 
                               value="{{ old('card_description', $firstTransaction->card_description) }}" 
                               placeholder="Ex: LOJA X JS">
                        <small class="form-text text-muted">Descrição exata como aparece no cartão de crédito</small>
                        @error('card_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="debtor_id" class="form-label">Cobrar de (Devedor)</label>
                        <select class="form-select @error('debtor_id') is-invalid @enderror" id="debtor_id" name="debtor_id">
                            <option value="">Nenhum</option>
                            @foreach($debtors as $debtor)
                                <option value="{{ $debtor->id }}" {{ old('debtor_id', $firstTransaction->debtor_id) == $debtor->id ? 'selected' : '' }}>
                                    {{ $debtor->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Selecione se emprestou o cartão para alguém</small>
                        @error('debtor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Prévia das Parcelas</label>
                        <div class="alert alert-info" id="installmentsPreview">
                            <strong>Parcelas que serão atualizadas:</strong>
                            <ul class="mb-0 mt-2" id="previewList">
                                @foreach($groupTransactions as $t)
                                    <li>Parcela {{ $t->installment_number }}/{{ $t->installments_total }}: 
                                        R$ {{ number_format($t->amount, 2, ',', '.') }} - 
                                        {{ $t->transaction_date->format('d/m/Y') }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Atualizar Grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transações do Grupo</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    @foreach($groupTransactions as $t)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>Parcela {{ $t->installment_number }}/{{ $t->installments_total }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $t->transaction_date->format('d/m/Y') }}</small>
                                    <br>
                                    <span class="badge bg-primary">R$ {{ number_format($t->amount, 2, ',', '.') }}</span>
                                </div>
                                @if($t->is_paid)
                                    <span class="badge bg-success">Pago</span>
                                @else
                                    <span class="badge bg-secondary">Não Pago</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const transactionDateInput = document.getElementById('transaction_date');
    const amountInput = document.getElementById('amount');
    const descriptionInput = document.getElementById('description');
    const previewList = document.getElementById('previewList');
    const installmentsTotal = {{ $groupTransactions->count() }};

    function updatePreview() {
        const date = transactionDateInput.value;
        const amount = parseFloat(amountInput.value) || 0;
        const description = descriptionInput.value || '';

        if (date && amount > 0) {
            // Parse date string to avoid timezone issues
            const [year, month, day] = date.split('-').map(Number);
            const baseDate = new Date(year, month - 1, day);
            
            let preview = '';
            for (let i = 1; i <= installmentsTotal; i++) {
                const installmentDate = new Date(baseDate);
                installmentDate.setMonth(baseDate.getMonth() + (i - 1));
                
                const formattedDate = installmentDate.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                
                const installmentAmount = amount.toFixed(2);
                preview += `<li>Parcela ${i}/${installmentsTotal}: R$ ${installmentAmount.replace('.', ',')} - ${formattedDate}</li>`;
            }
            
            previewList.innerHTML = preview;
        }
    }

    transactionDateInput.addEventListener('change', updatePreview);
    amountInput.addEventListener('input', updatePreview);
    descriptionInput.addEventListener('input', function() {
        // Description change doesn't affect preview, but we can update if needed
    });

    // Initial preview update
    updatePreview();
});
</script>
@endpush
