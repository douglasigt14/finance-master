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

@if(isset($cycleInfo) && $cycleInfo->isNotEmpty())
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i> 
        <strong>Próximo Ciclo:</strong>
        @foreach($cycleInfo as $info)
            <span class="me-3">
                <strong>{{ $info['card'] }}:</strong> {{ $info['start'] }} até {{ $info['end'] }}
            </span>
        @endforeach
    </div>
@endif

@if($debtors->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Nenhum devedor cadastrado. 
        <a href="{{ route('debtors.create') }}">Crie seu primeiro devedor</a>
    </div>
@else
    @php
        // Get all debtors including those without transactions and "Meu" (transactions without debtor)
        $allDebtorsWithTransactions = collect();
        
        // Add ALL debtors (with or without transactions)
        foreach ($debtors as $debtor) {
            $debtorTransactions = $transactionsByDebtor->get($debtor->id, collect());
            $allDebtorsWithTransactions->push([
                'id' => $debtor->id,
                'name' => $debtor->name,
                'transactions' => $debtorTransactions
            ]);
        }
        
        // Add "Meu" (transactions without debtor) if it has transactions
        $meuTransactions = $transactionsByDebtor->get('sem_devedor', collect());
        if ($meuTransactions->isNotEmpty()) {
            $allDebtorsWithTransactions->push([
                'id' => null,
                'name' => 'Meu',
                'transactions' => $meuTransactions
            ]);
        }
    @endphp

    @if($allDebtorsWithTransactions->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Nenhum devedor cadastrado.
        </div>
    @else
        @foreach($allDebtorsWithTransactions as $debtorData)
            <div class="card mb-4" @if($debtorData['name'] === 'Meu') id="meu-debtor-card" @endif>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person"></i> {{ $debtorData['name'] }}
                        @if($debtorData['transactions']->isNotEmpty())
                            <small class="text-muted">({{ $debtorData['transactions']->count() }})</small>
                        @endif
                    </h5>
                    @if($debtorData['id'] !== null)
                        <div class="btn-group">
                            <a href="{{ route('debtors.edit', $debtorData['id']) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('debtors.destroy', $debtorData['id']) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                        onclick="return confirm('Tem certeza?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    @if($debtorData['transactions']->isEmpty())
                        <p class="text-muted mb-0">Nenhuma transação no ciclo atual.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Nome na Fatura</th>
                                        <th>Descrição</th>
                                        <th>Categoria</th>
                                        <th>Cartão</th>
                                        <th>Valor</th>
                                        <th>Parcelas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($debtorData['transactions'] as $transaction)
                                    @php
                                        $cardName = $transaction->card ? $transaction->card->name : ($transaction->payment_method === 'PIX' ? 'PIX' : ($transaction->payment_method === 'CASH' ? 'Dinheiro' : ($transaction->payment_method === 'DEBIT' ? 'Débito' : 'Sem Cartão')));
                                        $categoryName = $transaction->category->name ?? 'Sem Categoria';
                                        
                                        $installmentStatus = 'Compras à Vista';
                                        if ($transaction->installments_total > 1) {
                                            $remaining = $transaction->installments_total - $transaction->installment_number;
                                            if ($remaining === 0) {
                                                $installmentStatus = 'Última Parcela';
                                            } elseif ($remaining === 1) {
                                                $installmentStatus = 'Penúltima Parcela';
                                            } elseif ($remaining === 2) {
                                                $installmentStatus = 'Antepenúltima Parcela';
                                            } else {
                                                $installmentStatus = 'Faltam mais de 4 Parcelas';
                                            }
                                        }
                                    @endphp
                                    <tr data-meu-card="{{ $cardName }}" 
                                        data-meu-category="{{ $categoryName }}" 
                                        data-meu-installment="{{ $installmentStatus }}"
                                        class="meu-transaction-row">
                                        <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                        <td>{{ $transaction->card_description ?? '-' }}</td>
                                        <td>{{ $transaction->description ?? '-' }}</td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $transaction->category->color ?? '#6c757d' }}">
                                                {{ $categoryName }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction->card)
                                                <span class="badge" style="background-color: {{ $transaction->card->color ?? '#0d6efd' }}20; color: {{ $transaction->card->color ?? '#0d6efd' }}">
                                                    {{ $transaction->card->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    {{ $transaction->payment_method === 'PIX' ? 'PIX' : ($transaction->payment_method === 'CASH' ? 'Dinheiro' : ($transaction->payment_method === 'DEBIT' ? 'Débito' : 'Sem Cartão')) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($transaction->installments_total > 1)
                                                <small class="fst-italic">{{ $transaction->installment_number }}/{{ $transaction->installments_total }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($transaction->group_uuid)
                                                <div class="btn-group">
                                                    <a href="{{ route('transactions.edit-group', $transaction->id) }}" class="btn btn-sm btn-outline-info" title="Editar Grupo">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a> 
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total:</th>
                                    <th>R$ {{ number_format($debtorData['transactions']->sum('amount'), 2, ',', '.') }}</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
        
        @if($chartDataByCard->isNotEmpty() || $chartDataByCategory->isNotEmpty() || $chartDataByInstallmentStatus->isNotEmpty())
            <div class="row mt-4">
                @if($chartDataByCard->isNotEmpty())
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-credit-card"></i> Distribuição por Cartão (Meu)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartByCard" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($chartDataByCategory->isNotEmpty())
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-tags"></i> Distribuição por Categoria (Meu)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartByCategory" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                @endif
                
                @if($chartDataByInstallmentStatus->isNotEmpty())
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Distribuição por Status de Parcelas (Meu)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartByInstallmentStatus" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif
@endif
@endsection

@push('scripts')
@if($chartDataByCard->isNotEmpty() || $chartDataByCategory->isNotEmpty() || $chartDataByInstallmentStatus->isNotEmpty())
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($chartDataByCard->isNotEmpty())
    // Chart by Card
    const ctxCard = document.getElementById('chartByCard');
    if (ctxCard) {
        const cardColors = {!! json_encode($chartDataByCard->pluck('color')) !!};
        const hexToRgba = (hex, alpha = 0.6) => {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        };
        
        const chartByCard = new Chart(ctxCard, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartDataByCard->pluck('label')) !!},
                datasets: [{
                    label: 'Valor (R$)',
                    data: {!! json_encode($chartDataByCard->pluck('amount')) !!},
                    backgroundColor: cardColors.map(color => hexToRgba(color, 0.6)),
                    borderColor: cardColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    }
    @endif
    
    @if($chartDataByCategory->isNotEmpty())
    // Chart by Category
    const ctxCategory = document.getElementById('chartByCategory');
    if (ctxCategory) {
        const colors = {!! json_encode($chartDataByCategory->pluck('color')) !!};
        const chartByCategory = new Chart(ctxCategory, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartDataByCategory->pluck('label')) !!},
                datasets: [{
                    label: 'Valor (R$)',
                    data: {!! json_encode($chartDataByCategory->pluck('amount')) !!},
                    backgroundColor: colors.map(color => {
                        // Convert hex to rgba with opacity
                        const r = parseInt(color.slice(1, 3), 16);
                        const g = parseInt(color.slice(3, 5), 16);
                        const b = parseInt(color.slice(5, 7), 16);
                        return `rgba(${r}, ${g}, ${b}, 0.6)`;
                    }),
                    borderColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const label = chartByCategory.data.labels[index];
                        filterMeuTable('category', label);
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    }
    @endif
    
    @if($chartDataByInstallmentStatus->isNotEmpty())
    // Chart by Installment Status
    const ctxInstallmentStatus = document.getElementById('chartByInstallmentStatus');
    if (ctxInstallmentStatus) {
        const statusColors = {!! json_encode($chartDataByInstallmentStatus->pluck('color')) !!};
        const hexToRgba = (hex, alpha = 0.6) => {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        };
        
        const chartByInstallmentStatus = new Chart(ctxInstallmentStatus, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartDataByInstallmentStatus->pluck('label')) !!},
                datasets: [{
                    label: 'Valor (R$)',
                    data: {!! json_encode($chartDataByInstallmentStatus->pluck('amount')) !!},
                    backgroundColor: statusColors.map(color => hexToRgba(color, 0.6)),
                    borderColor: statusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onClick: (event, elements) => {
                    if (elements.length > 0) {
                        const index = elements[0].index;
                        const label = chartByInstallmentStatus.data.labels[index];
                        filterMeuTable('installment', label);
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    }
    @endif
    
    // Function to filter "Meu" table based on chart clicks
    let currentFilter = null;
    
    function filterMeuTable(type, value) {
        currentFilter = { type: type, value: value };
        const rows = document.querySelectorAll('#meu-debtor-card .meu-transaction-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            let show = false;
            
            if (type === 'card') {
                show = row.getAttribute('data-meu-card') === value;
            } else if (type === 'category') {
                show = row.getAttribute('data-meu-category') === value;
            } else if (type === 'installment') {
                show = row.getAttribute('data-meu-installment') === value;
            }
            
            if (show) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update count in header
        const header = document.querySelector('#meu-debtor-card .card-header h5 small');
        if (header) {
            header.textContent = `(${visibleCount})`;
        }
    }
    
    function resetMeuTable() {
        currentFilter = null;
        const rows = document.querySelectorAll('#meu-debtor-card .meu-transaction-row');
        const totalCount = rows.length;
        
        rows.forEach(row => {
            row.style.display = '';
        });
        
        // Reset count in header
        const header = document.querySelector('#meu-debtor-card .card-header h5 small');
        if (header) {
            header.textContent = `(${totalCount})`;
        }
    }
    
    // Click outside to reset filter
    document.addEventListener('click', function(event) {
        const charts = document.querySelectorAll('#chartByCard, #chartByCategory, #chartByInstallmentStatus');
        const isChartClick = Array.from(charts).some(chart => chart.contains(event.target));
        
        if (!isChartClick && currentFilter) {
            resetMeuTable();
        }
    });
});
</script>
@endif
@endpush
