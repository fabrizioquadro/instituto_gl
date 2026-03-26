@extends('layout.admin')

@section('conteudo')
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start align-items-sm-center gap-4">
            @if($adm->imagem)
                <img src="{{ asset('/public/img/administradores/'.$adm->imagem.'?'.date('ymdhis')) }}" alt="user-avatar" class="d-block w-px-120 h-px-120 rounded" id="uploadedAvatar"/>
            @else
                <div class="avatar avatar-xl me-2 w-px-120 h-px-120">
                    <span style="height:100px !important; width: 100px !important" class="avatar-initial rounded-circle bg-label-secondary">{{ substr($adm->nome,0,2) }}</span>
                </div>
            @endif
            <div class="button-wrapper">
                <h4 class="card-title">{{ $adm->nome }}</h4>
            </div>
        </div>
    </div>
    <div class="card-body pt-2 mt-1">
        <form method="post" action="{{ route('adm.alterar_senha.update') }}">
            @csrf
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
