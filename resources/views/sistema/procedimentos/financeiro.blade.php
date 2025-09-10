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
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input required onblur="calcula_total()" class="form-control" type="text" id="vl_consulta" name="vl_consulta" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/>
                                <label for="vl_consulta">Valor da Consulta:</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input onblur="calcula_desconto()" class="form-control" type="number" id="porcentagem_desconto" value="0"/>
                                <label for="porcentagem_desconto">Desconto (%):</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input required onblur="calcula_total()" class="form-control" type="text" id="vl_desconto" name="vl_desconto" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/>
                                <label for="vl_desconto">Valor do Desconto:</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <input required onblur="calcula_total()" class="form-control" type="text" id="vl_pagamento" name="vl_pagamento" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="0,00"/>
                                <label for="vl_pagamento">Valor do Pagamento:</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-floating form-floating-outline">
                                <input class="form-control" type="text" id="obs_pagamento" name="obs_pagamento"/>
                                <label for="obs_pagamento">Obs Pagamento:</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title">Forma de Pagamento</h5>
                        <button type="button" onclick="adicionar_forma()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
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
                            <button type="button" id='botao_salvar' class="btn btn-primary me-2">Salvar</button>
                            {{--
                            @if($retorno == "sistema_dashboard")
                                <a href="{{ route('sistema.dashboard') }}" class="btn btn-danger">Não Registrar Pagamento</a>
                            @elseif($retorno == "adm_dashboard")
                                <a href="{{ route('adm.sistema.dashboard') }}" class="btn btn-danger">Não Registrar Pagamento</a>
                            @else
                                <a href="{{ route('sistema.procedimentos') }}" class="btn btn-danger">Não Registrar Pagamento</a>
                            @endif
                            --}}
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('botao_salvar').addEventListener('click', ()=>{
    //vamos somar todos os valores de pagamentos
    //let somatorio = 0;
    //let variavel = "input.valor";

    //inputs = document.querySelectorAll(variavel);
    //[].forEach.call(inputs, function(input) {
    //    valor = input.value;
    //    valor = valor.replaceAll('.','');
    //    valor = parseFloat(valor.replace(',','.'));
    //    somatorio = somatorio + valor;
    //});

    //total = document.getElementById('vl_pagamento').value;
    //total = total.replaceAll('.','');
    //total = parseFloat(total.replace(',','.'));

    //console.log(total, somatorio);

    //if(total == somatorio){
        document.getElementById('formulario').submit();
    //}
    //else{
    //    alert('Valor do pagamento e soma dos valores da forma de pagamento não confere.');
    //}
});

function adicionar_forma(){
    contador = parseInt(document.getElementById('contador_formas').value);
    contador++;
    document.getElementById('contador_formas').value = contador;
    tr = document.createElement('tr');
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
    if(confirm('Tem certeza que deseja excluir a linha de pagamentp?')){
        document.getElementById('linha_forma_' + linha).remove();
    }
}

function controle_parcelas(linha){
    if(document.getElementById('forma_pagamento_' + linha).value == "Crédito"){
        document.getElementById('parcelas_' + linha).removeAttribute('disabled');
        document.getElementById('parcelas_' + linha).setAttribute('required','required');
    }
    else{
        document.getElementById('parcelas_' + linha).setAttribute('disabled','disabled');
        document.getElementById('parcelas_' + linha).removeAttribute('required');
    }
}

function calcula_desconto(){
    desconto = parseInt(document.getElementById('porcentagem_desconto').value);
    if(desconto > 0){
        let somatorio = 0;
        let variavel = "input.somatorio";

        inputs = document.querySelectorAll(variavel);
        [].forEach.call(inputs, function(input) {
            if(input.checked){
                valor = input.dataset.valor;
                valor = parseFloat(valor);
                somatorio = somatorio + valor;
            }
        });

        vl_consulta = document.getElementById('vl_consulta').value;
        vl_consulta = vl_consulta.replaceAll('.','');
        vl_consulta = parseFloat(vl_consulta.replace(',','.'));
        somatorio += vl_consulta;

        vl_desconto = somatorio * desconto / 100;

        vl_desconto = vl_desconto.toFixed(2);
        vl_desconto = vl_desconto.replace('.',",");

        document.getElementById('vl_desconto').value = vl_desconto;

        calcula_total();
    }
}

function calcula_total(){
    let somatorio = 0;
    let variavel = "input.somatorio";

    inputs = document.querySelectorAll(variavel);
    [].forEach.call(inputs, function(input) {
        if(input.checked){
            valor = input.dataset.valor;
            valor = parseFloat(valor);
            somatorio = somatorio + valor;
        }
    });

    vl_consulta = document.getElementById('vl_consulta').value;
    vl_consulta = vl_consulta.replaceAll('.','');
    vl_consulta = parseFloat(vl_consulta.replace(',','.'));

    vl_desconto = document.getElementById('vl_desconto').value;
    vl_desconto = vl_desconto.replaceAll('.','');
    vl_desconto = parseFloat(vl_desconto.replace(',','.'));

    somatorio = somatorio + vl_consulta - vl_desconto;

    somatorio = somatorio.toFixed(2);
    somatorio = somatorio.replace('.',",");

    document.getElementById('vl_pagamento').value = somatorio
}

calcula_total();
</script>
@endsection
