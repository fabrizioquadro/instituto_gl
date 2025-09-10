@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<style media="screen">
    .select2-selection__rendered{
        line-height: 40px !important;
        border-color: red !important;
    }
    .select2-selection{
        height: 40px !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Procedimentos @if(isset($codigo)) - Grupo: {{ $codigo }} @endif</h4>
            <button type="button" id="botao_gerador" class="btn btn-label-secondary waves-effect">
                <span class="tf-icons mdi mdi-cog-outline me-1"></span>
                Gerador
            </button>
        </div>
        <hr>
        <form action="{{ route('sistema.procedimentos.insert') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="contador_procedimentos" id="contador_procedimentos" value="1">
            <input type="hidden" name="retorno" value="{{ $retorno }}">
            @if(isset($codigo))
                <input type="hidden" name="codigo" value="{{ $codigo }}">
                <div class="row mt-2 gy-4 align-items-end mb-3">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input class="form-control" type="file" multiple id="anexos" name="anexos[]"/>
                            <label for="anexos">Anexos:</label>
                        </div>
                    </div>
                </div>
            @else
                <div class="row mt-2 gy-4 align-items-end mb-3">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <select required id="paciente_id" name='paciente_id' class="select2 form-select">
                                <option value="">Opções</option>
                            </select>
                            <label for="paciente_id">Paciente:</label>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row mt-2 gy-4 align-items-end">
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <select required id="medico" name='medico' class="select2 form-select">
                                <option value="">Opções</option>
                                @foreach($medicos as $medico)
                                    <option value="{{ $medico['profissional_nome'] }}">{{ $medico['profissional_nome'] }}</option>
                                @endforeach
                            </select>
                            <label for="medico">Médico:</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating form-floating-outline">
                            <input class="form-control" type="file" multiple id="anexos" name="anexos[]"/>
                            <label for="anexos">Anexos:</label>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <button type="button" id="botao_adicionar_procedimento" onclick="adicionar_procedimento()" class="btn btn-label-primary waves-effect">
                        <span class="tf-icons mdi mdi-plus me-1"></span>
                        Procedimento
                    </button>
                </div>
            </div>
            <div id="div_procedimentos">
                <div id="card_1" class="card card-border-shadow-primary mt-4">
                    <div class="card-body">
                        <input type="hidden" name="contador_medicamentos_1" id="contador_medicamentos_1" value="1">
                        <div class="d-flex justify-content-between">
                            <h6 class="card-title">Semana 1</h6>
                            <button type="button" onclick="adicionar_medicamento(1)" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                                <span class="tf-icons mdi mdi-plus me-1"></span>
                                Medicamento
                            </button>
                        </div>
                        <hr>
                        <div class="row gy-4 align-items-end">
                            <div class="col-md-3">
                                <div class="form-floating form-floating-outline">
                                    <input class="form-control" type="date" id="data_aplicacao_1" name="data_aplicacao_1" required/>
                                    <label for="data_aplicacao_1">Data Prevista:</label>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-floating form-floating-outline">
                                    <input class="form-control" type="text" id="obs_1" name="obs_1"/>
                                    <label for="obs_1">Obs:</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="switch">
                                    <input type="checkbox" class="switch-input" name="semana_sem_aplicacao_1" value="true">
                                    <span class="switch-toggle-slider">
                                        <span class="switch-on"></span>
                                        <span class="switch-off"></span>
                                    </span>
                                    <span class="switch-label">Semana sem Aplicação</span>
                                </label>
                            </div>
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
                                <tbody id="tabela_medicamentos_1">
                                    <tr id="linha_medicamento_1_1">
                                        <td>
                                            <select onchange="set_valor_medicamento(1,1)" required name="medicamento_id_1_1" id="medicamento_id_1_1" class="form-control">
                                                <option>Opções</option>
                                                @foreach($medicamentos as $medicamento)
                                                    <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input onblur="calcula_total_medicamento(1,1)" name="quantidade_1_1" id="quantidade_1_1" required type="text" class="form-control"></td>
                                        <td><input onblur="calcula_total_medicamento(1,1)" name="valor_1_1" id="valor_1_1" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                        <td><input onblur="calcula_total_medicamento(1,1)" name="total_1_1" id="total_1_1" required type="text" class="form-control total_1" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                        <td>
                                            <button type="button" title='Excluir linha' onclick='excluir_linha_medicamento(1,1)' class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
                                                <span class="tf-icons mdi mdi-delete mdi-24px"></span>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-2 gy-4 align-items-end">
                            <div class="col-md-4">
                                <div class="form-floating form-floating-outline">
                                    <input onblur="calcula_total_procedimento(1)" class="form-control total_procedimento" type="text" id="total_procedimento_1" name="total_procedimento_1" required onkeypress="return(MascaraMoeda(this,'.',',',event))"/>
                                    <label for="total_procedimento_1">Total Procedimento 1:</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-border-shadow-primary mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6 class="card-title">Financeiro Total</h6>
                    </div>
                    <div class="row mt-2 gy-4 align-items-end">
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input onblur="calcula_total_procedimento_all()" class="form-control" type="text" id="total_geral_procedimento" name="total_geral_procedimento" required onkeypress="return(MascaraMoeda(this,'.',',',event))"/>
                                <label for="total_geral_procedimento">Total Geral Procedimentos:</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                    <button type="button" id='botao_imprimir' class="btn btn-secondary me-2">Imprimir</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form target="_blank" id="form_imprimir" action="{{ route('sistema.procedimentos.imprimir') }}" method="post">
    @csrf
    <input type="hidden" name="data" id="imprimir_data">
</form>

<script>
window.addEventListener('load',()=>{
    $('.combobox').combobox();

    $('#paciente_id').select2({
        placeholder: "Escolha o Paciente.",
        allowClear: true,
        minimumInputLength: 2,
        ajax:{
            url:"{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
            dataType: "json",
            type: 'GET',
            delay: 250,
            data:function(params){
                return {
                    q: params.term,
                };
            },
            processResults: function(data){
                return {
                    results:data
                };
            },
        cache: true
        }
    });
});

document.getElementById('botao_imprimir').addEventListener('click', ()=>{
    //vamos montar o html
    html = `
    <table class='table'>
        <thead>
            <tr>
                <th>Data</th>
                <th>Semana</th>
                <th>Medicamento</th>
                <th>Quantidade</th>
                <th>Valor</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
    `;
    for(i=1 ; i<=parseInt(document.getElementById('contador_procedimentos').value) ; i++ ){
        data = document.getElementById('data_aplicacao_' + i).value;
        if(data != ""){
            data = data.split('-');
            data = data[2] + '/' + data[1] + '/' + data[0];        }
        else{
            data = '';
        }
        for(j=1 ; j<=parseInt(document.getElementById('contador_medicamentos_' + i).value) ; j++){
            let select = document.getElementById('medicamento_id_' + i + '_' + j);
            let option = select.children[select.selectedIndex];
            let medicamento = option.textContent;
            let quantidade = document.getElementById('quantidade_' + i + '_' + j).value;
            let valor = document.getElementById('valor_' + i + '_' + j).value;
            let total = document.getElementById('total_' + i + '_' + j).value;

            html = html + `
            <tr>
                <td>${data}</td>
                <td>Semana ${i}</td>
                <td>${medicamento}</td>
                <td>${quantidade}</td>
                <td>${valor}</td>
                <td>${total}</td>
            </tr>
            `;
        }
    }
    valor_total = document.getElementById('total_geral_procedimento').value;
    html = html + `
    </tbody>
    <tfoot>
        <tr>
            <td colspan='5'>Total</td>
            <td>${valor_total}</td>
        </tr>
    </tfoot>
    </table>
    `;
    document.getElementById('imprimir_data').value = html;
    document.getElementById('form_imprimir').submit();
})

function set_valor_medicamento(linha, medicamento){
    select = document.getElementById("medicamento_id_" + linha + '_' + medicamento);
    selectedOption = select.options[select.selectedIndex];
    valor = parseInt(selectedOption.dataset.valor);
    valor = valor.toFixed(2);
    document.getElementById("valor_" + linha + '_' + medicamento).value = valor.replace('.',',');
    calcula_total_medicamento(linha,medicamento)
}

function calcula_total_medicamento(linha,medicamento){
    quantidade = parseFloat(document.getElementById("quantidade_" + linha + '_' + medicamento).value);
    valor = document.getElementById("valor_" + linha + '_' + medicamento).value;
    if(quantidade && valor){
        valor = valor.replace('.','');
        valor = parseFloat(valor.replace(',','.'));
        total = quantidade * valor;
        total = total.toFixed(2);
        document.getElementById('total_' + linha + '_' + medicamento).value = total.replace('.',',');
    }
    calcula_total_procedimento(linha);
}

function calcula_total_procedimento(linha){
    let somatorio = 0;
    let variavel = "input.total_" + linha;

    inputs = document.querySelectorAll(variavel);
    [].forEach.call(inputs, function(input) {
        valor = input.value;
        if(valor){
            valor = valor.replaceAll('.','');
            valor = valor.replace(',','.');
            valor = parseFloat(valor);
            somatorio = somatorio + valor;
        }
    });

    somatorio = somatorio.toFixed(2);
    somatorio = somatorio.replace('.',",");
    document.getElementById('total_procedimento_' + linha).value = somatorio

    calcula_total_procedimento_all();
}

function calcula_total_procedimento_all(){
    let somatorio = 0;
    let variavel = "input.total_procedimento";

    inputs = document.querySelectorAll(variavel);
    [].forEach.call(inputs, function(input) {
        valor = input.value;
        if(valor){
            valor = valor.replaceAll('.','');
            valor = valor.replace(',','.');
            valor = parseFloat(valor);
            somatorio = somatorio + valor;
        }
    });

    somatorio = somatorio.toFixed(2);
    somatorio = somatorio.replace('.',",");
    document.getElementById('total_geral_procedimento').value = somatorio
}

function adicionar_medicamento(linha){
    contador = parseInt(document.getElementById('contador_medicamentos_' + linha).value);
    contador++;
    document.getElementById('contador_medicamentos_' + linha).value = contador;
    tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_medicamento_' + linha + '_' + contador);
    variavel = linha + '_' + contador;

    html = `
    <td>
        <select onchange="set_valor_medicamento(${linha},${contador})" required name="medicamento_id_${variavel}" id="medicamento_id_${variavel}" class="form-control">
            <option>Opções</option>
            @foreach($medicamentos as $medicamento)
                <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
            @endforeach
        </select>
    </td>
    <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="quantidade_${variavel}" id="quantidade_${variavel}" required type="text" class="form-control"></td>
    <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="valor_${variavel}" id="valor_${variavel}" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
    <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="total_${variavel}" id="total_${variavel}" required type="text" class="form-control total_${linha}" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
    <td>
        <button type="button" title='Excluir linha' onclick='excluir_linha_medicamento(${linha},${contador})' class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
            <span class="tf-icons mdi mdi-delete mdi-24px"></span>
        </button>
    </td>
    `;

    tr.innerHTML = html;
    document.getElementById('tabela_medicamentos_' + linha).appendChild(tr);

    document.getElementById('medicamento_id_' + variavel).focus();

}

function adicionar_procedimento(){
    contador = parseInt(document.getElementById('contador_procedimentos').value);
    contador++;
    document.getElementById('contador_procedimentos').value = contador;
    card = document.createElement('div');
    card.setAttribute('class', 'card card-border-shadow-primary mt-4');
    card.setAttribute('id', 'card_' + contador);
    html = `
    <div class="card-body">
        <input type="hidden" name="contador_medicamentos_${contador}" id="contador_medicamentos_${contador}" value="1">
        <div class="d-flex justify-content-between">
            <h6 class="card-title">Semana ${contador}</h6>
            <div>
                <button type="button" onclick="excluir_procedimento(${contador})" class="btn btn-sm rounded-pill btn-outline-danger waves-effect">
                    <span class="tf-icons mdi mdi-delete me-1"></span>
                    Excluir Procedimento
                </button>
                <button type="button" onclick="adicionar_medicamento(${contador})" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                    <span class="tf-icons mdi mdi-plus me-1"></span>
                    Medicamento
                </button>
            </div>
        </div>
        <hr>
        <div class="row gy-4 align-items-end">
            <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                    <input class="form-control" type="date" id="data_aplicacao_${contador}" name="data_aplicacao_${contador}" required/>
                    <label for="data_aplicacao_${contador}">Data Prevista:</label>
                </div>
            </div>
            <div class="col-md-9">
                <div class="form-floating form-floating-outline">
                    <input class="form-control" type="text" id="obs_${contador}" name="obs_${contador}"/>
                    <label for="obs_${contador}">Obs:</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="switch">
                    <input type="checkbox" class="switch-input" name="semana_sem_aplicacao_${contador}" value="true">
                    <span class="switch-toggle-slider">
                        <span class="switch-on"></span>
                        <span class="switch-off"></span>
                    </span>
                    <span class="switch-label">Semana sem Aplicação</span>
                </label>
            </div>
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
                <tbody id="tabela_medicamentos_${contador}">
                    <tr id="linha_medicamento_${contador}_1">
                        <td>
                            <select onchange="set_valor_medicamento(${contador},1)" required name="medicamento_id_${contador}_1" id="medicamento_id_${contador}_1" class="form-control">
                                <option>Opções</option>
                                @foreach($medicamentos as $medicamento)
                                    <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input onblur="calcula_total_medicamento(${contador},1)" name="quantidade_${contador}_1" id="quantidade_${contador}_1" required type="text" class="form-control"></td>
                        <td><input onblur="calcula_total_medicamento(${contador},1)" name="valor_${contador}_1" id="valor_${contador}_1" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                        <td><input onblur="calcula_total_medicamento(${contador},1)" name="total_${contador}_1" id="total_${contador}_1" required type="text" class="form-control total_${contador}" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                        <td>
                            <button type="button" title='Excluir linha' onclick='excluir_linha_medicamento(${contador},1)' class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
                                <span class="tf-icons mdi mdi-delete mdi-24px"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="row mt-2 gy-4 align-items-end">
            <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                    <input onblur="calcula_total_procedimento(${contador})" class="form-control total_procedimento" type="text" id="total_procedimento_${contador}" name="total_procedimento_${contador}" required onkeypress="return(MascaraMoeda(this,'.',',',event))"/>
                    <label for="total_procedimento_${contador}">Total Procedimento ${contador}:</label>
                </div>
            </div>
        </div>
    </div>
    `;
    card.innerHTML = html;
    document.getElementById('div_procedimentos').appendChild(card);
}

function excluir_linha_medicamento(linha, contador){
    if(confirm('Tem certeza que deseja excluir a linha selecionada?')){
        document.getElementById('linha_medicamento_' + linha + "_" + contador).remove();
        calcula_total_procedimento(linha);
    }
}

function excluir_procedimento(nr_procedimento){
    if(confirm('Tem certeza que deseja excluir o procedimento selecionada')){
        document.getElementById('card_' + nr_procedimento).remove();
        calcula_total_procedimento_all();
    }
}

</script>

<div class="modal fade" id="modal_gerador" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="post">
            <input type="hidden" id="gerador_contador_medicamentos" value='1'>
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Gerador Procedimentos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mt-2 gy-4">
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <input class="form-control" type="date" id="gerador_dt_inicio"/>
                            <label for="gerador_dt_inicio">Data 1º Procedimento:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <input class="form-control" type="number" id="gerador_nr_procedimentos"/>
                            <label for="gerador_nr_procedimentos">Nr. Procedimentos:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <input class="form-control" type="number" id="gerador_intervalo"/>
                            <label for="gerador_intervalo">Intervalo entre Procedimentos:</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-5">
                    <h6 class="card-title">Medicamentos</h6>
                    <button type="button" onclick="gerador_adicionar_medicamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                        <span class="tf-icons mdi mdi-plus me-1"></span>
                        Medicamento
                    </button>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Medicamento</th>
                                <th>Quantidade</th>
                                <th>Valor</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="gerador_tabela_medicamentos">
                            <tr id="gerador_linha_medicamento_1">
                                <td>
                                    <select onchange="gerador_set_valor_medicamento(1)" name="gerador_medicamento_id_1" id="gerador_medicamento_id_1" class="form-control">
                                        <option>Opções</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_quantidade_1" id="gerador_quantidade_1" required type="text" class="form-control"></td>
                                <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_valor_1" id="gerador_valor_1" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_total_1" id="gerador_total_1" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mb-3 mt-3">
                    <button class="btn btn-primary" type="button" onclick="gera_procedimentos_gerador()">Gerar Procedimentos</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
var modalGerador;

document.getElementById('botao_gerador').addEventListener('click', ()=>{
    modalGerador = new bootstrap.Modal(document.getElementById('modal_gerador'));
    modalGerador.show();
})

function gerador_set_valor_medicamento(linha){
    select = document.getElementById("gerador_medicamento_id_" + linha);
    selectedOption = select.options[select.selectedIndex];
    valor = parseInt(selectedOption.dataset.valor);
    valor = valor.toFixed(2);
    document.getElementById("gerador_valor_" + linha).value = valor.replace('.',',');
    gerador_calcula_total_medicamento(linha);
}

function gerador_calcula_total_medicamento(linha){
    quantidade = parseFloat(document.getElementById("gerador_quantidade_" + linha).value);
    valor = document.getElementById("gerador_valor_" + linha).value;
    if(quantidade && valor){
        valor = valor.replace('.','');
        valor = parseFloat(valor.replace(',','.'));
        total = quantidade * valor;
        total = total.toFixed(2);
        document.getElementById('gerador_total_' + linha).value = total.replace('.',',');
    }
}

function gerador_adicionar_medicamento(){
    contador = parseInt(document.getElementById('gerador_contador_medicamentos').value);
    contador++;
    document.getElementById('gerador_contador_medicamentos').value = contador;

    tr = document.createElement('tr');
    tr.setAttribute('id', 'gerador_linha_medicamento_' + contador);

    html = `
    <td>
        <select onchange="gerador_set_valor_medicamento(${contador})" required name="gerador_medicamento_id_${contador}" id="gerador_medicamento_id_${contador}" class="form-control">
            <option>Opções</option>
            @foreach($medicamentos as $medicamento)
                <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante }}</option>
            @endforeach
        </select>
    </td>
    <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_quantidade_${contador}" id="gerador_quantidade_${contador}" required type="text" class="form-control"></td>
    <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_valor_${contador}" id="gerador_valor_${contador}" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
    <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_total_${contador}" id="gerador_total_${contador}" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
    <td></td>
    `;

    tr.innerHTML = html;
    document.getElementById('gerador_tabela_medicamentos').appendChild(tr);
}

function gera_procedimentos_gerador(){
    dt_inicio = document.getElementById('gerador_dt_inicio').value;
    nr_procedimentos = parseInt(document.getElementById('gerador_nr_procedimentos').value);
    intervalo = parseInt(document.getElementById('gerador_intervalo').value);
    contador_medicamentos = parseInt(document.getElementById('gerador_contador_medicamentos').value);

    data = new Date(dt_inicio);

    if(dt_inicio && nr_procedimentos && intervalo){
        for(i=1 ; i<=nr_procedimentos ; i++){
            if(i != 1){
                //vamos jogar na pagina o procedimento i
                adicionar_procedimento();
                data.setDate(data.getDate() + intervalo);
            }
            //vamos setar a data
            document.getElementById('data_aplicacao_' + i).value = data.toISOString().slice(0, 10);

            //vamos adicionar os medicamentos
            for(j=1 ; j<=contador_medicamentos ; j++){
                if(j != 1){
                    adicionar_medicamento(i);
                }
                medicamento_id = document.getElementById('gerador_medicamento_id_' + j).value;
                quantidade = document.getElementById('gerador_quantidade_' + j).value;
                valor = document.getElementById('gerador_valor_' + j).value;
                total = document.getElementById('gerador_total_' + j).value;

                document.getElementById('medicamento_id_' + i + "_" + j).value = medicamento_id;
                document.getElementById('quantidade_' + i + "_" + j).value = quantidade;
                document.getElementById('valor_' + i + "_" + j).value = valor;
                document.getElementById('total_' + i + "_" + j).value = total;

                calcula_total_medicamento(i,j);
            }
        }
        modalGerador.hide();
    }
    else{
        alert('é necessário preencher todos os campos.');
    }
}

</script>
@endsection
