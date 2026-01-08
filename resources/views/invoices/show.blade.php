@extends('layouts.app')

@section('title', 'Detalhes da Fatura')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-receipt"></i> Fatura - {{ $card->name }} ({{ $invoice->cycle_month }}/{{ $invoice->cycle_year }})</h2>
        <div>
            <a href="{{ route('invoices.index', ['card_id' => $card->id]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

@include('invoices.partials.details', [
    'card' => $card,
    'invoice' => $invoice,
    'transactions' => $transactions,
    'cycleDates' => $cycleDates
])
@endsection
