@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-danger mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Excluir Baixa Abertos</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.baixas_abertos.delete') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="baixa_id" value="{{ $baixa->id }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-12">
                    <p>Tem certeza que deseja excluir a baixa selecionada?</p>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-danger me-2">Excluir</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
