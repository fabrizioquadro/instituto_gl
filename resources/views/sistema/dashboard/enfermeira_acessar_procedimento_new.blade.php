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
<form action="{{ route('sistema.dashboard.set_aplicacao') }}" method="post" id="formulario_aplicacao">
    <input type="hidden" name="procedimento_id" value="{{ $procedimento->id }}">
    @csrf
    @isset($visualizar)
        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
            <span class="alert-icon mdi mdi-information-outline me-2"></span>
            <div>
                <strong>Modo de Visualização:</strong> Você está apenas visualizando os dados. Nenhuma alteração será salva e o paciente não foi vinculado ao seu usuário.
            </div>
        </div>
    @endisset
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h4 class="card-title">Procedimento New</h4>
                <div>
                    <a href="{{ route('sistema.procedimentos.imprimir_cadastro', $procedimento->codigo) }}" target="_blank" class="btn btn-label-info waves-effect me-2">
                        <span class="tf-icons mdi mdi-folder-open me-1"></span>
                        Visualizar Prontuário Completo
                    </a>
                    @empty($visualizar)
                        <button type="button" id="botao_abrir_frasco" class="btn btn-label-primary waves-effect">
                            <span class="tf-icons mdi mdi-medication-outline me-1"></span>
                            Abrir Frasco
                        </button>
                    @endempty
                </div>
            </div>
            <div class="row mt-2 gy-4">
                <div class="col-md-3 form-group">
                    <label for="">Paciente:</label><br>
                    <strong>{{ $procedimento->paciente->nm_paciente }}</strong>
                </div>
                <div class="col-md-3 form-group">
                    <label for="">Nascimento:</label><br>
                    <strong>{{ str_replace('-','/',$nascimento) }}</strong>
                </div>
                <div class="col-md-3 form-group">
                    <label for="">CPF:</label><br>
                    <strong>{{ $procedimento->paciente->cpf }}</strong>
                </div>
                <div class="col-md-3 form-group">
                    <label for="">Médico:</label><br>
                    <strong>{{ $procedimento->medico }}</strong>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <label for="">Obs Paciente:</label><br>
                    <div class="alert alert-warning py-2 mb-0">
                        <strong>{{ $procedimento->paciente->obs ?? 'Sem observações' }}</strong>
                    </div>
                </div>
            </div>
            <div class="row mt-2 gy-4">
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
            @php
            $procedimentos_arqs = App\Models\Procedimento::where('codigo', $procedimento->codigo)->get();
            $in = array();
            foreach($procedimentos_arqs as $proc){
                $in[] = $proc->id;
            }

            $arquivos = App\Models\ProcedimentoAnexo::whereIn('procedimento_id', $in)->get();

            @endphp
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
                            <textarea {{ isset($visualizar) ? 'readonly' : '' }} class="form-control h-px-100" id="obs_biopedancia" name="obs_biopedancia">{{ $procedimento->obs_biopedancia }}</textarea>
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
                            <textarea {{ isset($visualizar) ? 'readonly' : '' }} class="form-control h-px-100" id="obs_coleta" name="obs_coleta">{{ $procedimento->obs_coleta }}</textarea>
                            <label for="obs_coleta">Obs Coleta:</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select {{ isset($visualizar) ? 'disabled' : 'required' }} id="tp_coleta" name='tp_coleta' class="select2 form-select">
                            <option value="">Opções</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Reduzida' ? 'selected' : '' }} value="Coleta Reduzida">Coleta Reduzida</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Copleta' ? 'selected' : '' }} value="Coleta Copleta">Coleta Copleta</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Retorno' ? 'selected' : '' }} value="Coleta Retorno">Coleta Retorno</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Reduzida 2' ? 'selected' : '' }} value="Coleta Reduzida 2">Coleta Reduzida 2</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Particular' ? 'selected' : '' }} value="Coleta Particular">Coleta Particular</option>
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
                            <textarea {{ isset($visualizar) ? 'readonly' : '' }} class="form-control h-px-100" id="obs_aplicacao" name="obs_aplicacao"></textarea>
                            <label for="obs_aplicacao">Obs Aplicação:</label>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
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
                                @include('sistema.dashboard.inc.linha_aplicacao', ['aplicacao' => $aplicacao, 'visualizar' => $visualizar ?? null, 'user' => $user])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
    <div class="row mt-4 mb-4">
        <div class="col-md-6 form-group">
            @empty($visualizar)
                <button type="button" id="btn_registrar_aplicacao" class="btn btn-primary me-2">Registrar Aplicação</button>
            @else
                <a href="{{ route('sistema.dashboard') }}" class="btn btn-secondary me-2">Voltar</a>
            @endempty
        </div>
    </div>
