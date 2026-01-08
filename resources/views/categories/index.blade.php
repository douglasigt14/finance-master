@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-tags"></i> Categorias</h2>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nova Categoria
        </a>
    </div>
</div>

@if($categories->isEmpty())
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Nenhuma categoria cadastrada. 
        <a href="{{ route('categories.create') }}">Crie sua primeira categoria</a>
    </div>
@else
    <div class="row mb-4">
        <div class="col-12">
            <h4>Categorias de Entrada</h4>
        </div>
    </div>
    <div class="row mb-4">
        @foreach($categories->where('type', 'INCOME') as $category)
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge rounded-pill" style="background-color: {{ $category->color ?? '#10b981' }}">
                                    {{ $category->name }}
                                </span>
                            </div>
                            <div class="btn-group">
                                <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline">
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

    <div class="row mb-4">
        <div class="col-12">
            <h4>Categorias de Saída</h4>
        </div>
    </div>
    <div class="row">
        @foreach($categories->where('type', 'EXPENSE') as $category)
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge rounded-pill" style="background-color: {{ $category->color ?? '#ef4444' }}">
                                    {{ $category->name }}
                                </span>
                            </div>
                            <div class="btn-group">
                                <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-inline">
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
