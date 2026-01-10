@extends('layouts.app')

@section('title', 'Editar Transação Recorrente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-pencil"></i> Editar Transação Recorrente</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('recurring-transactions.update', $recurringTransaction->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="INCOME" {{ old('type', $recurringTransaction->type) === 'INCOME' ? 'selected' : '' }}>Entrada</option>
                            <option value="EXPENSE" {{ old('type', $recurringTransaction->type) === 'EXPENSE' ? 'selected' : '' }}>Saída</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria <span class="text-danger">*</span></label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ old('category_id', $recurringTransaction->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="paymentMethodGroup" style="display: {{ $recurringTransaction->type === 'EXPENSE' ? 'block' : 'none' }};">
                        <label for="payment_method" class="form-label">Forma de Pagamento</label>
                        <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                            <option value="">Selecione a forma de pagamento</option>
                            <option value="CASH" {{ old('payment_method', $recurringTransaction->payment_method) === 'CASH' ? 'selected' : '' }}>Dinheiro</option>
                            <option value="PIX" {{ old('payment_method', $recurringTransaction->payment_method) === 'PIX' ? 'selected' : '' }}>PIX</option>
                            <option value="DEBIT" {{ old('payment_method', $recurringTransaction->payment_method) === 'DEBIT' ? 'selected' : '' }}>Cartão de Débito</option>
                            <option value="CREDIT" {{ old('payment_method', $recurringTransaction->payment_method) === 'CREDIT' ? 'selected' : '' }}>Cartão de Crédito</option>
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="cardGroup" style="display: {{ $recurringTransaction->payment_method === 'CREDIT' ? 'block' : 'none' }};">
                        <label for="card_id" class="form-label">Cartão de Crédito</label>
                        <select class="form-select @error('card_id') is-invalid @enderror" id="card_id" name="card_id">
                            <option value="">Selecione o cartão</option>
                            @foreach($cards as $card)
                                <option value="{{ $card->id }}" {{ old('card_id', $recurringTransaction->card_id) == $card->id ? 'selected' : '' }}>
                                    {{ $card->name }} (Limit: R$ {{ number_format($card->credit_limit, 2, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        @error('card_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Valor <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0.01" 
                                   class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" name="amount" 
                                   value="{{ old('amount', $recurringTransaction->amount) }}" required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $recurringTransaction->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="cardDescriptionGroup" style="display: {{ $recurringTransaction->payment_method === 'CREDIT' ? 'block' : 'none' }};">
                        <label for="card_description" class="form-label">Descrição no Cartão</label>
                        <input type="text" class="form-control @error('card_description') is-invalid @enderror" 
                               id="card_description" name="card_description" 
                               value="{{ old('card_description', $recurringTransaction->card_description) }}" 
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
                                <option value="{{ $debtor->id }}" {{ old('debtor_id', $recurringTransaction->debtor_id) == $debtor->id ? 'selected' : '' }}>
                                    {{ $debtor->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('debtor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <h5 class="mb-3">Configurações de Recorrência</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="frequency" class="form-label">Frequência <span class="text-danger">*</span></label>
                                <select class="form-select @error('frequency') is-invalid @enderror" id="frequency" name="frequency" required>
                                    <option value="WEEKLY" {{ old('frequency', $recurringTransaction->frequency) === 'WEEKLY' ? 'selected' : '' }}>Semanal</option>
                                    <option value="MONTHLY" {{ old('frequency', $recurringTransaction->frequency) === 'MONTHLY' ? 'selected' : '' }}>Mensal</option>
                                    <option value="YEARLY" {{ old('frequency', $recurringTransaction->frequency) === 'YEARLY' ? 'selected' : '' }}>Anual</option>
                                </select>
                                @error('frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="dayOfMonthGroup">
                                <label for="day_of_month" class="form-label">Dia do Mês <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('day_of_month') is-invalid @enderror" 
                                       id="day_of_month" name="day_of_month" 
                                       value="{{ old('day_of_month', $recurringTransaction->day_of_month) }}" 
                                       min="1" max="31">
                                <small class="form-text text-muted">Dia em que a transação será gerada (1-31)</small>
                                @error('day_of_month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Data de Início <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                       id="start_date" name="start_date" 
                                       value="{{ old('start_date', $recurringTransaction->start_date->format('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Data de Término (Opcional)</label>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                       id="end_date" name="end_date" 
                                       value="{{ old('end_date', $recurringTransaction->end_date ? $recurringTransaction->end_date->format('Y-m-d') : '') }}">
                                <small class="form-text text-muted">Deixe em branco para continuar indefinidamente</small>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $recurringTransaction->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                <strong>Ativa</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">Desmarque para pausar a geração automática de transações</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('recurring-transactions.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Atualizar Transação Recorrente
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
    const paymentMethodGroup = document.getElementById('paymentMethodGroup');
    const paymentMethodSelect = document.getElementById('payment_method');
    const cardGroup = document.getElementById('cardGroup');
    const cardDescriptionGroup = document.getElementById('cardDescriptionGroup');
    const frequencySelect = document.getElementById('frequency');
    const dayOfMonthGroup = document.getElementById('dayOfMonthGroup');

    function updateFormVisibility() {
        const type = typeSelect.value;
        const paymentMethod = paymentMethodSelect.value;
        const isExpense = type === 'EXPENSE';
        const isCredit = paymentMethod === 'CREDIT';

        paymentMethodGroup.style.display = isExpense ? 'block' : 'none';
        cardGroup.style.display = isCredit ? 'block' : 'none';
        cardDescriptionGroup.style.display = isCredit ? 'block' : 'none';
    }

    function updateFrequencyFields() {
        if (frequencySelect.value === 'MONTHLY') {
            dayOfMonthGroup.style.display = 'block';
        } else {
            dayOfMonthGroup.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', updateFormVisibility);
    paymentMethodSelect.addEventListener('change', updateFormVisibility);
    frequencySelect.addEventListener('change', updateFrequencyFields);

    updateFormVisibility();
    updateFrequencyFields();
});
</script>
@endpush
