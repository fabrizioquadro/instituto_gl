@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<style>
/* botões de ação não devem ficar fixos (floating) */
.btn-fab:not(.demo) {
    position: static !important;
    bottom: auto !important;
    right: auto !important;
    margin: 0 !important;
    z-index: auto !important;
}
</style>
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Semanas</h4>
            <a href="{{ route('sistema.prescricoes.acessar', $prescricao->id) }}" class="btn btn-outline-dark btn-sm">Voltar</a>
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

        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="mdi mdi-information-outline me-2"></i>
            <div>Paciente: <b>{{ $prescricao->paciente->nm_paciente ?? '-' }}</b> — Prescrição #{{ $prescricao->id }}. Use o gerador para adicionar as novas semanas.</div>
        </div>

        <div class="card card-border-shadow-secondary mb-4">
            <div class="card-body">
                <h5 class="card-title mb-0">Semanas Já Cadastradas</h5>
                <hr>
                @if($prescricao->semanas->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Semana</th>
                                    <th>Data Prevista</th>
                                    <th>Data Aplicada</th>
                                    <th>Medicações</th>
                                    <th>Pagamento</th>
                                    <th>Situação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prescricao->semanas as $s)
                                    @php
                                    $badge_s = 'bg-label-secondary';
                                    if($s->situacao == 'Agendada'){ $badge_s = 'bg-label-warning'; }
                                    elseif(in_array($s->situacao, ['Fila de Aplicação', 'Em Atendimento'])){ $badge_s = 'bg-label-info'; }
                                    elseif($s->situacao == 'Aplicada'){ $badge_s = 'bg-label-success'; }
                                    elseif($s->situacao == 'Aplicação Parcial'){ $badge_s = 'bg-label-primary'; }
                                    elseif($s->situacao == 'Cancelada'){ $badge_s = 'bg-label-danger'; }
                                    @endphp
                                    <tr>
                                        <td class="fw-medium">Semana {{ $s->nr_semana }}</td>
                                        <td>{{ $s->data_prevista ? dataDbForm($s->data_prevista) : '-' }}</td>
                                        <td>{{ $s->data_aplicada ? dataDbForm($s->data_aplicada) : '-' }}</td>
                                        <td>
                                            @if($s->medicamentos->count() > 0)
                                                @foreach($s->medicamentos as $m)
                                                    <div class="small">{{ $m->medicamento->nome ?? '?' }} ({{ $m->quantidade }})</div>
                                                @endforeach
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($s->parcela)
                                                @php
                                                $badge_pag = 'bg-danger';
                                                if($s->parcela->situacao == 'Paga'){ $badge_pag = 'bg-success'; }
                                                elseif($s->parcela->situacao == 'Parcial'){ $badge_pag = 'bg-warning'; }
                                                @endphp
                                                <span class="badge rounded-pill {{ $badge_pag }}">{{ $s->parcela->situacao }}</span>
                                                <div class="small text-muted">R$ {{ number_format($s->parcela->valor_pago, 2, ',', '.') }} / R$ {{ number_format($s->parcela->valor_parcela, 2, ',', '.') }}</div>
                                            @else
                                                <span class="badge rounded-pill bg-secondary">Sem Parcela</span>
                                            @endif
                                        </td>
                                        <td><span class="badge rounded-pill {{ $badge_s }}">{{ $s->situacao }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Nenhuma semana cadastrada ainda.</p>
                @endif
            </div>
        </div>

        <form id="form_adicionar_semanas" action="{{ route('sistema.prescricoes.insert_semana') }}" method="post">
            @csrf
            <input type="hidden" name="prescricao_id" value="{{ $prescricao->id }}">
            <input type="hidden" name="contador_procedimentos" id="contador_procedimentos" value="1">

            <div class="row mt-2 gy-3 align-items-end">
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="valor_adicional" name="valor_adicional" onkeypress="return(MascaraMoeda(this,'.',',',event))" onkeyup="recalcular_financeiro()"/>
                        <label for="valor_adicional">Valor Adicional Total (R$):</label>
                    </div>
                    <small class="text-muted">Dividido igualmente entre as novas semanas <b>com medicação</b> (pausa fica fora). Gera as parcelas continuando a numeração existente.</small>
                </div>
                <div class="col-md-8">
                    <button type="button" id="botao_gerador" class="btn btn-outline-info waves-effect">
                        <span class="tf-icons mdi mdi-cog-outline me-1"></span> Gerador de Semanas
                    </button>
                    <button type="button" id="botao_adicionar_procedimento" onclick="adicionar_procedimento()" class="btn btn-label-primary waves-effect">
                        <span class="tf-icons mdi mdi-plus me-1"></span> Semana
                    </button>
                </div>
            </div>

            {{-- SEMANAS --}}
            <div id="div_procedimentos">
                <div id="card_1" class="card card-border-shadow-primary mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title">Semana 1</h5>
                            <button type="button" onclick="remover_procedimento(1)" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab" title="Remover semana">
                                <span class="tf-icons mdi mdi-close mdi-24px"></span>
                            </button>
                        </div>
                        <div class="row mt-2 gy-3 align-items-end">
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <input required class="form-control" type="date" id="data_prevista_1" name="data_prevista_1" onchange="recalcular_financeiro()"/>
                                    <label for="data_prevista_1">Data Prevista:</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" value="true" id="pausa_1" name="pausa_1" onchange="recalcular_financeiro()">
                                    <label class="form-check-label" for="pausa_1">Pausa (sem medicação)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating form-floating-outline">
                                    <input class="form-control" type="text" id="obs_1" name="obs_1"/>
                                    <label for="obs_1">Obs:</label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <h6 class="card-title mb-0">Medicações</h6>
                            <button type="button" onclick="adicionar_medicamento(1)" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                                <span class="tf-icons mdi mdi-plus me-1"></span> Medicamento
                            </button>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Medicamento</th>
                                        <th>Quantidade</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_medicamentos_1">
                                    <tr id="linha_medicamento_1_1">
                                        <td>
                                            <select name="medicamento_id_1_1" class="form-select" onchange="recalcular_financeiro()">
                                                <option value="">— Selecionar —</option>
                                                @foreach($medicamentos as $medicamento)
                                                    <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="quantidade_1_1" class="form-control" value="1" onkeyup="recalcular_financeiro()"></td>
                                        <td>
                                            <button type="button" onclick="remover_medicamento(1,1)" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab">
                                                <span class="tf-icons mdi mdi-delete mdi-24px"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <input type="hidden" name="contador_medicamentos_1" id="contador_medicamentos_1" value="1">
                        </div>
                    </div>
                </div>
            </div>

            {{-- RESUMO DE MEDICAMENTOS --}}
            <div class="card card-border-shadow-primary mt-4">
                <div class="card-body">
                    <h5 class="card-title">Resumo de Medicamentos</h5>
                    <hr>
                    <p class="text-muted mb-2">Total de cada medicamento que será adicionado, para conferência antes de salvar.</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Quantidade Total</th>
                                </tr>
                            </thead>
                            <tbody id="tabela_resumo_medicamentos">
                                <tr><td colspan="2" class="text-center text-muted">Nenhum medicamento adicionado ainda.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- FINANCEIRO --}}
            <div class="card card-border-shadow-primary mt-4">
                <div class="card-body">
                    <h5 class="card-title">Financeiro</h5>
                    <hr>
                    <p class="text-muted mb-2">As parcelas são geradas a partir do valor adicional, uma por nova semana <b>com medicação</b> (semanas de pausa ficam fora do cálculo).</p>
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="modo_financeiro" id="modo_financeiro_1" value="1" checked onchange="recalcular_financeiro()">
                            <label class="form-check-label" for="modo_financeiro_1">Ratear somente nas parcelas novas</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="modo_financeiro" id="modo_financeiro_2" value="2" onchange="recalcular_financeiro()">
                            <label class="form-check-label" for="modo_financeiro_2">Reestruturar: somar o aberto existente + novo e dividir por todas as semanas não totalmente pagas</label>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Parcela</th>
                                    <th>Semana</th>
                                    <th>Vencimento</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody id="tabela_parcelas">
                                <tr><td colspan="4" class="text-center text-muted">Informe o valor adicional e as semanas com medicação para gerar as parcelas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL GERADOR --}}
