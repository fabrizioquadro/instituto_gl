@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

@php
switch($prescricao->situacao_financeira){
    case 'Pago': $badge_fin = 'bg-success'; break;
    case 'Parcial': $badge_fin = 'bg-warning'; break;
    case 'Em Aberto': $badge_fin = 'bg-danger'; break;
    default: $badge_fin = 'bg-secondary';
}
@endphp

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Financeiro — Prescrição #{{ $prescricao->id }}</h4>
            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal_registrar_pagamento">
                    <span class="tf-icons mdi mdi-cash-plus me-1"></span> Registrar Pagamento
                </button>
                <a href="{{ route('sistema.prescricoes.acessar', $prescricao->id) }}" class="btn btn-outline-dark btn-sm">Voltar à Prescrição</a>
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
                        <th>Data Prescrição</th>
                        <td>{{ $prescricao->data_prescricao ? dataDbForm($prescricao->data_prescricao) : '-' }}</td>
                    </tr>
                    <tr>
                        <th>Clínica</th>
                        <td>{{ $prescricao->clinica->nome ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="w-25">Valor Tratamento</th>
                        <td>R$ {{ number_format($prescricao->valor_tratamento, 2, ',', '.') }}</td>
                    </tr>
                    @if((float) $prescricao->credito_em_aberto > 0)
                    <tr>
                        <th>Crédito em Aberto</th>
                        <td>
                            <span class="text-success fw-semibold">R$ {{ number_format($prescricao->credito_em_aberto, 2, ',', '.') }}</span>
                            <span class="text-muted small d-block">Utilizado de prescrição anterior (já abatido do valor a parcelar)</span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th>Total Pago</th>
                        <td>R$ {{ number_format($prescricao->total_pago, 2, ',', '.') }}</td>
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
        <h4 class="card-title mb-0">Parcelas</h4>
        <hr>
        <div class="table-responsive">
            <table class="tabela-index table table-sm nowrap" id="table-parcelas">
                <thead class="table-light">
                    <tr>
                        <th>Parcela</th>
                        <th>Semana</th>
                        <th>Dt Vencimento</th>
                        <th>Valor Parcela</th>
                        <th>Valor Pago</th>
                        <th>Saldo</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescricao->parcelas as $parcela)
                        @php
                        $badge_p = 'bg-danger';
                        if($parcela->situacao == 'Paga'){ $badge_p = 'bg-success'; }
                        elseif($parcela->situacao == 'Parcial'){ $badge_p = 'bg-warning'; }
                        elseif($parcela->situacao == 'Cancelada'){ $badge_p = 'bg-secondary'; }
                        $saldo = max(0, $parcela->valor_parcela - $parcela->valor_pago);
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ $parcela->nr_parcela }}</td>
                            <td>{{ $parcela->semana ? 'Semana ' . $parcela->semana->nr_semana : '-' }}</td>
                            <td>{{ $parcela->dt_vencimento ? dataDbForm($parcela->dt_vencimento) : '-' }}</td>
                            <td>R$ {{ number_format($parcela->valor_parcela, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($parcela->valor_pago, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($saldo, 2, ',', '.') }}</td>
                            <td><span class="badge rounded-pill {{ $badge_p }}">{{ $parcela->situacao }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Nenhuma parcela.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title mb-0">Pagamentos</h4>
        <hr>
        <div class="table-responsive">
            <table class="tabela-index table table-sm nowrap" id="table-pagamentos">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Formas de Pagamento</th>
                        <th>Obs</th>
                        <th>Registrado por</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescricao->pagamentos as $pagamento)
                        <tr>
                            <td>{{ $pagamento->dt_pagamento ? dataDbForm($pagamento->dt_pagamento) : '-' }}</td>
                            <td>R$ {{ number_format($pagamento->vl_total, 2, ',', '.') }}</td>
                            <td>
                                @if($pagamento->formas->count())
                                    @foreach($pagamento->formas as $forma)
                                        <span class="badge bg-label-primary me-1">{{ $forma->forma_pagamento }} — R$ {{ number_format($forma->vl_pagamento, 2, ',', '.') }}@if($forma->parcelas > 1) ({{ $forma->parcelas }}x)@endif</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $pagamento->obs ?? '-' }}</td>
                            <td>{{ $pagamento->user->nome ?? '-' }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-icon btn-label-primary" title="Editar" onclick="abrir_modal_editar_pagamento({{ $pagamento->id }})">
                                    <span class="tf-icons mdi mdi-pencil"></span>
                                </button>
                                <form action="{{ route('sistema.prescricoes.excluir_pagamento') }}" method="post" class="d-inline" onsubmit="return confirm('Excluir este pagamento e estornar os valores das parcelas?');">
                                    @csrf
                                    <input type="hidden" name="pagamento_id" value="{{ $pagamento->id }}">
                                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Excluir">
                                        <span class="tf-icons mdi mdi-delete"></span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Nenhum pagamento registrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title mb-0">Histórico Financeiro</h4>
        <hr>
        @if($logs_financeiro->count() > 0)
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
                        @foreach($logs_financeiro as $log)
                            <tr>
                                <td>{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}</td>
                                <td>{{ $log->user ? $log->user->nome : 'Sistema' }}</td>
                                <td><span class="badge bg-label-info">{{ $log->acao }}</span></td>
                                <td>{{ $log->descricao }}</td>
                                <td>
                                    @if($log->dados_novos)
                                        <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#fin_log_{{ $log->id }}">Ver Detalhes</button>
                                        <div class="collapse" id="fin_log_{{ $log->id }}">
                                            <div class="mt-2 text-wrap" style="font-size: 0.8rem">
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
        @else
            <p class="text-muted mb-0">Nenhum log financeiro registrado.</p>
        @endif
    </div>
</div>

<style>
.table.table-sm.dataTable thead th,
.table.table-sm.dataTable thead td,
.table.table-sm.dataTable tbody th,
.table.table-sm.dataTable tbody td {
    padding: 0.3125rem 0.625rem !important;
    white-space: nowrap !important;
}
</style>
<script>
window.addEventListener('load',()=>{
  $('#table-parcelas, #table-pagamentos').DataTable({
    order: [[0, 'asc']],
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

{{-- MODAL REGISTRAR PAGAMENTO --}}
<div class="modal fade" id="modal_registrar_pagamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary d-flex align-items-center">
                    <span class="mdi mdi-cash-plus mdi-24px me-2"></span>Registrar Pagamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sistema.prescricoes.registrar_pagamento') }}" method="post" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="prescricao_id" value="{{ $prescricao->id }}">
                <input type="hidden" name="contador_formas" id="contador_formas_financeiro" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Como deseja lançar o pagamento?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modo_pagamento" id="modo_reestruturar" value="1">
                            <label class="form-check-label" for="modo_reestruturar">
                                1 - Lançar o valor na próxima parcela aberta e recalcular o valor das demais
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modo_pagamento" id="modo_parcela_parcela" value="2" checked>
                            <label class="form-check-label" for="modo_parcela_parcela">
                                2 - Pagar parcela por parcela
                            </label>
                        </div>
                        <small class="text-muted" id="hint_modo_pagamento">O valor é aplicado parcela por parcela (na ordem) até o dinheiro acabar.</small>
                    </div>
                    <div class="row gy-3">
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
                        <button type="button" onclick="adicionar_forma_pagamento_financeiro()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
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
                            <tbody id="tabela_formas_financeiro">
                                <tr id="linha_forma_financeiro_1">
                                    <td>
                                        <select required id="forma_pagamento_financeiro_1" onchange="controle_parcelas_financeiro(1)" name="forma_pagamento_1" class="form-select">
                                            <option value="">Opções</option>
                                            <option value="Dinheiro">Dinheiro</option>
                                            <option value="Débito">Débito</option>
                                            <option value="Crédito">Crédito</option>
                                            <option value="Pix">Pix</option>
                                            <option value="Link de Pagamento">Link de Pagamento</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select disabled id="parcelas_financeiro_1" name="parcelas_1" class="form-select">
                                            <option value="">Opções</option>
                                            @for($n=1;$n<=10;$n++)<option value="{{ $n }}">{{ $n }}</option>@endfor
                                        </select>
                                    </td>
                                    <td><input class="form-control" type="text" id="id_transacao_financeiro_1" name="id_transacao_1"/></td>
                                    <td><input required class="form-control" type="text" id="vl_pagamento_financeiro_1" name="vl_pagamento_1" value="0,00" onkeypress="return(MascaraMoeda(this,'.',',',event))"/></td>
                                    <td><input class="form-control" type="file" id="arquivo_financeiro_1" name="arquivo_1"/></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Registrar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let contador_formas_financeiro = 1;
const opcoes_formas_financeiro = '<option value="">Opções</option><option value="Dinheiro">Dinheiro</option><option value="Débito">Débito</option><option value="Crédito">Crédito</option><option value="Pix">Pix</option><option value="Link de Pagamento">Link de Pagamento</option>';
const opcoes_parcelas_financeiro = '@for($n=1;$n<=10;$n++)<option value="{{ $n }}">{{ $n }}</option>@endfor';

function adicionar_forma_pagamento_financeiro(){
    contador_formas_financeiro++;
    document.getElementById('contador_formas_financeiro').value = contador_formas_financeiro;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_forma_financeiro_' + contador_formas_financeiro);
    tr.innerHTML = `
        <td><select required id="forma_pagamento_financeiro_${contador_formas_financeiro}" onchange="controle_parcelas_financeiro(${contador_formas_financeiro})" name="forma_pagamento_${contador_formas_financeiro}" class="form-select">${opcoes_formas_financeiro}</select></td>
        <td><select disabled id="parcelas_financeiro_${contador_formas_financeiro}" name="parcelas_${contador_formas_financeiro}" class="form-select">${opcoes_parcelas_financeiro}</select></td>
        <td><input class="form-control" type="text" id="id_transacao_financeiro_${contador_formas_financeiro}" name="id_transacao_${contador_formas_financeiro}"/></td>
        <td><input required class="form-control" type="text" id="vl_pagamento_financeiro_${contador_formas_financeiro}" name="vl_pagamento_${contador_formas_financeiro}" value="0,00" onkeypress="return(MascaraMoeda(this,'.',',',event))"/></td>
        <td><input class="form-control" type="file" id="arquivo_financeiro_${contador_formas_financeiro}" name="arquivo_${contador_formas_financeiro}"/></td>
        <td><button type="button" onclick="remover_forma_pagamento_financeiro(${contador_formas_financeiro})" class="btn btn-sm btn-icon btn-label-danger" title="Remover forma"><span class="tf-icons mdi mdi-delete"></span></button></td>`;
    document.getElementById('tabela_formas_financeiro').appendChild(tr);
}

function remover_forma_pagamento_financeiro(n){
    let el = document.getElementById('linha_forma_financeiro_' + n);
    if(el){ el.remove(); }
}

function controle_parcelas_financeiro(n){
    let forma = document.getElementById('forma_pagamento_financeiro_' + n).value;
    let sel = document.getElementById('parcelas_financeiro_' + n);
    if(forma == 'Crédito' || forma == 'Link de Pagamento'){
        sel.disabled = false;
        sel.required = true;
    } else {
        sel.disabled = true;
        sel.required = false;
        sel.value = '';
    }
}

document.addEventListener('change', function(e){
    if(e.target && e.target.name === 'modo_pagamento'){
        let hint = document.getElementById('hint_modo_pagamento');
        if(e.target.value == '1'){
            hint.textContent = 'O valor informado vira a 1ª parcela aberta e a diferença é dividida entre as demais parcelas.';
        } else {
            hint.textContent = 'O valor é aplicado parcela por parcela (na ordem) até o dinheiro acabar.';
        }
    }
});
</script>

{{-- MODAL EDITAR PAGAMENTO --}}
<div class="modal fade" id="modal_editar_pagamento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary d-flex align-items-center">
                    <span class="mdi mdi-pencil mdi-24px me-2"></span>Editar Pagamento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('sistema.prescricoes.update_pagamento') }}" method="post" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="pagamento_id" id="editar_pagamento_id">
                <input type="hidden" name="contador_formas" id="contador_formas_edicao" value="0">
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="mdi mdi-information-outline me-2"></i>
                        <div>O pagamento antigo é estornado das parcelas e o novo valor é aplicado parcela por parcela.</div>
                    </div>
                    <div class="row gy-3">
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input class="form-control" type="date" id="editar_dt_pagamento" name="dt_pagamento"/>
                                <label for="editar_dt_pagamento">Data do Pagamento:</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-floating form-floating-outline">
                                <input class="form-control" type="text" id="editar_obs_pagamento" name="obs_pagamento"/>
                                <label for="editar_obs_pagamento">Obs:</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <h6 class="card-title mb-0">Formas de Pagamento</h6>
                        <small class="text-muted">O valor não pode ser alterado aqui. Para mudar, exclua e cadastre novamente.</small>
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
                            <tbody id="tabela_formas_edicao"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Pagamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let pagamentosFinanceiro = @json($pagamentos_json);
let contador_formas_edicao = 0;
const opcoes_formas_edicao = '<option value="">Opções</option><option value="Dinheiro">Dinheiro</option><option value="Débito">Débito</option><option value="Crédito">Crédito</option><option value="Pix">Pix</option><option value="Link de Pagamento">Link de Pagamento</option>';
const opcoes_parcelas_edicao = '@for($n=1;$n<=10;$n++)<option value="{{ $n }}">{{ $n }}</option>@endfor';

function adicionar_linha_forma_edicao(data){
    contador_formas_edicao++;
    let n = contador_formas_edicao;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_forma_edicao_' + n);
    let isParcela = data && (data.forma == 'Crédito' || data.forma == 'Link de Pagamento');
    let parcelasAttr = isParcela ? '' : 'disabled';
    let parcelasOpts = opcoes_parcelas_edicao;
    if(isParcela && data.parcelas){
        parcelasOpts = parcelasOpts.replace('<option value="'+data.parcelas+'">', '<option value="'+data.parcelas+'" selected>');
    }
    tr.innerHTML = `
        <td><select required id="edita_forma_${n}" onchange="controle_parcelas_edicao(${n})" name="forma_pagamento_${n}" class="form-select">${opcoes_formas_edicao}</select></td>
        <td><select ${parcelasAttr} id="edita_parcelas_${n}" name="parcelas_${n}" class="form-select">${parcelasOpts}</select></td>
        <td><input class="form-control" type="text" id="edita_id_transacao_${n}" name="id_transacao_${n}" value="${data ? (data.id_transacao || '') : ''}"/></td>
        <td><input readonly class="form-control" type="text" id="edita_vl_${n}" value="${data ? data.valor.toFixed(2).replace('.', ',') : '0,00'}" title="Valor não editável"/></td>
        <td><input class="form-control" type="file" id="edita_arquivo_${n}" name="arquivo_${n}"/></td>`;
    document.getElementById('tabela_formas_edicao').appendChild(tr);
    if(data && data.forma){
        document.getElementById('edita_forma_' + n).value = data.forma;
    }
    document.getElementById('contador_formas_edicao').value = contador_formas_edicao;
}

function remover_linha_forma_edicao(n){
    let el = document.getElementById('linha_forma_edicao_' + n);
    if(el){ el.remove(); }
}

function controle_parcelas_edicao(n){
    let forma = document.getElementById('edita_forma_' + n).value;
    let sel = document.getElementById('edita_parcelas_' + n);
    if(forma == 'Crédito' || forma == 'Link de Pagamento'){
        sel.disabled = false;
        sel.required = true;
    } else {
        sel.disabled = true;
        sel.required = false;
        sel.value = '';
    }
}

function abrir_modal_editar_pagamento(id){
    let p = pagamentosFinanceiro.find(x => x.id === id);
    if(!p) return;
    document.getElementById('editar_pagamento_id').value = id;
    document.getElementById('editar_dt_pagamento').value = p.dt_pagamento || '';
    document.getElementById('editar_obs_pagamento').value = p.obs || '';
    contador_formas_edicao = 0;
    document.getElementById('tabela_formas_edicao').innerHTML = '';
    let formas = p.formas || [];
    formas.forEach(f => adicionar_linha_forma_edicao(f));
    if(formas.length === 0){ adicionar_linha_forma_edicao(null); }
    let modal = new bootstrap.Modal(document.getElementById('modal_editar_pagamento'));
    modal.show();
}
</script>
@endsection
