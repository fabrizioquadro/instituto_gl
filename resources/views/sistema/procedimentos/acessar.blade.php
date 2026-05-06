@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

@if($mensagem = Session::get('mensagem'))
    <div class="alert alert-success alert-dismissible mt-3" role="alert">
        {{ $mensagem }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($mensagem = Session::get('mensagem_erro'))
    <div class="alert alert-danger alert-dismissible mt-3" role="alert">
        {{ $mensagem }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($procedimento->st_pagamento != 'Sim' && $procedimento->valor > 0)
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Pagamento - Valor Procedimento: R${{ valorDbForm($procedimento->valor) }}</h4>
        </div>
        @if($financeiro->vl_consulta_pagamento < $financeiro->vl_consulta)
            <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                Valor pendente da consulta de R$ {{ valorDbform($financeiro->vl_consulta - $financeiro->vl_consulta_pagamento) }}
            </div>
        @endif
        <form id="formulario_pagamento" action="{{ route('sistema.procedimentos.setar_pagamento') }}" method="post">
            @csrf
            <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
            <input type="hidden" name="retorno" value="{{ $retorno }}">
            <input type="hidden" name="contador_formas" id="contador_formas" value="1">
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="obs_pagamento" name="obs_pagamento"/>
                        <label for="obs_pagamento">Obs Pagamento:</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <h6 class="card-title">Forma de Pagamento</h6>
                <button type="button" onclick="adicionar_forma()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                    <span class="tf-icons mdi mdi-plus me-1"></span>
                    Forma Pgt
                </button>
            </div>
            <div class="table-responsive mt-3" style="min-height: 50px !important">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Numero</th>
                            <th>Forma Pagamento</th>
                            <th>Parcelas</th>
                            <th>ID Pagamento / DOC</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabela_formas">
                        <tr id="linha_forma_1">
                            <td>1</td>
                            <td>
                                <select required id="forma_pagamento_1" onchange="controle_parcelas(1)" name='forma_pagamento_1' class="form-control">
                                    <option value="">Opções</option>
                                    <option value="Dinheiro">Dinheiro</option>
                                    <option value="Débito">Débito</option>
                                    <option value="Crédito">Crédito</option>
                                    <option value="Pix">Pix</option>
                                </select>
                            </td>
                            <td>
                                <select disabled id="parcelas_1" name='parcelas_1' class="form-control">
                                    <option value="">Opções</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                </select>
                            </td>
                            <td><input class="form-control" type="text" id="id_pagamento_1" name="id_pagamento_1"/></td>
                            <td><input required class="form-control valor" type="text" id="vl_pagamento_1" name="vl_pagamento_1" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/></td>
                            <td>
                                <button title="Excluir Forma de Pagamento" onclick="excluir_forma(1)" type="button" class="btn btn-icon btn-outline-danger waves-effect">
                                    <span class="tf-icons mdi mdi-delete"></span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-md-6 form-group">
                    <button type="button" id='botao_setar_pagamento' class="btn btn-sm btn-primary me-2">Setar Pagamento</button>
                    @if($procedimento->st_pagamento != "Pendente")
                        <button type="button" id='botao_setar_pagamento_pendente' class="btn btn-sm btn-warning me-2">Marcar Como Pendente</button>
                        <script type="text/javascript">
                        document.getElementById('botao_setar_pagamento_pendente').addEventListener('click', ()=>{
                            if(confirm('Tem certeza que deseja marcar o pagamento desse procedimento como pendente?')){
                                window.location.href = "{{ route('sistema.procedimentos.setar_pagamento_pendente', $procedimento->id) }}";
                            }
                        });
                        </script>
                    @endif
                </div>
            </div>
        </form>
        <script>
        document.getElementById('botao_setar_pagamento').addEventListener('click', ()=>{
            document.getElementById('formulario_pagamento').submit();
        });

        function adicionar_forma(){
            contador = parseInt(document.getElementById('contador_formas').value);
            contador++;
            document.getElementById('contador_formas').value = contador;
            tr = document.createElement('tr');
            tr.setAttribute('id', 'linha_forma_' + contador);
            tr.innerHTML = `
                <td>${contador}</td>
                <td>
                    <select required id="forma_pagamento_${contador}" onchange="controle_parcelas(${contador})" name='forma_pagamento_${contador}' class="form-control">
                        <option value="">Opções</option>
                        <option value="Dinheiro">Dinheiro</option>
                        <option value="Débito">Débito</option>
                        <option value="Crédito">Crédito</option>
                        <option value="Pix">Pix</option>
                    </select>
                </td>
                <td>
                    <select disabled id="parcelas_${contador}" name='parcelas_${contador}' class="form-control">
                        <option value="">Opções</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </td>
                <td><input class="form-control" type="text" id="id_pagamento_${contador}" name="id_pagamento_${contador}"/></td>
                <td><input required class="form-control valor" type="text" id="vl_pagamento_${contador}" name="vl_pagamento_${contador}" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/></td>
                <td>
                    <button title="Excluir Forma de Pagamento" onclick="excluir_forma(${contador})" type="button" class="btn btn-icon btn-outline-danger waves-effect">
                        <span class="tf-icons mdi mdi-delete"></span>
                    </button>
                </td>
            `;

            document.getElementById('tabela_formas').appendChild(tr);

        }

        function excluir_forma(linha){
            if(confirm('Tem certeza que deseja excluir a linha de pagamentp?')){
                document.getElementById('linha_forma_' + linha).remove();
            }
        }

        function controle_parcelas(linha){
            if(document.getElementById('forma_pagamento_' + linha).value == "Crédito"){
                document.getElementById('parcelas_' + linha).removeAttribute('disabled');
                document.getElementById('parcelas_' + linha).setAttribute('required','required');
            }
            else{
                document.getElementById('parcelas_' + linha).setAttribute('disabled','disabled');
                document.getElementById('parcelas_' + linha).removeAttribute('required');
            }
        }
        </script>
        <hr>
        <form autocomplete="off" action="{{ route('sistema.procedimentos.enviar_fila_aplicacao_sem_pagamento') }}" method="post">
            @csrf
            <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
            <input type="hidden" name="retorno" value="{{ $retorno }}">
            <div class="row mt-3">
                <div class="col-12">
                    <h6 class="card-title mt-1">Enviar Para Fila de Aplicação</h6>
                    <p>Para esta ação é necessário informar os dados de um administrador para liberação.</p>
                </div>
            </div>
            @if($controle_aviso_coleta == 'ultimo')
                <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                    Último aplicação do conjunto de procedimentos.
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <select required id="consulta_tratamento_agendada" name='consulta_tratamento_agendada' class="select2 form-select">
                                <option value="">Opções</option>
                                <option value="Sim">Sim</option>
                                <option value="Não">Não</option>
                            </select>
                            <label for="consulta_tratamento_agendada">Consulta Tratamento Agendado:</label>
                        </div>
                    </div>
                </div>
            @elseif($controle_aviso_coleta == 'penultimo')
                <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                    Penúltimo aplicação do conjunto de procedimentos.
                </div>
            @endif
            <div class="row align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline mb-3">
                        <input required type="email" class="form-control" name="autorizador_email"/>
                        <label for="autorizador_email">Email:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline mb-3">
                        <input required type="password" class="form-control" name="autorizador_senha"/>
                        <label for="autorizador_senha">Senha:</label>
                    </div>
                </div>
                <div class="col-md-6">
                        <select id="exames_sem_pgt" name='exames' class="select2 form-select" onchange="document.getElementById('campo_obs_retirada_sem_pgt').style.display = this.value.includes('Retirada') ? 'block' : 'none'">
                            <option value="">Opções</option>
                            <option value="Biopedância">Biopedância</option>
                            <option value="Coleta">Coleta</option>
                            <option value="Retirada">Retirada</option>
                            <option value="Biopedância e Coleta">Biopedância e Coleta</option>
                            <option value="Biopedância e Retirada">Biopedância e Retirada</option>
                            <option value="Coleta e Retirada">Coleta e Retirada</option>
                            <option value="Biopedância, Coleta e Retirada">Biopedância, Coleta e Retirada</option>
                        </select>
                        <label for="exames">Exames Adicionais:</label>
                    </div>
                </div>
                <div class="col-md-6" id="campo_obs_retirada_sem_pgt" style="display:none">
                    <div class="form-floating form-floating-outline mb-3">
                        <input type="text" class="form-control" name="obs_retirada" placeholder="Observação da Retirada"/>
                        <input type="hidden" name="retirada" id="retirada_val_sem_pgt" value="Não">
                        <label for="obs_retirada">Obs Retirada:</label>
                    </div>
                </div>
                <script>
                    document.getElementById('exames_sem_pgt').addEventListener('change', function() {
                        document.getElementById('retirada_val_sem_pgt').value = this.value.includes('Retirada') ? 'Sim' : 'Não';
                    });
                </script>
                    <div class="col-md-6 form-group">
                        <button type="submit" class="btn btn-primary me-2">Enviar Para Fila de Aplicação</button>
                    </div>
            </div>
        </form>
    </div>
</div>
@else
<div class="row mt-3 mb-3">
    <div class="col-md-6 form-group">
        @if($procedimento->st_pagamento != "Pendente")
            <button type="button" id='botao_setar_pagamento_pendente' class="btn btn-sm btn-warning me-2">Marcar Como Pendente</button>
            <script type="text/javascript">
            document.getElementById('botao_setar_pagamento_pendente').addEventListener('click', ()=>{
                if(confirm('Tem certeza que deseja marcar o pagamento desse procedimento como pendente?')){
                    window.location.href = "{{ route('sistema.procedimentos.setar_pagamento_pendente', $procedimento->id) }}";
                }
            });
            </script>
        @endif
    </div>
</div>
@endif

@if(($procedimento->st_pagamento == 'Sim' || $procedimento->valor == 0) && ($procedimento->situacao == 'Agendado' || $procedimento->situacao == 'Aplicação Parcial'))
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h4 class="card-title">Procedimento</h4>
            </div>
            <form action="{{ route('sistema.procedimentos.enviar_fila_aplicacao') }}" method="post">
                @csrf
                <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
                <input type="hidden" name="retorno" value="{{ $retorno }}">
                @if($controle_aviso_coleta == 'ultimo')
                    <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                        Último aplicação do conjunto de procedimentos.
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-floating form-floating-outline">
                                <select required id="consulta_tratamento_agendada" name='consulta_tratamento_agendada' class="select2 form-select">
                                    <option value="">Opções</option>
                                    <option value="Sim">Sim</option>
                                    <option value="Não">Não</option>
                                </select>
                                <label for="consulta_tratamento_agendada">Consulta Tratamento Agendado:</label>
                            </div>
                        </div>
                    </div>
                @elseif($controle_aviso_coleta == 'penultimo')
                    <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                        Penúltimo aplicação do conjunto de procedimentos.
                    </div>
                @endif
                <div class="row mt-2 gy-4 align-items-end">
                    <div class="col-md-6">
                            <select id="exames_normal" name='exames' class="select2 form-select" onchange="document.getElementById('campo_obs_retirada_normal').style.display = this.value.includes('Retirada') ? 'block' : 'none'">
                                <option value="">Opções</option>
                                <option value="Biopedância">Biopedância</option>
                                <option value="Coleta">Coleta</option>
                                <option value="Retirada">Retirada</option>
                                <option value="Biopedância e Coleta">Biopedância e Coleta</option>
                                <option value="Biopedância e Retirada">Biopedância e Retirada</option>
                                <option value="Coleta e Retirada">Coleta e Retirada</option>
                                <option value="Biopedância, Coleta e Retirada">Biopedância, Coleta e Retirada</option>
                            </select>
                            <label for="exames">Exames Adicionais:</label>
                        </div>
                    </div>
                    <div class="col-md-6" id="campo_obs_retirada_normal" style="display:none">
                        <div class="form-floating form-floating-outline mb-3">
                            <input type="text" class="form-control" name="obs_retirada" placeholder="Observação da Retirada"/>
                            <input type="hidden" name="retirada" id="retirada_val_normal" value="Não">
                            <label for="obs_retirada">Obs Retirada:</label>
                        </div>
                    </div>
                    <script>
                        document.getElementById('exames_normal').addEventListener('change', function() {
                            document.getElementById('retirada_val_normal').value = this.value.includes('Retirada') ? 'Sim' : 'Não';
                        });
                    </script>
                        <button type="submit" class="btn btn-primary me-2">Enviar Para Fila de Aplicação</button>
                    </div>
                </div>
            </form>
            {{--
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-4 form-group">
                    <a href="{{ route('sistema.procedimentos.enviar_fila_aplicacao', [$procedimento->id,$retorno]) }}" class="btn btn-sm btn-primary">Enviar para Fila de Aplicação</a>
                </div>
            </div>
            --}}
        </div>
    </div>
@endif
@php
if($procedimento->situacao == "Agendado"){
    $situacao = '<span class="badge rounded-pill bg-label-warning">Agendado</span>';
}
elseif($procedimento->situacao == "Fila de Aplicação"){
    $situacao = '<span class="badge rounded-pill bg-label-primary">Fila de Aplicação</span>';
}
elseif($procedimento->situacao == "Atendimento"){
    $situacao = '<span class="badge rounded-pill bg-label-danger">Fila de Aplicação</span>';
}
elseif($procedimento->situacao == "Aplicado"){
    $situacao = '<span class="badge rounded-pill bg-label-success">Aplicado</span>';
}
elseif($procedimento->situacao == "Aplicação Parcial" || $procedimento->situacao == "Pendente"){
    $situacao = '<span class="badge rounded-pill bg-label-warning">Aplicação Parcial</span>';
}
elseif($procedimento->situacao == "Cancelado"){
    $situacao = '<span class="badge rounded-pill bg-label-danger">Cancelado</span>';
}
else{
    $situacao = '<span class="badge rounded-pill bg-label-dark">'.$procedimento->situacao.'</span>';
}

if($procedimento->st_pagamento == 'Sim'){
    $st_pagamento = "<span class='badge bg-success'>$procedimento->st_pagamento</span>";
}
else{
    $st_pagamento = "<span class='badge bg-danger'>$procedimento->st_pagamento</span>";
}

@endphp
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Procedimento</h4>
        </div>
        <div class="row mt-2 gy-4 align-items-end">
            <div class="col-md-3 form-group">
                <label for="">Procedimento:</label><br>
                <strong>{{ $procedimento->codigo }}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Número Procedimento:</label><br>
                <strong>{{ $procedimento->nr_procedimento }}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Data Cadastro:</label><br>
                <strong>{{ dataDbForm($procedimento->data_cad) }}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Clinica Cadastro:</label><br>
                <strong>{{ $procedimento->clinica->nome }}</strong>
            </div>
        </div>
        <div class="row mt-2 gy-4 align-items-end">
            <div class="col-md-3 form-group">
                <label for="">Paciente:</label><br>
                <strong>{{ $procedimento->paciente->nm_paciente }}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label>Médico:</label><br>
                <strong>{{ $procedimento->medico }}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label>Atendimento:</label><br>
                <strong>{{ $procedimento->tipo_atendimento }}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Situação:</label><br>
                {!! $situacao !!}
            </div>
            <div class="col-md-3 form-group">
                <label for="">Data Aplicação:</label><br>
                <strong>{{ dataDbForm($procedimento->data_aplicacao) }}</strong>
            </div>
        </div>
        @if($procedimento->agendamento)
        <div class="row mt-2">
            <div class="col-md-12 form-group">
                <label for="">Agendamento:</label><br>
                <strong>{{ $procedimento->agendamento }}</strong>
            </div>
        </div>
        @endif
        <div class="row mt-2">
            <div class="col-md-12 form-group">
                <label for="">Obs Paciente:</label><br>
                <div class="alert alert-info py-2 mb-0">
                    <strong>{{ $procedimento->paciente->obs ?? 'Sem observações' }}</strong>
                </div>
            </div>
        </div>
        @if($controle_aviso_coleta == 'ultimo')
        <div class="row mt-2 gy-4 align-items-end">
            <div class="col-md-3 form-group">
                <label for="">Consulta Tratamento Agendado:</label><br>
                <strong>{{ $procedimento->consulta_tratamento_agendada }}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Coleta:</label><br>
                <strong>{{ $procedimento->st_coleta }}</strong>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Financeiro</h4>
        </div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label for="">Valor Procedimento:</label><br>
                <b>R$ {{ valorDbForm($procedimento->valor) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Pago + Desconto:</label><br>
                <b>R$ {{ valorDbForm($procedimento->vl_pago) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Pagamento:</label><br>
                <b>{{ $procedimento->st_pagamento }}</b>
            </div>
            <div class="col-md-3 form-group">
                <a href="{{ route('sistema.financeiros.acessar', $financeiro->id) }}" class="btn btn-primary" target="_blank">Acessar Financeiro</a>
            </div>
        </div>
        {{--
        <div class="row mt-2 gy-4">
            <div class="col-md-3 form-group">
                <label for="">Situação Pagamento:</label><br>
                <strong>{!! $st_pagamento !!}</strong>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Data Pagamento:</label><br>
                <strong>{{ dataDbForm($procedimento->data_pagamento) }}</strong>
            </div>
            <div class="col-md-12 form-group">
                <label for="">Obs Pagamento:</label><br>
                <strong>{{ $procedimento->obs_pagamento }}</strong>
            </div>
        </div>
        @if($procedimento->st_pagamento == 'Sim')
            <div class="table-responsive mt-3">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>Forma Pagamento</th>
                            <th>Parcelas</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $i=0;
                        @endphp
                        @foreach($financeiro->formas as $forma)
                            <tr>
                                <td>{{ ++$i }}</td>
                                <td>{{ $forma->forma_pagamento }}</td>
                                <td>{{ $forma->parcelas }}</td>
                                <td>R$ {{ valorDbForm($forma->vl_pagamento) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        --}}
    </div>
</div>
@php
$procedimentos_arqs = App\Models\Procedimento::where('codigo', $procedimento->codigo)->get();
$in = array();
foreach($procedimentos_arqs as $proc){
    $in[] = $proc->id;
}

$arquivos = App\Models\ProcedimentoAnexo::whereIn('procedimento_id', $in)->get();

@endphp
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Anexos</h4>
        </div>
        <div class="row">
            <div class="col-12 col-md-6 mb-4 mb-xl-0">
                <div class="demo-inline-spacing mt-3">
                    <div class="list-group">
                        @if($arquivos->count() == 0)
                            <div class="list-group-item list-group-item-action d-flex align-items-center waves-effect" style='cursor: default !important'>
                                <div class="w-100">
                                    <div class="d-flex justify-content-between">
                                        <div class="user-info">
                                            <h6 class="mt-2 mb-0">Nenhum anexo para este procedimento.</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @foreach($arquivos as $arquivo)
                            <div class="list-group-item list-group-item-action d-flex align-items-center waves-effect" style='cursor: default !important'>
                                <div class="w-100">
                                    <div class="d-flex justify-content-between">
                                        <div class="user-info">
                                            <a target="_blank" href="/public/procedimentos/{{ $arquivo->procedimento_id }}/anexos/{{ $arquivo->anexo }}">
                                                <h6 class="mt-2 mb-0">{{ $arquivo->nm_anexo }}</h6>
                                            </a>
                                            <small class="text-muted">Enviado em: {{ $arquivo->created_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                        <div class="add-btn">
                                            <a href="{{ route('sistema.procedimentos.delete_anexo', $arquivo->id) }}" onclick="return confirm('Tem certeza que deseja excluir este anexo?')" class="btn btn-danger btn-sm waves-effect waves-light">Excluir</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if($procedimento->st_biopedancia == "Sim")
    <div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Biopedância</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p>{{ $procedimento->obs_biopedancia }}</p>
            </div>
        </div>
    </div>
</div>
@endif
@if($procedimento->st_coleta == 'Sim')
    <div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Coleta</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p>{{ $procedimento->obs_coleta }}</p>
            </div>
        </div>
    </div>
</div>
@endif
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Aplicações</h4>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label for="">Obs Prévia:</label><br>
                <b>{{ $procedimento->obs }}</b>
            </div>
        </div>
        <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Medicamento</th>
                        <th>Unidade</th>
                        <th>Quantidade</th>
                        <th>Valor</th>
                        <th>Total</th>
                        <th>Obs</th>
                        <th>Situação</th>
                        <th>Data Aplicação</th>
                        <th>Lote Aplicação</th>
                        <th>C.Barras</th>
                        <th>Enfermagem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($procedimento->aplicacaos as $aplicacao)
                        @php
                        $dt_aplicacao = null;
                        if($aplicacao->lote){
                            $var = explode(' ',$aplicacao->lote->created_at);
                            $dt_aplicacao = dataDbForm($var[0]);
                        }
                        @endphp
                        @include('sistema.dashboard.inc.linha_aplicacao_recepcao', ['aplicacao' => $aplicacao, 'dt_aplicacao' => $dt_aplicacao])
                    @endforeach
                    @if($procedimento->st_biopedancia == 'Sim')
                        <tr>
                            <th>Biopedância</th>
                            <th>-</th>
                            <th>1</th>
                            <th>-</th>
                            <th>-</th>
                            <th>{{ $procedimento->obs_biopedancia }}</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? 'Aplicada' : 'Aberta' }}</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? dataDbForm(explode(' ',$procedimento->dt_hr_finalizacao)[0]) : '-' }}</th>
                            <th>-</th>
                            <th>-</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? ($procedimento->aplicadora ? $procedimento->aplicadora->nome : '') : '-' }}</th>
                        </tr>
                    @endif
                    @if($procedimento->st_coleta == 'Sim')
                        <tr>
                            <th>Coleta ({{ $procedimento->tp_coleta }})</th>
                            <th>-</th>
                            <th>1</th>
                            <th>-</th>
                            <th>-</th>
                            <th>{{ $procedimento->obs_coleta }}</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? 'Aplicada' : 'Aberta' }}</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? dataDbForm(explode(' ',$procedimento->dt_hr_finalizacao)[0]) : '-' }}</th>
                            <th>-</th>
                            <th>-</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? ($procedimento->aplicadora ? $procedimento->aplicadora->nome : '') : '-' }}</th>
                        </tr>
                    @endif
                    @if($procedimento->st_retirada == 'Sim')
                        <tr>
                            <th>Retirada</th>
                            <th>-</th>
                            <th>1</th>
                            <th>-</th>
                            <th>-</th>
                            <th>{{ $procedimento->obs_retirada }}</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? 'Aplicada' : 'Aberta' }}</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? dataDbForm(explode(' ',$procedimento->dt_hr_finalizacao)[0]) : '-' }}</th>
                            <th>-</th>
                            <th>-</th>
                            <th>{{ in_array($procedimento->situacao,['Aplicado','Aplicação Parcial']) ? ($procedimento->aplicadora ? $procedimento->aplicadora->nome : '') : '-' }}</th>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Procedimentos Vinculados</h4>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Dt Cad</th>
                        <th>Paciente</th>
                        <th>Procedimento</th>
                        <th>Numero</th>
                        <th>Médico</th>
                        <th>Dt Aplicação</th>
                        <th>Valor</th>
                        <th>Situação Pg</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                @foreach($procedimentos_vinculados as $proc)
                    @php
                    if($proc->situacao == "Agendado"){
                        $situacao = '<span class="badge rounded-pill bg-label-warning">Agendado</span>';
                    }
                    elseif($proc->situacao == "Fila de Aplicação"){
                        $situacao = '<span class="badge rounded-pill bg-label-primary">Fila de Aplicação</span>';
                    }
                    elseif($proc->situacao == "Atendimento"){
                        $situacao = '<span class="badge rounded-pill bg-label-danger">Fila de Aplicação</span>';
                    }
                    elseif($proc->situacao == "Aplicado"){
                        $situacao = '<span class="badge rounded-pill bg-label-success">Aplicado</span>';
                    }
                    elseif($proc->situacao == "Cancelado"){
                        $situacao = '<span class="badge rounded-pill bg-label-danger">Cancelado</span>';
                    }

                    if($proc->st_pagamento == 'Sim'){
                        $st_pagamento = "<span class='badge bg-success'>$proc->st_pagamento</span>";
                    }
                    else{
                        $st_pagamento = "<span class='badge bg-danger'>$proc->st_pagamento</span>";
                    }

                    @endphp
                    <tr>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu" data-popper-placement="bottom-end">
                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.procedimentos.acessar', $proc->id) }}"><i class="mdi mdi-eye me-1"></i> Acessar</a>
                                </div>
                            </div>
                        </td>
                        <td> <span style='display: none'>{{ strtotime($proc->data_cad) }}</span> {{ dataDbForm($proc->data_cad) }}</td>
                        <td>{{ $proc->paciente->nm_paciente }}</td>
                        <td>{{ $proc->codigo }}</td>
                        <td>{{ $proc->nr_procedimento }}</td>
                        <td>{{ $proc->medico }}</td>
                        <td>{{ dataDbForm($proc->data_aplicacao) }}</td>
                        <td>{{ valorDbForm($proc->valor) }}</td>
                        <td>{!! $st_pagamento !!}</td>
                        <td>{!! $situacao !!}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