<div class="modal fade" id="modal_gerador" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Gerador de Semanas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="gerador_contador_medicamentos" value="1">
                <div class="row gy-3">
                    <div class="col-md-4">
                        <label class="form-label" for="gerador_dt_inicio">Data 1ª Semana:</label>
                        <input class="form-control" type="date" id="gerador_dt_inicio"/>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="gerador_nr_procedimentos">Nr. de Semanas:</label>
                        <input class="form-control" type="number" id="gerador_nr_procedimentos" min="1"/>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="gerador_intervalo">Intervalo entre Semanas (dias):</label>
                        <input class="form-control" type="number" id="gerador_intervalo" min="0" value="7"/>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <h6 class="mb-0">Medicações (aplicadas em todas as semanas geradas)</h6>
                    <div>
                        <button type="button" onclick="gerador_adicionar_combo()" class="btn btn-sm rounded-pill btn-outline-info waves-effect">
                            <span class="tf-icons mdi mdi-cube-outline me-1"></span> Combo
                        </button>
                        <button type="button" onclick="gerador_adicionar_medicamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                            <span class="tf-icons mdi mdi-plus me-1"></span> Medicamento
                        </button>
                    </div>
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Quantidade</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="gerador_tabela_medicamentos">
                            <tr id="gerador_linha_medicamento_1">
                                <td>
                                    <select id="gerador_medicamento_id_1" class="form-select">
                                        <option value="">— Selecionar —</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" id="gerador_quantidade_1" class="form-control" value="1"></td>
                                <td>
                                    <button type="button" onclick="gerador_remover_medicamento(1)" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab">
                                        <span class="tf-icons mdi mdi-delete mdi-24px"></span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" type="button" onclick="gera_procedimentos_gerador()">Gerar Semanas</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL COMBOS (gerador) --}}
