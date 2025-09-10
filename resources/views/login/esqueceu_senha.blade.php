@extends('layout.login')

@section('conteudo')
<p class="mb-4">Recuperar Senha</p>
@if($mensagem = Session::get('erro'))
    <div class="alert alert-danger" role="alert">
        {{ $mensagem }}
    </div>
@endif
<form id="formAuthentication" class="mb-3" action="{{ route('recuperar_senha') }}" method="POST">
    @csrf
    <div class="form-floating form-floating-outline mb-3">
        <input required type="email" class="form-control" name="email" placeholder="Email:"/>
        <label for="email">Email:</label>
    </div>
    <button class="btn btn-primary d-grid w-100">Gerar Nova Senha</button>
</form>
@endsection
