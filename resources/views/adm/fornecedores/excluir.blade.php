@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-danger mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Excluir Fornecedor</h4>
        </div>
        <hr>
        <form action="{{ route('adm.fornecedores.delete') }}" method="post">
            @csrf
            <input type="hidden" name="fornecedor_id" value="{{ $fornecedor->id }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-12">
                    <p>Tem certeza que deseja excluir o fornecedor {{ $fornecedor->nome }}?</p>
                </div>
                <div class="col-md-12 form-group">
                    <button type="submit" class="btn btn-danger me-2">Excluir</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