<div class="modal fade" id="modal_combos" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Combos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mt-2 gy-4">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <select id="combo_id" class="select2 form-select">
                                <option value="">Opções</option>
                                @foreach($combos as $combo)
                                    <option value="{{ $combo->id }}">{{ $combo->nome }}</option>
                                @endforeach
                            </select>
                            <label for="combo_id">Escolha o Combo para inserir:</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <button class="btn btn-primary" type="button" id="adicionar_gerador_combo">Adicionar</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE ERROS DE VALIDAÇÃO --}}
<div class="modal fade" id="modal_erros_validacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger d-flex align-items-center">
                    <span class="mdi mdi-alert-circle-outline mdi-24px me-2"></span>Inconsistências na Adição de Semanas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">Por favor, corrija os seguintes itens antes de salvar:</p>
                <ul id="lista_erros_validacao" class="list-unstyled text-danger ps-2"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE CONFIRMAÇÃO --}}
<div class="modal fade" id="modal_confirmacao_prescricao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary d-flex align-items-center">
                    <span class="mdi mdi-checkbox-marked-circle-outline mdi-24px me-2"></span>Confirmar Adição de Semanas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">Revise o resumo abaixo antes de confirmar:</p>
                <h6 class="mb-2">Semanas</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-light">
                            <tr><th>Semana</th><th>Data Prevista</th><th>Medicamentos / Situação</th></tr>
                        </thead>
                        <tbody id="tabela_confirmacao_semanas"></tbody>
                    </table>
                </div>
                <h6 class="mb-2 mt-3">Resumo Financeiro (Parcelas)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-light">
                            <tr><th>Parcela</th><th>Semana</th><th>Vencimento</th><th>Valor</th></tr>
                        </thead>
                        <tbody id="tabela_confirmacao_parcelas"></tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end fw-bold">Valor Adicional Total:</th>
                                <th id="confirmacao_valor_adicional" class="text-end fw-bold font-monospace">R$ 0,00</th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end fw-bold text-primary">Total a Distribuir:</th>
                                <th id="confirmacao_total_parcelar" class="text-end fw-bold font-monospace text-primary">R$ 0,00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Corrigir Dados</button>
                <button type="button" id="confirmar_e_salvar_prescricao" class="btn btn-primary">Confirmar e Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
let opcoes_medicamentos = `@foreach($medicamentos as $medicamento)<option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>@endforeach`;
let semanasExistentes = @json($prescricao->semanas->pluck('data_prevista')->filter()->values());
let parcelasExistentesAbertas = @json($parcelas_abertas);
let semanaOffset = {{ $qt_semanas_existentes }};      // semanas já existentes na prescrição
let parcelaOffset = {{ $ultima_parcela_nr + 1 }};     // próxima parcela a criar
let modalGerador;
let modalCombo;
let isConfirmed = false;
let modalConfirmacaoPrescricao;
let modalErrosPrescricao;

// ---------- SEMANAS ----------
function adicionar_procedimento(dt = '', obs = '', pausa = false){
    let contador = parseInt(document.getElementById('contador_procedimentos').value) + 1;
    document.getElementById('contador_procedimentos').value = contador;
    let div = document.createElement('div');
    div.setAttribute('id', 'card_' + contador);
    div.className = 'card card-border-shadow-primary mt-4';
    div.innerHTML = `
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h5 class="card-title">Semana ${contador + semanaOffset}</h5>
                <button type="button" onclick="remover_procedimento(${contador})" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab" title="Remover semana">
                    <span class="tf-icons mdi mdi-close mdi-24px"></span>
                </button>
            </div>
            <div class="row mt-2 gy-3 align-items-end">
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="date" id="data_prevista_${contador}" name="data_prevista_${contador}" value="${dt}" onchange="recalcular_financeiro()"/>
                        <label for="data_prevista_${contador}">Data Prevista:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" value="true" id="pausa_${contador}" name="pausa_${contador}" ${pausa ? 'checked' : ''} onchange="recalcular_financeiro()">
                        <label class="form-check-label" for="pausa_${contador}">Pausa (sem medicação)</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="obs_${contador}" name="obs_${contador}" value="${obs}"/>
                        <label for="obs_${contador}">Obs:</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <h6 class="card-title mb-0">Medicações</h6>
                <button type="button" onclick="adicionar_medicamento(${contador})" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                    <span class="tf-icons mdi mdi-plus me-1"></span> Medicamento
                </button>
            </div>
            <div class="table-responsive mt-2">
                <table class="table table-sm">
                    <thead class="table-light"><tr><th>Medicamento</th><th>Quantidade</th><th></th></tr></thead>
                    <tbody id="tabela_medicamentos_${contador}">
                        <tr id="linha_medicamento_${contador}_1">
                            <td><select name="medicamento_id_${contador}_1" class="form-select" onchange="recalcular_financeiro()"><option value="">— Selecionar —</option>${opcoes_medicamentos}</select></td>
                            <td><input type="text" name="quantidade_${contador}_1" class="form-control" value="1" onkeyup="recalcular_financeiro()"></td>
                            <td><button type="button" onclick="remover_medicamento(${contador},1)" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab"><span class="tf-icons mdi mdi-delete mdi-24px"></span></button></td>
                        </tr>
                    </tbody>
                </table>
                <input type="hidden" name="contador_medicamentos_${contador}" id="contador_medicamentos_${contador}" value="1">
            </div>
        </div>`;
    document.getElementById('div_procedimentos').appendChild(div);
    recalcular_financeiro();
    return contador;
}