</form>

@php
$observacoes = $procedimento->observacoes_procedimento()->orderBy('created_at','desc')->get();
@endphp
@if($observacoes->count() > 0)
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <h4 class="card-title">Observações Avulsas</h4>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mt-3">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%">Data / Hora</th>
                            <th style="width: 20%">Usuário</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($observacoes as $obs)
                        <tr>
                            <td>{{ $obs->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $obs->user ? $obs->user->nome : 'Sistema' }}</td>
                            <td style="white-space: pre-wrap;">{{ $obs->observacao }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

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

function busca_lote_por_codigo(e, medicamento_id, clinica_id, quantidade){
    if(e.value){
        $.getJSON(
            '{{ route("sistema.dashboard.busca_lote_por_codigo") }}',
            {
                codigo : e.value,
                clinica_id : clinica_id,
                quantidade : quantidade,
                medicamento_id : medicamento_id
            },
            function(json){
                if(json.controle == 'vencido'){
                    Swal.fire({
                        icon: 'error',
                        title: '🚨 MEDICAMENTO VENCIDO!',
                        html: '<b style="font-size: 1.3rem; color: #dc3545;">' + json.mensagem + '</b>',
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                    document.getElementById('lote_' + medicamento_id).value = '';
                    document.getElementById('codigo_barras_' + medicamento_id).value = '';
                    return;
                }
                if(json.controle == 'true'){
                    document.getElementById('lote_' + medicamento_id).value = json.lote;
                }
                else if(json.controle == 'insuficiente'){
                    alert('Quantidade em estoque insuficiente!');
                    document.getElementById('lote_' + medicamento_id).value = '';
                    document.getElementById('codigo_barras_' + medicamento_id).value = '';
                }
                else{
                    alert('Código de barras inválido para este medicamento!');
                    document.getElementById('lote_' + medicamento_id).value = '';
                    document.getElementById('codigo_barras_' + medicamento_id).value = '';
                }
            }
        );
    }
    else{
        document.getElementById('lote_' + medicamento_id).value = '';
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
                if(json.controle == 'vencido'){
                    Swal.fire({
                        icon: 'error',
                        title: '🚨 MEDICAMENTO VENCIDO!',
                        html: '<b style="font-size: 1.3rem; color: #dc3545;">' + json.mensagem + '</b>',
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                    document.getElementById('codigo_barras_' + medicamento_id).value = '';
                    document.getElementById('lote_' + medicamento_id).value = '';
                    document.getElementById('codigo_barras_' + medicamento_id).focus();
                    return;
                }
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
    else{
        document.getElementById('lote_' + medicamento_id).value = '';
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
                if(json.controle == 'vencido'){
                    Swal.fire({
                        icon: 'error',
                        title: '🚨 MEDICAMENTO VENCIDO!',
                        html: '<b style="font-size: 1.3rem; color: #dc3545;">' + json.mensagem + '</b>',
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    });
                    document.getElementById('modal2Codigo_codigo_' + numero).value = '';
                    document.getElementById('modal2Codigo_lote_' + numero).value = '';
                    document.getElementById('modal2Codigo_codigo_' + numero).focus();
                    return;
                }
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

function controle_pendente(medicamento_id, elem){
    unidade = elem.dataset.medicamento;
    if(unidade == 'Procedimento'){
        if(document.getElementById('controle_pendente_' + medicamento_id).checked == true){
            //document.getElementById('lote_' + medicamento_id).setAttribute('disabled','disabled');
            document.getElementById('codigo_barras_' + medicamento_id).removeAttribute('required');
        }
        else{
            document.getElementById('codigo_barras_' + medicamento_id).setAttribute('required','required');
        }
    }
    else{
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
                            <select required id="modal_codigo_barras" name='codigo_barras' onchange="modal_seleciona_lote(this)" class="select2 form-select">
                                <option value="">Opções</option>
                            </select>
                            <label for="modal_codigo_barras">Codigo de Barra:</label>
                        </div>
                    </div>
                    <input type="hidden" id="modal_lote" name="lote" value="">
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
                document.getElementById('modal_codigo_barras').innerHTML = json.codigos;
                document.getElementById('modal_lote').value = '';
            }
        );
    }
}

function modal_seleciona_lote(e){
    if(e.value){
        document.getElementById('modal_lote').value = e.options[e.selectedIndex].getAttribute('data-lote');
    } else {
        document.getElementById('modal_lote').value = '';
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
                                    <input type="text" id='modal2Codigo_quantidade_1' class="form-control" autocomplete="off">
                                </td>
                                <td>
                                    <input onblur="busca_lote_por_codigo_frasco_2codigo(1)" type="text" id='modal2Codigo_codigo_1' class="form-control" autocomplete="off">
                                </td>
                                <td>
                                    <input readonly type="text" id='modal2Codigo_lote_1' class="form-control" autocomplete="off">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input type="text" id='modal2Codigo_quantidade_2' class="form-control" autocomplete="off">
                                </td>
                                <td>
                                    <input onblur="busca_lote_por_codigo_frasco_2codigo(2)" type="text" id='modal2Codigo_codigo_2' class="form-control" autocomplete="off">
                                </td>
                                <td>
                                    <input readonly type="text" id='modal2Codigo_lote_2' class="form-control" autocomplete="off">
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

        input_lote_1 = document.createElement('input');
        input_lote_1.setAttribute('type', 'hidden');
        input_lote_1.setAttribute('name', 'lote_med_1_' + medicamento_id);
        input_lote_1.setAttribute('value', lote1);

        input_lote_2 = document.createElement('input');
        input_lote_2.setAttribute('type', 'hidden');
        input_lote_2.setAttribute('name', 'lote_med_2_' + medicamento_id);
        input_lote_2.setAttribute('value', lote2);

        descricao = "Codigo: " + codigo1 + ", Quantidade: " + quantidade1 + "<br>Codigo: " + codigo2 + ", Quantidade: " + quantidade2;
        descricao_lote = "Lote: " + lote1 + "<br>Lote: " + lote2;

        document.getElementById('td_aplicacao_codigo_' + medicamento_id).innerHTML = descricao;
        document.getElementById('td_aplicacao_lote_' + medicamento_id).innerHTML = descricao_lote;
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_controle);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_qtd_1);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_qtd_2);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_cod_1);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_cod_2);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_lote_1);
        document.getElementById('td_aplicacao_codigo_' + medicamento_id).appendChild(input_lote_2);


        modal2Codigo.hide();

    }
    else{
        alert('É necessário preencher todos os campos');
    }
});
    // Ação do Leitor (Enter = Tab/Blur) para todos os usuários
    $(document).on('keydown', 'input[id^="codigo_barras_"]', function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            $(this).blur();
            return false;
        }
    });

    // Bloqueio de Digitação Manual Inteligente para Código de Barras
    // DESABILITADO em 2026-08-01: a leitora quebrou e as enfermeiras precisam digitar manualmente
    @php
        $is_admin = session()->has('administrador') || (session()->has('user') && session()->get('user')->tipo == 'Administrador');
    @endphp
    @if(false && !$is_admin)
    let lastKeyTime = Date.now();
    $(document).on('keydown', 'input[id^="codigo_barras_"]', function(e) {
        // Permitir teclas de controle: Backspace, Tab, Enter, Setas
        if ([8, 9, 13, 37, 38, 39, 40].includes(e.keyCode)) {
            return;
        }
        
        let currentTime = Date.now();
        let timeDiff = currentTime - lastKeyTime;
        
        // Permitir a primeira tecla se o campo estiver vazio
        if ($(this).val().length === 0) {
            lastKeyTime = currentTime;
            return;
        }

        // Se o tempo entre teclas for maior que 50ms, bloqueia (digitação manual)
        if (timeDiff > 50) {
            e.preventDefault();
            return false;
        }
        
        lastKeyTime = currentTime;
    });

    // Bloqueio de Cópia e Colar
    $(document).on('paste drop', 'input[id^="codigo_barras_"]', function(e) {
        e.preventDefault();
        return false;
    });
    @endif
