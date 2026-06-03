@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Financeiro</h4>
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
        <form id='formulario' action="{{ route('sistema.procedimentos.financeiros') }}" method="post">
            @csrf
            <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">
            <input type="hidden" name="retorno" value="{{ $retorno }}">
            <input type="hidden" name="medico" value="{{ $medico }}">
            <input type="hidden" name="contador_formas" id="contador_formas" value="1">
            <div class="row mt-3">
                <div class="col-md-6 form-group">
                    <label for="">Paciente:</label><br>
                    <b>{{ $paciente->nm_paciente }}</b>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <h5 class="card-title">Procedimentos</h5>
                    @foreach($array_procedimentos as $procedimento)
                        <div class="form-check mt-3">
                            <input style='display:none' checked onclick="calcula_total()" class="form-check-input somatorio" data-valor='{{ $procedimento->valor }}' type="checkbox" value="{{ $procedimento->id }}" id="procedimentos_{{ $procedimento->id }}" name="procedimentos[]">
                            <label class="form-check-label" for=""> {{ dataDbForm($procedimento->data_aplicacao)." - R$ ".valorDbForm($procedimento->valor) }} </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-9">
                    <h5 class="card-title">Pagamento</h5>
                    <div class="row gy-4 mt-3">
                        <div class="col-md-6">
                            <div class="form-floating form-floating-outline">
                                <select id="pagamento_modelo" name="pagamento_modelo" class="select2 form-select">
                                    <option value="" selected disabled>Selecione 1 opção</option>
                                    <option value="Pagamento Parcial">Pagamento Parcial</option>
                                    <option value="Pagamento Total">Pagamento Total</option>
                                    <option value="Pagamento Avista">Pagamento Avista</option>
                                </select>
                                <label for="pagamento_modelo">Pagamento:</label>
                            </div>
                        </div>
                    </div>
                    <div id="detalhes_pagamento" style="display: none;">
                    <div class="row gy-4 mt-3">
                        <div class="col-md-4" style="display:none">
                            <div class="form-floating form-floating-outline">
                                <input required onblur="calcula_total()" class="form-control" type="text" id="vl_consulta" name="vl_consulta" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/>
                                <label for="vl_consulta">Valor da Consulta:</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating form-floating-outline">
                                <input onblur="calcula_desconto()" class="form-control" type="number" id="porcentagem_desconto" value="0"/>
                                <label for="porcentagem_desconto">Desconto (%):</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating form-floating-outline">
                                <input required onblur="calcula_total()" class="form-control" type="text" id="vl_desconto" name="vl_desconto" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/>
                                <label for="vl_desconto">Valor do Desconto:</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating form-floating-outline">
                                <input required onblur="calcula_total()" class="form-control" type="text" id="vl_adicional" name="vl_adicional" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/>
                                <label for="vl_adicional">Valor Adicional:</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-floating form-floating-outline">
                                <input required onblur="calcula_total()" class="form-control" type="text" id="vl_pagamento" name="vl_pagamento" readonly value="0,00"/>
                                <label for="vl_pagamento">Valor Total:</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-floating form-floating-outline">
                                <input class="form-control" type="text" id="obs_pagamento" name="obs_pagamento"/>
                                <label for="obs_pagamento">Obs Pagamento:</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">Forma de Pagamento</h5>
                        <button type="button" id='botao_add_forma_pagamento' onclick="adicionar_forma()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                            <span class="tf-icons mdi mdi-plus me-1"></span>
                            Forma Pgt
                        </button>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Numero</th>
                                    <th>Forma Pagamento</th>
                                    <th>Parcelas</th>
                                    <th>ID Pagamento / DOC</th>
                                    <th>Valor</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="tabela_formas">
                                <tr id="linha_forma_1">
                                    <td>1</td>
                                    <td>
                                        <select required id="forma_pagamento_1" onchange="controle_parcelas(1)" name='forma_pagamento_1' class="form-control">
                                            <option value="">Opções</option>
                                            <option value="Dinheiro">Dinheiro</option>
                                            <option value="Débito">Débito</option>
                                            <option value="Crédito">Crédito</option>
                                            <option value="Pix">Pix</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select disabled id="parcelas_1" name='parcelas_1' class="form-control">
                                            <option value="">Opções</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                            <option value="9">9</option>
                                            <option value="10">10</option>
                                        </select>
                                    </td>
                                    <td><input class="form-control" type="text" id="id_pagamento_1" name="id_pagamento_1"/></td>
                                    <td><input required class="form-control valor" type="text" id="vl_pagamento_1" name="vl_pagamento_1" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/></td>
                                    <td>
                                        <button title="Excluir Forma de Pagamento" onclick="excluir_forma(1)" type="button" class="btn btn-icon btn-outline-danger waves-effect">
                                            <span class="tf-icons mdi mdi-delete"></span>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                        <div class="row mt-3">
                            <div class="col-md-12 form-group">
                                <input type="hidden" name="enviar_fila" id="enviar_fila" value="0">
                                <button type="button" id='botao_salvar' class="btn btn-primary me-2">Salvar</button>
                                <button type="button" id='botao_salvar_enviar' class="btn btn-success me-2">Salvar e Enviar para Fila de Atendimento</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
function submit_formulario(enviar_fila = 0) {
    let modelo = document.getElementById('pagamento_modelo').value;

    if(modelo == ""){
        alert('Por favor, selecione uma opção de pagamento.');
        return;
    }

    // Validação de formas de pagamento
    let formas_preenchidas = 0;
    let selects_formas = document.querySelectorAll("select[name^='forma_pagamento_']");
    let has_empty_forma = false;
    
    selects_formas.forEach(select => {
        if(select.value == "") {
            has_empty_forma = true;
        } else {
            formas_preenchidas++;
        }
    });

    if(formas_preenchidas == 0) {
        alert('Selecione pelo menos uma forma de pagamento.');
        return;
    }

    if(has_empty_forma) {
        alert('Existem formas de pagamento não selecionadas. Por favor, corrija ou remova a linha.');
        return;
    }

    // Verificar parcelas se for Crédito
    let has_empty_parcelas = false;
    let inputs_parcelas = document.querySelectorAll("select[name^='parcelas_']");
    inputs_parcelas.forEach(select => {
        let id_num = select.id.split('_')[1];
        let forma = document.getElementById('forma_pagamento_' + id_num).value;
        if(forma == 'Crédito' && select.value == "") {
            has_empty_parcelas = true;
        }
    });

    if(has_empty_parcelas) {
        alert('Por favor, informe a quantidade de parcelas para pagamentos no Crédito.');
        return;
    }

    // Validação de observação se houver desconto
    let vl_desconto = document.getElementById('vl_desconto').value;
    vl_desconto = vl_desconto.replaceAll('.','');
    vl_desconto = parseFloat(vl_desconto.replace(',','.'));

    if(vl_desconto > 0){
        let obs = document.getElementById('obs_pagamento').value;
        if(obs == "" || obs == null){
            alert('É necessário preencher a observação do pagamento quando há desconto.');
            return ;
        }
    }

    // Somatório dos pagamentos
    let somatorio_pagos = 0;
    let inputs_pagos = document.querySelectorAll("input.valor");
    let has_zero_payment = false;

    inputs_pagos.forEach(input => {
        let valor = input.value;
        valor = valor.replaceAll('.','');
        valor = parseFloat(valor.replace(',','.'));
        if(valor <= 0) has_zero_payment = true;
        somatorio_pagos += valor;
    });

    if(has_zero_payment) {
        alert('O valor de cada forma de pagamento deve ser maior que zero.');
        return;
    }

    let total_final = document.getElementById('vl_pagamento').value;
    total_final = total_final.replaceAll('.','');
    total_final = parseFloat(total_final.replace(',','.'));

    // Arredondar para evitar problemas de precisão de ponto flutuante
    somatorio_pagos = Math.round(somatorio_pagos * 100) / 100;
    total_final = Math.round(total_final * 100) / 100;

    if(modelo == 'Pagamento Avista' || modelo == 'Pagamento Total'){
        if(Math.abs(total_final - somatorio_pagos) > 0.01){
            alert('A soma das formas de pagamento deve ser igual ao valor total: R$ ' + document.getElementById('vl_pagamento').value);
            return;
        }
    }
    else if(modelo == 'Pagamento Parcial'){
        if(somatorio_pagos > total_final){
            alert('No Pagamento Parcial, a soma das formas de pagamento não pode ser superior ao valor total.');
            return;
        }
        if(somatorio_pagos <= 0) {
            alert('A soma das formas de pagamento deve ser maior que zero.');
            return;
        }
    }

    document.getElementById('enviar_fila').value = enviar_fila;
    document.getElementById('formulario').submit();
}