function remover_procedimento(n){
    let el = document.getElementById('card_' + n);
    if(el){ el.remove(); }
    recalcular_financeiro();
}

// ---------- MEDICAMENTOS ----------
function adicionar_medicamento(n){
    let contador = parseInt(document.getElementById('contador_medicamentos_' + n).value) + 1;
    document.getElementById('contador_medicamentos_' + n).value = contador;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_medicamento_' + n + '_' + contador);
    tr.innerHTML = `
        <td><select name="medicamento_id_${n}_${contador}" class="form-select" onchange="recalcular_financeiro()"><option value="">— Selecionar —</option>${opcoes_medicamentos}</select></td>
        <td><input type="text" name="quantidade_${n}_${contador}" class="form-control" value="1" onkeyup="recalcular_financeiro()"></td>
        <td><button type="button" onclick="remover_medicamento(${n},${contador})" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab"><span class="tf-icons mdi mdi-delete mdi-24px"></span></button></td>`;
    document.getElementById('tabela_medicamentos_' + n).appendChild(tr);
    return contador;
}

function remover_medicamento(n, m){
    let el = document.getElementById('linha_medicamento_' + n + '_' + m);
    if(el){ el.remove(); }
    recalcular_financeiro();
}

// ---------- GERADOR ----------
function abrir_gerador(){
    document.getElementById('gerador_dt_inicio').value = '';
    document.getElementById('gerador_nr_procedimentos').value = '';
    document.getElementById('gerador_intervalo').value = '7';
    document.getElementById('gerador_contador_medicamentos').value = 1;
    document.getElementById('gerador_tabela_medicamentos').innerHTML = `
        <tr id="gerador_linha_medicamento_1">
            <td><select id="gerador_medicamento_id_1" class="form-select"><option value="">— Selecionar —</option>${opcoes_medicamentos}</select></td>
            <td><input type="text" id="gerador_quantidade_1" class="form-control" value="1"></td>
            <td><button type="button" onclick="gerador_remover_medicamento(1)" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab"><span class="tf-icons mdi mdi-delete mdi-24px"></span></button></td>
        </tr>`;
    modalGerador = new bootstrap.Modal(document.getElementById('modal_gerador'));
    modalGerador.show();
}

function gerador_adicionar_medicamento(){
    let contador = parseInt(document.getElementById('gerador_contador_medicamentos').value) + 1;
    document.getElementById('gerador_contador_medicamentos').value = contador;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'gerador_linha_medicamento_' + contador);
    tr.innerHTML = `
        <td><select id="gerador_medicamento_id_${contador}" class="form-select"><option value="">— Selecionar —</option>${opcoes_medicamentos}</select></td>
        <td><input type="text" id="gerador_quantidade_${contador}" class="form-control" value="1"></td>
        <td><button type="button" onclick="gerador_remover_medicamento(${contador})" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab"><span class="tf-icons mdi mdi-delete mdi-24px"></span></button></td>`;
    document.getElementById('gerador_tabela_medicamentos').appendChild(tr);
}

function gerador_remover_medicamento(m){
    let el = document.getElementById('gerador_linha_medicamento_' + m);
    if(el){ el.remove(); }
}

// ---------- COMBOS NO GERADOR ----------
function gerador_adicionar_combo(){
    modalCombo = new bootstrap.Modal(document.getElementById('modal_combos'));
    modalGerador.hide();
    modalCombo.show();
}

