@extends('layouts.app')

@section('title', 'Editar Cartão')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-pencil"></i> Editar Cartão de Crédito</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('cards.update', $card->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Cartão <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $card->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">Bandeira</label>
                            <select class="form-select @error('brand') is-invalid @enderror" id="brand" name="brand">
                                <option value="">Selecione a bandeira</option>
                                <option value="VISA" {{ old('brand', $card->brand) === 'VISA' ? 'selected' : '' }}>VISA</option>
                                <option value="MASTERCARD" {{ old('brand', $card->brand) === 'MASTERCARD' ? 'selected' : '' }}>MASTERCARD</option>
                                <option value="AMEX" {{ old('brand', $card->brand) === 'AMEX' ? 'selected' : '' }}>AMEX</option>
                                <option value="ELO" {{ old('brand', $card->brand) === 'ELO' ? 'selected' : '' }}>ELO</option>
                            </select>
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_four" class="form-label">Últimos 4 Dígitos</label>
                            <input type="text" class="form-control @error('last_four') is-invalid @enderror" 
                                   id="last_four" name="last_four" value="{{ old('last_four', $card->last_four) }}" 
                                   maxlength="4" pattern="[0-9]{4}">
                            @error('last_four')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="credit_limit" class="form-label">Limite de Crédito <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0" 
                                   class="form-control @error('credit_limit') is-invalid @enderror" 
                                   id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $card->credit_limit) }}" required>
                            @error('credit_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="closing_day" class="form-label">Dia de Fechamento <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('closing_day') is-invalid @enderror" 
                                   id="closing_day" name="closing_day" value="{{ old('closing_day', $card->closing_day) }}" 
                                   min="1" max="31" required>
                            <small class="form-text text-muted">Dia do mês em que a fatura fecha (1-31)</small>
                            @error('closing_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="due_day" class="form-label">Dia de Vencimento <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('due_day') is-invalid @enderror" 
                                   id="due_day" name="due_day" value="{{ old('due_day', $card->due_day) }}" 
                                   min="1" max="31" required>
                            <small class="form-text text-muted">Dia do mês em que a fatura vence (1-31)</small>
                            @error('due_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                            <option value="active" {{ old('status', $card->status) === 'active' ? 'selected' : '' }}>Ativo</option>
                            <option value="inactive" {{ old('status', $card->status) === 'inactive' ? 'selected' : '' }}>Inativo</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('cards.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Atualizar Cartão
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
