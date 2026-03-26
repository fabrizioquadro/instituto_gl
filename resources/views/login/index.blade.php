@extends('layout.login')

@section('conteudo')
<p class="mb-4">Login - Sistema Online</p>
@if($mensagem = Session::get('erro'))
    <div class="alert alert-danger" role="alert">
        {{ $mensagem }}
    </div>
@endif
@if($mensagem = Session::get('sucesso'))
    <div class="alert alert-success" role="alert">
        {{ $mensagem }}
    </div>
@endif
<form id="formAuthentication" class="mb-3" action="{{ route('login') }}" method="POST">
    @csrf
    <div class="form-floating form-floating-outline mb-3">
        <input type="email" class="form-control" name="email" placeholder="Email"/>
        <label for="email">Email:</label>
    </div>
    <div class="mb-3">
        <div class="form-password-toggle">
            <div class="input-group input-group-merge">
                <div class="form-floating form-floating-outline">
                    <input type="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                    <label for="senha">Password</label>
                </div>
                <span class="input-group-text cursor-pointer"><i class="mdi mdi-eye-off-outline"></i></span>
            </div>
        </div>
    </div>
    <div class="mb-3 d-flex justify-content-between">
        <a href="{{ route('esqueceu_senha') }}" class="float-end mb-1">
            <span style="color: #828393">Esqueceu Senha?</span>
        </a>
    </div>
    <div class="mb-3">
        <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
    </div>
</form>
@endsection
