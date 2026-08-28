@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-danger mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Excluir Semana {{ $semana->nr_semana }}</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.prescricoes.delete_semana') }}" method="post">
            @csrf
            <input type="hidden" name="semana_id" value="{{ $semana->id }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-12">
                    <p>Tem certeza que deseja excluir a semana <b>{{ $semana->nr_semana }}</b> (prevista para {{ $semana->data_prevista ? dataDbForm($semana->data_prevista) : '-' }})?</p>
                    <p class="text-muted mb-0">As medicações e observações desta semana também serão removidas.</p>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-danger me-2">Excluir</button>
                    <a href="{{ route('sistema.prescricoes.acessar', $semana->prescricao_id) }}" class="btn btn-outline-dark">Cancelar</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
