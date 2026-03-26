@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-danger mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Excluir Grupo</h4>
        </div>
        <hr>
        <form action="{{ route('adm.grupos.delete') }}" method="post">
            <input type="hidden" name="grupo_id" value="{{ $grupo->id }}">
            @csrf
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-12">
                    <p>Tem certeza que deseja excluir o grupo {{ $grupo->nome }}? Para essa função funcionar nenhum medicamento pode estar atrelado a este grupo.</p>
                </div>
                <div class="col-md-12 form-group">
                    <button type="submit" class="btn btn-danger me-2">Excluir</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
