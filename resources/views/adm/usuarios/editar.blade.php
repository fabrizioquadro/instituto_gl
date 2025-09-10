@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Usuário</h4>
        </div>
        <hr>
        <form action="{{ route('adm.usuarios.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="row mt-2 gy-4">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="text" id="nome" name="nome" value="{{ $user->nome }}"/>
                        <label for="nome">Nome:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="email" id="email" name="email" value="{{ $user->email }}"/>
                        <label for="email">E-mail:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="clinica_id" name='clinica_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($clinicas as $clinica)
                                <option @if($clinica->id == $user->clinica_id) selected @endif value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
                            @endforeach
                        </select>
                        <label for="clinica_id">Clinica:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="tipo" name='tipo' class="select2 form-select">
                            <option value="">Opções</option>
                            <option @if($user->tipo == "Secretária") selected @endif value="Secretária">Secretária</option>
                            <option @if($user->tipo == "Enfermagem") selected @endif value="Enfermagem">Enfermagem</option>
                        </select>
                        <label for="tipo">Tipo:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="file" id="imagem" name="imagem"/>
                    </div>
                </div>
            </div>
            <div class="row mt-2 gy-4">
                <div class="col-md-6">
                    <label for="">Acessos:</label>
                    @foreach($opcoes as $opt)
                        <div class="form-check form-check-primary">
                            <input @if($user->$opt == "Sim") checked @endif class="form-check-input" type="checkbox" value="Sim" id="{{ $opt }}" name="{{ $opt }}">
                            <label class="form-check-label" for="{{ $opt }}" style="text-transform: capitalize"> {{ str_replace('_',' ', $opt) }} </label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary me-2">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection
