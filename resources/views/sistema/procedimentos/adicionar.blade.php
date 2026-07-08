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
        <form id="form_cadastro_procedimento" action="{{ route('sistema.procedimentos.insert') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="inicio_cadastro" value="{{ now() }}">
            <input type="hidden" name="contador_procedimentos" id="contador_procedimentos" value="1">
            <input type="hidden" name="retorno" value="{{ $retorno }}">
            @if(isset($codigo))
                <input type="hidden" name="codigo" value="{{ $codigo }}">
                <div class="row mt-2 gy-4 align-items-end mb-3">
                    <div class="col-md-4">
                         <div class="form-floating form-floating-outline">
                            <input class="form-control" type="file" multiple id="anexos" name="anexos[]"/>
                            <label for="anexos">Anexos:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                         <div class="form-floating form-floating-outline">
                            <input required class="form-control" type="text" id="agendamento" name="agendamento"/>
                            <label for="agendamento">Agendamento:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <select required id="tipo_atendimento" name='tipo_atendimento' class="form-select">
                                <option value="">Opções</option>
                                <option value="Consulta Tratamento">Consulta Tratamento</option>
                                <option value="Retorno">Retorno</option>
                                <option value="Consulta Nova">Consulta Nova</option>
                                <option value="Coleta/Bio">Coleta/Bio</option>
                            </select>
                            <label for="tipo_atendimento">Tipo de Atendimento:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="">Paciente:</label><br>
                        <strong>{{ $paciente->nm_paciente }}</strong>
                    </div>
                </div>
                <div class="row mt-2 gy-4 align-items-end mb-3">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <textarea class="form-control h-px-75" name="paciente_obs" id="paciente_obs">{{ $paciente->obs }}</textarea>
                            <label for="paciente_obs">Observação do Paciente:</label>
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
                <div class="row mt-2 gy-4 align-items-end mb-3">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <textarea class="form-control h-px-75" name="paciente_obs" id="paciente_obs"></textarea>
                            <label for="paciente_obs">Observação do Paciente:</label>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row mt-2 gy-4 align-items-end">
                    <div class="col-md-4">
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
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <input required class="form-control" type="text" id="agendamento" name="agendamento"/>
                            <label for="agendamento">Agendamento:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating form-floating-outline">
                            <select required id="tipo_atendimento" name='tipo_atendimento' class="form-select">
                                <option value="">Opções</option>
                                <option value="Consulta Tratamento">Consulta Tratamento</option>
                                <option value="Retorno">Retorno</option>
                                <option value="Consulta Nova">Consulta Nova</option>
                                <option value="Coleta/Bio">Coleta/Bio</option>
                            </select>
                            <label for="tipo_atendimento">Tipo de Atendimento:</label>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                            <div>
                                <button type="button" onclick="abrir_modal_combo(1)" class="btn btn-sm rounded-pill btn-outline-info waves-effect">
                                    <span class="tf-icons mdi mdi-plus me-1"></span>
                                    Combos
                                </button>
                                <button type="button" onclick="adicionar_medicamento(1)" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                                    <span class="tf-icons mdi mdi-plus me-1"></span>
                                    Medicamento
                                </button>
                            </div>
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
                                            <input type="hidden" name="is_soro_1_1" id="is_soro_1_1" value="0">
                                            <select onchange="set_valor_medicamento(1,1)" required name="medicamento_id_1_1" id="medicamento_id_1_1" class="form-control">
                                                <option value="">Opções</option>
                                                @foreach($medicamentos as $medicamento)
                                                    <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input onblur="calcula_total_medicamento(1,1)" name="quantidade_1_1" id="quantidade_1_1" required type="text" class="form-control"></td>
                                        <td><input onblur="calcula_total_medicamento(1,1)" name="valor_1_1" id="valor_1_1" required type="text" class="form-control"></td>
                                        <td><input onblur="calcula_total_medicamento(1,1)" name="total_1_1" id="total_1_1" required type="text" class="form-control total_1" readonly></td>
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
                                    <input onblur="calcula_total_procedimento(1)" class="form-control total_procedimento" type="text" id="total_procedimento_1" name="total_procedimento_1" required readonly/>
                                    <label for="total_procedimento_1">Total Procedimento 1:</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card card-border-shadow-info mt-4" id="card_resumo_medicamentos" style="display: none;">
                <div class="card-body">
                    <h6 class="card-title">Resumo de Medicações Adicionadas</h6>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicamento</th>
                                    <th>Quantidade Total</th>
                                    <th>Valor Unitário</th>
                                    <th>Total Parcial</th>
                                </tr>
                            </thead>
                            <tbody id="tabela_resumo_medicamentos">
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total Geral:</th>
                                    <th id="resumo_total_geral">R$ 0,00</th>
                                </tr>
                            </tfoot>
                        </table>
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
                                <input onblur="calcula_total_procedimento_all()" class="form-control" type="text" id="total_geral_procedimento" name="total_geral_procedimento" required readonly/>
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
const medicamentosMap = {
    @foreach($medicamentos as $medicamento)
        "{{ $medicamento->id }}": {
            unidade: "{{ $medicamento->unidade }}",
            vl_venda: {{ $medicamento->vl_venda ?? 0 }}
        },
    @endforeach
};

let isConfirmed = false;

window.addEventListener('load',()=>{
    const formObj = document.getElementById('form_cadastro_procedimento');
    if (formObj) {
        formObj.addEventListener('submit', function(e) {
            if (!isConfirmed) {
                e.preventDefault();
                valida_e_confirma_procedimento();
            }
        });
    }
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

    $('#paciente_id').on('select2:select', function (e) {
        var data = e.params.data;
        $.getJSON(
            '{{ route("sistema.pacientes.get_paciente_ajax") }}',
            {paciente_id : data.id},
            function(json){
                document.getElementById('paciente_obs').value = json.obs;
            }
        );
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
    valor = parseFloat(selectedOption.dataset.valor);
    valor = valor.toFixed(2);
    document.getElementById("valor_" + linha + '_' + medicamento).value = valor.replace('.',',');

    document.getElementById("valor_" + linha + '_' + medicamento).removeAttribute('readonly');
    document.getElementById("valor_" + linha + '_' + medicamento).setAttribute('onkeypress',"return(MascaraMoeda(this,'.',',',event))");

    calcula_total_medicamento(linha,medicamento)
}

function calcula_total_medicamento(linha,medicamento){
    medicamento_id = document.getElementById("medicamento_id_" + linha + '_' + medicamento).value;

    $.getJSON(
        '{{ route("adm.medicamentos.buscar") }}',
        {medicamento_id:medicamento_id},
        function(json){
            let valorInput = document.getElementById("valor_" + linha + '_' + medicamento);
            let valorDigitado = valorInput.value;
            if(valorDigitado){
                valorDigitado = valorDigitado.replace(/\./g,'').replace(',','.');
                valorDigitado = parseFloat(valorDigitado);
                let valorTabela = parseFloat(json.vl_venda);
                if(valorDigitado < valorTabela){
                    alert('O valor do medicamento não pode ser menor do que o preço de tabela (R$ ' + json.vl_venda.replace('.', ',') + ').');
                    valorInput.value = json.vl_venda.replace('.', ',');
                }
            }

            if(json.unidade == "Ampola"){
                quantidade = Math.ceil(parseFloat(document.getElementById("quantidade_" + linha + '_' + medicamento).value));
            }
            else{
                quantidade = parseFloat(document.getElementById("quantidade_" + linha + '_' + medicamento).value);
            }

            valor = document.getElementById("valor_" + linha + '_' + medicamento).value;
            if(quantidade && valor){
                valor = valor.replace(/\./g,'');
                valor = parseFloat(valor.replace(',','.'));
                total = quantidade * valor;
                total = total.toFixed(2);
                document.getElementById('total_' + linha + '_' + medicamento).value = total.replace('.',',');
            }
            calcula_total_procedimento(linha);
        }
    );
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
    document.getElementById('total_geral_procedimento').value = somatorio;

    atualiza_resumo_medicamentos();
}

function atualiza_resumo_medicamentos() {
    let resumo = {};
    let num_procedimentos = parseInt(document.getElementById('contador_procedimentos').value);
    
    for(let i = 1; i <= num_procedimentos; i++) {
        if(!document.getElementById('contador_medicamentos_' + i)) continue;
        
        let num_meds = parseInt(document.getElementById('contador_medicamentos_' + i).value);
        for(let j = 1; j <= num_meds; j++) {
            let select = document.getElementById('medicamento_id_' + i + '_' + j);
            if(!select) continue;
            
            let med_id = select.value;
            if(med_id === "") continue;
            let med_nome = select.options[select.selectedIndex].text;
            
            let val_qtd = document.getElementById('quantidade_' + i + '_' + j).value;
            let val_tot = document.getElementById('total_' + i + '_' + j).value;
            let val_uni = document.getElementById('valor_' + i + '_' + j).value;
            
            let qtd = parseFloat(val_qtd ? val_qtd.replace(',','.') : 0);
            let tot = parseFloat(val_tot ? val_tot.replaceAll('.','').replace(',','.') : 0);
            let uni = parseFloat(val_uni ? val_uni.replaceAll('.','').replace(',','.') : 0);
            
            if(isNaN(qtd)) qtd = 0;
            if(isNaN(tot)) tot = 0;
            if(isNaN(uni)) uni = 0;
            
            if(!resumo[med_id]) {
                resumo[med_id] = { nome: med_nome, qtd: 0, val_unitario: uni, tot: 0 };
            }
            resumo[med_id].qtd += qtd;
            resumo[med_id].tot += tot;
            // Atualiza o valor unitario caso tenha mudado
            resumo[med_id].val_unitario = uni;
        }
    }
    
    let html = '';
    let total_geral = 0;
    
    for(let med_id in resumo) {
        let r = resumo[med_id];
        
        html += `
        <tr>
            <td>${r.nome}</td>
            <td>${r.qtd}</td>
            <td>R$ ${r.val_unitario.toFixed(2).replace('.', ',')}</td>
            <td>R$ ${r.tot.toFixed(2).replace('.', ',')}</td>
        </tr>
        `;
        total_geral += r.tot;
    }
    
    document.getElementById('tabela_resumo_medicamentos').innerHTML = html;
    document.getElementById('resumo_total_geral').innerText = 'R$ ' + total_geral.toFixed(2).replace('.', ',');
    
    if(Object.keys(resumo).length > 0) {
        document.getElementById('card_resumo_medicamentos').style.display = 'block';
    } else {
        document.getElementById('card_resumo_medicamentos').style.display = 'none';
    }
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
        <input type="hidden" name="is_soro_${variavel}" id="is_soro_${variavel}" value="0">
        <select onchange="set_valor_medicamento(${linha},${contador})" required name="medicamento_id_${variavel}" id="medicamento_id_${variavel}" class="form-control">
            <option value="">Opções</option>
            @foreach($medicamentos as $medicamento)
                <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
            @endforeach
        </select>
    </td>
    <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="quantidade_${variavel}" id="quantidade_${variavel}" required type="text" class="form-control"></td>
    <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="valor_${variavel}" id="valor_${variavel}" required type="text" class="form-control"></td>
    <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="total_${variavel}" id="total_${variavel}" required type="text" class="form-control total_${linha}" readonly></td>
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
                <button type="button" onclick="abrir_modal_combo(${contador})" class="btn btn-sm rounded-pill btn-outline-info waves-effect">
                    <span class="tf-icons mdi mdi-plus me-1"></span>
                    Combos
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
                            <input type="hidden" name="is_soro_${contador}_1" id="is_soro_${contador}_1" value="0">
                            <select onchange="set_valor_medicamento(${contador},1)" required name="medicamento_id_${contador}_1" id="medicamento_id_${contador}_1" class="form-control">
                                <option value="">Opções</option>
                                @foreach($medicamentos as $medicamento)
                                    <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input onblur="calcula_total_medicamento(${contador},1)" name="quantidade_${contador}_1" id="quantidade_${contador}_1" required type="text" class="form-control"></td>
                        <td><input onblur="calcula_total_medicamento(${contador},1)" name="valor_${contador}_1" id="valor_${contador}_1" required type="text" class="form-control" readonly></td>
                        <td><input onblur="calcula_total_medicamento(${contador},1)" name="total_${contador}_1" id="total_${contador}_1" required type="text" class="form-control total_${contador}" readonly></td>
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
                    <input onblur="calcula_total_procedimento(${contador})" class="form-control total_procedimento" type="text" id="total_procedimento_${contador}" name="total_procedimento_${contador}" required readonly/>
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
                    <div>
                        <button type="button" onclick="gerador_adicionar_combo()" class="btn btn-sm rounded-pill btn-outline-info waves-effect">
                            <span class="tf-icons mdi mdi-plus me-1"></span>
                            Combos
                        </button>
                        <button type="button" onclick="gerador_adicionar_medicamento()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                            <span class="tf-icons mdi mdi-plus me-1"></span>
                            Medicamento
                        </button>
                    </div>
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
                                        <option value="">Opções</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="gerador_is_soro_1" id="gerador_is_soro_1" value="0">
                                </td>
                                <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_quantidade_1" id="gerador_quantidade_1" required type="text" class="form-control"></td>
                                <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_valor_1" id="gerador_valor_1" required type="text" class="form-control"></td>
                                <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_total_1" id="gerador_total_1" required type="text" class="form-control" readonly></td>
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
var modalCombo;

document.getElementById('botao_gerador').addEventListener('click', ()=>{
    document.getElementById('gerador_contador_medicamentos').value = 1;
    document.getElementById('gerador_dt_inicio').value = '';
    document.getElementById('gerador_nr_procedimentos').value = '';
    document.getElementById('gerador_intervalo').value = '';
    document.getElementById('gerador_tabela_medicamentos').innerHTML = `
    <tr id="gerador_linha_medicamento_1">
        <td>
            <select onchange="gerador_set_valor_medicamento(1)" name="gerador_medicamento_id_1" id="gerador_medicamento_id_1" class="form-control">
                <option value="">Opções</option>
                @foreach($medicamentos as $medicamento)
                    <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                @endforeach
            </select>
            <input type="hidden" name="gerador_is_soro_1" id="gerador_is_soro_1" value="0">
        </td>
        <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_quantidade_1" id="gerador_quantidade_1" required type="text" class="form-control"></td>
        <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_valor_1" id="gerador_valor_1" required type="text" class="form-control"></td>
        <td><input onblur="gerador_calcula_total_medicamento(1)" name="gerador_total_1" id="gerador_total_1" required type="text" class="form-control" readonly></td>
        <td></td>
    </tr>
    `;
    modalGerador = new bootstrap.Modal(document.getElementById('modal_gerador'));
    modalGerador.show();
})

function gerador_set_valor_medicamento(linha){
    select = document.getElementById("gerador_medicamento_id_" + linha);
    selectedOption = select.options[select.selectedIndex];
    valor = parseFloat(selectedOption.dataset.valor);
    valor = valor.toFixed(2);
    document.getElementById("gerador_valor_" + linha).value = valor.replace('.',',');

    document.getElementById("gerador_valor_" + linha).removeAttribute('readonly');
    document.getElementById("gerador_valor_" + linha).setAttribute('onkeypress',"return(MascaraMoeda(this,'.',',',event))");

    gerador_calcula_total_medicamento(linha);
}

function gerador_calcula_total_medicamento(linha){
    medicamento_id = document.getElementById('gerador_medicamento_id_' + linha).value;
    $.getJSON(
        '{{ route("adm.medicamentos.buscar") }}',
        {
            medicamento_id : medicamento_id
        },
        function(json){
            let valorInput = document.getElementById("gerador_valor_" + linha);
            let valorDigitado = valorInput.value;
            if(valorDigitado){
                valorDigitado = valorDigitado.replace(/\./g,'').replace(',','.');
                valorDigitado = parseFloat(valorDigitado);
                let valorTabela = parseFloat(json.vl_venda);
                if(valorDigitado < valorTabela){
                    alert('O valor do medicamento não pode ser menor do que o preço de tabela (R$ ' + json.vl_venda.replace('.', ',') + ').');
                    valorInput.value = json.vl_venda.replace('.', ',');
                }
            }

            if(json.unidade == 'Ampola'){
                quantidade = Math.ceil(parseFloat(document.getElementById("gerador_quantidade_" + linha).value));
            }
            else{
                quantidade = parseFloat(document.getElementById("gerador_quantidade_" + linha).value);
            }

            valor = document.getElementById("gerador_valor_" + linha).value;
            if(quantidade && valor){
                valor = valor.replace(/\./g,'');
                valor = parseFloat(valor.replace(',','.'));
                total = quantidade * valor;
                total = total.toFixed(2);
                document.getElementById('gerador_total_' + linha).value = total.replace('.',',');
            }
        }
    );
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
            <option value="">Opções</option>
            @foreach($medicamentos as $medicamento)
                <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
            @endforeach
        </select>
        <input type="hidden" name="gerador_is_soro_${contador}" id="gerador_is_soro_${contador}" value="0">
    </td>
    <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_quantidade_${contador}" id="gerador_quantidade_${contador}" required type="text" class="form-control"></td>
    <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_valor_${contador}" id="gerador_valor_${contador}" required type="text" class="form-control"></td>
    <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_total_${contador}" id="gerador_total_${contador}" required type="text" class="form-control" readonly></td>
    <td></td>
    `;

    tr.innerHTML = html;
    document.getElementById('gerador_tabela_medicamentos').appendChild(tr);
}

function gerador_adicionar_medicamentos_combo(medicamento){
    if(document.getElementById('gerador_medicamento_id_1').value == ""){
        contador = 1;
    }
    else{
        contador = parseInt(document.getElementById('gerador_contador_medicamentos').value);
        contador++;
        document.getElementById('gerador_contador_medicamentos').value = contador;

        tr = document.createElement('tr');
        tr.setAttribute('id', 'gerador_linha_medicamento_' + contador);

        html = `
        <td>
            <select onchange="gerador_set_valor_medicamento(${contador})" required name="gerador_medicamento_id_${contador}" id="gerador_medicamento_id_${contador}" class="form-control">
                <option value="">Opções</option>
                @foreach($medicamentos as $medicamento)
                    <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                @endforeach
            </select>
            <input type="hidden" name="gerador_is_soro_${contador}" id="gerador_is_soro_${contador}" value="0">
        </td>
        <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_quantidade_${contador}" id="gerador_quantidade_${contador}" required type="text" class="form-control"></td>
        <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_valor_${contador}" id="gerador_valor_${contador}" required type="text" class="form-control"></td>
        <td><input onblur="gerador_calcula_total_medicamento(${contador})" name="gerador_total_${contador}" id="gerador_total_${contador}" required type="text" class="form-control" readonly></td>
        <td></td>
        `;

        tr.innerHTML = html;
        document.getElementById('gerador_tabela_medicamentos').appendChild(tr);
    }

    document.getElementById('gerador_medicamento_id_' + contador).value = medicamento['medicamento_id'];
    document.getElementById('gerador_quantidade_' + contador).value = medicamento['quantidade'];
    document.getElementById('gerador_valor_' + contador).value = medicamento['valor'];
    document.getElementById('gerador_total_' + contador).value = medicamento['total'];
    document.getElementById('gerador_is_soro_' + contador).value = is_soro_global ? 1 : 0;

}

var is_soro_global = false;

function gera_procedimentos_gerador(){
    dt_inicio = document.getElementById('gerador_dt_inicio').value;
    nr_procedimentos = parseInt(document.getElementById('gerador_nr_procedimentos').value);
    intervalo = parseInt(document.getElementById('gerador_intervalo').value);
    contador_medicamentos = parseInt(document.getElementById('gerador_contador_medicamentos').value);
    contador_procedimentos = parseInt(document.getElementById('contador_procedimentos').value);
    data = new Date(dt_inicio);

    if(dt_inicio && nr_procedimentos && intervalo){
        for(i=1 ; i<=nr_procedimentos ; i++){
            //vamos analizar se já existe nos procedimentos a data proposta
            controle_data_existe = false;
            for(c=1 ; c<=contador_procedimentos ; c++){
                input_controle = document.querySelector('#data_aplicacao_' + c);
                if(input_controle){
                    //vamos testar as datas
                    if(input_controle.value != ''){
                        data_compara = new Date(input_controle.value);
                        if(data.getTime() == data_compara.getTime()){
                            controle_data_existe = c;
                        }
                    }
                }
            }

            if(controle_data_existe){
                f = controle_data_existe;
            }
            else{
                if(contador_procedimentos == 1){
                    f = i;
                }
                else{
                    f = contador_procedimentos + i;
                }

                if(i != 1 || contador_procedimentos != 1){
                    //vamos jogar na pagina o procedimento i
                    adicionar_procedimento();
                }

                //vamos setar a data
                document.getElementById('data_aplicacao_' + f).value = data.toISOString().slice(0, 10);
            }

            //vamos adicionar os medicamentos
            for(j=1 ; j<=contador_medicamentos ; j++){
                if(controle_data_existe){
                    adicionar_medicamento(f);
                    m = document.getElementById('contador_medicamentos_' + f).value;
                }
                else{
                    if(j != 1){
                        adicionar_medicamento(f);
                    }
                }

                m = document.getElementById('contador_medicamentos_' + f).value;

                medicamento_id = document.getElementById('gerador_medicamento_id_' + j).value;
                quantidade = document.getElementById('gerador_quantidade_' + j).value;
                valor = document.getElementById('gerador_valor_' + j).value;
                total = document.getElementById('gerador_total_' + j).value;
                is_soro = document.getElementById('gerador_is_soro_' + j).value;

                document.getElementById('medicamento_id_' + f + "_" + m).value = medicamento_id;
                document.getElementById('quantidade_' + f + "_" + m).value = quantidade;
                document.getElementById('valor_' + f + "_" + m).value = valor;
                document.getElementById('total_' + f + "_" + m).value = total;
                document.getElementById('is_soro_' + f + "_" + m).value = is_soro;

                calcula_total_medicamento(f,m);
            }
            data.setDate(data.getDate() + intervalo);
        }
        modalGerador.hide();
    }
    else{
        alert('é necessário preencher todos os campos.');
    }
}

</script>

<div class="modal fade" id="modal_combos" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="post">
            <input type="hidden" id="gerador_contador_medicamentos_combo" value='1'>
            <input type="hidden" id="combo_target" value='gerador'>
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Combos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mt-2 gy-4">
                    <div class="col-md-12">
                        <div class="form-floating form-floating-outline">
                            <select required id="combo_id" class="select2 form-select">
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
        </form>
    </div>
</div>


<script>
function gerador_adicionar_combo(){
    document.getElementById('combo_target').value = 'gerador';
    modalCombo = new bootstrap.Modal(document.getElementById('modal_combos'));
    modalGerador.hide();
    modalCombo.show();
}

var is_soro_global = false;
function abrir_modal_combo(linha){
    document.getElementById('combo_target').value = linha;
    modalCombo = new bootstrap.Modal(document.getElementById('modal_combos'));
    modalCombo.show();
}

document.getElementById('adicionar_gerador_combo').addEventListener('click', ()=>{
    let target = document.getElementById('combo_target').value;
    if(document.getElementById('combo_id').value != ""){
        $.getJSON(
            "{{ route('adm.combos.buscar_medicamentos') }}",
            { combo_id : document.getElementById('combo_id').value },
            function(json){
                let combo_select = document.getElementById('combo_id');
                let combo_nome = combo_select.options[combo_select.selectedIndex].text;
                is_soro_global = combo_nome.toLowerCase().startsWith('soro');

                for(i=0 ; i<json.medicamentos.length ; i++){
                    medicamento = json.medicamentos[i];
                    if(target == 'gerador'){
                        gerador_adicionar_medicamentos_combo(medicamento);
                    } else {
                        adicionar_medicamentos_combo_semana(target, medicamento);
                    }
                }

                modalCombo.hide();
                if(target == 'gerador'){
                    modalGerador.show();
                }
            }
        );
    }
    else{
        alert('É necessário escolher o combo.');
    }
})

function adicionar_medicamentos_combo_semana(linha, medicamento_obj){
    if(document.getElementById('medicamento_id_' + linha + '_1').value == ""){
        contador = 1;
        document.getElementById('is_soro_' + linha + '_1').value = is_soro_global ? '1' : '0';
    }
    else{
        contador = parseInt(document.getElementById('contador_medicamentos_' + linha).value);
        contador++;
        document.getElementById('contador_medicamentos_' + linha).value = contador;

        tr = document.createElement('tr');
        tr.setAttribute('id', 'linha_medicamento_' + linha + '_' + contador);
        variavel = linha + '_' + contador;

        html = `
        <td>
            <input type="hidden" name="is_soro_${variavel}" id="is_soro_${variavel}" value="${is_soro_global ? '1' : '0'}">
            <select onchange="set_valor_medicamento(${linha},${contador})" required name="medicamento_id_${variavel}" id="medicamento_id_${variavel}" class="form-control">
                <option value="">Opções</option>
                @foreach($medicamentos as $medicamento)
                    <option data-valor='{{ $medicamento->vl_venda }}' value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                @endforeach
            </select>
        </td>
        <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="quantidade_${variavel}" id="quantidade_${variavel}" required type="text" class="form-control"></td>
        <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="valor_${variavel}" id="valor_${variavel}" required type="text" class="form-control" readonly></td>
        <td><input onblur="calcula_total_medicamento(${linha},${contador})" name="total_${variavel}" id="total_${variavel}" required type="text" class="form-control total_${linha}" readonly></td>
        <td>
            <button type="button" title='Excluir linha' onclick='excluir_linha_medicamento(${linha},${contador})' class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
                <span class="tf-icons mdi mdi-delete mdi-24px"></span>
            </button>
        </td>
        `;

        tr.innerHTML = html;
        document.getElementById('tabela_medicamentos_' + linha).appendChild(tr);
    }

    document.getElementById('medicamento_id_' + linha + '_' + contador).value = medicamento_obj.medicamento_id;
    document.getElementById('quantidade_' + linha + '_' + contador).value = medicamento_obj.quantidade;
    
    document.getElementById('valor_' + linha + '_' + contador).value = medicamento_obj.valor;
    document.getElementById('total_' + linha + '_' + contador).value = medicamento_obj.total;

    let select = document.getElementById("medicamento_id_" + linha + '_' + contador);
    let selectedOption = select.options[select.selectedIndex];
    let nomeMedicamento = selectedOption.textContent.trim().toLowerCase();

    $.getJSON(
        '{{ route("adm.medicamentos.buscar") }}',
        {medicamento_id:select.value},
        function(json){
            if(json.unidade == "Procedimento" || nomeMedicamento.startsWith('pellet')){
                document.getElementById("valor_" + linha + '_' + contador).removeAttribute('readonly');
                document.getElementById("valor_" + linha + '_' + contador).setAttribute('onkeypress',"return(MascaraMoeda(this,'.',',',event))");
            }
            else{
                document.getElementById("valor_" + linha + '_' + contador).setAttribute('readonly','readonly');
                document.getElementById("valor_" + linha + '_' + contador).removeAttribute('onkeypress');
            }
        }
    );

    calcula_total_procedimento(linha);
}

function valida_e_confirma_procedimento() {
    let erros = [];
    
    // 1. Basic fields validation
    const pacienteSelect = document.getElementById('paciente_id');
    if (pacienteSelect && pacienteSelect.value === '') {
        erros.push('Por favor, escolha o Paciente.');
    }
    
    const medicoSelect = document.getElementById('medico');
    if (medicoSelect && medicoSelect.value === '') {
        erros.push('Por favor, escolha o Médico.');
    }
    
    const agendamentoInput = document.getElementById('agendamento');
    if (agendamentoInput && agendamentoInput.value.trim() === '') {
        erros.push('Por favor, preencha a data ou descrição do Agendamento.');
    }
    
    const tipoAtendimentoSelect = document.getElementById('tipo_atendimento');
    if (tipoAtendimentoSelect && tipoAtendimentoSelect.value === '') {
        erros.push('Por favor, selecione o Tipo de Atendimento.');
    }
    
    // 2. Loop through weeks
    const numProcedimentos = parseInt(document.getElementById('contador_procedimentos').value);
    let temMedicamentoControlado = false;
    let totalGeral = 0;
    let resumoSemanasHtml = '';
    let semanasValidas = 0;
    
    for (let i = 1; i <= numProcedimentos; i++) {
        const card = document.getElementById('card_' + i);
        if (!card) continue;
        
        semanasValidas++;
        const dataInput = document.getElementById('data_aplicacao_' + i);
        if (!dataInput || dataInput.value === '') {
            erros.push(`Semana ${i}: Por favor, informe a Data Prevista.`);
        }
        
        const semAplicacaoCheckbox = document.querySelector(`input[name="semana_sem_aplicacao_${i}"]:checked`);
        const semAplicacao = semAplicacaoCheckbox !== null;
        
        if (semAplicacao) {
            resumoSemanasHtml += `
            <tr>
                <td><strong>Semana ${i}</strong></td>
                <td>${dataInput ? formata_data_pt(dataInput.value) : ''}</td>
                <td class="text-muted"><span class="mdi mdi-minus-circle-outline me-1"></span>Semana sem Aplicação</td>
                <td class="text-end font-monospace">R$ 0,00</td>
            </tr>
            `;
            continue;
        }
        
        const tabelaMeds = document.getElementById('tabela_medicamentos_' + i);
        const rows = tabelaMeds ? tabelaMeds.querySelectorAll('tr[id^="linha_medicamento_"]') : [];
        
        if (rows.length === 0) {
            erros.push(`Semana ${i}: Adicione pelo menos um medicamento ou marque a opção "Semana sem Aplicação".`);
            continue;
        }
        
        let medListText = '';
        let totalSemana = 0;
        
        for (let r = 0; r < rows.length; r++) {
            const row = rows[r];
            const rowIdParts = row.id.split('_');
            const j = rowIdParts[rowIdParts.length - 1]; // get medication index
            
            const medSelect = document.getElementById(`medicamento_id_${i}_${j}`);
            if (!medSelect || medSelect.value === '') {
                erros.push(`Semana ${i}, Linha ${r + 1}: Escolha um medicamento.`);
                continue;
            }
            
            const medNome = medSelect.options[medSelect.selectedIndex].text;
            const medId = medSelect.value;
            const medInfo = medicamentosMap[medId];
            
            if (medInfo && (medInfo.unidade === 'Ampola' || medInfo.unidade === 'Miligrama')) {
                temMedicamentoControlado = true;
            }
            
            const qtdInput = document.getElementById(`quantidade_${i}_${j}`);
            const qtdVal = qtdInput ? qtdInput.value.trim() : '';
            const qtd = parseFloat(qtdVal.replace(',', '.'));
            if (qtdVal === '' || isNaN(qtd) || qtd <= 0) {
                erros.push(`Semana ${i}, Medicamento "${medNome}": Informe uma quantidade válida (maior que 0).`);
            }
            
            const valorInput = document.getElementById(`valor_${i}_${j}`);
            const valorVal = valorInput ? valorInput.value.trim() : '';
            const valor = parseFloat(valorVal.replaceAll('.', '').replace(',', '.'));
            if (valorVal === '' || isNaN(valor) || valor <= 0) {
                erros.push(`Semana ${i}, Medicamento "${medNome}": Informe um valor unitário válido.`);
            }
            
            const totalInput = document.getElementById(`total_${i}_${j}`);
            const total = parseFloat(totalInput && totalInput.value ? totalInput.value.replaceAll('.', '').replace(',', '.') : 0);
            
            totalSemana += total;
            medListText += `<div class="mb-1"><span class="mdi mdi-pill me-1 text-secondary"></span>${medNome} (Qtd: ${qtdInput ? qtdInput.value : qtd}) - R$ ${valorInput ? valorInput.value : valor}</div>`;
        }
        
        totalGeral += totalSemana;
        resumoSemanasHtml += `
        <tr>
            <td><strong>Semana ${i}</strong></td>
            <td>${dataInput ? formata_data_pt(dataInput.value) : ''}</td>
            <td>${medListText}</td>
            <td class="text-end font-monospace fw-semibold">R$ ${totalSemana.toFixed(2).replace('.', ',')}</td>
        </tr>
        `;
    }
    
    if (semanasValidas === 0) {
        erros.push('Por favor, adicione pelo menos uma semana/procedimento.');
    }
    
    // 3. File attachments check (required if temMedicamentoControlado is true)
    const anexosInput = document.getElementById('anexos');
    if (temMedicamentoControlado && (!anexosInput || anexosInput.files.length === 0)) {
        erros.push('É obrigatório inserir o anexo (prescrição médica) pois o procedimento contém medicamentos em Ampola ou Miligrama.');
    }
    
    // 4. Action
    if (erros.length > 0) {
        let listaHtml = '';
        erros.forEach(err => {
            listaHtml += `<li class="mb-2"><span class="mdi mdi-circle-small me-1 text-danger"></span>${err}</li>`;
        });
        document.getElementById('lista_erros_validacao').innerHTML = listaHtml;
        const modalErros = new bootstrap.Modal(document.getElementById('modal_erros_validacao'));
        modalErros.show();
    } else {
        document.getElementById('tabela_confirmacao_resumo').innerHTML = resumoSemanasHtml;
        document.getElementById('confirmacao_total_geral').innerText = 'R$ ' + totalGeral.toFixed(2).replace('.', ',');
        const modalConfirmacao = new bootstrap.Modal(document.getElementById('modal_confirmacao_procedimento'));
        modalConfirmacao.show();
        
        document.getElementById('confirmar_e_salvar_botao').onclick = function() {
            isConfirmed = true;
            modalConfirmacao.hide();
            document.getElementById('form_cadastro_procedimento').submit();
        };
    }
}

function formata_data_pt(dataStr) {
    if (!dataStr) return '';
    const parts = dataStr.split('-');
    return parts[2] + '/' + parts[1] + '/' + parts[0];
}

</script>

<!-- Modal de Erros de Validação -->
<div class="modal fade" id="modal_erros_validacao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger d-flex align-items-center">
                    <span class="mdi mdi-alert-circle-outline mdi-24px me-2"></span>Inconsistências no Cadastro
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

<!-- Modal de Confirmação -->
<div class="modal fade" id="modal_confirmacao_procedimento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary d-flex align-items-center">
                    <span class="mdi mdi-checkbox-marked-circle-outline mdi-24px me-2"></span>Confirmar Cadastro de Procedimentos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">Revise o resumo das semanas abaixo antes de confirmar o cadastro:</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Semana</th>
                                <th>Data Prevista</th>
                                <th>Medicamentos / Situação</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="tabela_confirmacao_resumo">
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end fw-bold">Total Geral:</th>
                                <th id="confirmacao_total_geral" class="text-end fw-bold font-monospace">R$ 0,00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Corrigir Dados</button>
                <button type="button" id="confirmar_e_salvar_botao" class="btn btn-primary">Confirmar e Salvar</button>
            </div>
        </div>
    </div>
</div>

@endsection
