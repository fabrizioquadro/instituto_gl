@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Usuário</h4>
        </div>
        @if($mensagem = Session::get('mensagem_erro'))
            <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                {{ $mensagem }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
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
                            <option @if($user->tipo == "Médico") selected @endif value="Médico">Médico</option>
                        </select>
                        <label for="tipo">Tipo:</label>
                    </div>
                </div>
                @php
                    $medico_atual_na_lista = false;
                    foreach($medicos as $medico){
                        if((string) $medico['profissional_id'] === (string) $user->feegow_profissional_id){
                            $medico_atual_na_lista = true;
                            break;
                        }
                    }
                @endphp
                <div class="col-md-6" id="wrap_feegow_profissional" style="display: none">
                    <div class="form-floating form-floating-outline">
                        <select id="feegow_profissional_id" name="feegow_profissional_id" class="select2 form-select">
                            <option value="">Opções</option>
                            @if($user->feegow_profissional_id && !$medico_atual_na_lista)
                                <option value="{{ $user->feegow_profissional_id }}" data-nome="{{ $user->feegow_profissional_nome }}" selected>{{ $user->feegow_profissional_nome }}</option>
                            @endif
                            @foreach($medicos as $medico)
                                <option @if((string) $medico['profissional_id'] === (string) $user->feegow_profissional_id) selected @endif value="{{ $medico['profissional_id'] }}" data-nome="{{ $medico['profissional_nome'] }}">{{ $medico['profissional_nome'] }}</option>
                            @endforeach
                        </select>
                        <label for="feegow_profissional_id">Médico FEEGOW:</label>
                        <input type="hidden" id="feegow_profissional_nome" name="feegow_profissional_nome" value="{{ $user->feegow_profissional_nome }}"/>
                        @if(empty($medicos))
                            <small class="text-danger">Não foi possível carregar a lista de médicos da FEEGOW.</small>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="file" id="imagem" name="imagem"/>
                        <label for='imagem'>Imagem Perfil:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="file" id="imagem_carimbo" name="imagem_carimbo"/>
                        <label for='imagem'>Certificado Digital:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="senha_certificado" name="senha_certificado" value="{{ $user->senha_certificado }}"/>
                        <label for='senha_certificado'>Certificado Senha:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="coren" name="coren" value="{{ $user->coren }}"/>
                        <label for='coren'>Coren:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="st_usuario" name='st_usuario' class="select2 form-select">
                            <option @if($user->st_usuario == "Ativo") selected @endif value="Ativo">Ativo</option>
                            <option @if($user->st_usuario == "Inativo") selected @endif value="Inativo">Inativo</option>
                        </select>
                        <label for="st_usuario">Status Usuário:</label>
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
<script>
window.addEventListener('load', function(){
    var tipo = document.getElementById('tipo');
    var wrap = document.getElementById('wrap_feegow_profissional');
    var select = document.getElementById('feegow_profissional_id');
    var nome = document.getElementById('feegow_profissional_nome');

    function syncNome(){
        var opcao = select.options[select.selectedIndex];
        nome.value = (opcao && opcao.value) ? (opcao.dataset.nome || '') : '';
    }

    function atualizarVisibilidade(){
        var isMedico = tipo.value === 'Médico';
        wrap.style.display = isMedico ? '' : 'none';
        select.required = isMedico;
        if(!isMedico){
            select.value = '';
            syncNome();
        }
    }

    tipo.addEventListener('change', atualizarVisibilidade);
    select.addEventListener('change', syncNome);
    atualizarVisibilidade();
});
</script>
@endsection
