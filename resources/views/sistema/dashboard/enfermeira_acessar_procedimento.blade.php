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
                <h4 class="card-title">Estoques Abertos</h4>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Clinica</th>
                            <th>Medicamento</th>
                            <th>Abertura</th>
                            <th>Usuário</th>
                            <th>Lote</th>
                            <th>C. Barras</th>
                            <th>Frasco</th>
                            <th>Restante</th>
                        </tr>
                    </thead>
                    @foreach($array_abertos as $linha)
                        <tr>
                            <td>{{ $linha['clinica'] }}</td>
                            <td>{{ $linha['medicamento'] }}</td>
                            <td>{{ $linha['abertura'] }}</td>
                            <td>{{ $linha['usuario'] }}</td>
                            <td>{{ $linha['lote'] }}</td>
                            <td>{{ $linha['codigo_barras'] }}</td>
                            <td>{{ $linha['frasco'] }}</td>
                            <td>{{ $linha['restante'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    <hr>
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h4 class="card-title">Procedimento</h4>
                @empty($visualizar)
                    <button type="button" id="botao_abrir_frasco" class="btn btn-label-primary waves-effect">
                        <span class="tf-icons mdi mdi-medication-outline me-1"></span>
                        Abrir Frasco
                    </button>
                @endempty
            </div>
            <div class="row mt-2 gy-4">
                <div class="col-md-3 form-group">
                    <label for="">Paciente:</label><br>
                    <strong>{{ $procedimento->paciente->nm_paciente }}</strong>
                    <span id="google_badge_container">
                        @if($procedimento->paciente->st_google == 1)
                            <span class="badge bg-label-success ms-1" title="Paciente já avaliou no Google"><i class="mdi mdi-google"></i> Avaliado</span>
                        @else
                            @empty($visualizar)
                                <button type="button" onclick="marcarAvaliadoGoogle({{ $procedimento->paciente->id }})" class="btn btn-xs btn-outline-info ms-1" id="btn_google" title="Clique para marcar que o paciente avaliou no Google">
                                    <i class="mdi mdi-google"></i> Marcar Avaliado
                                </button>
                            @endempty
                        @endif
                    </span>
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
            @if($procedimento->agendamento)
            <div class="row mt-2">
                <div class="col-md-12">
                    <label for="">Agendamento:</label><br>
                    <strong>{{ $procedimento->agendamento }}</strong>
                </div>
            </div>
            @endif
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
        $nr_proc_ant = $procedimento->nr_procedimento;
        $proc_anterior = App\Models\Procedimento::where('codigo', $procedimento->codigo)
        ->where('nr_procedimento','<', $nr_proc_ant)
        ->where('semana_sem_aplicacao', 'Não')
        ->orderByDesc('id')
        ->first();
        $obs_anterior = '';
        @endphp
        @if($proc_anterior && $proc_anterior->situacao == "Aplicado")
            <div class="card card-border-shadow-primary mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h4 class="card-title">Procedimento Anterior -- {{$proc_anterior->id}}</h4>
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
                                    $estoque = false;
                                    if($aplicacao->lote){
                                        $estoque = App\Models\Estoque::where('medicamento_id', $aplicacao->medicamento->id)
                                        ->where('lote', $aplicacao->lote->lote)
                                        ->first();
                                    }
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
                            <option {{ $procedimento->tp_coleta == 'Coleta Completa Feminina' ? 'selected' : '' }} value="Coleta Completa Feminina">Coleta Completa Feminina</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Retorno Feminina' ? 'selected' : '' }} value="Coleta Retorno Feminina">Coleta Retorno Feminina</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Completa Masculina' ? 'selected' : '' }} value="Coleta Completa Masculina">Coleta Completa Masculina</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Retorno Masculina' ? 'selected' : '' }} value="Coleta Retorno Masculina">Coleta Retorno Masculina</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Cortesia' ? 'selected' : '' }} value="Coleta Cortesia">Coleta Cortesia</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Particular' ? 'selected' : '' }} value="Coleta Particular">Coleta Particular</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Reduzida' ? 'selected' : '' }} value="Coleta Reduzida">Coleta Reduzida</option>
                            <option {{ $procedimento->tp_coleta == 'Coleta Reduzida 2' ? 'selected' : '' }} value="Coleta Reduzida 2">Coleta Reduzida 2</option>
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
                <button type="submit" class="btn btn-primary me-2">Registrar Aplicação</button>
            @else
                <a href="{{ route('sistema.dashboard') }}" class="btn btn-secondary me-2">Voltar</a>
            @endempty
        </div>
    </div>
