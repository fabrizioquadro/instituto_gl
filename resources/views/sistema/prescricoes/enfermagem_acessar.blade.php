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
$tem_controlado = false;
foreach($semana->medicamentos as $m){
    if($m->situacao == 'Aberta' && $m->medicamento && in_array($m->medicamento->unidade, ['Ampola', 'Miligrama'])){
        $tem_controlado = true;
        break;
    }
}
@endphp

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Aplicação — Semana {{ $semana->nr_semana }}</h4>
            <a href="{{ route('sistema.dash') }}" class="btn btn-outline-dark btn-sm">Voltar à Fila</a>
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
                        <th>Médico</th>
                        <td>{{ $semana->prescricao->medico ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <th class="w-25">Situação</th>
                        <td><span class="badge rounded-pill {{ $badge }}">{{ $semana->situacao }}</span></td>
                    </tr>
                    <tr>
                        <th>Enfermeiro(a)</th>
                        <td>{{ $user->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Obs</th>
                        <td>{{ $semana->obs ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ANEXOS (PEDIDO MÉDICO) --}}
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h5 class="card-title mb-0">Anexos / Pedido Médico</h5>
        <hr>
        @if($semana->prescricao->anexos->count())
            <div class="d-flex flex-wrap gap-2">
                @foreach($semana->prescricao->anexos as $anexo)
                    @if($anexo->tipo == 'prescricao_medica')
                        <div class="d-inline-flex align-items-center border rounded px-2 py-1">
                            <span class="mdi mdi-file-document-outline me-1"></span>{{ $anexo->nm_anexo }}
                            <a href="{{ url('public/prescricoes/' . $anexo->prescricao_id . '/anexos/' . $anexo->arquivo) }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2" onclick="marcar_anexo_visualizado({{ $anexo->id }})">
                                Visualizar
                            </a>
                            @if($anexo->visualizado_em)
                                <span class="badge bg-success ms-1" id="badge_anexo_{{ $anexo->id }}">Conferido</span>
                            @else
                                <span class="badge bg-label-warning ms-1" id="badge_anexo_{{ $anexo->id }}">Pendente de conferência</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-muted mb-0">Nenhum anexo (pedido médico) para esta prescrição.</p>
        @endif
        @if($tem_controlado)
            <div class="alert alert-info d-flex align-items-center mt-3 mb-0">
                <i class="mdi mdi-information-outline me-2"></i>
                <div>Esta semana contém medicamentos <b>Ampola/Miligrama</b>. É <b>obrigatório abrir/conferir o pedido médico (anexo)</b> antes de registrar a aplicação.</div>
            </div>
        @endif
    </div>
</div>

{{-- APLICAÇÕES --}}
<form action="{{ route('sistema.prescricoes.set_aplicacao_enfermagem') }}" method="post" id="formulario_aplicacao">
    @csrf
    <input type="hidden" name="semana_id" value="{{ $semana->id }}">
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Medicações / Aplicação</h5>
                <button type="button" class="btn btn-sm btn-outline-info waves-effect" onclick="abre_modal_abrir_frasco()">
                    <span class="tf-icons mdi mdi-flask-outline me-1"></span> Abrir Frasco
                </button>
            </div>
            <hr>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Pendente</th>
                            <th>Medicamento</th>
                            <th>Qtd. Prescrita</th>
                            <th>Retirar do Estoque</th>
                            <th>Código de Barras</th>
                            <th>Lote</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semana->medicamentos as $medAplic)
                            @php
                            $med = $medAplic->medicamento;
                            if(!$med){ continue; }
                            $qtd = (float) $medAplic->quantidade;
                            $frac = fmod($qtd, 1.0) != 0;
                            @endphp
                            @if($medAplic->situacao == 'Aplicada')
                                <tr class="table-success">
                                    <td colspan="7">
                                        <span class="mdi mdi-check-circle me-1 text-success"></span>
                                        <b>{{ $med->nome }}</b> — Qtd: {{ $medAplic->quantidade }}
                                        @if($medAplic->lotes->count())
                                            <span class="text-muted ms-2">
                                                Lote: {{ $medAplic->lotes->pluck('lote')->unique()->implode(', ') }} |
                                                C. Barras: {{ $medAplic->lotes->pluck('codigo_barras')->unique()->implode(', ') }}
                                            </span>
                                        @endif
                                        <span class="badge bg-label-success ms-2">Aplicada</span>
                                    </td>
                                </tr>
                            @else
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="Sim" id="controle_pendente_{{ $medAplic->id }}" name="controle_pendente_{{ $medAplic->id }}" onclick="controle_pendente({{ $medAplic->id }}, this)">
                                        <label class="form-check-label" for="controle_pendente_{{ $medAplic->id }}">Pendente</label>
                                    </div>
                                </td>
                                <td>
                                    <b>{{ $med->nome }}</b>
                                    @if($med->is_soro) <span class="badge bg-info ms-1">soro</span> @endif
                                    <small class="text-muted d-block">{{ $med->unidade }}</small>
                                </td>
                                <td>
                                    <span class="fw-semibold" id="quantidade_prescrita_{{ $medAplic->id }}">{{ $medAplic->quantidade }}</span>
                                    @if($med->unidade == 'Miligrama' && $med->vasilhame)
                                        <small class="text-muted d-block">Frasco: {{ $med->vasilhame }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($med->unidade == 'Ampola' && $frac)
                                        <select class="form-select form-select-sm" id="quantidade_retirar_{{ $medAplic->id }}" name="quantidade_retirar_{{ $medAplic->id }}">
                                            <option value="1">Ampola inteira (1)</option>
                                            <option value="{{ $qtd }}">Fração ({{ $qtd }})</option>
                                        </select>
                                    @else
                                        <span class="text-muted">{{ $medAplic->quantidade }}</span>
                                    @endif
                                </td>
                                <td id="td_aplicacao_codigo_{{ $medAplic->id }}">
                                    @if($med->unidade != 'Procedimento')
                                        <input required class="form-control form-control-sm codigo_barras" id="codigo_barras_{{ $medAplic->id }}" name="codigo_barras_{{ $medAplic->id }}" placeholder="Ler código de barras" onfocus="aguardando_codigo({{ $medAplic->id }})"
                                            onblur="{{ $med->unidade == 'Miligrama' ? "busca_lote_por_codigo_frasco(this, {$med->id}, {$user->clinica_id}, get_quantidade_aplicacao({$medAplic->id}))" : "busca_lote_por_codigo(this, {$med->id}, {$user->clinica_id}, get_quantidade_aplicacao({$medAplic->id}))" }}"/>
                                    @else
                                        <input class="form-control form-control-sm codigo_barras" id="codigo_barras_{{ $medAplic->id }}" name="codigo_barras_{{ $medAplic->id }}" placeholder="Ler código de barras"/>
                                    @endif
                                </td>
                                <td id="td_aplicacao_lote_{{ $medAplic->id }}">
                                    @if($med->unidade != 'Procedimento')
                                        <span id="status_lote_{{ $medAplic->id }}" class="badge bg-secondary">Aguardando código</span>
                                        <input type="hidden" name="lote_{{ $medAplic->id }}" id="lote_{{ $medAplic->id }}" value="">
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($med->unidade == 'Miligrama')
                                        <button type="button" class="btn btn-sm btn-outline-info waves-effect" onclick="abre_modal_2_codigo({{ $medAplic->id }}, {{ $med->id }})">
                                            <span class="tf-icons mdi mdi-qrcode me-1"></span>2 códigos
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endif
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Semana sem medicações.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline">
                        <textarea class="form-control h-px-75" name="obs_aplicacao" id="obs_aplicacao"></textarea>
                        <label for="obs_aplicacao">Obs. da Aplicação:</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="button" id="btn_registrar_aplicacao" class="btn btn-primary" onclick="abrir_confirmacao_aplicacao()">
                    <span class="tf-icons mdi mdi-syringe me-1"></span> Registrar Aplicação
                </button>
            </div>
        </div>
    </div>
</form>

{{-- MODAL ABRIR FRASCO (Miligrama) --}}
<div class="modal fade" id="modal_abrir_frasco" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('sistema.prescricoes.abrir_frasco') }}" method="post">
                @csrf
                <input type="hidden" name="prescricao_semana_id" value="{{ $semana->id }}">
                <input type="hidden" name="lote" id="frasco_lote" value="">
                <div class="modal-header">
                    <h5 class="modal-title text-primary">Abrir Frasco</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Medicamento (frasco):</label>
                        <select class="form-select" id="modal_medicamento_frasco" name="medicamento_id" onchange="modal_get_lotes_medicamento(this)">
                            <option value="">Opções</option>
                            @foreach($semana->medicamentos as $medAplic)
                                @if($medAplic->medicamento && $medAplic->medicamento->unidade == 'Miligrama')
                                    <option value="{{ $medAplic->medicamento->id }}">{{ $medAplic->medicamento->nome }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Código de Barras / Lote:</label>
                        <select class="form-select" id="modal_codigo_barras" name="codigo_barras" onchange="modal_seleciona_lote()">
                            <option value="">Opções</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Abrir</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 2 CÓDIGOS (Miligrama) --}}
<div class="modal fade" id="modal_2_codigo" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary">Aplicação com 2 Códigos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal_2_codigo_med_aplic" value="">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Frasco</th>
                                <th>Quantidade</th>
                                <th>Código de Barras</th>
                                <th>Lote</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1º</td>
                                <td><input class="form-control form-control-sm" type="text" id="modal_quant_1" value="0"/></td>
                                <td><input class="form-control form-control-sm" type="text" id="modal_cod_1" onblur="busca_lote_por_codigo_frasco_2codigo(1)"/></td>
                                <td><input readonly class="form-control form-control-sm" type="text" id="modal_lote_1"/></td>
                            </tr>
                            <tr>
                                <td>2º</td>
                                <td><input class="form-control form-control-sm" type="text" id="modal_quant_2" value="0"/></td>
                                <td><input class="form-control form-control-sm" type="text" id="modal_cod_2" onblur="busca_lote_por_codigo_frasco_2codigo(2)"/></td>
                                <td><input readonly class="form-control form-control-sm" type="text" id="modal_lote_2"/></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvar_modal_2_codigo()">Salvar</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL CONFIRMAÇÃO DA APLICAÇÃO --}}
<div class="modal fade" id="modal_confirmar_aplicacao" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary d-flex align-items-center">
                    <span class="mdi mdi-checkbox-marked-circle-outline mdi-24px me-2"></span>Confirmar Aplicação
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">Revise os medicamentos que serão aplicados:</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Qtd. Prescrita</th>
                                <th>Retirar</th>
                                <th>Código</th>
                                <th>Lote</th>
                            </tr>
                        </thead>
                        <tbody id="conteudo_confirmacao_medicamentos"></tbody>
                    </table>
                </div>

                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="mdi mdi-alert-circle-outline me-2"></i>
                    <div>Antes de confirmar, <b>abra e confira o pedido médico (anexo)</b> acima.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btn_confirmar_submissao" class="btn btn-primary" onclick="confirmar_aplicacao()">Confirmar e Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
let modal_abrir_frasco_2, modal_2_codigo_obj, modal_confirmar_aplicacao_obj;
let precisa_conferir_anexo = {{ $tem_controlado ? 'true' : 'false' }};
let anexo_conferido = {{ ($tem_controlado && $semana->prescricao->anexos->contains(fn($a) => $a->tipo == 'prescricao_medica' && $a->visualizado_em)) ? 'true' : 'false' }};

function get_quantidade_aplicacao(med_aplic_id){
    let sel = document.getElementById('quantidade_retirar_' + med_aplic_id);
    if(sel){ return sel.value; }
    let presc = document.getElementById('quantidade_prescrita_' + med_aplic_id);
    return presc ? presc.textContent.trim() : '1';
}

function controle_pendente(med_aplic_id, cb){
    let cod = document.getElementById('codigo_barras_' + med_aplic_id);
    let lote = document.getElementById('lote_' + med_aplic_id);
    if(cb.checked){
        if(cod){ cod.removeAttribute('required'); }
        if(lote){ lote.removeAttribute('required'); }
    } else {
        if(cod){ cod.setAttribute('required', 'required'); }
        if(lote){ lote.setAttribute('required', 'required'); }
    }
}

function aguardando_codigo(med_aplic_id){
    let spanStatus = document.getElementById('status_lote_' + med_aplic_id);
    if(spanStatus){
        spanStatus.className = 'badge bg-secondary';
        spanStatus.innerHTML = 'Aguardando código';
    }
}

function pesquisando_codigo(med_aplic_id){
    let spanStatus = document.getElementById('status_lote_' + med_aplic_id);
    if(spanStatus){
        spanStatus.className = 'badge bg-info';
        spanStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Pesquisando...';
    }
}

function atualizar_status_lote(med_aplic_id, json){
    let spanStatus = document.getElementById('status_lote_' + med_aplic_id);
    let hidden = document.getElementById('lote_' + med_aplic_id);
    if(!spanStatus) return;
    if(json.controle == 'true'){
        spanStatus.className = 'badge bg-success';
        spanStatus.innerHTML = '<span class="mdi mdi-check-circle me-1"></span>Lote: ' + json.lote;
        if(hidden){ hidden.value = json.lote; }
    } else {
        spanStatus.className = 'badge bg-danger';
        spanStatus.innerHTML = '<span class="mdi mdi-close-circle me-1"></span>Não encontrado';
        if(hidden){ hidden.value = ''; }
    }
}

function busca_lote_por_codigo(el, medicamento_id, clinica_id, quantidade){
    let codigo = el.value.trim();
    let med_aplic_id = el.id.replace('codigo_barras_', '');
    if(!codigo){
        aguardando_codigo(med_aplic_id);
        let hidden = document.getElementById('lote_' + med_aplic_id);
        if(hidden) hidden.value = '';
        return;
    }
    pesquisando_codigo(med_aplic_id);
    $.getJSON("{{ route('sistema.prescricoes.busca_lote_por_codigo') }}", { codigo: codigo, medicamento_id: medicamento_id, clinica_id: clinica_id, quantidade: quantidade }, function(json){
        if(json.controle == 'vencido'){
            el.value = '';
            atualizar_status_lote(med_aplic_id, json);
        } else if(json.controle == 'insuficiente'){
            atualizar_status_lote(med_aplic_id, json);
        } else if(json.controle == 'true'){
            atualizar_status_lote(med_aplic_id, json);
        } else {
            el.value = '';
            atualizar_status_lote(med_aplic_id, json);
        }
    });
}

function busca_lote_por_codigo_frasco(el, medicamento_id, clinica_id, quantidade){
    let codigo = el.value.trim();
    let med_aplic_id = el.id.replace('codigo_barras_', '');
    if(!codigo){
        aguardando_codigo(med_aplic_id);
        let hidden = document.getElementById('lote_' + med_aplic_id);
        if(hidden) hidden.value = '';
        return;
    }
    pesquisando_codigo(med_aplic_id);
    $.getJSON("{{ route('sistema.prescricoes.busca_lote_por_codigo_frasco') }}", { codigo: codigo, medicamento_id: medicamento_id, clinica_id: clinica_id, quantidade: quantidade }, function(json){
        if(json.controle == 'vencido'){
            el.value = '';
            atualizar_status_lote(med_aplic_id, json);
        } else if(json.controle == 'true'){
            atualizar_status_lote(med_aplic_id, json);
        } else {
            el.value = '';
            atualizar_status_lote(med_aplic_id, json);
        }
    });
}

// ---------- ANEXO (regra: obrigar conferir pedido médico) ----------
function marcar_anexo_visualizado(anexo_id){
    $.post("{{ route('sistema.prescricoes.marcar_anexo_visualizado') }}", { anexo_id: anexo_id, _token: '{{ csrf_token() }}' }, function(json){
        if(json.ok){
            let badge = document.getElementById('badge_anexo_' + anexo_id);
            if(badge){ badge.className = 'badge bg-success ms-1'; badge.textContent = 'Conferido'; }
            anexo_conferido = true;
            atualizar_botao_confirmar();
        }
    });
}

function atualizar_botao_confirmar(){
    let btn = document.getElementById('btn_confirmar_submissao');
    if(btn){
        btn.disabled = precisa_conferir_anexo && !anexo_conferido;
    }
}

// ---------- ABRIR FRASCO ----------
function abre_modal_abrir_frasco(){
    modal_abrir_frasco_2 = new bootstrap.Modal(document.getElementById('modal_abrir_frasco'));
    modal_abrir_frasco_2.show();
}

function modal_get_lotes_medicamento(sel){
    let medicamento_id = sel.value;
    let select = document.getElementById('modal_codigo_barras');
    select.innerHTML = '<option value="">Opções</option>';
    if(!medicamento_id) return;
    $.get("{{ route('sistema.prescricoes.get_lotes_medicamento_mg') }}", { medicamento_id: medicamento_id }, function(html){
        select.innerHTML = html;
    });
}

function modal_seleciona_lote(){
    let select = document.getElementById('modal_codigo_barras');
    let opt = select.options[select.selectedIndex];
    if(opt && opt.getAttribute('data-lote')){
        document.getElementById('frasco_lote').value = opt.getAttribute('data-lote');
    }
}

// ---------- 2 CÓDIGOS ----------
function abre_modal_2_codigo(med_aplic_id, medicamento_id){
    document.getElementById('modal_2_codigo_med_aplic').value = med_aplic_id;
    document.getElementById('modal_quant_1').value = '0';
    document.getElementById('modal_quant_2').value = '0';
    document.getElementById('modal_cod_1').value = '';
    document.getElementById('modal_cod_2').value = '';
    document.getElementById('modal_lote_1').value = '';
    document.getElementById('modal_lote_2').value = '';
    window._modal2_medicamento_id = medicamento_id;
    modal_2_codigo_obj = new bootstrap.Modal(document.getElementById('modal_2_codigo'));
    modal_2_codigo_obj.show();
}

function busca_lote_por_codigo_frasco_2codigo(n){
    let codigo = document.getElementById('modal_cod_' + n).value.trim();
    if(!codigo) return;
    $.getJSON("{{ route('sistema.prescricoes.busca_lote_por_codigo_frasco') }}", {
        codigo: codigo,
        medicamento_id: window._modal2_medicamento_id,
        clinica_id: {{ $user->clinica_id }},
        quantidade: document.getElementById('modal_quant_' + n).value || '1'
    }, function(json){
        if(json.controle == 'true'){
            document.getElementById('modal_lote_' + n).value = json.lote;
        } else {
            alert(json.mensagem || 'Código de Barras Inválido');
            document.getElementById('modal_cod_' + n).value = '';
            document.getElementById('modal_lote_' + n).value = '';
        }
    });
}

function salvar_modal_2_codigo(){
    let med_aplic_id = document.getElementById('modal_2_codigo_med_aplic').value;
    if(!med_aplic_id) return;
    let cod1 = document.getElementById('modal_cod_1').value.trim();
    let cod2 = document.getElementById('modal_cod_2').value.trim();
    let qtd1 = document.getElementById('modal_quant_1').value.trim() || '0';
    let qtd2 = document.getElementById('modal_quant_2').value.trim() || '0';
    if(!cod1 || !cod2){
        alert('Informe o código de barras dos dois frascos.');
        return;
    }
    // injeta os campos hidden dentro da linha da aplicação
    let td_codigo = document.getElementById('td_aplicacao_codigo_' + med_aplic_id);
    let td_lote = document.getElementById('td_aplicacao_lote_' + med_aplic_id);
    let inputs = '';
    inputs += '<input type="hidden" name="controle_med_' + med_aplic_id + '" value="2_codigo">';
    inputs += '<input type="hidden" name="cod_med_1_' + med_aplic_id + '" value="' + cod1 + '">';
    inputs += '<input type="hidden" name="quant_med_1_' + med_aplic_id + '" value="' + qtd1 + '">';
    inputs += '<input type="hidden" name="lote_med_1_' + med_aplic_id + '" value="' + document.getElementById('modal_lote_1').value + '">';
    inputs += '<input type="hidden" name="cod_med_2_' + med_aplic_id + '" value="' + cod2 + '">';
    inputs += '<input type="hidden" name="quant_med_2_' + med_aplic_id + '" value="' + qtd2 + '">';
    inputs += '<input type="hidden" name="lote_med_2_' + med_aplic_id + '" value="' + document.getElementById('modal_lote_2').value + '">';
    td_codigo.innerHTML = '<input readonly class="form-control form-control-sm" value="' + cod1 + ' / ' + cod2 + '">' + inputs;
    td_lote.innerHTML = '<input readonly class="form-control form-control-sm" value="' + document.getElementById('modal_lote_1').value + ' / ' + document.getElementById('modal_lote_2').value + '">';
    // remove required dos campos originais (substituídos)
    modal_2_codigo_obj.hide();
    alert('2 códigos registrados.');
}

// ---------- CONFIRMAÇÃO DA APLICAÇÃO ----------
function abrir_confirmacao_aplicacao(){
    let form = document.getElementById('formulario_aplicacao');
    if(!form.checkValidity()){
        form.reportValidity();
        return;
    }

    let html = '';
    document.querySelectorAll('#formulario_aplicacao tbody tr').forEach(function(tr){
        let cb = tr.querySelector('input[name^="controle_pendente_"]');
        let nomeEl = tr.querySelector('td:nth-child(2) b');
        let qtdPresc = tr.querySelector('#quantidade_prescrita_' + (cb ? cb.name.replace('controle_pendente_', '') : 'x'));
        if(!cb || !cb.checked){
            let codEl = tr.querySelector('input[name^="codigo_barras_"]');
            let loteEl = tr.querySelector('input[name^="lote_"]');
            let retirarEl = tr.querySelector('select[name^="quantidade_retirar_"]');
            if(codEl && (loteEl || true)){
                let id = codEl.name.replace('codigo_barras_', '');
                let nome = nomeEl ? nomeEl.textContent : 'Medicamento';
                let presc = qtdPresc ? qtdPresc.textContent.trim() : '-';
                let retirar = retirarEl ? retirarEl.value : presc;
                let codigo = codEl.value || '-';
                let lote = loteEl ? loteEl.value || '-' : '-';
                // verifica 2 códigos
                let hidden = tr.querySelector('input[name="controle_med_' + id + '"]');
                if(hidden && hidden.value == '2_codigo'){
                    let c1 = tr.querySelector('input[name="cod_med_1_' + id + '"]').value;
                    let c2 = tr.querySelector('input[name="cod_med_2_' + id + '"]').value;
                    codigo = c1 + ' / ' + c2;
                    lote = (tr.querySelector('input[name="lote_med_1_' + id + '"]').value || '-') + ' / ' + (tr.querySelector('input[name="lote_med_2_' + id + '"]').value || '-');
                }
                html += '<tr><td>' + nome + '</td><td>' + presc + '</td><td>' + retirar + '</td><td>' + codigo + '</td><td>' + lote + '</td></tr>';
            }
        }
    });

    if(!html){
        alert('Nenhuma aplicação para confirmar (todas pendentes?).');
        return;
    }

    document.getElementById('conteudo_confirmacao_medicamentos').innerHTML = html;
    atualizar_botao_confirmar();
    modal_confirmar_aplicacao_obj = new bootstrap.Modal(document.getElementById('modal_confirmar_aplicacao'));
    modal_confirmar_aplicacao_obj.show();
}

function confirmar_aplicacao(){
    if(precisa_conferir_anexo && !anexo_conferido){
        alert('É obrigatório abrir/conferir o pedido médico (anexo) antes de registrar a aplicação.');
        return;
    }
    document.getElementById('formulario_aplicacao').submit();
}
</script>

<style>
.table.table-sm.dataTable thead th,
.table.table-sm.dataTable thead td,
.table.table-sm.dataTable tbody th,
.table.table-sm.dataTable tbody td {
    padding: 0.3125rem 0.625rem !important;
    white-space: nowrap !important;
}
</style>
@endsection