function gerador_adicionar_medicamentos_combo(medicamento){
    // preenche a primeira linha se estiver vazia, senão cria nova linha
    let primeira = document.getElementById('gerador_linha_medicamento_1');
    let primeiro_select = primeira ? primeira.querySelector('select') : null;
    let contador = parseInt(document.getElementById('gerador_contador_medicamentos').value);

    if(primeira && primeiro_select && primeiro_select.value === ''){
        primeiro_select.value = medicamento.medicamento_id;
        let input = primeira.querySelector('input');
        input.value = medicamento.quantidade;
    } else {
        contador++;
        document.getElementById('gerador_contador_medicamentos').value = contador;
        let tr = document.createElement('tr');
        tr.setAttribute('id', 'gerador_linha_medicamento_' + contador);
        tr.innerHTML = `
            <td><select id="gerador_medicamento_id_${contador}" class="form-select"><option value="">— Selecionar —</option>${opcoes_medicamentos}</select></td>
            <td><input type="text" id="gerador_quantidade_${contador}" class="form-control" value="${medicamento.quantidade}"></td>
            <td><button type="button" onclick="gerador_remover_medicamento(${contador})" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab"><span class="tf-icons mdi mdi-delete mdi-24px"></span></button></td>`;
        document.getElementById('gerador_tabela_medicamentos').appendChild(tr);
        document.getElementById('gerador_medicamento_id_' + contador).value = medicamento.medicamento_id;
    }
}

// ---------- UTILIDADES DO GERADOR ----------
function semana_esta_vazia(n){
    let card = document.getElementById('card_' + n);
    if(!card) return false;
    let dt = document.getElementById('data_prevista_' + n) ? document.getElementById('data_prevista_' + n).value : '';
    if(dt) return false;
    return !semana_tem_medicamento(n);
}

function semana_tem_medicamento(n){
    let rows = document.querySelectorAll('#tabela_medicamentos_' + n + ' tr select');
    for(let i = 0; i < rows.length; i++){ if(rows[i].value) return true; }
    return false;
}

function semana_por_data(dt){
    let cont = parseInt(document.getElementById('contador_procedimentos').value);
    for(let n = 1; n <= cont; n++){
        let card = document.getElementById('card_' + n);
        if(!card) continue;
        let d = document.getElementById('data_prevista_' + n).value;
        if(d === dt) return n;
    }
    return null;
}

function aplicar_medicamentos_na_semana(n, meds){
    let existentes = new Set();
    document.querySelectorAll('#tabela_medicamentos_' + n + ' tr select').forEach(s => { if(s.value) existentes.add(String(s.value)); });

    let primeiraVazia = null;
    let linhas = document.querySelectorAll('#tabela_medicamentos_' + n + ' tr');
    for(let i = 0; i < linhas.length; i++){
        let sel = linhas[i].querySelector('select');
        if(sel && !sel.value){ primeiraVazia = linhas[i]; break; }
    }

    meds.forEach(m => {
        if(existentes.has(String(m.mid))) return;
        if(primeiraVazia){
            let sel = primeiraVazia.querySelector('select');
            let input = primeiraVazia.querySelector('input');
            sel.value = m.mid;
            if(input) input.value = m.qtd;
            primeiraVazia = null;
            existentes.add(String(m.mid));
            return;
        }
        let linha = adicionar_medicamento(n);
        let sel = document.querySelector('#linha_medicamento_' + n + '_' + linha + ' select');
        let input = document.querySelector('#linha_medicamento_' + n + '_' + linha + ' input');
        if(sel) sel.value = m.mid;
        if(input) input.value = m.qtd;
        existentes.add(String(m.mid));
    });
}

function garantir_sequencia_semanal(){
    // âncora: última data de semana já existente na prescrição
    let ultimaExistente = (semanasExistentes && semanasExistentes.length) ? semanasExistentes.slice().sort().pop() : null;
    let datas = [];
    if(ultimaExistente) datas.push(ultimaExistente);

    let cont = parseInt(document.getElementById('contador_procedimentos').value);
    for(let n = 1; n <= cont; n++){
        let card = document.getElementById('card_' + n);
        if(!card) continue;
        let d = document.getElementById('data_prevista_' + n).value;
        if(d) datas.push(d);
    }
    if(datas.length < 2) return;
    datas.sort();
    let min = new Date(datas[0] + 'T12:00:00');
    let max = new Date(datas[datas.length-1] + 'T12:00:00');
    let cur = new Date(min);
    while(cur <= max){
        let dt = cur.toISOString().split('T')[0];
        // não recria semana que já existe no banco nem no formulário
        if(!semanasExistentes.includes(dt) && !semana_por_data(dt)){
            let n = adicionar_procedimento(dt);
            document.getElementById('pausa_' + n).checked = true;
        }
        cur.setDate(cur.getDate() + 7);
    }
}

