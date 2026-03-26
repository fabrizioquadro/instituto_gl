@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Alterar Senha Administrador</h4>
        </div>
        <hr>
        <form action="{{ route('adm.administradores.alterar_senha_update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="administrador_id" value="{{ $adm->id }}">
            <div class="row mt-2 gy-4">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="password" id="password" name="password"/>
                        <label for="password">Nova Senha:</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary me-2">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection
