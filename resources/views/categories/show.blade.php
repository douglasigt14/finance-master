@extends('layouts.app')

@section('title', 'Detalhes da Categoria')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-tag"></i> {{ $category->name }}</h2>
        <div>
            <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-secondary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Informações da Categoria</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Nome:</th>
                        <td>{{ $category->name }}</td>
                    </tr>
                    <tr>
                        <th>Tipo:</th>
                        <td>
                            <span class="badge bg-{{ $category->type === 'INCOME' ? 'success' : 'danger' }}">
                                {{ $category->type === 'INCOME' ? 'ENTRADA' : 'SAÍDA' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Cor:</th>
                        <td>
                            <span class="badge rounded-pill" style="background-color: {{ $category->color ?? '#6c757d' }}">
                                {{ $category->name }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