document.getElementById('botao_salvar').addEventListener('click', ()=>{
    submit_formulario(0);
});

document.getElementById('botao_salvar_enviar').addEventListener('click', ()=>{
    submit_formulario(1);
});

document.getElementById('pagamento_modelo').addEventListener('change', (e)=>{
    let secao = document.getElementById('detalhes_pagamento');
    secao.style.display = 'block';

    let pc_desconto = document.getElementById('porcentagem_desconto');
    let obs = document.getElementById('obs_pagamento');
    let btn_add = document.getElementById('botao_add_forma_pagamento');
    let vl_pago_1 = document.getElementById('vl_pagamento_1');
    let parcelas_1 = document.getElementById('parcelas_1');
    let vl_adicional = document.getElementById('vl_adicional');
    let vl_desconto_input = document.getElementById('vl_desconto');

    if(e.target.value == 'Pagamento Avista'){
        pc_desconto.value = 3;
        pc_desconto.setAttribute('readonly', 'readonly');
        
        obs.value = 'Pagamento Avista';
        obs.setAttribute('readonly', 'readonly');
        
        btn_add.setAttribute('disabled','disabled');
        vl_adicional.value = '0,00';
        
        // Remove extra forms if any
        let rows = document.querySelectorAll('#tabela_formas tr');
        rows.forEach((row, index) => {
            if (index > 0) row.remove();
        });
        document.getElementById('contador_formas').value = 1;

        vl_pago_1.setAttribute('readonly','readonly');
        
        parcelas_1.value = 1;
        parcelas_1.setAttribute('disabled', 'disabled');

        calcula_desconto();
        vl_pago_1.value = document.getElementById('vl_pagamento').value;
    }
    else if(e.target.value == 'Pagamento Total'){
        pc_desconto.value = 0;
        pc_desconto.removeAttribute('readonly');
        
        obs.value = '';
        obs.removeAttribute('readonly');
        
        btn_add.removeAttribute('disabled');
        vl_pago_1.removeAttribute('readonly');
        vl_adicional.removeAttribute('readonly');
        
        // Reset discount value if switching from Avista
        vl_desconto_input.value = '0,00';
        calcula_total();
    }
    else if(e.target.value == 'Pagamento Parcial'){
        pc_desconto.value = 0;
        pc_desconto.removeAttribute('readonly');
        
        obs.value = '';
        obs.removeAttribute('readonly');
        
        btn_add.removeAttribute('disabled');
        vl_pago_1.removeAttribute('readonly');
        vl_adicional.removeAttribute('readonly');

        // Reset discount value if switching from Avista
        vl_desconto_input.value = '0,00';
        calcula_total();
    }
})

