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
<form action="{{ route('sistema.dashboard.set_aplicacao') }}" method="post">
    <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
    @csrf
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h4 class="card-title">Procedimento New</h4>
                <button type="button" id="botao_abrir_frasco" class="btn btn-label-primary waves-effect">
                    <span class="tf-icons mdi mdi-medication-outline me-1"></span>
                    Abrir Frasco
                </button>
            </div>
            <div class="row mt-2 gy-4">
                <div class="col-md-4 form-group">
                    <label for="">Paciente:</label><br>
                    <strong>{{ $procedimento->paciente->nm_paciente }}</strong>
                </div>
                <div class="col-md-4 form-group">
                    <label for="">Nascimento:</label><br>
                    <strong>{{ str_replace('-','/',$nascimento) }}</strong>
                </div>
                <div class="col-md-4 form-group">
                    <label for="">Médico:</label><br>
                    <strong>{{ $procedimento->medico }}</strong>
                </div>
                <div class="col-md-6 form-group">
                    <label for="">Data Aplicação:</label><br>
                    <strong>{{ dataDbForm($procedimento->data_aplicacao) }}</strong>
                </div>
                <div class="col-md-6 form-group">
                    <label for="">Numero Procedimento:</label><br>
                    <strong>{{ $procedimento->nr_procedimento }}</strong>
                </div>
                <div class="col-md-12 form-group">
                    <label for="">Observação Prévia:</label><br>
                    <strong> - {{ $procedimento->obs }}</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h4 class="card-title">Anexos</h4>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 mb-4 mb-xl-0">
                    <div class="demo-inline-spacing mt-3">
                        <div class="list-group">
                            @if($procedimento->anexos->count() == 0)
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
                            @foreach($procedimento->anexos as $arquivo)
                                <div class="list-group-item list-group-item-action d-flex align-items-center waves-effect" style='cursor: default !important'>
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between">
                                            <div class="user-info">
                                                <a target="_blank" href="/public/procedimentos/{{ $procedimento->id }}/anexos/{{ $arquivo->anexo }}">
                                                    <h6 class="mt-2 mb-0">{{ $arquivo->nm_anexo }}</h6>
                                                </a>
                                            </div>
                                            {{--
                                            <div class="add-btn">
                                                <button onclick="excluir_arquivo({{ $arquivo->id }})" class="btn btn-danger btn-sm waves-effect waves-light">Excluir</button>
                                            </div>
                                            --}}
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
    @if($procedimento->nr_procedimento > 1)
        @php
        $nr_proc_ant = $procedimento->nr_procedimento - 1;
        $proc_anterior = App\Models\Procedimento::where('codigo', $procedimento->codigo)
        ->where('nr_procedimento', $nr_proc_ant)
        ->where('semana_sem_aplicacao', 'Não')
        ->first();
        @endphp
        @if($proc_anterior->situacao == "Aplicado")
            <div class="card card-border-shadow-primary mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h4 class="card-title">Procedimento Anterior</h4>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Unidade</th>
                                    <th>Quantidade</th>
                                    <th>Valor</th>
                                    <th>Total</th>
                                    <th>Situação</th>
                                    <th>Lote Aplicação</th>
                                    <th>C.Barras</th>
                                    <th>Vencimento</th>
                                    <th>Enfermagem</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($proc_anterior->aplicacaos as $aplicacao)
                                    @php
                                    $obs_anterior = $aplicacao->obs;
                                    $estoque = App\Models\Estoque::where('medicamento_id', $aplicacao->medicamento->id)
                                    ->where('lote', $aplicacao->lote->lote)
                                    ->first();
                                    @endphp
                                    <tr>
                                        <th>{{ $aplicacao->medicamento->nome }}</th>
                                        <th>{{ $aplicacao->medicamento->unidade }}</th>
                                        <th>{{ $aplicacao->quantidade }}</th>
                                        <th>R$ {{ valorDbForm($aplicacao->valor) }}</th>
                                        <th>R$ {{ valorDbForm($aplicacao->total) }}</th>
                                        <th>{{ $aplicacao->situacao }}</th>
                                        <th>{!! $aplicacao->lotes() !!}</th>
                                        <th>{!! $aplicacao->codigos() !!}</th>
                                        <th>{{ $estoque ? dataDbForm($estoque->dt_vencimento) : '' }}</th>
                                        <th>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '' }}</th>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12 form-group">
                            <label for="">Obs Aplicação:</label><br>
                            <b>{{ $obs_anterior }}</b>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    @if($procedimento->st_biopedancia == 'Sim')
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title">Biopedância</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <textarea class="form-control h-px-100" id="obs_biopedancia" name="obs_biopedancia"></textarea>
                            <label for="obs_biopedancia">Obs Biopedância:</label>
                        </div>
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
                        <div class="form-floating form-floating-outline mb-4">
                            <textarea class="form-control h-px-100" id="obs_coleta" name="obs_coleta"></textarea>
                            <label for="obs_coleta">Obs Coleta:</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="tp_coleta" name='tp_coleta' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Coleta Reduzida">Coleta Reduzida</option>
                            <option value="Coleta Copleta">Coleta Copleta</option>
                            <option value="Coleta Retorno">Coleta Retorno</option>
                            <option value="Coleta Reduzida 2">Coleta Reduzida 2</option>
                            <option value="Coleta Particular">Coleta Particular</option>
                        </select>
                        <label for="tp_coleta">Tipo Coleta:</label>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if($procedimento->aplicacaos->count() > 0)
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title">Aplicações</h4>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline mb-4">
                            <textarea class="form-control h-px-100" id="obs_aplicacao" name="obs_aplicacao"></textarea>
                            <label for="obs_aplicacao">Obs Aplicação:</label>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Pendente</th>
                                <th>Medicamento</th>
                                <th>Unidade</th>
                                <th>Quant</th>
                                <th>Codigo</th>
                                <th>Lote</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($procedimento->aplicacaos as $aplicacao)
                                <tr>
                                    <td>
                                        @if($aplicacao->situacao == "Aberta" || $aplicacao->situacao == 'Pendente')
                                            <input class="form-check-input" data-medicamento="{{ $aplicacao->medicamento->unidade }}" type="checkbox" value="Sim" onclick="controle_pendente({{ $aplicacao->medicamento->id }})" name="controle_pendente_{{ $aplicacao->medicamento->id }}" id="controle_pendente_{{ $aplicacao->medicamento->id }}"></td>
                                        @endif
                                    <td>{{ $aplicacao->medicamento->nome }}</td>
                                    <td>{{ $aplicacao->medicamento->unidade }}</td>
                                    <td>{{ $aplicacao->quantidade }}</td>
                                    @if($aplicacao->situacao == "Aberta" || $aplicacao->situacao == 'Pendente')
                                        @if($aplicacao->medicamento->unidade == "Ampola")
                                            <td><input required onblur="busca_lote_por_codigo(this,{{ $aplicacao->medicamento->id }}, {{ $user->clinica_id }})" type="text" name="codigo_barras_{{ $aplicacao->medicamento->id }}" id="codigo_barras_{{ $aplicacao->medicamento->id }}" class="form-control"></td>
                                            <td><input required readonly type="text" class="form-control" name="lote_{{ $aplicacao->medicamento->id }}" id="lote_{{ $aplicacao->medicamento->id }}"></td>
                                            <td></td>
                                        @else
                                            <td id="td_aplicacao_codigo_{{ $aplicacao->medicamento->id }}"><input required onblur="busca_lote_por_codigo_frasco(this,{{ $aplicacao->medicamento->id }}, {{ $user->clinica_id }}, {{ $aplicacao->quantidade }})" type="text" name="codigo_barras_{{ $aplicacao->medicamento->id }}" id="codigo_barras_{{ $aplicacao->medicamento->id }}" class="form-control"></td>
                                            <td id="td_aplicacao_lote_{{ $aplicacao->medicamento->id }}"><input required readonly type="text" class="form-control" name="lote_{{ $aplicacao->medicamento->id }}" id="lote_{{ $aplicacao->medicamento->id }}"></td>
                                            <td>
                                                <button title="Aplicação com 2 codigo" onclick="abre_modal_2_codigo({{ $aplicacao->medicamento->id }})" type="button" class="btn rounded-pill btn-icon btn-outline-secondary waves-effect">
                                                    <span class="tf-icons mdi mdi-numeric-2-box"></span>
                                                </button>
                                            </td>
                                        @endif
                                    @else
                                        <td>{{$aplicacao->lotes()}}</td>
                                        <td>{{$aplicacao->codigos()}}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    <div class="row mt-4 mb-4">
        <div class="col-md-6 form-group">
            <button type="submit" class="btn btn-primary me-2">Registrar Aplicação</button>
        </div>
    </div>
