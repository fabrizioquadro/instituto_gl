@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

@php
switch($prescricao->situacao){
    case 'Agendada': $badge_situacao = 'bg-label-warning'; break;
    case 'Em Andamento': $badge_situacao = 'bg-label-info'; break;
    case 'Concluída': $badge_situacao = 'bg-label-success'; break;
    case 'Encerrada': $badge_situacao = 'bg-label-dark'; break;
    case 'Cancelada': $badge_situacao = 'bg-label-danger'; break;
    default: $badge_situacao = 'bg-label-secondary';
}
switch($prescricao->situacao_financeira){
    case 'Pago': $badge_fin = 'bg-success'; break;
    case 'Parcial': $badge_fin = 'bg-warning'; break;
    case 'Em Aberto': $badge_fin = 'bg-danger'; break;
    default: $badge_fin = 'bg-secondary';
}

// ---------- crédito em aberto ----------
// só pode ser corrigido enquanto não existe nenhum pagamento
// (o recálculo apaga e recria todas as parcelas)
$tem_pagamento_credito = $prescricao->pagamentos->count() > 0
    || $prescricao->parcelas->sum('valor_pago') > 0;
$pode_editar_credito = !in_array($prescricao->situacao, ['Encerrada', 'Cancelada'])
    && !$tem_pagamento_credito
    && (float) $prescricao->valor_tratamento > 0;

// base das parcelas para a prévia do modal: reutiliza as semanas das parcelas atuais;
// se ainda não existem parcelas, usa as semanas com medicação (mesma regra do cadastro)
$credito_parcelas = $prescricao->parcelas->sortBy('nr_parcela')->values()->map(function ($p) {
    return [
        'parcela' => (int) $p->nr_parcela,
        'semana' => $p->semana ? (int) $p->semana->nr_semana : null,
        'vencimento' => $p->dt_vencimento ? date('Y-m-d', strtotime($p->dt_vencimento)) : null,
        'valor' => (float) $p->valor_parcela,
        'existe' => true,
    ];
});

