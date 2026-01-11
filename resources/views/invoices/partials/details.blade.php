<!-- Invoice Summary -->

<div class="row mb-4">
 <div class="col-md-3">
    <div class="card text-white bg-primary mb-2">
        <div class="card-body">
            <h6 class="card-title">Valor Total</h6>
            <h3 class="mb-0">R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</h3>
        </div>
    </div>
    <div class="card text-white bg-success mb-2">
        <div class="card-body">
            <h6 class="card-title">Valor Pago</h6>
            <h3 class="mb-0">R$ {{ number_format($invoice->paid_amount, 2, ',', '.') }}</h3>
        </div>
    </div>
    <div class="card text-white bg-warning mb-2">
        <div class="card-body">
            <h6 class="card-title">Restante</h6>
            <h3 class="mb-0">R$ {{ number_format($invoice->remaining_amount, 2, ',', '.') }}</h3>
        </div>
    </div>
    <div class="card mb-2">
        <div class="card-body">
            <h6 class="card-title">Status</h6>
            @if($invoice->is_paid)
                <span class="badge bg-success fs-6">Paga</span>
            @else
                <span class="badge bg-warning fs-6">Não Paga</span>
            @endif
        </div>
    </div>
 </div>
 <div class="col-md-9">
        @php
            // Agrupar transações por categoria (para gráfico de barras)
            $categoryData = $transactions->groupBy('category_id')->map(function ($group) {
                $category = $group->first()->category;
                return [
                    'name' => $category->name ?? 'Sem categoria',
                    'color' => $category->color ?? '#6c757d',
                    'amount' => $group->sum('amount'),
                    'count' => $group->count()
                ];
            })->sortByDesc('amount')->values();
            
            // Agrupar transações por devedor (para gráfico de pizza)
            $debtorData = $transactions->groupBy('debtor_id')->map(function ($group) {
                $debtor = $group->first()->debtor;
                return [
                    'name' => $debtor ? $debtor->name : 'Meu',
                    'amount' => $group->sum('amount'),
                    'count' => $group->count()
                ];
            })->sortByDesc('amount')->values();
            
            // Paleta de cores para devedores
            $debtorColors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#0dcaf0', '#6610f2', '#e83e8c'];
        @endphp
        
        @if($categoryData->isNotEmpty() || $debtorData->isNotEmpty())
            <div class="card h-100">
                <div class="card-header" style="background-color: {{ $card->color ?? '#0d6efd' }}20;">
                    <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Distribuição</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div style="position: relative; height: 300px;">
                                <canvas id="debtorPieChart"></canvas>
                            </div>
                            <p class="text-center mt-2 mb-0"><small class="text-muted">Por Devedor</small></p>
                        </div>
                        <div class="col-md-6">
                            <div style="position: relative; height: 300px;">
                                <canvas id="categoryBarChart"></canvas>
                            </div>
                            <p class="text-center mt-2 mb-0"><small class="text-muted">Por Categoria</small></p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 200px;">
                    <p class="text-muted mb-0">Nenhuma transação para exibir gráficos</p>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Invoice Info -->
