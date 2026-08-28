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
            <h4 class="card-title">Adicionar Medicamentos</h4>
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
            <div>Paciente: <b>{{ $prescricao->paciente->nm_paciente ?? '-' }}</b> — Prescrição #{{ $prescricao->id }}. Selecione as semanas que receberão as medicações.</div>
        </div>

        <form action="{{ route('sistema.prescricoes.insert_medicamentos') }}" method="post">
            @csrf
            <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="1">

            <div class="row mt-3">
                <div class="col-md-3">
                    <h5 class="card-title">Semanas</h5>
                    @foreach($prescricao->semanas as $semana)
                        @if($semana->situacao != 'Cancelada')
                            @php
                            $ja_aplicada = in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial']);
                            $badge_semana = 'bg-label-secondary';
                            if($semana->situacao == 'Agendada'){ $badge_semana = 'bg-label-warning'; }
                            elseif($semana->situacao == 'Em Atendimento'){ $badge_semana = 'bg-label-info'; }
                            elseif($semana->situacao == 'Fila de Aplicação'){ $badge_semana = 'bg-label-info'; }
                            elseif($semana->situacao == 'Aplicada'){ $badge_semana = 'bg-label-success'; }
                            elseif($semana->situacao == 'Aplicação Parcial'){ $badge_semana = 'bg-label-primary'; }
                            @endphp
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" {{ $ja_aplicada ? '' : 'checked' }} value="{{ $semana->id }}" id="semanas_{{ $semana->id }}" name="semanas[]">
                                <label class="form-check-label" for="semanas_{{ $semana->id }}">
                                    {{ 'Semana '.$semana->nr_semana." - ".($semana->data_prevista ? dataDbForm($semana->data_prevista) : '') }}
                                    <span class="badge rounded-pill {{ $badge_semana }} ms-1">{{ $semana->situacao }}</span>
                                    @if($ja_aplicada)
                                        <span class="badge rounded-pill bg-label-warning ms-1">novo medicamento entra como Pendente</span>
                                    @endif
                                </label>
                            </div>
                        @endif
                    @endforeach
                    @if(!$prescricao->semanas->where('situacao', '!=', 'Cancelada')->count())
                        <p class="text-muted mt-2">Nenhuma semana disponível.</p>
                    @endif
                </div>
                <div class="col-md-9">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">Medicamentos</h5>
                        <button type="button" onclick="adicionar_medicamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                            <span class="tf-icons mdi mdi-plus me-1"></span> Medicamento
                        </button>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Quantidade Semanal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tabela_medicamentos">
                                <tr id="linha_medicamento_1">
                                    <td>
                                        <select name="medicamento_id_1" class="form-select">
                                            <option value="">— Selecionar —</option>
                                            @foreach($medicamentos as $medicamento)
                                                <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="quantidade_1" class="form-control" value="1"></td>
                                    <td>
                                        <button type="button" title="Excluir linha" onclick="excluir_linha_medicamento(1)" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab">
                                            <span class="tf-icons mdi mdi-delete mdi-24px"></span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-md-4">
                    <h5 class="card-title">Financeiro — Valor Adicional</h5>
                    <div class="alert alert-warning d-flex align-items-center mb-2" role="alert">
                        <i class="mdi mdi-cash me-2"></i>
                        <div>Caso as medicações adicionadas tenham custo extra, informe o valor adicional e selecione as parcelas que receberão o rateio.</div>
                    </div>
                    <div class="form-group">
                        <label for="valor_adicional">Valor Adicional (R$)</label>
                        <input class="form-control" type="text" id="valor_adicional" name="valor_adicional" value="{{ old('valor_adicional') }}" onkeypress="return(MascaraMoeda(this,'.',',',event))" placeholder="0,00">
                    </div>
                </div>
                <div class="col-md-8">
                    <h5 class="card-title">Rateio nas Parcelas Existentes</h5>
                    @if($prescricao->parcelas->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th></th>
                                        <th>Parcela</th>
                                        <th>Semana</th>
                                        <th>Valor</th>
                                        <th>Valor Pago</th>
                                        <th>Situação</th>
                                        <th>Novo Valor</th>
                                        <th>Nova Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prescricao->parcelas as $parcela)
                                        @php
                                        $badge_p = 'bg-danger';
                                        if($parcela->situacao == 'Paga'){ $badge_p = 'bg-success'; }
                                        elseif($parcela->situacao == 'Parcial'){ $badge_p = 'bg-warning'; }
                                        @endphp
                                        <tr>
                                            <td>
                                                <input class="form-check-input parcela-check" type="checkbox" value="{{ $parcela->id }}" id="parcela_{{ $parcela->id }}" name="parcelas_rateio[]" data-valor="{{ $parcela->valor_parcela }}" data-valor-pago="{{ $parcela->valor_pago }}" {{ old('parcelas_rateio') && in_array($parcela->id, old('parcelas_rateio')) ? 'checked' : '' }}>
                                            </td>
                                            <td><label class="form-check-label" for="parcela_{{ $parcela->id }}">{{ $parcela->nr_parcela }}</label></td>
                                            <td>Semana {{ $parcela->semana ? $parcela->semana->nr_semana : '-' }}</td>
                                            <td>R$ {{ valorDbForm($parcela->valor_parcela) }}</td>
                                            <td>R$ {{ valorDbForm($parcela->valor_pago) }}</td>
                                            <td><span class="badge rounded-pill {{ $badge_p }}">{{ $parcela->situacao }}</span></td>
                                            <td><span id="novo_valor_{{ $parcela->id }}" class="text-success">—</span></td>
                                            <td><span id="sit_novo_{{ $parcela->id }}">—</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted mb-0"><small>O valor adicional será dividido igualmente entre as parcelas selecionadas.</small></p>
                    @else
                        <p class="text-muted">Nenhuma parcela existente para rateio.</p>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>

        <script>
        document.querySelector('form').addEventListener('submit', function(e){
            var campoValor = document.getElementById('valor_adicional');
            if (!campoValor) return;
            var valor = (campoValor.value || '0').replace(/[^\d,]/g, '').replace(',', '.');
            if (parseFloat(valor) > 0) {
                var selecionadas = document.querySelectorAll('input[name="parcelas_rateio[]"]:checked');
                if (selecionadas.length === 0) {
                    e.preventDefault();
                    alert('Informe o valor adicional e selecione ao menos uma parcela para o rateio.');
                }
            }
        });
        </script>

        <script>
        function parse_valor_br(str){
            var v = (str || '0').replace(/[^\d,]/g, '').replace(',', '.');
            return parseFloat(v) || 0;
        }
        function format_br(v){
            var s = v.toFixed(2).split('.');
            s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return s.join(',');
        }
        function atualizar_rateio(){
            var valor = parse_valor_br(document.getElementById('valor_adicional').value);
            var checks = Array.prototype.slice.call(document.querySelectorAll('input.parcela-check'));
            var selecionadas = checks.filter(function(c){ return c.checked; }).sort(function(a,b){ return parseInt(a.value,10) - parseInt(b.value,10); });
            var total = selecionadas.length;

            checks.forEach(function(c){
                document.getElementById('novo_valor_' + c.value).textContent = '—';
                document.getElementById('sit_novo_' + c.value).textContent = '—';
            });

            if (valor <= 0 || total === 0) return;

            var base = Math.floor((valor / total) * 100) / 100;
            var resto = Math.round((valor - base * total) * 100) / 100;

            selecionadas.forEach(function(c, idx){
                var parcelaValor = parseFloat(c.getAttribute('data-valor'));
                var pago = parseFloat(c.getAttribute('data-valor-pago'));
                var parte = (idx === total - 1) ? Math.round((base + resto) * 100) / 100 : base;
                var novo = Math.round((parcelaValor + parte) * 100) / 100;

                document.getElementById('novo_valor_' + c.value).textContent = 'R$ ' + format_br(novo);

                var sitNovo = 'Em Aberto', cls = 'bg-danger';
                if (pago >= novo - 0.005) { sitNovo = 'Paga'; cls = 'bg-success'; }
                else if (pago > 0) { sitNovo = 'Parcial'; cls = 'bg-warning'; }
                document.getElementById('sit_novo_' + c.value).innerHTML = '<span class="badge rounded-pill ' + cls + '">' + sitNovo + '</span>';
            });
        }
        document.getElementById('valor_adicional').addEventListener('input', atualizar_rateio);
        document.querySelectorAll('input.parcela-check').forEach(function(c){ c.addEventListener('change', atualizar_rateio); });
        </script>
    </div>
</div>

<script>
let opcoes_medicamentos = `@foreach($medicamentos as $medicamento)<option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>@endforeach`;

function adicionar_medicamento(){
    let contador = parseInt(document.getElementById('contador_medicamentos').value) + 1;
    document.getElementById('contador_medicamentos').value = contador;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_medicamento_' + contador);
    tr.innerHTML = `
        <td><select name="medicamento_id_${contador}" class="form-select"><option value="">— Selecionar —</option>${opcoes_medicamentos}</select></td>
        <td><input type="text" name="quantidade_${contador}" class="form-control" value="1"></td>
        <td><button type="button" title="Excluir linha" onclick="excluir_linha_medicamento(${contador})" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab"><span class="tf-icons mdi mdi-delete mdi-24px"></span></button></td>
    `;
    document.getElementById('tabela_medicamentos').appendChild(tr);
}

function excluir_linha_medicamento(linha){
    let el = document.getElementById('linha_medicamento_' + linha);
    if(el){ el.remove(); }
}
</script>
@endsection
