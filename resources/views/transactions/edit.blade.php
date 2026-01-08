@extends('layouts.app')

@section('title', 'Editar Transação')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-pencil"></i> Editar Transação</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('transactions.update', $transaction->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="INCOME" {{ old('type', $transaction->type) === 'INCOME' ? 'selected' : '' }}>Entrada</option>
                            <option value="EXPENSE" {{ old('type', $transaction->type) === 'EXPENSE' ? 'selected' : '' }}>Saída</option>
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
                                        {{ old('category_id', $transaction->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }} ({{ $category->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Forma de Pagamento</label>
                        <select class="form-select @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method">
                            <option value="">Selecione a forma de pagamento</option>
                            <option value="CASH" {{ old('payment_method', $transaction->payment_method) === 'CASH' ? 'selected' : '' }}>Dinheiro</option>
                            <option value="PIX" {{ old('payment_method', $transaction->payment_method) === 'PIX' ? 'selected' : '' }}>PIX</option>
                            <option value="DEBIT" {{ old('payment_method', $transaction->payment_method) === 'DEBIT' ? 'selected' : '' }}>Cartão de Débito</option>
                            <option value="CREDIT" {{ old('payment_method', $transaction->payment_method) === 'CREDIT' ? 'selected' : '' }}>Cartão de Crédito</option>
                        </select>
                        @error('payment_method')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="card_id" class="form-label">Cartão de Crédito</label>
                        <select class="form-select @error('card_id') is-invalid @enderror" id="card_id" name="card_id">
                            <option value="">Sem cartão</option>
                            @foreach($cards as $card)
                                <option value="{{ $card->id }}" 
                                        {{ old('card_id', $transaction->card_id) == $card->id ? 'selected' : '' }}>
                                    {{ $card->name }}
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
                                   id="amount" name="amount" value="{{ old('amount', $transaction->amount) }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">Data da Transação <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" 
                               id="transaction_date" name="transaction_date" 
                               value="{{ old('transaction_date', $transaction->transaction_date->format('Y-m-d')) }}" required>
                        @error('transaction_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3">{{ old('description', $transaction->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_paid" name="is_paid" 
                                   {{ old('is_paid', $transaction->is_paid) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_paid">
                                Marcar como pago
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Atualizar Transação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
