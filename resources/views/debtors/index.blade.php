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
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="flex-grow-1">
                <i class="bi bi-info-circle"></i> 
                <strong>Próximo Ciclo:</strong>
                @foreach($cycleInfo as $info)
                    <span class="me-3">
                        <strong>{{ $info['card'] }}:</strong> {{ $info['start'] }} até {{ $info['end'] }}
                    </span>
                @endforeach
            </div>
            <div class="ms-3">
                <select id="monthSelector" class="form-select form-select-sm" style="min-width: 180px;">
                    @php
                        $months = [
                            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                        ];
                        $now = now();
                        $currentYear = $targetYear ?? $now->copy()->addMonth()->year;
                        $currentMonth = $targetMonth ?? $now->copy()->addMonth()->month;
                        $selectedValue = $currentMonth . '-' . $currentYear;
                    @endphp
                    @for($year = $currentYear - 1; $year <= $currentYear + 1; $year++)
                        @foreach($months as $monthNum => $monthName)
                            @php
                                $optionValue = $monthNum . '-' . $year;
                                $isSelected = ($targetMonth ?? null) == $monthNum && ($targetYear ?? null) == $year;
                            @endphp
                            <option value="{{ $optionValue }}" @if($isSelected) selected @endif>
                                {{ $monthName }} - {{ $year }}
                            </option>
                        @endforeach
                    @endfor
                </select>
            </div>
        </div>
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
            <div class="card mb-4" 
                 @if($debtorData['name'] === 'Meu') 
                     id="meu-debtor-card"
                 @else
                     id="debtor-{{ $debtorData['id'] }}"
                 @endif>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-person"></i> {{ $debtorData['name'] }}
                        @if($debtorData['transactions']->isNotEmpty())
                            <small class="text-muted">({{ $debtorData['transactions']->count() }})</small>
                        @endif
                    </h5>
                    @if($debtorData['id'] !== null)
                        <div class="btn-group">
                            @if($debtorData['transactions']->isNotEmpty())
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="generateImage('debtor-{{ $debtorData['id'] }}', '{{ $debtorData['name'] }}')"
                                        title="Gerar Imagem">
                                    <i class="bi bi-image"></i> Imagem
                                </button>
                            @endif
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
                                        <th>Valor</th>
                                        <th>Descrição</th>
                                        <th>Nome na Fatura</th>
                                        <th>Parcela</th>
                                        <th>Cartão</th>
                                        <th>Categoria</th>
                                        <th>Ação</th>
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
                                        <td>R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                                        <td>{{ $transaction->description ?? '-' }}</td>
                                        <td>{{ $transaction->card_description ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($transaction->installments_total > 1)
                                                <small class="fst-italic">{{ $transaction->installment_number }}/{{ $transaction->installments_total }}</small>
                                            @endif
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
                                        <td>
                                            <span class="badge" style="background-color: {{ $transaction->category->color ?? '#6c757d' }}">
                                                {{ $categoryName }}
                                            </span>
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
                                    <th class="text-end">Total:</th>
                                    <th>R$ {{ number_format($debtorData['transactions']->sum('amount'), 2, ',', '.') }}</th>
                                    <th colspan="6"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
        
        @if($chartDataByCard->isNotEmpty() || $chartDataByCategory->isNotEmpty() || $chartDataByInstallmentStatus->isNotEmpty() || $chartDataByDebtor->isNotEmpty())
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
                    <div class="col-md-6 mb-4">
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
                
                @if($chartDataByDebtor->isNotEmpty())
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Distribuição por Devedor</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="chartByDebtor" height="300"></canvas>
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
@if($chartDataByCard->isNotEmpty() || $chartDataByCategory->isNotEmpty() || $chartDataByInstallmentStatus->isNotEmpty() || $chartDataByDebtor->isNotEmpty())
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
    
    @if($chartDataByDebtor->isNotEmpty())
    // Chart by Debtor (Pie Chart)
    const ctxDebtor = document.getElementById('chartByDebtor');
    if (ctxDebtor) {
        const debtorColors = {!! json_encode($chartDataByDebtor->pluck('color')) !!};
        const hexToRgba = (hex, alpha = 0.8) => {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        };
        
        const chartByDebtor = new Chart(ctxDebtor, {
            type: 'pie',
            data: {
                labels: {!! json_encode($chartDataByDebtor->pluck('label')) !!},
                datasets: [{
                    label: 'Valor (R$)',
                    data: {!! json_encode($chartDataByDebtor->pluck('amount')) !!},
                    backgroundColor: debtorColors.map(color => hexToRgba(color, 0.8)),
                    borderColor: debtorColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': R$ ' + value.toFixed(2).replace('.', ',') + ' (' + percentage + '%)';
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

// Function to generate image from table
function generateImage(debtorCardId, debtorName) {
    const element = document.getElementById(debtorCardId);
    if (!element) {
        alert('Erro: elemento não encontrado.');
        return;
    }
    
    const originalButton = event.target.closest('button');
    if (!originalButton) {
        return;
    }
    
    const originalHTML = originalButton.innerHTML;
    originalButton.disabled = true;
    originalButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Gerando...';
    
    // Find the table in the card
    const table = element.querySelector('.table-responsive') || element.querySelector('table');
    
    if (!table) {
        alert('Erro: Tabela não encontrada.');
        originalButton.disabled = false;
        originalButton.innerHTML = originalHTML;
        return;
    }
    
    // Create a temporary container for the image
    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'absolute';
    tempContainer.style.left = '-9999px';
    tempContainer.style.backgroundColor = '#ffffff';
    tempContainer.style.padding = '20px';
    tempContainer.style.width = table.offsetWidth + 'px';
    
    // Clone the table
    const clonedTable = table.cloneNode(true);
    
    // Remove action buttons and column
    const buttons = clonedTable.querySelectorAll('.btn-group, .btn, button');
    buttons.forEach(btn => btn.remove());
    
    // Remove "Ação" column
    const headers = clonedTable.querySelectorAll('thead th');
    let acoesIndex = -1;
    headers.forEach((header, index) => {
        if (header.textContent.trim() === 'Ação') {
            acoesIndex = index;
            header.remove();
        }
    });
    
    if (acoesIndex >= 0) {
        const rows = clonedTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells[acoesIndex]) {
                cells[acoesIndex].remove();
            }
        });
    }
    
    // Add title
    const title = document.createElement('h5');
    title.textContent = debtorName;
    title.style.marginBottom = '15px';
    title.style.textAlign = 'center';
    title.style.color = '#333';
    
    tempContainer.appendChild(title);
    tempContainer.appendChild(clonedTable);
    document.body.appendChild(tempContainer);
    
    // Generate image using html2canvas
    html2canvas(tempContainer, {
        backgroundColor: '#ffffff',
        scale: 2,
        logging: false,
        useCORS: true,
        allowTaint: true
    }).then(canvas => {
        // Convert canvas to blob
        canvas.toBlob(function(blob) {
            // Create download link
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `transacoes_${debtorName.replace(/\s+/g, '_').toLowerCase()}_${new Date().toISOString().split('T')[0]}.jpg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            // Remove temporary container
            document.body.removeChild(tempContainer);
            
            // Restore button
            originalButton.disabled = false;
            originalButton.innerHTML = originalHTML;
        }, 'image/jpeg', 0.95);
    }).catch(error => {
        console.error('Erro ao gerar imagem:', error);
        alert('Erro ao gerar imagem: ' + error.message);
        document.body.removeChild(tempContainer);
        originalButton.disabled = false;
        originalButton.innerHTML = originalHTML;
    });
}
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthSelector = document.getElementById('monthSelector');
    if (monthSelector) {
        monthSelector.addEventListener('change', function() {
            const [month, year] = this.value.split('-');
            const url = new URL(window.location.href);
            url.searchParams.set('month', month);
            url.searchParams.set('year', year);
            window.location.href = url.toString();
        });
    }
});
</script>
@endpush