</script>

@section('scripts')
<script>
function marcarAvaliadoGoogle(pacienteId) {
    if(confirm('Tem certeza que deseja marcar este paciente como avaliado no Google? Esta ação não pode ser desfeita.')) {
        $.ajax({
            url: "{{ route('sistema.procedimentos.update_google_flag') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: pacienteId
            },
            success: function(response) {
                if(response.success) {
                    $('#google_badge_container').html('<span class="badge bg-label-success ms-1"><i class="mdi mdi-google"></i> Paciente já respondeu...</span>');
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Paciente marcado como avaliado no Google.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    alert('Erro ao atualizar: ' + response.message);
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor.');
            }
        });
}

// Interceptar clique em Registrar Aplicação
@empty($visualizar)
$('#btn_registrar_aplicacao').on('click', function(e) {
    // Encontrar todos os medicamentos que serão aplicados (checkbox não marcado)
    let medicamentos = [];
    $('input[name^="controle_pendente_"]').each(function() {
        if (!this.checked) {
            let id = $(this).attr('data-medicamento-id');
            let nome = $(this).attr('data-nome-medicamento');
            let quantidade = $(this).attr('data-quantidade');

            let codigo, lote;
            let cod1 = $('input[name="cod_med_1_' + id + '"]').val();
            if (cod1) {
                // 2 códigos: ler dos hidden inputs
                let cod2 = $('input[name="cod_med_2_' + id + '"]').val();
                let lote1 = $('input[name="lote_med_1_' + id + '"]').val();
                let lote2 = $('input[name="lote_med_2_' + id + '"]').val();
                let qtd1 = $('input[name="quant_med_1_' + id + '"]').val();
                let qtd2 = $('input[name="quant_med_2_' + id + '"]').val();
                codigo = cod1 + ' / ' + cod2;
                lote = lote1 + ' / ' + lote2;
                quantidade = qtd1 + ' + ' + qtd2;
            } else {
                codigo = $('#codigo_barras_' + id).val() || '-';
                lote = $('#lote_' + id).val() || '-';
            }

            if (nome) {
                medicamentos.push({ nome, quantidade, codigo, lote });
            }
        }
    });

    if (!$('#formulario_aplicacao')[0].checkValidity()) {
        $('#formulario_aplicacao')[0].reportValidity();
        return;
    }

    let htmlTabela = '';
    if (medicamentos.length > 0) {
        htmlTabela = `
            <p style="font-size: 1.1rem; font-weight: 500; color: #2b303a;">Confirme os dados dos medicamentos abaixo:</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Medicamento</th>
                            <th>Quantidade</th>
                            <th>Código</th>
                            <th>Lote</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${medicamentos.map(m => `
                            <tr>
                                <td>${m.nome}</td>
                                <td>${m.quantidade}</td>
                                <td>${m.codigo}</td>
                                <td>${m.lote}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <hr>
        `;
    } else {
        htmlTabela = `<p style="font-size: 1.1rem; font-weight: 500; color: #2b303a;">Nenhum medicamento selecionado para aplicação nesta semana. Você confirma as demais anotações/exames conforme prescrição médica?</p><hr>`;
    }

    $('#conteudo_confirmacao_medicamentos').html(htmlTabela);

    $('#modal_confirmar_aplicacao').appendTo('body');
    let modalConfirmar = new bootstrap.Modal(document.getElementById('modal_confirmar_aplicacao'));
    modalConfirmar.show();
});

$(document).on('click', '#btn_confirmar_submissao', function() {
    $('#formulario_aplicacao').submit();
});
@endempty
</script>

<!-- Modal de Confirmação da Aplicação -->
<div class="modal fade" id="modal_confirmar_aplicacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Aplicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="conteudo_confirmacao_medicamentos"></div>
                
                <h6 class="mb-2"><i class="mdi mdi-paperclip me-1"></i> Receita / Anexos do Procedimento</h6>
                <div class="list-group">
                    @if($arquivos->count() == 0)
                        <div class="list-group-item text-muted">Nenhum anexo encontrado.</div>
                    @else
                        @foreach($arquivos as $arquivo)
                            <a target="_blank" href="/public/procedimentos/{{ $arquivo->procedimento_id }}/anexos/{{ $arquivo->anexo }}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2">
                                <span>{{ $arquivo->nm_anexo }}</span>
                                <span class="badge bg-label-primary"><i class="mdi mdi-eye"></i> Visualizar</span>
                            </a>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success" id="btn_confirmar_submissao" form="formulario_aplicacao">Confirmar e Salvar</button>
            </div>
        </div>
    </div>
</div>
@endsection
@endsection