function ordenar_semanas(){
    let container = document.getElementById('div_procedimentos');
    let cont = parseInt(document.getElementById('contador_procedimentos').value);
    let cards = [];
    for(let n = 1; n <= cont; n++){
        let card = document.getElementById('card_' + n);
        if(!card) continue;
        let d = document.getElementById('data_prevista_' + n).value || '9999-99-99';
        cards.push({n, card, d});
    }
    cards.sort((a,b) => a.d < b.d ? -1 : (a.d > b.d ? 1 : a.n - b.n));
    cards.forEach((c, idx) => {
        container.appendChild(c.card);
        let h = c.card.querySelector('h5.card-title');
        if(h) h.textContent = 'Semana ' + (idx+1+semanaOffset);
    });
}

function remover_semanas_vazias(){
    let cont = parseInt(document.getElementById('contador_procedimentos').value);
    for(let n = 1; n <= cont; n++){
        let card = document.getElementById('card_' + n);
        if(!card) continue;
        let dt = document.getElementById('data_prevista_' + n).value;
        let pausa = document.getElementById('pausa_' + n).checked;
        if(!dt && !pausa && !semana_tem_medicamento(n)){
            card.remove();
        }
    }
}

function gera_procedimentos_gerador(){
    let dt_inicio = document.getElementById('gerador_dt_inicio').value;
    let nr = parseInt(document.getElementById('gerador_nr_procedimentos').value);
    let intervalo = parseInt(document.getElementById('gerador_intervalo').value) || 0;

    if(!dt_inicio || !nr || nr < 1){
        alert('Informe a data da 1ª semana e o número de semanas.');
        return;
    }

    let meds = [];
    let cont = parseInt(document.getElementById('gerador_contador_medicamentos').value);
    for(let i = 1; i <= cont; i++){
        let el = document.getElementById('gerador_linha_medicamento_' + i);
        if(!el) continue;
        let mid = document.getElementById('gerador_medicamento_id_' + i).value;
        let qtd = document.getElementById('gerador_quantidade_' + i).value;
        if(mid){ meds.push({mid, qtd}); }
    }

    let appDates = [];
    let data = new Date(dt_inicio + 'T12:00:00');
    for(let i = 0; i < nr; i++){
        appDates.push(data.toISOString().split('T')[0]);
        data.setDate(data.getDate() + intervalo);
    }

    let usar_semana_1 = semana_esta_vazia(1);

    appDates.forEach((dt, idx) => {
        if(idx === 0 && usar_semana_1){
            document.getElementById('data_prevista_1').value = dt;
            document.getElementById('pausa_1').checked = false;
            aplicar_medicamentos_na_semana(1, meds);
            return;
        }
        let n = semana_por_data(dt);
        if(n){
            document.getElementById('pausa_' + n).checked = false;
            aplicar_medicamentos_na_semana(n, meds);
        } else {
            let n2 = adicionar_procedimento(dt);
            document.getElementById('pausa_' + n2).checked = false;
            aplicar_medicamentos_na_semana(n2, meds);
        }
    });

    garantir_sequencia_semanal();
    remover_semanas_vazias();
    ordenar_semanas();

    if(modalGerador){ modalGerador.hide(); }
    recalcular_financeiro();
}

// ---------- RESUMO DE MEDICAMENTOS ----------
function recalcular_resumo_medicamentos(){
    let cont = parseInt(document.getElementById('contador_procedimentos').value);
    let mapa = new Map();

    for(let n = 1; n <= cont; n++){
        let card = document.getElementById('card_' + n);
        if(!card) continue;
        let pausa = document.getElementById('pausa_' + n) ? document.getElementById('pausa_' + n).checked : false;
        if(pausa) continue;
        document.querySelectorAll('#tabela_medicamentos_' + n + ' tr').forEach(tr => {
            let sel = tr.querySelector('select');
            if(!sel || !sel.value) return;
            let input = tr.querySelector('input.form-control');
            let nome = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';
            let qtd = input ? (parseFloat((input.value || '0').replace(',', '.')) || 0) : 0;
            let mid = sel.value;
            if(mapa.has(mid)){
                mapa.get(mid).qtd += qtd;
            } else {
                mapa.set(mid, { nome, qtd });
            }
        });
    }

    let tbody = document.getElementById('tabela_resumo_medicamentos');
    let items = Array.from(mapa.values()).filter(i => i.qtd > 0);
    if(items.length === 0){
        tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">Nenhum medicamento adicionado ainda.</td></tr>`;
        return;
    }
    items.sort((a,b) => a.nome.localeCompare(b.nome));
    let html = '';
    items.forEach(item => {
        html += `<tr><td>${item.nome}</td><td class="fw-bold">${item.qtd}</td></tr>`;
    });
    tbody.innerHTML = html;
}

// ---------- FINANCEIRO ----------
function valor_form_db(valor){
    valor = valor.replace(/\./g, '').replace(',', '.');
    return parseFloat(valor) || 0;
}

