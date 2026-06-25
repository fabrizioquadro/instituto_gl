@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Medicamentos</h4>
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
        <form id='formulario' action="{{ route('sistema.procedimentos.adicionar_medicamentos_insert') }}" method="post">
            @csrf
            <input type="hidden" name="codigo" value="{{ $codigo }}">
            <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="1">
            <div class="row mt-3">
                <div class="col-md-3">
                    <h5 class="card-title">Procedimentos</h5>
                    @foreach($procedimentos as $procedimento)
                        <div class="form-check mt-3">
                            <input @if($procedimento->situacao != "Aplicado") checked @endif class="form-check-input" type="checkbox" value="{{ $procedimento->id }}" id="procedimentos_{{ $procedimento->id }}" name="procedimentos[]">
                            <label class="form-check-label" for=""> {{ 'Semana '.$procedimento->nr_procedimento." - ".dataDbForm($procedimento->data_aplicacao) }} </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-9">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">Medicamentos</h5>
                        <button type="button" onclick="adicionar_medicamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                            <span class="tf-icons mdi mdi-plus me-1"></span>
                            Medicamento
                        </button>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Quantidade Semanal</th>
                                    <th>Valor Unitário</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tabela_medicamentos">
                                <tr id="linha_medicamento_1">
                                    <td>
                                        <select onchange="set_valor_medicamento(1)" required name="medicamento_id_1" id="medicamento_id_1" class="form-control">
                                            <option>Opções</option>
                                            @foreach($medicamentos as $medicamento)
                                                <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input onblur="calcula_total_medicamento(1)" name="quantidade_1" id="quantidade_1" required type="text" class="form-control"></td>
                                    <td><input onblur="calcula_total_medicamento(1)" name="valor_1" id="valor_1" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                    <td><input onblur="calcula_total_medicamento(1)" name="total_1" id="total_1" required type="text" class="form-control total_1" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                    <td>
                                        <button type="button" title='Excluir linha' onclick='excluir_linha_medicamento(1)' class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
                                            <span class="tf-icons mdi mdi-delete mdi-24px"></span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-warning mb-2">
                        <h6 class="alert-heading fw-bold mb-1"><i class="mdi mdi-alert-circle-outline me-1"></i>Atenção: Adição de Valores!</h6>
                        <span>A inclusão de novas medicações acarretará na geração de valores adicionais no financeiro. O paciente deverá realizar o pagamento para que as aplicações sejam liberadas na fila de atendimento.</span>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="aceite_cliente" id="aceite_cliente" required>
                        <label class="form-check-label fw-bold text-danger" for="aceite_cliente">
                            Confirmo que informei o paciente sobre o custo adicional destas medicações.
                        </label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary me-2">Salvar</button>
            </div>
        </form>
    </div>
</div>
<script>
function adicionar_medicamento(linha){
    contador = parseInt(document.getElementById('contador_medicamentos').value);
    contador++;
    document.getElementById('contador_medicamentos').value = contador;
    tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_medicamento_' + contador);

    html = `
    <td>
        <select onchange="set_valor_medicamento(${contador})" required name="medicamento_id_${contador}" id="medicamento_id_${contador}" class="form-control">
            <option>Opções</option>
            @foreach($medicamentos as $medicamento)
                <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
            @endforeach
        </select>
    </td>
    <td><input onblur="calcula_total_medicamento(${contador})" name="quantidade_${contador}" id="quantidade_${contador}" required type="text" class="form-control"></td>
    <td><input onblur="calcula_total_medicamento(${contador})" name="valor_${contador}" id="valor_${contador}" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
    <td><input onblur="calcula_total_medicamento(${contador})" name="total_${contador}" id="total_${contador}" required type="text" class="form-control total_${contador}" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
    <td>
        <button type="button" title='Excluir linha' onclick='excluir_linha_medicamento(${contador})' class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
            <span class="tf-icons mdi mdi-delete mdi-24px"></span>
        </button>
    </td>
    `;

    tr.innerHTML = html;
    document.getElementById('tabela_medicamentos').appendChild(tr);

    document.getElementById('medicamento_id_' + contador).focus();

}

function set_valor_medicamento(medicamento){
    select = document.getElementById("medicamento_id_" + medicamento);
    selectedOption = select.options[select.selectedIndex];
    valor = parseInt(selectedOption.dataset.valor);
    valor = valor.toFixed(2);
    document.getElementById("valor_" + medicamento).value = valor.replace('.',',');

    $.getJSON(
        '{{ route("adm.medicamentos.buscar") }}',
        {medicamento_id:select.value},
        function(json){
            if(json.unidade == "Procedimento" || json.nome.toLowerCase().startsWith('pellet')){
                document.getElementById("valor_" + medicamento).removeAttribute('readonly');
            }
            else{
                document.getElementById("valor_" + medicamento).setAttribute('readonly','readonly');
            }
        }
    );

    calcula_total_medicamento(medicamento)
}

function calcula_total_medicamento(medicamento){
    medicamento_id = document.getElementById("medicamento_id_" + medicamento).value;

    $.getJSON(
        '{{ route("adm.medicamentos.buscar") }}',
        {medicamento_id:medicamento_id},
        function(json){
            if(json.unidade == "Ampola"){
                quantidade = Math.ceil(parseFloat(document.getElementById("quantidade_" + medicamento).value));
            }
            else{
                quantidade = parseFloat(document.getElementById("quantidade_" + medicamento).value);
            }

            valor = document.getElementById("valor_" + medicamento).value;
            if(quantidade && valor){
                valor = valor.replace('.','');
                valor = parseFloat(valor.replace(',','.'));
                total = quantidade * valor;
                total = total.toFixed(2);
                document.getElementById('total_' + medicamento).value = total.replace('.',',');
            }
        }
    );
}

function excluir_linha_medicamento(linha){
    if(confirm('Tem certeza que deseja excluir a linha selecionada?')){
        document.getElementById('linha_medicamento_' + linha).remove();
    }
}
</script>
@endsection
