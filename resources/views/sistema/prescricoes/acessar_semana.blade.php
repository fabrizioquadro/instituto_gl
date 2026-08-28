@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

@php
switch($semana->situacao){
    case 'Agendada': $badge = 'bg-label-warning'; break;
    case 'Fila de Aplicação': $badge = 'bg-label-primary'; break;
    case 'Em Atendimento': $badge = 'bg-label-primary'; break;
    case 'Aplicada': $badge = 'bg-label-success'; break;
    case 'Aplicação Parcial': $badge = 'bg-label-warning'; break;
    case 'Cancelada': $badge = 'bg-label-danger'; break;
    default: $badge = 'bg-label-secondary';
}
@endphp

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Semana {{ $semana->nr_semana }}</h4>
            <a href="{{ route('sistema.prescricoes.acessar', $semana->prescricao_id) }}" class="btn btn-outline-dark btn-sm">Voltar</a>
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
                        <td><b>{{ $semana->prescricao->paciente->nm_paciente ?? '-' }}</b></td>
                    </tr>
                    <tr>
                        <th>Prescrição</th>
                        <td>#{{ $semana->prescricao_id }}</td>
                    </tr>
                    <tr>
                        <th>Data Prevista</th>
                        <td>{{ $semana->data_prevista ? dataDbForm($semana->data_prevista) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Data Aplicada</th>
                        <td>{{ $semana->data_aplicada ? dataDbForm($semana->data_aplicada) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Chegada</th>
                        <td>{{ $semana->dt_hr_chegada ? date('d/m/Y H:i:s', strtotime($semana->dt_hr_chegada)) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Início Atendimento</th>
                        <td>{{ $semana->dt_hr_atendimento ? date('d/m/Y H:i:s', strtotime($semana->dt_hr_atendimento)) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Finalização</th>
                        <td>{{ $semana->dt_hr_finalizacao ? date('d/m/Y H:i:s', strtotime($semana->dt_hr_finalizacao)) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Enfermeiro(a)</th>
                        <td>{{ $semana->userAplicacao->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Situação</th>
                        <td><span class="badge rounded-pill {{ $badge }}">{{ $semana->situacao }}</span></td>
                    </tr>
                    <tr>
                        <th>Obs</th>
                        <td>{{ $semana->obs ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title mb-0">Medicações</h5>
            <a href="{{ route('sistema.prescricoes.editar_semana', $semana->id) }}" class="btn btn-outline-dark btn-sm">Editar Semana</a>
        </div>

        <div class="table-responsive">
            <table class="tabela-index table table-sm nowrap">
                <thead class="table-light">
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Lote</th>
                        <th>Código de Barras</th>
                        <th>Chegada</th>
                        <th>Início</th>
                        <th>Finalização</th>
                        <th>Aplicado por</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($semana->medicamentos as $med)
                        @php
                        $st_med = $med->situacao;
                        $badge_med = 'bg-label-secondary';
                        if($st_med == 'Aplicada'){ $badge_med = 'bg-label-success'; }
                        elseif($st_med == 'Aberta'){ $badge_med = 'bg-label-warning'; }
                        elseif($st_med == 'Cancelada'){ $badge_med = 'bg-label-danger'; }
                        @endphp
                        <tr>
                            <td>{{ $med->medicamento->nome ?? '?' }} @if($med->is_soro) <span class="badge bg-info ms-1">soro</span> @endif</td>
                            <td>{{ $med->quantidade }}</td>
                            <td>{{ $med->lotes->pluck('lote')->filter()->unique()->implode(', ') ?: '-' }}</td>
                            <td>{{ $med->lotes->pluck('codigo_barras')->filter()->unique()->implode(', ') ?: '-' }}</td>
                            <td>{{ ($med->dt_hr_chegada ?: $semana->dt_hr_chegada) ? date('d/m/Y H:i:s', strtotime($med->dt_hr_chegada ?: $semana->dt_hr_chegada)) : '-' }}</td>
                            <td>{{ ($med->dt_hr_atendimento ?: $semana->dt_hr_atendimento) ? date('d/m/Y H:i:s', strtotime($med->dt_hr_atendimento ?: $semana->dt_hr_atendimento)) : '-' }}</td>
                            <td>{{ $med->aplicado_em ? date('d/m/Y H:i:s', strtotime($med->aplicado_em)) : '-' }}</td>
                            <td>{{ $med->userAplicacao->nome ?? '-' }}</td>
                            <td><span class="badge rounded-pill {{ $badge_med }}">{{ $st_med }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">Semana sem medicações.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title mb-0">Observações</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.prescricoes.salvar_observacao') }}" method="POST">
            @csrf
            <input type="hidden" name="prescricao_semana_id" value="{{ $semana->id }}">
            <div class="row">
                <div class="col-md-10">
                    <div class="form-floating form-floating-outline mb-3">
                        <textarea required class="form-control" name="observacao" style="height: 60px" placeholder="Digite a observação..."></textarea>
                        <label>Nova Observação</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-center">
                    <button type="submit" class="btn btn-primary w-100 mb-3" style="height: 60px">Salvar</button>
                </div>
            </div>
        </form>

        @php
        $observacoes = $semana->observacoes->sortByDesc('created_at');
        @endphp
        @if($observacoes->count() > 0)
        <div class="table-responsive mt-3">
            <table class="table table-sm table-bordered">
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
                        <td>{{ $obs->created_at ? $obs->created_at->format('d/m/Y H:i') : '-' }}</td>
                        <td>{{ $obs->user ? $obs->user->nome : 'Sistema' }}</td>
                        <td style="white-space: pre-wrap;">{{ $obs->observacao }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <p class="text-muted mb-0">Nenhuma observação para esta semana.</p>
        @endif
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title mb-0">Financeiro</h4>
            <a href="{{ route('sistema.prescricoes.financeiro', $semana->prescricao_id) }}" class="btn btn-outline-primary btn-sm">Financeiro Completo</a>
        </div>
        <hr>
        @if($parcela)
            @php
            switch($parcela->situacao){
                case 'Paga': $badge_fin = 'bg-success'; break;
                case 'Parcial': $badge_fin = 'bg-warning'; break;
                case 'Cancelada': $badge_fin = 'bg-secondary'; break;
                default: $badge_fin = 'bg-danger';
            }
            @endphp
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block">Valor da Parcela</small>
                    <b>R$ {{ number_format($parcela->valor_parcela, 2, ',', '.') }}</b>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Valor Pago</small>
                    <b>R$ {{ number_format($parcela->valor_pago, 2, ',', '.') }}</b>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Saldo</small>
                    <b>R$ {{ number_format(max(0, $parcela->valor_parcela - $parcela->valor_pago), 2, ',', '.') }}</b>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Situação</small>
                    <span class="badge rounded-pill {{ $badge_fin }}">{{ $parcela->situacao }}</span>
                </div>
            </div>
        @else
            <p class="text-muted mb-0">Sem parcela financeira para esta semana.</p>
        @endif

        @if($parcela && ($parcela->valor_parcela - $parcela->valor_pago) > 0)
            @php $saldo_parcela = max(0, $parcela->valor_parcela - $parcela->valor_pago); @endphp
            <hr>
            <h5 class="card-title mb-0">Lançar Pagamento</h5>
            <form action="{{ route('sistema.prescricoes.lancar_pagamento') }}" method="post" enctype="multipart/form-data" onsubmit="return validar_pagamento()">
                @csrf
                <input type="hidden" name="parcela_id" value="{{ $parcela->id }}">
                <input type="hidden" name="contador_formas" id="contador_formas" value="1">
                <div class="row gy-3 mt-2">
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <input class="form-control" type="date" id="dt_pagamento" name="dt_pagamento" value="{{ date('Y-m-d') }}"/>
                            <label for="dt_pagamento">Data do Pagamento:</label>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-floating form-floating-outline">
                            <input class="form-control" type="text" id="obs_pagamento" name="obs_pagamento"/>
                            <label for="obs_pagamento">Obs:</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <h6 class="card-title mb-0">Formas de Pagamento</h6>
                    <button type="button" onclick="adicionar_forma_pagamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                        <span class="tf-icons mdi mdi-plus me-1"></span> Forma Pgt
                    </button>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Forma Pagamento</th>
                                <th>Parcelas</th>
                                <th>ID Pgto / DOC</th>
                                <th>Valor</th>
                                <th>Arquivo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tabela_formas_pagamento">
                            <tr id="linha_forma_pagamento_1">
                                <td>
                                    <select required id="forma_pagamento_1" onchange="controle_parcelas(1)" name="forma_pagamento_1" class="form-select">
                                        <option value="">Opções</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Débito">Débito</option>
                                        <option value="Crédito">Crédito</option>
                                        <option value="Pix">Pix</option>
                                        <option value="Link de Pagamento">Link de Pagamento</option>
                                    </select>
                                </td>
                                <td>
                                    <select disabled id="parcelas_1" name="parcelas_1" class="form-select">
                                        <option value="">Opções</option>
                                        @for($n=1;$n<=10;$n++)<option value="{{ $n }}">{{ $n }}</option>@endfor
                                    </select>
                                </td>
                                <td><input class="form-control" type="text" id="id_transacao_1" name="id_transacao_1"/></td>
                                <td>
                                    <input required class="form-control" type="text" id="vl_pagamento_1" name="vl_pagamento_1" value="{{ valorDbForm($saldo_parcela) }}" onkeypress="return(MascaraMoeda(this,'.',',',event))"/>
                                </td>
                                <td>
                                    <input class="form-control" type="file" id="arquivo_1" name="arquivo_1"/>
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-primary">Registrar Pagamento</button>
                </div>
            </form>
        @endif
    </div>
</div>

@if(in_array($semana->situacao, ['Agendada', 'Aplicação Parcial']))
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title mb-0">Fila de Aplicação</h4>
        <hr>
        @if($semana_paga)
            <form action="{{ route('sistema.prescricoes.enviar_fila_aplicacao') }}" method="post">
                @csrf
                <input type="hidden" name="semana_id" value="{{ $semana->id }}">
                <p class="mb-2">Esta semana está com o pagamento em dia. Pode ser enviada para a fila de aplicação.</p>
                <button type="submit" class="btn btn-primary">Enviar Para Fila de Aplicação</button>
            </form>
        @else
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="mdi mdi-alert-circle-outline me-2"></i>
                <div>Esta semana <b>não está paga</b>. Para enviar à fila de aplicação, é necessária a autorização de um administrador.</div>
            </div>
            <form autocomplete="off" action="{{ route('sistema.prescricoes.enviar_fila_aplicacao_sem_pagamento') }}" method="post">
                @csrf
                <input type="hidden" name="semana_id" value="{{ $semana->id }}">
                <div class="row align-items-end gy-2">
                    <div class="col-md-4">
                        <label class="form-label" for="autorizador_email">Email do administrador</label>
                        <input required type="email" class="form-control" name="autorizador_email" id="autorizador_email">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="autorizador_senha">Senha do administrador</label>
                        <input required type="password" class="form-control" name="autorizador_senha" id="autorizador_senha">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Enviar Para Fila de Aplicação</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
@endif

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title mb-0">Todas as Semanas da Prescrição</h4>
        <hr>
        <div class="table-responsive">
            <table class="tabela-index table table-sm nowrap" id="table-semanas-prescricao">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Semana</th>
                        <th>Dt Prevista</th>
                        <th>Dt Aplicada</th>
                        <th>Medicações</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($semanas as $s)
                        @php
                        $badge = 'bg-label-secondary';
                        if($s->situacao == 'Agendada'){ $badge = 'bg-label-warning'; }
                        elseif($s->situacao == 'Fila de Aplicação'){ $badge = 'bg-label-primary'; }
                        elseif($s->situacao == 'Em Atendimento'){ $badge = 'bg-label-primary'; }
                        elseif($s->situacao == 'Aplicada'){ $badge = 'bg-label-success'; }
                        elseif($s->situacao == 'Aplicação Parcial'){ $badge = 'bg-label-warning'; }
                        elseif($s->situacao == 'Cancelada'){ $badge = 'bg-label-danger'; }
                        @endphp
                        <tr @if($s->id == $semana->id) class="table-active" @endif>
                            <td>
                                <a href="{{ route('sistema.prescricoes.acessar_semana', $s->id) }}" class="btn btn-sm btn-icon btn-label-primary @if($s->id == $semana->id) disabled @endif" title="Acessar semana {{ $s->nr_semana }}">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                            </td>
                            <td class="fw-medium">
                                Semana {{ $s->nr_semana }}
                                @if($s->id == $semana->id)
                                    <span class="badge bg-primary ms-1">atual</span>
                                @endif
                            </td>
                            <td>{{ $s->data_prevista ? dataDbForm($s->data_prevista) : '-' }}</td>
                            <td>{{ $s->data_aplicada ? dataDbForm($s->data_aplicada) : '-' }}</td>
                            <td>{{ $s->medicamentos->count() ? $s->medicamentos->count() . ' medicação(ões)' : '-' }}</td>
                            <td><span class="badge rounded-pill {{ $badge }}">{{ $s->situacao }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title mb-0">Logs da Semana</h4>
        <hr>
        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
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
                            <tr class="{{ $index > 9 ? 'd-none more-logs-semana' : '' }}">
                                <td>{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}</td>
                                <td>{{ $log->user ? $log->user->nome : 'Sistema' }}</td>
                                <td><span class="badge bg-label-info">{{ $log->acao }}</span></td>
                                <td>{{ $log->descricao }}</td>
                                <td>
                                    @if($log->dados_novos)
                                        <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#log_semana_{{ $log->id }}">Ver Detalhes</button>
                                        <div class="collapse" id="log_semana_{{ $log->id }}">
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
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($logs->count() > 10)
                <div class="text-center mt-2">
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="document.querySelectorAll('.more-logs-semana').forEach(el => el.classList.remove('d-none')); this.style.display='none'">
                        Ver mais ({{ $logs->count() - 10 }})
                    </button>
                </div>
            @endif
        @else
            <p class="text-muted mb-0">Nenhum log registrado para esta semana.</p>
        @endif
    </div>
</div>

<script>
window.addEventListener('load',()=>{
  $('#table-semanas-prescricao').DataTable({
    order: [[1, 'asc']],
    "language": {
        "sEmptyTable": "Nenhum registro encontrado",
        "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
        "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
        "sInfoFiltered": "(Filtrados de _MAX_ registros)",
        "sInfoPostFix": "",
        "sInfoThousands": ".",
        "sLengthMenu": "_MENU_ resultados por página",
        "sLoadingRecords": "Carregando...",
        "sProcessing": "Processando...",
        "sZeroRecords": "Nenhum registro encontrado",
        "sSearch": "Pesquisar",
        "oPaginate": {
            "sNext": "Próximo",
            "sPrevious": "Anterior",
            "sFirst": "Primeiro",
            "sLast": "Último"
        },
        "oAria": {
            "sSortAscending": ": Ordenar colunas de forma ascendente",
            "sSortDescending": ": Ordenar colunas de forma descendente"
        }
    }
  });
})
</script>

<script>
let contador_formas_pagamento = 1;
let saldo_parcela_pagamento = {{ $parcela ? max(0, $parcela->valor_parcela - $parcela->valor_pago) : 0 }};

function validar_pagamento(){
    let total = 0;
    document.querySelectorAll('#tabela_formas_pagamento input[name^="vl_pagamento_"]').forEach(inp => {
        total += parseFloat((inp.value || '0').replace(/\./g, '').replace(',', '.')) || 0;
    });
    if(total > saldo_parcela_pagamento + 0.005){
        alert('O valor do pagamento (R$ ' + total.toFixed(2).replace('.', ',') + ') é maior que o saldo da parcela desta semana (R$ ' + saldo_parcela_pagamento.toFixed(2).replace('.', ',') + '). Informe um valor menor ou igual ao saldo.');
        return false;
    }
    return true;
}

const opcoes_formas_pagamento = '<option value="">Opções</option><option value="Dinheiro">Dinheiro</option><option value="Débito">Débito</option><option value="Crédito">Crédito</option><option value="Pix">Pix</option><option value="Link de Pagamento">Link de Pagamento</option>';
const opcoes_parcelas_pagamento = '@for($n=1;$n<=10;$n++)<option value="{{ $n }}">{{ $n }}</option>@endfor';

function adicionar_forma_pagamento(){
    contador_formas_pagamento++;
    document.getElementById('contador_formas').value = contador_formas_pagamento;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_forma_pagamento_' + contador_formas_pagamento);
    tr.innerHTML = `
        <td><select required id="forma_pagamento_${contador_formas_pagamento}" onchange="controle_parcelas(${contador_formas_pagamento})" name="forma_pagamento_${contador_formas_pagamento}" class="form-select">${opcoes_formas_pagamento}</select></td>
        <td><select disabled id="parcelas_${contador_formas_pagamento}" name="parcelas_${contador_formas_pagamento}" class="form-select">${opcoes_parcelas_pagamento}</select></td>
        <td><input class="form-control" type="text" id="id_transacao_${contador_formas_pagamento}" name="id_transacao_${contador_formas_pagamento}"/></td>
        <td><input required class="form-control" type="text" id="vl_pagamento_${contador_formas_pagamento}" name="vl_pagamento_${contador_formas_pagamento}" value="0,00" onkeypress="return(MascaraMoeda(this,'.',',',event))"/></td>
        <td><input class="form-control" type="file" id="arquivo_${contador_formas_pagamento}" name="arquivo_${contador_formas_pagamento}"/></td>
        <td><button type="button" onclick="remover_forma_pagamento(${contador_formas_pagamento})" class="btn btn-sm btn-icon btn-label-danger" title="Remover forma"><span class="tf-icons mdi mdi-delete"></span></button></td>`;
    document.getElementById('tabela_formas_pagamento').appendChild(tr);
}

function remover_forma_pagamento(n){
    let el = document.getElementById('linha_forma_pagamento_' + n);
    if(el){ el.remove(); }
}

function controle_parcelas(n){
    let forma = document.getElementById('forma_pagamento_' + n).value;
    let sel = document.getElementById('parcelas_' + n);
    if(forma == 'Crédito' || forma == 'Link de Pagamento'){
        sel.disabled = false;
        sel.required = true;
    } else {
        sel.disabled = true;
        sel.required = false;
        sel.value = '';
    }
}
</script>

@endsection