function montar_financeiro_html(){
    let modo = document.querySelector('input[name="modo_financeiro"]:checked');
    modo = modo ? modo.value : '1';
    let valor = valor_form_db(document.getElementById('valor_adicional').value || '0');
    let cont = parseInt(document.getElementById('contador_procedimentos').value);

    // semanas novas com data
    let todas = [];
    for(let n = 1; n <= cont; n++){
        let card = document.getElementById('card_' + n);
        if(!card) continue;
        let dt = document.getElementById('data_prevista_' + n) ? document.getElementById('data_prevista_' + n).value : '';
        if(!dt) continue;
        let pausa = document.getElementById('pausa_' + n) ? document.getElementById('pausa_' + n).checked : false;
        let tem_med = false;
        document.querySelectorAll('#tabela_medicamentos_' + n + ' tr select').forEach(sel => { if(sel.value){ tem_med = true; } });
        todas.push({n, dt, pausa, tem_med});
    }

    todas.sort((a,b) => a.dt < b.dt ? -1 : (a.dt > b.dt ? 1 : a.n - b.n));
    todas.forEach((s, idx) => { s.rotulo = idx + 1 + semanaOffset; });

    let novas = todas.filter(s => !s.pausa && s.tem_med);
    let totalNovas = novas.length;
    const vazio = `<tr><td colspan="4" class="text-center text-muted">Informe o valor adicional para gerar as parcelas.</td></tr>`;

    if(valor <= 0){
        return { html: vazio, total: 0, valor: 0, totalDistribuir: 0 };
    }

    if(modo === '2'){
        // ---- opção 2: reestruturar (aberto existente + novo, dividido por todas as não pagas) ----
        let abertas = (parcelasExistentesAbertas || []).filter(p => parseFloat(p.saldo) > 0);
        let totalAberto = abertas.reduce((a,p) => a + parseFloat(p.saldo), 0);
        let totalDistribuir = Math.round((totalAberto + valor) * 100) / 100;
        let alvo = abertas.length + totalNovas;

        if(alvo === 0 || totalDistribuir <= 0){
            return { html: vazio, total: 0, valor, totalDistribuir };
        }

        let quota = Math.floor((totalDistribuir / alvo) * 100) / 100;
        let resto = Math.round((totalDistribuir - quota * alvo) * 100) / 100;
        let i = 0;
        let html = '';

        abertas.forEach(p => {
            i++;
            let v = (i === alvo) ? Math.round((quota + resto) * 100) / 100 : quota;
            let venc = p.dt ? p.dt.split('-').reverse().join('/') : '-';
            html += `<tr><td>${p.nr}</td><td>Semana ${p.semana}</td><td>${venc}</td><td>R$ ${v.toFixed(2).replace('.', ',')}</td></tr>`;
        });
        let idxNova = 0;
        novas.forEach(s => {
            i++;
            idxNova++;
            let v = (i === alvo) ? Math.round((quota + resto) * 100) / 100 : quota;
            let venc = s.dt.split('-').reverse().join('/');
            html += `<tr><td>${parcelaOffset + idxNova - 1}</td><td>Semana ${s.rotulo} (nova)</td><td>${venc}</td><td>R$ ${v.toFixed(2).replace('.', ',')}</td></tr>`;
        });

        return { html, total: totalDistribuir, valor, totalDistribuir };
    }

    // ---- opção 1: ratear somente nas parcelas novas ----
    if(totalNovas === 0){
        return { html: vazio, total: 0, valor, totalDistribuir: 0 };
    }

    let base = Math.floor((valor / totalNovas) * 100) / 100;
    let resto2 = Math.round((valor - base * totalNovas) * 100) / 100;
    let html2 = '';
    novas.forEach((s, idx) => {
        let v = base;
        if(idx === totalNovas - 1){ v = Math.round((base + resto2) * 100) / 100; }
        let venc = s.dt.split('-').reverse().join('/');
        html2 += `<tr><td>${parcelaOffset + idx}</td><td>Semana ${s.rotulo} (nova)</td><td>${venc}</td><td>R$ ${v.toFixed(2).replace('.', ',')}</td></tr>`;
    });
    return { html: html2, total: valor, valor, totalDistribuir: 0 };
}

function recalcular_financeiro(){
    recalcular_resumo_medicamentos();
    let fin = montar_financeiro_html();
    document.getElementById('tabela_parcelas').innerHTML = fin.html;
}

// ---------- CONFIRMAÇÃO ----------
function formata_data_pt(dataStr){
    if(!dataStr) return '';
    let parts = dataStr.split('-');
    return parts[2] + '/' + parts[1] + '/' + parts[0];
}

