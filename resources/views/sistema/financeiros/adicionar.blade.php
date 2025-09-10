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
            <h4 class="card-title">Adicionar Financeiro</h4>
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
        <form id='formulario' action="{{ route('sistema.financeiros.insert') }}" method="post">
            @csrf
            <input type="hidden" name="contador_formas" id="contador_formas" value="1">
            <div class="row mt-2 gy-4 align-items-end mb-3">
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline">
                        <select onchange="busca_procedimentos_abertos(this)" required id="paciente_id" name='paciente_id' class="select2 form-select">
                            <option value="">Opções</option>
                        </select>
                        <label for="paciente_id">Paciente:</label>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row mt-3">
                <div style="display:none;" class="col-md-3">
                    <h5 class="card-title">Procedimentos</h5>
                    <div class="form-floating form-floating-outline mb-4">
                        <select multiple="" class="form-select h-px-100" id="procedimentos" name="procedimentos[]">

                        </select>
                        <label for="procedimentos">Lista de procedimentos abertos</label>
                    </div>
                </div>
                <div class="col-md-9">
                    <h5 class="card-title">Forma de Pagamento</h5>
                    <div class="row mt-2 gy-4 align-items-end">
                        <div class="col-md-6">
                            <div class="form-floating form-floating-outline">
                                <select id="medico" name='medico' class="select2 form-select">
                                    <option value="">Opções</option>
                                    @foreach($medicos as $medico)
                                        <option value="{{ $medico['profissional_nome'] }}">{{ $medico['profissional_nome'] }}</option>
                                    @endforeach
                                </select>
                                <label for="medico">Médico:</label>
                            </div>
                        </div>
                    </div>
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
                    </div>
                    <div class="row mt-3">
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
                    <div class="row mt-2">
                        <div class="col-md-6 form-group">
                            <button type="button" class="btn btn-primary me-2" id="botao_salvar">Salvar</button>
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
    //vamos verificar o paciente
    if(document.getElementById('paciente_id').value == ""){
        alert('É necessário preencher o paciente');
        return;
    }

    let somatorio = 0;
    let variavel = "input.valor";

    inputs = document.querySelectorAll(variavel);
    [].forEach.call(inputs, function(input) {
        valor = input.value;
        valor = valor.replaceAll('.','');
        valor = parseFloat(valor.replace(',','.'));
        somatorio = somatorio + valor;
    });

    total = document.getElementById('vl_pagamento').value;
    total = total.replaceAll('.','');
    total = parseFloat(total.replace(',','.'));

    console.log(total, somatorio);

    if(total == somatorio){
        document.getElementById('formulario').submit();
    }
    else{
        alert('Valor do pagamento e soma dos valores da forma de pagamento não confere.');
    }
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

window.addEventListener('load',()=>{
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

function calcula_desconto(){
    desconto = parseInt(document.getElementById('porcentagem_desconto').value);
    if(desconto > 0){
        let somatorio = 0;
        select = document.getElementById('procedimentos');

        for (let i = 0; i < select.options.length; i++) {
            option = select.options[i];
            if (option.selected){
                dataValor = parseFloat(option.getAttribute('data-valor'));
                somatorio = somatorio + dataValor;
            }
        }

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

function busca_procedimentos_abertos(e){
    if(e.value){
        $.getJSON(
            '{{ route("sistema.financeiros.get_procedimentos_abertos") }}',
            {
                paciente_id : e.value
            },
            function(json){
                document.getElementById('procedimentos').innerHTML = json.html;
            }
        );
    }
}

document.getElementById('procedimentos').addEventListener('change', ()=>{
    calcula_total();
})

function calcula_total(){
    let somatorio = 0;
    select = document.getElementById('procedimentos');

    for (let i = 0; i < select.options.length; i++) {
        option = select.options[i];
        if (option.selected){
            dataValor = parseFloat(option.getAttribute('data-valor'));
            somatorio = somatorio + dataValor;
        }
    }

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

</script>
@endsection