function adicionar_forma(){
    let contador = parseInt(document.getElementById('contador_formas').value);
    contador++;
    document.getElementById('contador_formas').value = contador;
    let tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_forma_' + contador);
    tr.innerHTML = `
        <td>${contador}</td>
        <td>
            <select required id="forma_pagamento_${contador}" onchange="controle_parcelas(${contador})" name='forma_pagamento_${contador}' class="form-control">
                <option value="">Opções</option>
                <option value="Dinheiro">Dinheiro</option>
                <option value="Débito">Débito</option>
                <option value="Crédito">Crédito</option>
                <option value="Pix">Pix</option>
            </select>
        </td>
        <td>
            <select disabled id="parcelas_${contador}" name='parcelas_${contador}' class="form-control">
                <option value="">Opções</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
            </select>
        </td>
        <td><input class="form-control" type="text" id="id_pagamento_${contador}" name="id_pagamento_${contador}"/></td>
        <td><input required class="form-control valor" type="text" id="vl_pagamento_${contador}" name="vl_pagamento_${contador}" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/></td>
        <td>
            <button title="Excluir Forma de Pagamento" onclick="excluir_forma(${contador})" type="button" class="btn btn-icon btn-outline-danger waves-effect">
                <span class="tf-icons mdi mdi-delete"></span>
            </button>
        </td>
    `;

    document.getElementById('tabela_formas').appendChild(tr);

}

function excluir_forma(linha){
    if(confirm('Tem certeza que deseja excluir a linha de pagamento?')){
        document.getElementById('linha_forma_' + linha).remove();
    }
}

function controle_parcelas(linha){
    let modelo = document.getElementById('pagamento_modelo').value;
    let forma_pagamento = document.getElementById('forma_pagamento_' + linha).value;
    let select_parcelas = document.getElementById('parcelas_' + linha);

    if(modelo == 'Pagamento Avista'){
        select_parcelas.value = 1;
        select_parcelas.setAttribute('disabled','disabled');
        return;
    }

    if(forma_pagamento == "Crédito"){
        select_parcelas.removeAttribute('disabled');
        select_parcelas.setAttribute('required','required');
    }
    else{
        select_parcelas.value = 1;
        select_parcelas.setAttribute('disabled','disabled');
        select_parcelas.removeAttribute('required');
    }
}

function calcula_desconto(){
    let desconto = parseInt(document.getElementById('porcentagem_desconto').value);
    if(desconto > 0){
        let somatorio = 0;
        let variavel = "input.somatorio";

        let inputs = document.querySelectorAll(variavel);
        [].forEach.call(inputs, function(input) {
            if(input.checked){
                let valor = input.dataset.valor;
                valor = parseFloat(valor);
                somatorio = somatorio + valor;
            }
        });

        let vl_consulta = document.getElementById('vl_consulta').value;
        vl_consulta = vl_consulta.replaceAll('.','');
        vl_consulta = parseFloat(vl_consulta.replace(',','.'));
        somatorio += vl_consulta;

        let vl_desconto = somatorio * desconto / 100;

        vl_desconto = vl_desconto.toFixed(2);
        vl_desconto = vl_desconto.replace('.',",");

        document.getElementById('vl_desconto').value = vl_desconto;

        calcula_total();
    }
}

function calcula_total(){
    let somatorio = 0;
    let variavel = "input.somatorio";

    let inputs = document.querySelectorAll(variavel);
    [].forEach.call(inputs, function(input) {
        if(input.checked){
            let valor = input.dataset.valor;
            valor = parseFloat(valor);
            somatorio = somatorio + valor;
        }
    });

    let vl_consulta = document.getElementById('vl_consulta').value;
    vl_consulta = vl_consulta.replaceAll('.','');
    vl_consulta = parseFloat(vl_consulta.replace(',','.'));

    let vl_desconto = document.getElementById('vl_desconto').value;
    vl_desconto = vl_desconto.replaceAll('.','');
    vl_desconto = parseFloat(vl_desconto.replace(',','.'));

    let vl_adicional = document.getElementById('vl_adicional').value;
    vl_adicional = vl_adicional.replaceAll('.','');
    vl_adicional = parseFloat(vl_adicional.replace(',','.'));

    somatorio = somatorio + vl_consulta + vl_adicional - vl_desconto;

    somatorio = somatorio.toFixed(2);
    somatorio = somatorio.replace('.',",");

    document.getElementById('vl_pagamento').value = somatorio

    if(document.getElementById('pagamento_modelo').value == "Pagamento Avista"){
        document.getElementById('vl_pagamento_1').value = document.getElementById('vl_pagamento').value;
    }
}

calcula_total();
</script>
@endsection
