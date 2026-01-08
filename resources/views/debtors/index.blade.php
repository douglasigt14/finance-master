@extends('layouts.app')

@section('title', 'Devedores')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-person-badge"></i> Devedores</h2>
        <a href="{{ route('debtors.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Devedor
        </a>
    </div>
</div>

@if($debtors->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Nenhum devedor cadastrado. 
        <a href="{{ route('debtors.create') }}">Crie seu primeiro devedor</a>
    </div>
@else
    <div class="row">
        @foreach($debtors as $debtor)
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="bi bi-person"></i> {{ $debtor->name }}
                                </h5>
                                <small class="text-muted">
                                    {{ $debtor->transactions_count }} transação(ões)
                                </small>
                            </div>
                            <div class="btn-group">
                                <a href="{{ route('debtors.edit', $debtor->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('debtors.destroy', $debtor->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                            onclick="return confirm('Tem certeza?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