<div class="card card-border-shadow-info mb-4">
    <div class="card-body">
        <h4 class="card-title">Histórico de Alterações</h4>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Autor</th>
                        <th>Ação</th>
                        <th>Descrição</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $index => $log)
                        <tr class="{{ $index > 3 ? 'd-none more-logs' : '' }}">
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->autor() }}</td>
                            <td><span class="badge bg-label-info">{{ $log->acao }}</span></td>
                            <td>{{ $log->descricao }}</td>
                            <td>
                                @if($log->dados_novos)
                                    <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#log_{{ $log->id }}">Ver Detalhes</button>
                                    <div class="collapse" id="log_{{ $log->id }}">
                                        <div class="mt-2 text-wrap" style="font-size: 0.8rem; min-width: 200px">
                                            @foreach($log->dados_novos as $campo => $novo)
                                                @php $antigo = $log->dados_antigos[$campo] ?? 'N/A'; @endphp
                                                <strong>{{ ucfirst(str_replace('_', ' ', $campo)) }}:</strong> 
                                                <span class="text-danger"><del>{{ is_array($antigo) ? json_encode($antigo) : $antigo }}</del></span> 
                                                <i class="mdi mdi-arrow-right"></i> 
                                                <span class="text-success">{{ is_array($novo) ? json_encode($novo) : $novo }}</span><br>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($logs->count() > 4)
                <div class="text-center mt-2">
                    <button type="button" id="btn-ver-mais-logs" class="btn btn-sm btn-outline-info" onclick="document.querySelectorAll('.more-logs').forEach(el => el.classList.remove('d-none')); this.style.display='none'">
                        Ver mais ({{ $logs->count() - 4 }})
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