</form>

@if($procedimentos_vinculados->count() > 0)
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
                            $situacao = '<span class="badge rounded-pill bg-label-danger">Atendimento</span>';
                        }
                        elseif($proc->situacao == "Aplicado"){
                            $situacao = '<span class="badge rounded-pill bg-label-success">Aplicado</span>';
                        }
                        elseif($proc->situacao == "Pendente"){
                            $situacao = '<span class="badge rounded-pill bg-label-warning">Pendente</span>';
                        }
                        else{
                            $situacao = '<span class="badge rounded-pill bg-label-secondary">'.$proc->situacao.'</span>';
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
@endif
<script>
var modalAbrirFrasco;
var modal2Codigo;

function busca_lote_por_codigo(e, medicamento_id, clinica_id){
    if(e.value){
        $.getJSON(
            '{{ route("sistema.dashboard.busca_lote_por_codigo") }}',
            {
                codigo : e.value
            },
            function(json){
                document.getElementById('lote_' + medicamento_id).value = json.lote;
            }
        );
    }
}

function busca_lote_por_codigo_frasco(e, medicamento_id, clinica_id, quantidade){
    if(e.value){
        $.getJSON(
            '{{ route("sistema.dashboard.busca_lote_por_codigo_frasco") }}',
            {
                codigo : e.value,
                quantidade : quantidade,
                medicamento_id : medicamento_id
            },
            function(json){
                console.log(json);
                if(json.controle == 'true'){
                    document.getElementById('lote_' + medicamento_id).value = json.lote;
                }
                else{
                    alert(json.mensagem);
                    document.getElementById('codigo_barras_' + medicamento_id).value = '';
                    document.getElementById('lote_' + medicamento_id).value = '';
                    document.getElementById('codigo_barras_' + medicamento_id).focus();
                }
            }
        );
    }
}

function busca_lote_por_codigo_frasco_2codigo(numero){
    codigo = document.getElementById('modal2Codigo_codigo_' + numero).value,
    quantidade = document.getElementById('modal2Codigo_quantidade_' + numero).value,
    medicamento_id = document.getElementById('modal2Codigo_medicamento_id').value
    if(codigo != "" && quantidade != "" && medicamento_id != ""){
        $.getJSON(
            '{{ route("sistema.dashboard.busca_lote_por_codigo_frasco") }}',
            {
                codigo : codigo,
                quantidade : quantidade,
                medicamento_id : medicamento_id
            },
            function(json){
                console.log(json);
                if(json.controle == 'true'){
                    document.getElementById('modal2Codigo_lote_' + numero).value = json.lote;
                }
                else{
                    alert(json.mensagem);
                    document.getElementById('modal2Codigo_codigo_' + numero).value = '';
                    document.getElementById('modal2Codigo_lote_' + numero).value = '';
                    document.getElementById('modal2Codigo_codigo_' + numero).focus();
                }
            }
        );
    }
}

function controle_pendente(medicamento_id){
    if(document.getElementById('controle_pendente_' + medicamento_id).checked == true){
        //document.getElementById('lote_' + medicamento_id).setAttribute('disabled','disabled');
        document.getElementById('lote_' + medicamento_id).removeAttribute('required');
        document.getElementById('codigo_barras_' + medicamento_id).removeAttribute('required');
        //if(document.getElementById('controle_pendente_' + medicamento_id).dataset.medicamento == "Ampola"){
        //    document.getElementById('codigo_barras_' + medicamento_id).setAttribute('disabled','disabled');
        //}
    }
    else{
        document.getElementById('lote_' + medicamento_id).setAttribute('required','required');
        document.getElementById('codigo_barras_' + medicamento_id).setAttribute('required','required');
        //document.getElementById('lote_' + medicamento_id).removeAttribute('disabled');
        //if(document.getElementById('controle_pendente_' + medicamento_id).dataset.medicamento == "Ampola"){
        //    document.getElementById('codigo_barras_' + medicamento_id).removeAttribute('disabled');
        //}
    }
}

</script>

<div class="modal fade" id="modal_abrir_frasco" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form action="{{ route('sistema.dashboard.abrir_frasco') }}" class="modal-content" method="post">
            @csrf
            <input type="hidden" id="procedimento_id" name="procedimento_id" value='{{ $procedimento->id }}'>
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Abrir Frasco</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mt-2 gy-4 align-items-end">
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <select onchange='modal_get_lotes_medicamento(this)' required id="modal_medicamento_id" name='medicamento_id' class="select2 form-select">
                                <option value="">Opções</option>
                                @foreach($procedimento->aplicacaos as $aplicacao)
                                    @if($aplicacao->medicamento->unidade == 'Miligrama')
                                        <option value="{{ $aplicacao->medicamento->id }}">{{ $aplicacao->medicamento->nome }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <label for="modal_medicamento_id">Medicamento:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <select required id="modal_codigo_barras" name='codigo_barras' class="select2 form-select">
                                <option value="">Opções</option>
                            </select>
                            <label for="modal_codigo_barras">Codigo de Barra:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-secondary" type="submit" onclick="gera_procedimentos_gerador()">Abrir</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('botao_abrir_frasco').addEventListener('click', ()=>{
    modalAbrirFrasco = new bootstrap.Modal(document.getElementById('modal_abrir_frasco'));
    modalAbrirFrasco.show();
})

