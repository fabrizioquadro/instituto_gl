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
            <h4 class="card-title">Editar Semana {{ $semana->nr_semana }}</h4>
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

        <form action="{{ route('sistema.prescricoes.update_semana') }}" method="post">
            @csrf
            <input type="hidden" name="semana_id" value="{{ $semana->id }}">

            <div class="row mt-2 gy-3">
                <div class="col-md-6">
                    <label class="form-label" for="data_prevista">Data Prevista</label>
                    <input type="date" name="data_prevista" id="data_prevista" class="form-control" value="{{ $semana->data_prevista }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="obs">Obs</label>
                    <input type="text" name="obs" id="obs" class="form-control" value="{{ $semana->obs }}">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4">
                <h5 class="card-title mb-0">Medicações</h5>
                <div>
                    <button type="button" onclick="adicionar_novo_medicamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                        <span class="tf-icons mdi mdi-plus me-1"></span> Medicamento
                    </button>
                    <button type="button" onclick="gerador_adicionar_combo()" class="btn btn-sm rounded-pill btn-outline-info waves-effect">
                        <span class="tf-icons mdi mdi-cube-outline me-1"></span> Combo
                    </button>
                </div>
            </div>
            <div class="table-responsive mt-2">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Medicamento</th>
                            <th>Quantidade</th>
                            <th>Situação</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabela_medicamentos_edit">
                        @php $i = 1; @endphp
                        @foreach($semana->medicamentos as $med)
                            <tr id="linha_med_edit_{{ $med->id }}" class="{{ old('excluir_medicamento') && in_array($med->id, old('excluir_medicamento')) ? 'table-danger' : '' }}">
                                <td>
                                    <input type="hidden" name="medicamento_editar_id_{{ $i }}" value="{{ $med->id }}">
                                    <b>{{ $med->medicamento->nome ?? 'Medicamento #'.$med->medicamento_id }}</b>
                                </td>
                                <td><input type="text" name="quantidade_{{ $i }}" class="form-control" value="{{ $med->quantidade }}" required></td>
                                <td>
                                    <select name="situacao_medicamento_{{ $i }}" class="form-select">
                                        <option value="Aberta" @if($med->situacao == 'Aberta') selected @endif>Aberta</option>
                                        <option value="Aplicada" @if($med->situacao == 'Aplicada') selected @endif>Aplicada</option>
                                        <option value="Cancelada" @if($med->situacao == 'Cancelada') selected @endif>Cancelada</option>
                                    </select>
                                </td>
                                <td class="text-center align-middle">
                                    @if($med->situacao != 'Aplicada')
                                        <input type="checkbox" class="btn-check excluir-med-check" id="excluir_med_{{ $med->id }}" name="excluir_medicamento[]" value="{{ $med->id }}" autocomplete="off" {{ old('excluir_medicamento') && in_array($med->id, old('excluir_medicamento')) ? 'checked' : '' }}>
                                        <label class="btn btn-sm btn-outline-danger" for="excluir_med_{{ $med->id }}" title="Marcar para excluir (exclui ao salvar)">
                                            <span class="tf-icons mdi mdi-delete me-1"></span> Excluir
                                        </label>
                                    @else
                                        <span class="text-muted small">Aplicada</span>
                                    @endif
                                </td>
                            </tr>
                            @php $i++; @endphp
                        @endforeach
                        <input type="hidden" name="contador_medicamentos" value="{{ $i - 1 }}">
                        <input type="hidden" name="contador_novos_medicamentos" id="contador_novos_medicamentos" value="0">
                    </tbody>
                </table>
                @if($semana->medicamentos->isEmpty())
                    <p class="text-muted">Semana sem medicações.</p>
                @endif
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>

        {{-- MODAL COMBOS --}}
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
                                    <select id="combo_id" class="form-select">
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
    </div>
</div>

<script>
let opcoes_medicamentos = `@foreach($medicamentos as $medicamento)<option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>@endforeach`;

function atualizar_destaque_exclusao(checkbox){
    var tr = document.getElementById('linha_med_edit_' + checkbox.value);
    if (tr) tr.classList.toggle('table-danger', checkbox.checked);
}

// ---------- adicionar novos medicamentos na edição ----------
function adicionar_novo_medicamento(){
    let contador = parseInt(document.getElementById('contador_novos_medicamentos').value) + 1;
    document.getElementById('contador_novos_medicamentos').value = contador;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_novo_medicamento_' + contador);
    tr.innerHTML = `
        <td><select name="novo_medicamento_id_${contador}" class="form-select"><option value="">— Selecionar —</option>${opcoes_medicamentos}</select></td>
        <td><input type="text" name="nova_quantidade_${contador}" class="form-control" value="1"></td>
        <td><span class="text-muted small">—</span></td>
        <td><button type="button" title="Excluir linha" onclick="excluir_linha_novo_medicamento(${contador})" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab"><span class="tf-icons mdi mdi-delete mdi-24px"></span></button></td>
    `;
    document.getElementById('tabela_medicamentos_edit').appendChild(tr);
}

function excluir_linha_novo_medicamento(linha){
    let el = document.getElementById('linha_novo_medicamento_' + linha);
    if (el) el.remove();
}

// ---------- COMBOS ----------
let modalCombo;
function gerador_adicionar_combo(){
    modalCombo = new bootstrap.Modal(document.getElementById('modal_combos'));
    modalCombo.show();
}
function gerador_adicionar_medicamentos_combo(medicamento){
    adicionar_novo_medicamento();
    let contador = parseInt(document.getElementById('contador_novos_medicamentos').value);
    let tr = document.getElementById('linha_novo_medicamento_' + contador);
    let select = tr.querySelector('select');
    let input = tr.querySelector('input');
    select.value = medicamento.medicamento_id;
    input.value = medicamento.quantidade;
}

window.addEventListener('load', function(){
    // destaque da exclusão
    document.querySelectorAll('input.excluir-med-check').forEach(function(c){
        c.addEventListener('change', function(){ atualizar_destaque_exclusao(this); });
        if (c.checked) atualizar_destaque_exclusao(c);
    });

    // botão adicionar combo do modal
    var btn = document.getElementById('adicionar_gerador_combo');
    if (btn) {
        btn.addEventListener('click', function(){
            var combo_id = document.getElementById('combo_id').value;
            if (combo_id === '') {
                alert('É necessário escolher o combo.');
                return;
            }
            if (typeof $ === 'undefined') {
                alert('Erro ao carregar o combo. Recarregue a página.');
                return;
            }
            $.getJSON(
                "{{ route('adm.combos.buscar_medicamentos') }}",
                { combo_id: combo_id },
                function(json){
                    json.medicamentos.forEach(function(m){ gerador_adicionar_medicamentos_combo(m); });
                    modalCombo.hide();
                }
            );
        });
    }
});
</script>
@endsection
