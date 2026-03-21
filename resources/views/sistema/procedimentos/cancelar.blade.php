@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-danger mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Cancelar Grupo de Procedimentos</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.procedimentos.cancelar_set') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="codigo" value="{{ $codigo }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-12">
                    <p>Tem certeza que deseja cancelar o grupo de procedimentos {{ $codigo }}?</p>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-danger me-2">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