function modal_get_lotes_medicamento(e){
    if(e.value){
        $.getJSON(
            "{{ route('sistema.dashboard.get_lotes_medicamento_mg') }}",
            {
                medicamento_id : e.value
            },
            function(json){
                //console.log(json);
                document.getElementById('modal_codigo_barras').innerHTML = json.codigos;
            }
        );
    }
}

function abre_modal_2_codigo(id){
    document.getElementById('modal2Codigo_medicamento_id').value = id;
    modal2Codigo = new bootstrap.Modal(document.getElementById('modal_2_codigo'));
    modal2Codigo.show();
}

</script>

<div class="modal fade" id="modal_2_codigo" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post">
            <input type="hidden" id="modal2Codigo_medicamento_id">
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Aplicação com 2 Códigos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="responsive">
                    <table class="table">
                        <thead class="table-light">
                            <tr>
                                <th>Quantidade</th>
                                <th>Codigo</th>
                                <th>Lote</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="text" id='modal2Codigo_quantidade_1' class="form-control">
                                </td>
                                <td>
                                    <input onblur="busca_lote_por_codigo_frasco_2codigo(1)" type="text" id='modal2Codigo_codigo_1' class="form-control">
                                </td>
                                <td>
                                    <input readonly type="text" id='modal2Codigo_lote_1' class="form-control">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" id='modal2Codigo_quantidade_2' class="form-control">
                                </td>
                                <td>
                                    <input onblur="busca_lote_por_codigo_frasco_2codigo(2)" type="text" id='modal2Codigo_codigo_2' class="form-control">
                                </td>
                                <td>
                                    <input readonly type="text" id='modal2Codigo_lote_2' class="form-control">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </table>
                <div class="row mt-2 gy-4 align-items-end">
                    <div class="col-md-4">
                        <button class="btn btn-secondary" type="button" id="modal2Codigo_salvar">Salvar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
