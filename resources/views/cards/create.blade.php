@extends('layouts.app')

@section('title', 'Criar Cartão')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h2><i class="bi bi-plus-circle"></i> Criar Cartão de Crédito</h2>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('cards.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nome do Cartão <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">Bandeira</label>
                            <select class="form-select @error('brand') is-invalid @enderror" id="brand" name="brand">
                                <option value="">Selecione a bandeira</option>
                                <option value="VISA" {{ old('brand') === 'VISA' ? 'selected' : '' }}>VISA</option>
                                <option value="MASTERCARD" {{ old('brand') === 'MASTERCARD' ? 'selected' : '' }}>MASTERCARD</option>
                                <option value="AMEX" {{ old('brand') === 'AMEX' ? 'selected' : '' }}>AMEX</option>
                                <option value="ELO" {{ old('brand') === 'ELO' ? 'selected' : '' }}>ELO</option>
                            </select>
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_four" class="form-label">Últimos 4 Dígitos</label>
                            <input type="text" class="form-control @error('last_four') is-invalid @enderror" 
                                   id="last_four" name="last_four" value="{{ old('last_four') }}" 
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
                                   id="credit_limit" name="credit_limit" value="{{ old('credit_limit') }}" required>
                            @error('credit_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="closing_day" class="form-label">Dia de Fechamento <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('closing_day') is-invalid @enderror" 
                                   id="closing_day" name="closing_day" value="{{ old('closing_day') }}" 
                                   min="1" max="31" required>
                            <small class="form-text text-muted">Dia do mês em que a fatura fecha (1-31)</small>
                            @error('closing_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="due_day" class="form-label">Dia de Vencimento <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('due_day') is-invalid @enderror" 
                                   id="due_day" name="due_day" value="{{ old('due_day') }}" 
                                   min="1" max="31" required>
                            <small class="form-text text-muted">Dia do mês em que a fatura vence (1-31)</small>
                            @error('due_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Ativo</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inativo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="color" class="form-label">Cor</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                       id="color" name="color" value="{{ old('color', '#0d6efd') }}" 
                                       title="Escolha a cor do cartão">
                                <input type="text" class="form-control @error('color') is-invalid @enderror" 
                                       id="color_text" value="{{ old('color', '#0d6efd') }}" 
                                       pattern="^#[0-9A-Fa-f]{6}$" placeholder="#0d6efd">
                            </div>
                            <small class="form-text text-muted">Escolha uma cor para identificar o cartão</small>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('cards.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Criar Cartão
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('color');
    const colorText = document.getElementById('color_text');
    
    if (colorPicker && colorText) {
        // Sync color picker to text input
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value;
        });
        
        // Sync text input to color picker
        colorText.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                colorPicker.value = this.value;
            }
        });
    }
});
</script>
@endsection