</form>

<script>
    document.getElementById("formulario_aplicacao").addEventListener("keydown", function (e) {

        if (e.key === "Enter") {
            e.preventDefault(); // bloqueia submit automático
        }
    });

</script>

@if($procedimentos_vinculados->count() > 0)
    <hr>
    <h4 class="mb-3 px-3">Procedimentos Vinculados</h4>
    @foreach($procedimentos_vinculados as $proc)
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Semana {{ $proc->nr_procedimento }}</h5>
                    <div>
                        <span class="me-3"><strong>Data Aplicação:</strong> {{ dataDbForm($proc->data_aplicacao) }}</span>
                        @if($proc->situacao == "Agendado")
                            <span class="badge rounded-pill bg-label-warning">Agendado</span>
                        @elseif($proc->situacao == "Fila de Aplicação")
                            <span class="badge rounded-pill bg-label-primary">Fila de Aplicação</span>
                        @elseif($proc->situacao == "Atendimento")
                            <span class="badge rounded-pill bg-label-danger">Atendimento</span>
                        @elseif($proc->situacao == "Aplicado")
                            <span class="badge rounded-pill bg-label-success">Aplicado</span>
                        @elseif($proc->situacao == "Pendente" || $proc->situacao == "Aplicação Parcial")
                            <span class="badge rounded-pill bg-label-warning">Pendente</span>
                        @else
                            <span class="badge rounded-pill bg-label-secondary">{{$proc->situacao}}</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="text-muted small">Observação:</label><br>
                        <strong>{{ $proc->obs ?? 'Nenhuma observação' }}</strong>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Unidade</th>
                                <th>Quantidade</th>
                                <th>Valor</th>
                                <th>Total</th>
                                <th>Situação</th>
                                <th>Data Aplicação</th>
                                <th>Lote</th>
                                <th>C.Barras</th>
                                <th>Enfermagem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($proc->aplicacaos as $aplicacao)
                                @php
                                $dt_aplicacao_item = null;
                                if($aplicacao->lote){
                                    $var_date = explode(' ',$aplicacao->lote->created_at);
                                    $dt_aplicacao_item = dataDbForm($var_date[0]);
                                }
                                @endphp
                                <tr>
                                    <td>{{ $aplicacao->medicamento->nome }}</td>
                                    <td>{{ $aplicacao->medicamento->unidade }}</td>
                                    <td>{{ $aplicacao->quantidade }}</td>
                                    <td>R$ {{ valorDbForm($aplicacao->valor) }}</td>
                                    <td>R$ {{ valorDbForm($aplicacao->total) }}</td>
                                    <td>{{ $aplicacao->situacao }}</td>
                                    <td>{{ $dt_aplicacao_item }}</td>
                                    <td>{!! $aplicacao->lotes() !!}</td>
                                    <td>{!! $aplicacao->codigos() !!}</td>
                                    <td>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
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
                                        @if($aplicacao->medicamento->grupo_id)
                                            {{-- se possui grupo vamos trazer os medicamentos do grupo --}}
                                            @php
                                            $medicamentos_grupo = App\Models\Medicamento::where('grupo_id', $aplicacao->medicamento->grupo_id)->get();
                                            @endphp
                                            @foreach($medicamentos_grupo as $med)
                                                <option value="{{ $med->id }}">{{ $med->nome }}</option>
                                            @endforeach
                                        @else
                                            <option value="{{ $aplicacao->medicamento->id }}">{{ $aplicacao->medicamento->nome }}</option>
                                        @endif
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
                    $('#google_badge_container').html('<span class="badge bg-label-success ms-1"><i class="mdi mdi-google"></i> Avaliado</span>');
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
            }
        });
    }
}
    // Bloqueio de Digitação Manual Inteligente para Código de Barras
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
</script>
@endsection