document.getElementById('modal2Codigo_salvar').addEventListener('click', ()=>{
    quantidade1 = document.getElementById('modal2Codigo_quantidade_1').value;
    quantidade2 = document.getElementById('modal2Codigo_quantidade_2').value;
    codigo1 = document.getElementById('modal2Codigo_codigo_1').value;
    codigo2 = document.getElementById('modal2Codigo_codigo_2').value;
    lote1 = document.getElementById('modal2Codigo_lote_1').value;
    lote2 = document.getElementById('modal2Codigo_lote_2').value;
    medicamento_id = document.getElementById('modal2Codigo_medicamento_id').value;

    if(quantidade1 != "" && quantidade2 != "" && codigo1 != "" && codigo2 != "" && lote1 != "" && lote2 != ""){
        input_controle = document.createElement('input');
        input_controle.setAttribute('type', 'hidden');
        input_controle.setAttribute('name', 'controle_med_' + medicamento_id);
        input_controle.setAttribute('value', '2_codigo');

        input_qtd_1 = document.createElement('input');
        input_qtd_1.setAttribute('type', 'hidden');
        input_qtd_1.setAttribute('name', 'quant_med_1_' + medicamento_id);
        input_qtd_1.setAttribute('value', quantidade1);

        input_qtd_2 = document.createElement('input');
        input_qtd_2.setAttribute('type', 'hidden');
        input_qtd_2.setAttribute('name', 'quant_med_2_' + medicamento_id);
        input_qtd_2.setAttribute('value', quantidade2);

        input_cod_1 = document.createElement('input');
        input_cod_1.setAttribute('type', 'hidden');
        input_cod_1.setAttribute('name', 'cod_med_1_' + medicamento_id);
        input_cod_1.setAttribute('value', codigo1);

        input_cod_2 = document.createElement('input');
        input_cod_2.setAttribute('type', 'hidden');
        input_cod_2.setAttribute('name', 'cod_med_2_' + medicamento_id);
        input_cod_2.setAttribute('value', codigo2);

        descricao = "Codigo: " + codigo1 + ", Quantidade: " + quantidade1 + "<br>Codigo: " + codigo2 + ", Quantidade: " + quantidade2;
        descricao_lote = "Lote: " + lote1 + "<br>Lote: " + lote2;

        document.getElementById('td_aplicacao_codigo_' + medicamento_id).innerHTML = descricao;
        document.getElementById('td_aplicacao_lote_' + medicamento_id).innerHTML = descricao_lote;
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_controle);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_qtd_1);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_qtd_2);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_cod_1);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_cod_2);


        modal2Codigo.hide();

    }
    else{
        alert('É necessário preencher todos os campos');
    }
});
</script>
@endsection
