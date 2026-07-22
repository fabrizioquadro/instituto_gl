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
elseif($procedimento->situacao == "Pendente"){
    $situacao = '<span class="badge rounded-pill bg-label-warning">Pendente</span>';
}
elseif($procedimento->situacao == "Semana Sem Aplicação"){
    $situacao = '<span class="badge rounded-pill bg-label-secondary">Semana Sem Aplicação</span>';
}
else{
    $situacao = '<span class="badge rounded-pill bg-label-secondary">'.$procedimento->situacao.'</span>';
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
                <label for="">Médico:</label><br>
                <strong>{{ $procedimento->medico }}</strong>
            </div>
            <div class="col-md-2 form-group">
                <label for="">Situação:</label><br>
                {!! $situacao !!}
            </div>
            <div class="col-md-2 form-group">
                <label for="">Data Aplicação:</label><br>
                <strong>{{ dataDbForm($procedimento->data_aplicacao) }}</strong>
                <button type="button" class="btn btn-sm btn-icon waves-effect" data-bs-toggle="modal" data-bs-target="#modal_editar_datas">
                    <i class="mdi mdi-pencil-outline"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Financeiro</h4>
        </div>
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
        <form action="{{ route('sistema.procedimentos.adicionar_anexos') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
            <div class="row mt-2 gy-4 align-items-end mb-3">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="file" multiple id="anexos" name="anexos[]"/>
                        <label for="anexos">Anexos:</label>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Adicionar</button>
                </div>
            </div>
        </form>
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
            <div>
                <button type="button" onclick="adicionar_medicamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                    <span class="tf-icons mdi mdi-plus me-1"></span>
                    Medicamento
                </button>
                <button type="button" onclick="adicionar_combo()" class="btn btn-sm rounded-pill btn-outline-info waves-effect">
                    <span class="tf-icons mdi mdi-plus me-1"></span>
                    Combos
                </button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label for="">Obs Prévia:</label><br>
                <b>{{ $procedimento->obs }}</b>
            </div>
        </div>
        <div class="table-responsive mt-3" style="min-height: 150px">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th></th>
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
                <tbody id="tabela_aplicacao_aplicacao">
                    @foreach($procedimento->aplicacaos as $aplicacao)
                        @php
                        $dt_aplicacao = null;
                        if($aplicacao->lote){
                            $var = explode(' ',$aplicacao->lote->created_at);
                            $dt_aplicacao = dataDbForm($var[0]);
                        }
                        @endphp
                        <tr id="tabela_aplicacao_aplicacao_linha_{{ $aplicacao->id }}">
                            <td>
                                @if($aplicacao->situacao != "Aplicada")
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu" data-popper-placement="bottom-end">
                                            <button onclick="editar_aplicacao({{ $aplicacao->id }})" class="dropdown-item waves-effect"><i class="mdi mdi-pencil me-1"></i> Editar</button>
                                            @if(session()->has('administrador'))
                                            <button onclick="excluir_aplicacao({{ $aplicacao->id }})" class="dropdown-item waves-effect"><i class="mdi mdi-delete me-1"></i> Excluir</button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $aplicacao->medicamento->nome }}</td>
                            <td>{{ $aplicacao->medicamento->unidade }}</td>
                            <td>{{ $aplicacao->quantidade }}</td>
                            <td>R$ {{ valorDbForm($aplicacao->valor) }}</td>
                            <td>R$ {{ valorDbForm($aplicacao->total) }}</td>
                            <td>{{ $aplicacao->obs }}</td>
                            <td>{{ $aplicacao->situacao }}</td>
                            <td>{{ $dt_aplicacao }}</td>
                            <td>{{ $aplicacao->lotes() }}</td>
                            <td>{!! $aplicacao->codigos() !!}</td>
                            <td>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '' }}</td>
                        </tr>
                    @endforeach
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
                    elseif($proc->situacao == "Semana Sem Aplicação"){
                        $situacao = '<span class="badge rounded-pill bg-label-secondary">Semana Sem Aplicação</span>';
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

<div class="modal fade" id="modal_editar_aplicacao" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="post">
            <input type="hidden" id="modal_editar_aplicacao_id">
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Editar Aplicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mt-3">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Quantidade</th>
                                <th>Valor</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select onchange="set_valor_medicamento()" id="modal_editar_aplicacao_medicamento_id" class="form-control">
                                        <option>Opções</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input onblur="modal_editar_aplicacao_calcula_total_medicamento()" id="modal_editar_aplicacao_quantidade" type="text" class="form-control"></td>
                                <td><input onblur="modal_editar_aplicacao_calcula_total_medicamento()" id="modal_editar_aplicacao_valor" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td><input onblur="modal_editar_aplicacao_calcula_total_medicamento()" id="modal_editar_aplicacao_total" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <span>* Esta ação será executada diretamente no banco de dados, não podendo ser desfeita.</span>
                <div class="mb-3 mt-3">
                    <button class="btn btn-primary" type="button" onclick="salvar_edicao_aplicacao()">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal_adicionar_aplicacao" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="post">
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Adicionar Aplicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive mt-3">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Quantidade</th>
                                <th>Valor</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select onchange="set_valor_medicamento_adicionar()" id="modal_adicionar_aplicacao_medicamento_id" class="form-control">
                                        <option>Opções</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input onblur="modal_adicionar_aplicacao_calcula_total_medicamento()" id="modal_adicionar_aplicacao_quantidade" type="text" class="form-control"></td>
                                <td><input onblur="modal_adicionar_aplicacao_calcula_total_medicamento()" id="modal_adicionar_aplicacao_valor" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td><input onblur="modal_adicionar_aplicacao_calcula_total_medicamento()" id="modal_adicionar_aplicacao_total" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning mb-2 mt-3">
                    <h6 class="alert-heading fw-bold mb-1"><i class="mdi mdi-alert-circle-outline me-1"></i>Atenção: Adição de Valores!</h6>
                    <span>A inclusão de novas medicações acarretará na geração de valores adicionais no financeiro. O paciente deverá realizar o pagamento para que as aplicações sejam liberadas na fila de atendimento.</span>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="aceite_cliente_modal" id="aceite_cliente_modal" required>
                    <label class="form-check-label fw-bold text-danger" for="aceite_cliente_modal">
                        Confirmo que informei o paciente sobre o custo adicional destas medicações.
                    </label>
                </div>
                <span>* Esta ação será executada diretamente no banco de dados, não podendo ser desfeita.</span>
                <div class="mb-3 mt-3">
                    <button class="btn btn-primary" type="button" onclick="salvar_adicionar_aplicacao()">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal_adicionar_combo" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{  route('sistema.procedimentos.insert_combo') }}" class="modal-content" method="post">
            @csrf
            <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Adicionar Combo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mt-2 gy-4">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <select required id="combo_id" name="combo_id" class="select2 form-select">
                                <option value="">Opções</option>
                                @foreach($combos as $combo)
                                    <option value="{{ $combo->id }}">{{ $combo->nome }}</option>
                                @endforeach
                            </select>
                            <label for="combo_id">Escolha o Combo para inserir:</label>
                        </div>
                    </div>
                </div>
                <span>* Esta ação será executada diretamente no banco de dados, não podendo ser desfeita.</span>
                <div class="mb-3 mt-3">
                    <button class="btn btn-primary" type="submit">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal_editar_datas" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('sistema.procedimentos.update_data') }}" class="modal-content" method="post">
            @csrf
            <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Editar Data do Procedimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mt-2 gy-4">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <input required class="form-control" type="date" id="data_aplicacao_edit" name="data_aplicacao" value="{{ $procedimento->data_aplicacao }}"/>
                            <label for="data_aplicacao_edit">Nova Data de Aplicação:</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <button class="btn btn-primary" type="submit">Salvar Alteração</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">

var modalEditarAplicacao;
var modalAdicionarAplicacao;
var modalAdicionarCombo;

function adicionar_medicamento(){
    modalAdicionarAplicacao = new bootstrap.Modal(document.getElementById('modal_adicionar_aplicacao'));
    modalAdicionarAplicacao.show();
}

function adicionar_combo(){
    modalAdicionarCombo = new bootstrap.Modal(document.getElementById('modal_adicionar_combo'));
    modalAdicionarCombo.show();
}

function excluir_aplicacao(aplicacao_id){
    if(confirm('Tem certeza que deseja excluir a aplicação? Esta operação não poderá ser desfeita.')){
        $.getJSON(
            "{{ route('sistema.procedimentos.delete_aplicacao') }}",
            {
                aplicacao_id : aplicacao_id
            },
            function(json){
                if(json.controle == 'true'){
                    document.getElementById('tabela_aplicacao_aplicacao_linha_' + aplicacao_id).remove();
                } else {
                    alert(json.erro || 'Erro ao excluir a aplicação.');
                }
            }
        );
    }
}

function editar_aplicacao(aplicacao_id){
    $.getJSON(
        "{{ route('sistema.procedimentos.get_aplicacao') }}",
        {
            aplicacao_id : aplicacao_id
        },
        function(json){
            document.getElementById('modal_editar_aplicacao_id').value = aplicacao_id;
            document.getElementById('modal_editar_aplicacao_medicamento_id').value = json.medicamento_id;
            document.getElementById('modal_editar_aplicacao_quantidade').value = json.quantidade;
            document.getElementById('modal_editar_aplicacao_valor').value = json.valor;
            document.getElementById('modal_editar_aplicacao_total').value = json.total;

            $.getJSON(
                '{{ route("adm.medicamentos.buscar") }}',
                {medicamento_id:json.medicamento_id},
                function(med){
                    document.getElementById("modal_editar_aplicacao_valor").removeAttribute('readonly');
                }
            );

            modalEditarAplicacao = new bootstrap.Modal(document.getElementById('modal_editar_aplicacao'));
            modalEditarAplicacao.show();
        }
    );
}

function set_valor_medicamento(){
    select = document.getElementById("modal_editar_aplicacao_medicamento_id");
    selectedOption = select.options[select.selectedIndex];
    valor = parseFloat(selectedOption.dataset.valor);
    valor = valor.toFixed(2);
    document.getElementById("modal_editar_aplicacao_valor").value = valor.replace('.',',');

    document.getElementById("modal_editar_aplicacao_valor").removeAttribute('readonly');

    modal_editar_aplicacao_calcula_total_medicamento();
}

function set_valor_medicamento_adicionar(){
    select = document.getElementById("modal_adicionar_aplicacao_medicamento_id");
    selectedOption = select.options[select.selectedIndex];
    valor = parseFloat(selectedOption.dataset.valor);
    valor = valor.toFixed(2);
    document.getElementById("modal_adicionar_aplicacao_valor").value = valor.replace('.',',');

    document.getElementById("modal_adicionar_aplicacao_valor").removeAttribute('readonly');

    modal_adicionar_aplicacao_calcula_total_medicamento();
}

function modal_editar_aplicacao_calcula_total_medicamento(){
    medicamento_id = document.getElementById('modal_editar_aplicacao_medicamento_id').value;

    $.getJSON(
        '{{ route("adm.medicamentos.buscar") }}',
        {
            medicamento_id : medicamento_id
        },
        function(json){
            let valorInput = document.getElementById("modal_editar_aplicacao_valor");
            let valorDigitado = valorInput.value;
            if(valorDigitado){
                valorDigitado = valorDigitado.replace(/\./g,'').replace(',','.');
                valorDigitado = parseFloat(valorDigitado);
                let valorTabela = parseFloat(json.vl_venda);
                if(valorDigitado < valorTabela){
                    alert('O valor do medicamento não pode ser menor do que o preço de tabela (R$ ' + json.vl_venda.replace('.', ',') + ').');
                    valorInput.value = json.vl_venda.replace('.', ',');
                }
            }

            if(json.unidade == 'Ampola'){
                quantidade = Math.ceil(parseFloat(document.getElementById("modal_editar_aplicacao_quantidade").value));
            }
            else{
                quantidade = parseFloat(document.getElementById("modal_editar_aplicacao_quantidade").value);
            }

            valor = document.getElementById("modal_editar_aplicacao_valor").value;
            if(quantidade && valor){
                valor = valor.replace(/\./g,'');
                valor = parseFloat(valor.replace(',','.'));
                total = quantidade * valor;
                total = total.toFixed(2);
                document.getElementById('modal_editar_aplicacao_total').value = total.replace('.',',');
            }
        }
    );
}

function modal_adicionar_aplicacao_calcula_total_medicamento(){
    medicamento_id = document.getElementById('modal_adicionar_aplicacao_medicamento_id').value;

    $.getJSON(
        '{{ route("adm.medicamentos.buscar") }}',
        {
            medicamento_id : medicamento_id
        },
        function(json){
            let valorInput = document.getElementById("modal_adicionar_aplicacao_valor");
            let valorDigitado = valorInput.value;
            if(valorDigitado){
                valorDigitado = valorDigitado.replace(/\./g,'').replace(',','.');
                valorDigitado = parseFloat(valorDigitado);
                let valorTabela = parseFloat(json.vl_venda);
                if(valorDigitado < valorTabela){
                    alert('O valor do medicamento não pode ser menor do que o preço de tabela (R$ ' + json.vl_venda.replace('.', ',') + ').');
                    valorInput.value = json.vl_venda.replace('.', ',');
                }
            }

            if(json.unidade == 'Ampola'){
                quantidade = Math.ceil(parseFloat(document.getElementById("modal_adicionar_aplicacao_quantidade").value));
            }
            else{
                quantidade = parseFloat(document.getElementById("modal_adicionar_aplicacao_quantidade").value);
            }

            valor = document.getElementById("modal_adicionar_aplicacao_valor").value;
            if(quantidade && valor){
                valor = valor.replace(/\./g,'');
                valor = parseFloat(valor.replace(',','.'));
                total = quantidade * valor;
                total = total.toFixed(2);
                document.getElementById('modal_adicionar_aplicacao_total').value = total.replace('.',',');
            }
        }
    );
}

function salvar_edicao_aplicacao(){
    aplicacao_id = document.getElementById('modal_editar_aplicacao_id').value;
    medicamento_id = document.getElementById('modal_editar_aplicacao_medicamento_id').value;
    quantidade = document.getElementById('modal_editar_aplicacao_quantidade').value;
    valor = document.getElementById('modal_editar_aplicacao_valor').value;
    total = document.getElementById('modal_editar_aplicacao_total').value;

    $.getJSON(
        "{{ route('sistema.procedimentos.update_aplicacao') }}",
        {
            aplicacao_id : aplicacao_id,
            medicamento_id : medicamento_id,
            quantidade : quantidade,
            valor : valor,
            total : total
        },
        function(json){
            document.getElementById('tabela_aplicacao_aplicacao_linha_' + aplicacao_id).innerHTML = json.html;
            modalEditarAplicacao.hide();
        }
    );
}

function salvar_adicionar_aplicacao(){
    medicamento_id = document.getElementById('modal_adicionar_aplicacao_medicamento_id').value;
    quantidade = document.getElementById('modal_adicionar_aplicacao_quantidade').value;
    valor = document.getElementById('modal_adicionar_aplicacao_valor').value;
    total = document.getElementById('modal_adicionar_aplicacao_total').value;
    
    if(!document.getElementById('aceite_cliente_modal').checked){
        alert('Você deve confirmar que informou o paciente sobre o custo adicional destas medicações.');
        return false;
    }

    if(medicamento_id != "" && quantidade != "" && valor != "" && total != ""){
        $.getJSON(
            "{{ route('sistema.procedimentos.insert_aplicacao') }}",
            {
                procedimento_id : {{ $procedimento->id }},
                medicamento_id : medicamento_id,
                quantidade : quantidade,
                valor : valor,
                total : total
            },
            function(json){
                tr = document.createElement('tr');
                tr.setAttribute('id', 'tabela_aplicacao_aplicacao_linha_' + json.aplicacao_id);
                tr.innerHTML = json.html;
                document.getElementById('tabela_aplicacao_aplicacao').appendChild(tr);
                modalAdicionarAplicacao.hide();
            }
        );
    }
    else{
        alert('É necessario preencher todos os campos');
    }
}
</script>

@endsection
