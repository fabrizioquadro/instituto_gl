@extends('layout.sistema')

@section('conteudo')
<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-header">Perfil</h4>
        @if($mensagem = Session::get('mensagem'))
        <div class="alert alert-success" role="alert">
            {{ $mensagem }}
        </div>
        @endif
        <div class="d-flex align-items-start align-items-sm-center gap-4">
            @if($user->imagem)
                <img src="{{ asset('/public/img/usuarios/'.$user->imagem.'?'.date('ymdhis')) }}" alt="user-avatar" class="d-block w-px-120 h-px-120 rounded" id="uploadedAvatar"/>
            @else
                <div class="avatar avatar-xl me-2 w-px-120 h-px-120">
                    <span style="height:100px !important; width: 100px !important" class="avatar-initial rounded-circle bg-label-secondary">{{ substr($user->nome,0,2) }}</span>
                </div>
            @endif
            <form id="form_foto" action="{{ route('sistema.perfil.atualizar_foto') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="button-wrapper">
                    <label for="upload" class="btn btn-primary me-2 mb-3" tabindex="0">
                        <span class="d-none d-sm-block">Atualizar Foto</span>
                        <i class="mdi mdi-tray-arrow-up d-block d-sm-none"></i>
                        <input required onchange="document.getElementById('form_foto').submit()" type="file" name="imagem" id="upload" class="account-file-input" hidden accept="image/png, image/jpeg" />
                    </label>
                    <button id='resetar_foto_perfil' type="button" class="btn btn-outline-danger account-image-reset mb-3">
                        <i class="mdi mdi-reload d-block d-sm-none"></i>
                        <span class="d-none d-sm-block">Reset</span>
                    </button>
                    <div class="small">Allowed JPG, GIF or PNG. Max size of 800K</div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body pt-2 mt-1">
        <form method="post" action="{{ route('sistema.perfil.update') }}">
            @csrf
            <div class="row mt-2 gy-4">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="nome" name="nome" value="{{ $user->nome }}"/>
                        <label for="nome">Nome:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="email" name="email" value="{{ $user->email }}"/>
                        <label for="email">Email:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="clinica_id" name='clinica_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($clinicas as $clinica)
                                <option @if($user->clinica_id == $clinica->id) selected @endif value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
                            @endforeach
                        </select>
                        <label for="clinica_id">Clinica:</label>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary me-2">Salvar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('resetar_foto_perfil').addEventListener('click', ()=>{
    if(confirm('Tem certeza que deseja excluir a imagem de foto?')){
        window.location.href = "{{ route('sistema.perfil.resetar_foto') }}"
    }
})
</script>
@endsection