function valida_e_confirma_prescricao(){
    // preenche lacunas semanais automaticamente (semanas de pausa entre datas) e ordena
    garantir_sequencia_semanal();
    ordenar_semanas();

    let erros = [];

    let cont = parseInt(document.getElementById('contador_procedimentos').value);
    let resumoSemanasHtml = '';
    let semanasValidas = 0;

    for(let i = 1; i <= cont; i++){
        let card = document.getElementById('card_' + i);
        if(!card) continue;
        semanasValidas++;
        let dataInput = document.getElementById('data_prevista_' + i);
        let dt = dataInput ? dataInput.value : '';
        if(!dt){
            erros.push(`Semana ${i + semanaOffset}: Por favor, informe a Data Prevista.`);
        } else {
            let dataProc = new Date(dt + 'T23:59:59');
            let hoje = new Date();
            hoje.setHours(23, 59, 59, 0);
            if(dataProc < hoje){
                erros.push(`Semana ${i + semanaOffset}: A Data Prevista (${formata_data_pt(dt)}) está no passado. Não é permitido adicionar semana com data retroativa.`);
            }
        }

        let pausa = document.getElementById('pausa_' + i) ? document.getElementById('pausa_' + i).checked : false;

        let medListText = '';
        let temMed = false;
        document.querySelectorAll('#tabela_medicamentos_' + i + ' tr select').forEach(sel => {
            if(!sel.value) return;
            temMed = true;
            let nome = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';
            let linha = sel.closest('tr');
            let qtdInput = linha ? linha.querySelector('input.form-control') : null;
            let qtd = qtdInput ? qtdInput.value : '1';
            medListText += `<div class="mb-1"><span class="mdi mdi-pill me-1 text-secondary"></span>${nome} (Qtd: ${qtd})</div>`;
        });

        if(pausa){
            resumoSemanasHtml += `<tr><td><strong>Semana ${i + semanaOffset}</strong></td><td>${dt ? formata_data_pt(dt) : ''}</td><td class="text-muted"><span class="mdi mdi-minus-circle-outline me-1"></span>Pausa (sem medicação)</td></tr>`;
            continue;
        }
        if(!temMed){
            erros.push(`Semana ${i + semanaOffset}: Adicione pelo menos um medicamento ou marque "Pausa (sem medicação)".`);
            continue;
        }
        resumoSemanasHtml += `<tr><td><strong>Semana ${i + semanaOffset}</strong></td><td>${dt ? formata_data_pt(dt) : ''}</td><td>${medListText}</td></tr>`;
    }

    if(semanasValidas === 0){
        erros.push('Por favor, adicione pelo menos uma semana.');
    }

    let fin = montar_financeiro_html();

    if(erros.length > 0){
        let lista = '';
        erros.forEach(err => { lista += `<li class="mb-2"><span class="mdi mdi-circle-small me-1 text-danger"></span>${err}</li>`; });
        document.getElementById('lista_erros_validacao').innerHTML = lista;
        modalErrosPrescricao = new bootstrap.Modal(document.getElementById('modal_erros_validacao'));
        modalErrosPrescricao.show();
        return;
    }

    document.getElementById('tabela_confirmacao_semanas').innerHTML = resumoSemanasHtml;
    document.getElementById('tabela_confirmacao_parcelas').innerHTML = fin.html;
    document.getElementById('confirmacao_valor_adicional').innerText = 'R$ ' + (fin.valor ? fin.valor.toFixed(2).replace('.', ',') : '0,00');
    document.getElementById('confirmacao_total_parcelar').innerText = 'R$ ' + (fin.total ? fin.total.toFixed(2).replace('.', ',') : '0,00');
    modalConfirmacaoPrescricao = new bootstrap.Modal(document.getElementById('modal_confirmacao_prescricao'));
    modalConfirmacaoPrescricao.show();

    document.getElementById('confirmar_e_salvar_prescricao').onclick = function(){
        isConfirmed = true;
        modalConfirmacaoPrescricao.hide();
        document.getElementById('form_adicionar_semanas').submit();
    };
}
</script>

<script>
window.addEventListener('load', function(){
    let form = document.getElementById('form_adicionar_semanas');
    if(form){
        form.addEventListener('submit', function(e){
            if(!isConfirmed){
                e.preventDefault();
                valida_e_confirma_prescricao();
            }
        });
    }
    let botaoGerador = document.getElementById('botao_gerador');
    if(botaoGerador){
        botaoGerador.addEventListener('click', abrir_gerador);
    }
    let botaoCombo = document.getElementById('adicionar_gerador_combo');
    if(botaoCombo){
        botaoCombo.addEventListener('click', function(){
            let combo_id = document.getElementById('combo_id').value;
            if(combo_id === ''){
                alert('É necessário escolher o combo.');
                return;
            }
            if(typeof $ === 'undefined'){
                alert('Erro ao carregar o combo. Recarregue a página.');
                return;
            }
            $.getJSON(
                "{{ route('adm.combos.buscar_medicamentos') }}",
                { combo_id: combo_id },
                function(json){
                    json.medicamentos.forEach(m => gerador_adicionar_medicamentos_combo(m));
                    modalCombo.hide();
                    modalGerador.show();
                }
            );
        });
    }
});
</script>
@endsection