<div class="card mb-4" style="border-top: 4px solid {{ $card->color ?? '#0d6efd' }};">
    <div class="card-header" style="background-color: {{ $card->color ?? '#0d6efd' }}20;">
        <h5 class="mb-0">Informações da Fatura</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Ciclo:</strong><br>
                {{ \Carbon\Carbon::create($invoice->cycle_year, $invoice->cycle_month, 1)->locale('pt_BR')->translatedFormat('F/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Período do Ciclo:</strong><br>
                {{ $cycleDates['start']->format('d/m/Y') }} até {{ $cycleDates['end']->format('d/m/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Data de Fechamento:</strong><br>
                {{ \Carbon\Carbon::parse($invoice->closing_date)->format('d/m/Y') }}
            </div>
            <div class="col-md-3">
                <strong>Data de Vencimento:</strong><br>
                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
            </div>
        </div>
    </div>
</div>

<!-- Transactions -->
<div class="card" style="border-top: 4px solid {{ $card->color ?? '#0d6efd' }};">
    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: {{ $card->color ?? '#0d6efd' }}20;">
        <h5 class="mb-0">Transações</h5>
        <div>
            <form action="{{ route('invoices.recalculate', [$card->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                  method="POST" class="d-inline me-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-info">
                    <i class="bi bi-arrow-clockwise"></i> Recalcular
                </button>
            </form>
            @if(!$invoice->is_paid)
                <form action="{{ route('invoices.mark-paid', [$card->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">
                        <i class="bi bi-check-circle"></i> Marcar como Paga
                    </button>
                </form>
            @else
                <form action="{{ route('invoices.mark-unpaid', [$card->id, $invoice->cycle_month, $invoice->cycle_year]) }}" 
                      method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="bi bi-x-circle"></i> Marcar como Não Paga
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($transactions->isEmpty())
            <p class="text-muted">Nenhuma transação neste ciclo.</p>
        @else
            <div class="mb-3">
                <input type="text" id="transactionSearch" class="form-control" 
                       placeholder="Buscar transações por descrição, nome na fatura, categoria..." 
                       onkeyup="filterTransactions()">
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Nome na Fatura</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Parcelas</th>
                            <th>Devedor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                <td>{{ $transaction->card_description ?? '-' }}</td>
                                <td>{{ $transaction->description ?? '-' }}</td>
                                <td>
                                    <span class="badge" style="background-color: {{ $transaction->category->color ?? '#6c757d' }}">
                                        {{ $transaction->category->name }}
                                    </span>
                                </td>
                                <td>R$ {{ number_format($transaction->amount, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($transaction->installments_total > 1)
                                        <small class="fst-italic">{{ $transaction->installment_number }}/{{ $transaction->installments_total }}</small>
                                    @endif
                                </td>
                                <td>{{ $transaction->debtor->name ?? '' }}</td>
                                <td>
                                    @if($transaction->group_uuid)
                                        <div class="btn-group">
                                            <a href="{{ route('transactions.edit-group', $transaction->id) }}" class="btn btn-sm btn-outline-info" title="Editar Grupo">
                                                <i class="bi bi-pencil-square"></i>
                                            </a> 
                                        </div>
                                    @else
                                        <div class="btn-group">
                                            <a href="{{ route('transactions.edit', $transaction->id) }}" class="btn btn-sm btn-outline-info" title="Editar">
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
                            <th colspan="4" class="text-end">Total:</th>
                            <th>R$ {{ number_format($transactions->sum('amount'), 2, ',', '.') }}</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
function filterTransactions() {
    const input = document.getElementById('transactionSearch');
    if (!input) return;
    
    const filter = input.value.toLowerCase();
    const table = document.getElementById('transactionsTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) { // Start from 1 to skip header
        const row = rows[i];
        // Skip footer row
        if (row.parentElement.tagName === 'TFOOT') continue;
        
        const text = row.textContent || row.innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Função para renderizar gráficos (tornada global para ser chamada após AJAX)
window.renderCategoryCharts = function() {
    @if(($categoryData->isNotEmpty() || $debtorData->isNotEmpty()))
    // Verificar se Chart.js está disponível
    if (typeof Chart === 'undefined') {
        console.error('Chart.js não está disponível');
        return;
    }
    
    // Aguardar um pouco para garantir que os elementos foram renderizados após AJAX
    setTimeout(function() {
        const debtorPieCtx = document.getElementById('debtorPieChart');
        const barCtx = document.getElementById('categoryBarChart');
        
        // Verificar se os elementos existem
        if (!debtorPieCtx || !barCtx) {
            console.log('Elementos dos gráficos não encontrados');
            return;
        }
        
        // Destruir gráficos existentes nos canvas específicos (importante ao trocar de fatura)
        if (debtorPieCtx.chart) {
            debtorPieCtx.chart.destroy();
            debtorPieCtx.chart = null;
        }
        if (barCtx.chart) {
            barCtx.chart.destroy();
            barCtx.chart = null;
        }
        
        // Dados de devedores (gerados no servidor - sempre específicos da fatura exibida)
        const debtorData = @json($debtorData);
        const debtorColors = @json($debtorColors);
        
        // Dados de categorias (gerados no servidor - sempre específicos da fatura exibida)
        const categoryData = @json($categoryData);
        
        // Gráfico de Pizza por Devedor
        if (debtorData && debtorData.length > 0) {
            const debtorLabels = debtorData.map(item => item.name);
            const debtorAmounts = debtorData.map(item => parseFloat(item.amount));
            const debtorChartColors = debtorData.map((item, index) => debtorColors[index % debtorColors.length]);
            const debtorPercentages = debtorAmounts.map(amount => {
                const total = debtorAmounts.reduce((a, b) => a + b, 0);
                return total > 0 ? ((amount / total) * 100).toFixed(1) : '0';
            });
            
            debtorPieCtx.chart = new Chart(debtorPieCtx, {
                type: 'pie',
                data: {
                    labels: debtorLabels.map((label, i) => `${label} (${debtorPercentages[i]}%)`),
                    datasets: [{
                        data: debtorAmounts,
                        backgroundColor: debtorChartColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = 'R$ ' + context.parsed.toFixed(2).replace('.', ',');
                                    const percentage = debtorPercentages[context.dataIndex] + '%';
                                    return label.split(' (')[0] + ': ' + value + ' (' + percentage + ')';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de Barras por Categoria
        if (categoryData && categoryData.length > 0) {
            const categoryLabels = categoryData.map(item => item.name);
            const categoryAmounts = categoryData.map(item => parseFloat(item.amount));
            const categoryChartColors = categoryData.map(item => item.color);
            const categoryPercentages = categoryAmounts.map(amount => {
                const total = categoryAmounts.reduce((a, b) => a + b, 0);
                return total > 0 ? ((amount / total) * 100).toFixed(1) : '0';
            });
            
            barCtx.chart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Valor (R$)',
                        data: categoryAmounts,
                        backgroundColor: categoryChartColors,
                        borderColor: categoryChartColors.map(color => color + '80'),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                                    const percentage = categoryPercentages[context.dataIndex] + '%';
                                    return value + ' (' + percentage + ')';
                                }
                            }
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
                    }
                }
            });
        }
    }, 150); // Delay para garantir que o DOM foi atualizado após AJAX
    @endif
};

// Renderizar gráficos quando o DOM estiver pronto
// Nota: Esta função também será chamada após carregar conteúdo via AJAX
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(renderCategoryCharts, 100);
    });
} else {
    setTimeout(renderCategoryCharts, 100);
}
</script>