if ($credito_parcelas->isEmpty()) {
    $credito_parcelas = $prescricao->semanas
        ->filter(fn($s) => $s->medicamentos->count() > 0)
        ->sortBy('nr_semana')
        ->values()
        ->map(function ($s, $i) {
            return [
                'parcela' => $i + 1,
                'semana' => (int) $s->nr_semana,
                'vencimento' => $s->data_prevista ? date('Y-m-d', strtotime($s->data_prevista)) : null,
                'valor' => 0.0,
                'existe' => false,
            ];
        });
}
@endphp

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Prescrição #{{ $prescricao->id }}</h4>
            <div>
                @if(!in_array($prescricao->situacao, ['Encerrada', 'Cancelada']))
                <a href="{{ route('sistema.prescricoes.editar_prescricao', $prescricao->id) }}" class="btn btn-outline-secondary btn-sm">
                    <span class="tf-icons mdi mdi-pencil me-1"></span> Editar
                </a>
                @endif
                <a href="{{ route('sistema.prescricoes.imprimir_cadastro', $prescricao->id) }}" target="_blank" class="btn btn-outline-success btn-sm">
                    <span class="tf-icons mdi mdi-folder-open me-1"></span> Imprimir Cadastro
                </a>
                <a href="{{ route('sistema.prescricoes.financeiro', $prescricao->id) }}" class="btn btn-outline-primary btn-sm">
                    <span class="tf-icons mdi mdi-cash me-1"></span> Financeiro Completo
                </a>
                <a href="{{ route('sistema.prescricoes') }}" class="btn btn-outline-dark btn-sm">Voltar</a>
            </div>
        </div>

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

        <hr>

        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="w-25">Paciente</th>
                        <td><b>{{ $prescricao->paciente->nm_paciente ?? '-' }}</b></td>
                    </tr>
                    <tr>
                        <th>Código</th>
                        <td>{{ $prescricao->codigo_versao1 ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Data Prescrição</th>
                        <td>{{ $prescricao->data_prescricao ? dataDbForm($prescricao->data_prescricao) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Médico</th>
                        <td>{{ $prescricao->medico ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Tipo Atendimento</th>
                        <td>{{ $prescricao->tipo_atendimento ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Clínica</th>
                        <td>{{ $prescricao->clinica->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Cadastrante</th>
                        <td>{{ $prescricao->userCadastro->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Obs</th>
                        <td>{{ $prescricao->obs ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="w-25">Qt Semanas</th>
                        <td>{{ $prescricao->qt_semanas }}</td>
                    </tr>
                    <tr>
                        <th>Semanas c/ Aplicação</th>
                        <td>{{ $prescricao->qt_semanas_aplicacao }}</td>
                    </tr>
                    <tr>
                        <th>Semana Atual</th>
                        <td>{{ $prescricao->semana_atual > 0 ? $prescricao->semana_atual : 'Não Iniciado' }}</td>
                    </tr>
                    <tr>
                        <th>Valor Tratamento</th>
                        <td>R$ {{ number_format($prescricao->valor_tratamento, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Total Pago</th>
                        <td>R$ {{ number_format($prescricao->total_pago, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Crédito em Aberto</th>
                        <td>
                            R$ {{ number_format($prescricao->credito_em_aberto, 2, ',', '.') }}
                            @if($pode_editar_credito)
                            <button type="button" class="btn btn-sm btn-icon btn-label-primary ms-1" title="Editar Crédito em Aberto" onclick="abrir_modal_editar_credito()">
                                <span class="tf-icons mdi mdi-pencil"></span>
                            </button>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Situação</th>
                        <td><span class="badge rounded-pill {{ $badge_situacao }}">{{ $prescricao->situacao }}</span></td>
                    </tr>
                    <tr>
                        <th>Situação Financeira</th>
                        <td><span class="badge rounded-pill {{ $badge_fin }}">{{ $prescricao->situacao_financeira }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title mb-0">Semanas</h4>
            <div>
                @if(!in_array($prescricao->situacao, ['Encerrada', 'Cancelada', 'Concluída']))
                <a href="{{ route('sistema.prescricoes.adicionar_medicamentos', $prescricao->id) }}" class="btn btn-outline-dark btn-sm">+ Medicamentos</a>
                <a href="{{ route('sistema.prescricoes.adicionar_semana', $prescricao->id) }}" class="btn btn-primary btn-sm">+ Semanas</a>
                @endif
            </div>
        </div>
        <hr>
        <div class="table-responsive">
            <table class="tabela-index table table-sm nowrap" id="table-semanas">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Semana</th>
                        <th>Dt Prevista</th>
                        <th>Dt Aplicada</th>
                        <th>Aplicação</th>
                        <th>Medicações</th>
                        <th>Situação</th>
                        <th>Pagamento</th>
                        <th>Obs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescricao->semanas as $semana)
                        @php
                        switch($semana->situacao){
                            case 'Agendada': $badge = 'bg-label-warning'; break;
                            case 'Fila de Aplicação': $badge = 'bg-label-primary'; break;
                            case 'Em Atendimento': $badge = 'bg-label-primary'; break;
                            case 'Aplicada': $badge = 'bg-label-success'; break;
                            case 'Aplicação Parcial': $badge = 'bg-label-warning'; break;
                            case 'Encerrada': $badge = 'bg-label-dark'; break;
                            case 'Cancelada': $badge = 'bg-label-danger'; break;
                            default: $badge = 'bg-label-secondary';
                        }
                        @endphp
                        <tr>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="true">
                                        <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.acessar_semana', $semana->id) }}"><i class="mdi mdi-eye me-1"></i> Acessar</a>
                                        @if(in_array($semana->situacao, ['Agendada', 'Aplicação Parcial']) && $semana->pode_enviar_fila && $semana->semana_paga)
                                            <a class="dropdown-item waves-effect" href="javascript:void(0)" onclick="confirmar_enviar_fila({{ $semana->id }}, {{ $semana->nr_semana }})"><i class="mdi mdi-send me-1"></i> Enviar para a Fila de Aplicação</a>
                                        @endif
                                        @if(!in_array($semana->situacao, ['Cancelada', 'Encerrada']))
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.editar_semana', $semana->id) }}"><i class="mdi mdi-pencil me-1"></i> Editar</a>
                                            @if(session()->has('administrador') && !in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial', 'Em Atendimento']) && !$semana->medicamentos->where('situacao', 'Aplicada')->count())
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.excluir_semana', $semana->id) }}"><i class="mdi mdi-trash-can-outline me-1"></i> Excluir</a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="fw-medium">{{ $semana->nr_semana }}</td>
                            <td>{{ $semana->data_prevista ? dataDbForm($semana->data_prevista) : '-' }}</td>
                            <td>
                                @if($semana->data_aplicada)
                                    {{ dataDbForm($semana->data_aplicada) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($semana->tem_aplicacao)
                                    <span class="badge bg-info">c/ aplicação</span>
                                @else
                                    <span class="badge bg-label-secondary">sem aplicação</span>
                                @endif
                            </td>
                            <td>
                                @if($semana->medicamentos->count())
                                    @foreach($semana->medicamentos as $med)
                                        @php
                                        $st_med = $med->situacao;
                                        $badge_med = 'bg-label-secondary';
                                        if($st_med == 'Aplicada'){ $badge_med = 'bg-label-success'; }
                                        elseif($st_med == 'Aberta'){ $badge_med = 'bg-label-warning'; }
                                        elseif($st_med == 'Encerrada'){ $badge_med = 'bg-label-dark'; }
                                        elseif($st_med == 'Cancelada'){ $badge_med = 'bg-label-danger'; }
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $med->medicamento->nome ?? '?' }} @if($med->is_soro) <span class="badge bg-info ms-1">soro</span> @endif <small class="text-muted">({{ $med->quantidade }})</small></span>
                                            <span class="badge rounded-pill {{ $badge_med }}">{{ $st_med }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td><span class="badge rounded-pill {{ $badge }}">{{ $semana->situacao }}</span></td>
                            <td>
                                @if($semana->parcela)
                                    @php
                                    $par = $semana->parcela;
                                    $badge_pag = 'bg-danger';
                                    if($par->situacao == 'Paga'){ $badge_pag = 'bg-success'; }
                                    elseif($par->situacao == 'Parcial'){ $badge_pag = 'bg-warning'; }
                                    elseif($par->situacao == 'Encerrada'){ $badge_pag = 'bg-dark'; }
                                    elseif($par->situacao == 'Cancelada'){ $badge_pag = 'bg-secondary'; }
                                    @endphp
                                    <span class="badge rounded-pill {{ $badge_pag }}">{{ $par->situacao }}</span>
                                    <small class="text-muted d-block">R$ {{ number_format($par->valor_pago, 2, ',', '.') }} / R$ {{ number_format($par->valor_parcela, 2, ',', '.') }}</small>
                                @else
                                    <span class="text-muted">sem parcela</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $semana->obs ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL CONFIRMA ENVIO À FILA DE APLICAÇÃO --}}
<div class="modal fade" id="modal_confirmar_enviar_fila" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary d-flex align-items-center">
                    <span class="mdi mdi-send mdi-24px me-2"></span>Enviar para a Fila de Aplicação
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Confirma o envio da <strong id="txt_semana_enviar_fila"></strong> para a fila de aplicação?</p>
                <p class="text-muted mb-0">A semana ficará disponível para a enfermagem iniciar o atendimento.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_confirmar_enviar_fila">Confirmar e Enviar</button>
            </div>
        </div>
    </div>
</div>

<form id="form_enviar_fila_aplicacao" method="post" action="{{ route('sistema.prescricoes.enviar_fila_aplicacao') }}" style="display:none;">
    @csrf
    <input type="hidden" name="semana_id" id="semana_id_enviar_fila" value="">
    <input type="hidden" name="origem" value="prescricao">
</form>

{{-- ENCERRAR PROTOCOLO (somente administradores) --}}
@if(session()->has('administrador') && !in_array($prescricao->situacao, ['Encerrada', 'Cancelada', 'Concluída']))
<div class="card card-border-shadow-primary mb-4 border-danger">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title text-danger mb-1">Encerrar Protocolo</h5>
                <p class="text-muted mb-0">Marca como <b>Encerrada</b> toda a prescrição: semanas não aplicadas, medicamentos abertos/pendentes e parcelas em aberto. O que já foi <b>aplicado</b> não é alterado.</p>
            </div>
            <button type="button" class="btn btn-danger" onclick="abrir_modal_encerrar_protocolo()">
                <span class="tf-icons mdi mdi-close-octagon-outline me-1"></span> Encerrar Protocolo
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="modal_encerrar_protocolo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger d-flex align-items-center">
                    <span class="mdi mdi-close-octagon-outline mdi-24px me-2"></span>Encerrar Protocolo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Confirma o <b>encerramento do protocolo</b> da prescrição <b>#{{ $prescricao->id }}</b>?</p>
                <ul class="mb-2">
                    <li>Semanas <b>não aplicadas</b> (Agendada / Fila / Em Atendimento) → <b>Encerrada</b>;</li>
                    <li>Medicamentos <b>Aberto/Pendente</b> dessas semanas → <b>Encerrada</b>;</li>
                    <li>Parcelas <b>Em Aberto/Parcial</b> → <b>Encerrada</b>;</li>
                    <li>Semanas/medicamentos já <b>aplicados</b> permanecem como histórico.</li>
                </ul>
                <p class="text-muted mb-0">Esta ação não pode ser desfeita pela tela. Confirma?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('sistema.prescricoes.encerrar_protocolo') }}" method="post">
                    @csrf
                    <input type="hidden" name="prescricao_id" value="{{ $prescricao->id }}">
                    <button type="submit" class="btn btn-danger">Encerrar Protocolo</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<style>
/* tabela simples (sem DataTables): mantém espaçamento compacto do table-sm */
.table-sm thead th,
.table-sm thead td,
.table-sm tbody th,
.table-sm tbody td {
    padding: 0.3125rem 0.625rem !important;
}
</style>
<script>
function confirmar_enviar_fila(semana_id, nr_semana){
    document.getElementById('semana_id_enviar_fila').value = semana_id;
    document.getElementById('txt_semana_enviar_fila').innerText = 'Semana ' + nr_semana;
    let modal = new bootstrap.Modal(document.getElementById('modal_confirmar_enviar_fila'));
    modal.show();
    document.getElementById('btn_confirmar_enviar_fila').onclick = function(){
        document.getElementById('form_enviar_fila_aplicacao').submit();
    };
}

function abrir_modal_encerrar_protocolo(){
    let modal = new bootstrap.Modal(document.getElementById('modal_encerrar_protocolo'));
    modal.show();
}
</script>

@if($pode_editar_credito)
{{-- MODAL EDITAR CRÉDITO EM ABERTO --}}
<div class="modal fade" id="modal_editar_credito" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary d-flex align-items-center">
                    <span class="mdi mdi-cash-edit mdi-24px me-2"></span>Editar Crédito em Aberto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sistema.prescricoes.update_credito_em_aberto') }}" method="post" onsubmit="return validar_edicao_credito();">
                @csrf
                <input type="hidden" name="prescricao_id" value="{{ $prescricao->id }}">
                <div class="modal-body">
                    <div class="alert alert-warning d-flex align-items-start" role="alert">
                        <i class="mdi mdi-alert-outline me-2"></i>
                        <div>
                            @if(count($credito_parcelas) > 0)
                                Ao salvar, <b>todas as {{ count($credito_parcelas) }} parcelas desta prescrição serão apagadas e geradas novamente</b>
                                com o novo valor a parcelar. Esta ação não pode ser desfeita.
                            @else
                                Ao salvar, <b>as parcelas desta prescrição serão geradas</b> com base no novo valor a parcelar.
                            @endif
                        </div>
                    </div>

                    <div class="row gy-3">
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input class="form-control" type="text" id="credito_valor_tratamento" value="{{ number_format($prescricao->valor_tratamento, 2, ',', '.') }}" readonly/>
                                <label for="credito_valor_tratamento">Valor Tratamento (R$):</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input class="form-control" type="text" id="novo_credito_em_aberto" name="credito_em_aberto" value="{{ number_format($prescricao->credito_em_aberto, 2, ',', '.') }}" onkeypress="return(MascaraMoeda(this,'.',',',event))" onkeyup="atualizar_previa_credito()"/>
                                <label for="novo_credito_em_aberto">Crédito em Aberto (R$):</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input class="form-control fw-bold" type="text" id="credito_valor_parcelar" readonly/>
                                <label for="credito_valor_parcelar">Valor a Parcelar (R$):</label>
                            </div>
                        </div>
                    </div>

                    <h6 class="card-title mt-4 mb-2">Parcelas — antes × depois</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Parcela</th>
                                    <th>Semana</th>
                                    <th>Dt Vencimento</th>
                                    <th>Valor Atual</th>
                                    <th>Novo Valor</th>
                                    <th>Diferença</th>
                                </tr>
                            </thead>
                            <tbody id="tabela_previa_parcelas_credito"></tbody>
                        </table>
                    </div>
                    <div id="aviso_credito" class="text-danger small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar e Recalcular Parcelas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const CREDITO_TRATAMENTO = {{ (float) $prescricao->valor_tratamento }};
const CREDITO_PARCELAS = @json($credito_parcelas);

function credito_valor_form_db(valor){
    valor = (valor || '').replace(/\./g, '').replace(',', '.');
    return parseFloat(valor) || 0;
}

function credito_moeda(valor){
    return 'R$ ' + (valor || 0).toFixed(2).replace('.', ',');
}

function credito_data_br(data){
    if(!data){ return '-'; }
    return String(data).substring(0, 10).split('-').reverse().join('/');
}

// mesma regra de divisão usada no cadastro e no update_credito_em_aberto()
function calcular_novas_parcelas_credito(valor_parcelar, total){
    if(total <= 0 || valor_parcelar <= 0){ return []; }
    let base = Math.floor((valor_parcelar / total) * 100) / 100;
    let resto = Math.round((valor_parcelar - base * total) * 100) / 100;
    let valores = [];
    for(let i = 0; i < total; i++){
        valores.push(i === total - 1 ? Math.round((base + resto) * 100) / 100 : base);
    }
    return valores;
}

function atualizar_previa_credito(){
    let input = document.getElementById('novo_credito_em_aberto');
    let credito = credito_valor_form_db(input ? input.value : '0');
    let valor_parcelar = Math.max(0, Math.round((CREDITO_TRATAMENTO - credito) * 100) / 100);
    let novos = calcular_novas_parcelas_credito(valor_parcelar, CREDITO_PARCELAS.length);

    document.getElementById('credito_valor_parcelar').value = credito_moeda(valor_parcelar);

    let tbody = document.getElementById('tabela_previa_parcelas_credito');
    if(CREDITO_PARCELAS.length === 0){
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Esta prescrição não possui semanas com medicação para gerar parcelas.</td></tr>';
        return;
    }

    let html = '';
    CREDITO_PARCELAS.forEach(function(p, idx){
        let atual = p.existe ? parseFloat(p.valor) : 0;
        let novo = novos.length ? novos[idx] : 0;
        let dif = Math.round((novo - atual) * 100) / 100;
        let cor = dif > 0 ? 'text-danger' : (dif < 0 ? 'text-success' : 'text-muted');
        let sinal = dif > 0 ? '+ ' : (dif < 0 ? '- ' : '');
        html += '<tr>' +
            '<td class="fw-medium">' + p.parcela + '</td>' +
            '<td>' + (p.semana ? 'Semana ' + p.semana : '-') + '</td>' +
            '<td>' + credito_data_br(p.vencimento) + '</td>' +
            '<td>' + (p.existe ? credito_moeda(atual) : '<span class="text-muted">—</span>') + '</td>' +
            '<td class="fw-bold">' + (novos.length ? credito_moeda(novo) : '<span class="text-muted">—</span>') + '</td>' +
            '<td class="' + cor + '">' + (novos.length ? sinal + credito_moeda(Math.abs(dif)) : '—') + '</td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
}

function abrir_modal_editar_credito(){
    document.getElementById('aviso_credito').innerText = '';
    atualizar_previa_credito();
    new bootstrap.Modal(document.getElementById('modal_editar_credito')).show();
}

function validar_edicao_credito(){
    let aviso = document.getElementById('aviso_credito');
    aviso.innerText = '';

    let credito = credito_valor_form_db(document.getElementById('novo_credito_em_aberto').value);

    if(credito < 0){
        aviso.innerText = 'O crédito em aberto não pode ser negativo.';
        return false;
    }
    if(credito > CREDITO_TRATAMENTO + 0.005){
        aviso.innerText = 'O crédito em aberto não pode ser maior que o valor do tratamento (' + credito_moeda(CREDITO_TRATAMENTO) + ').';
        return false;
    }
    if(CREDITO_PARCELAS.length === 0 && credito < CREDITO_TRATAMENTO - 0.005){
        aviso.innerText = 'Não há semanas com medicação para gerar as parcelas desta prescrição.';
        return false;
    }
    return confirm('Confirma a alteração do crédito em aberto? Todas as parcelas serão apagadas e geradas novamente.');
}
</script>
@endif
@endsection
