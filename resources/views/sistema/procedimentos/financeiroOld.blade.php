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
        <form action="{{ route('sistema.procedimentos.financeiros') }}" method="post">
            @csrf
            <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">
            <input type="hidden" name="retorno" value="{{ $retorno }}">
            <input type="hidden" name="medico" value="{{ $medico }}">
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
                            <input checked onclick="calcula_total()" class="form-check-input somatorio" data-valor='{{ $procedimento->valor }}' type="checkbox" value="{{ $procedimento->id }}" id="procedimentos_{{ $procedimento->id }}" name="procedimentos[]">
                            <label class="form-check-label" for="procedimentos_{{ $procedimento->id }}"> {{ dataDbForm($procedimento->data_aplicacao)." - R$ ".valorDbForm($procedimento->valor) }} </label>
                        </div>
                    @endforeach
                </div>
                <div class="col-md-9">
                    <h5 class="card-title">Forma de Pagamento</h5>
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
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <select required id="forma_pagamento" name='forma_pagamento' class="select2 form-select">
                                    <option value="">Opções</option>
                                    <option value="Dinheiro">Dinheiro</option>
                                    <option value="Débito">Débito</option>
                                    <option value="Crédito">Crédito</option>
                                    <option value="Pix">Pix</option>
                                </select>
                                <label for="forma_pagamento">Tipo de Pagamento:</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating form-floating-outline">
                                <select disabled id="parcelas" name='parcelas' class="select2 form-select">
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
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                                <label for="parcelas">Número de Parcelas:</label>
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
                    <div class="row mt-2">
                        <div class="col-md-6 form-group">
                            <button type="submit" class="btn btn-primary me-2">Salvar</button>

                            @if($retorno == "sistema_dashboard")
                                <a href="{{ route('sistema.dashboard') }}" class="btn btn-danger">Não Registrar Pagamento</a>
                            @elseif($retorno == "adm_dashboard")
                                <a href="{{ route('adm.sistema.dashboard') }}" class="btn btn-danger">Não Registrar Pagamento</a>
                            @else
                                <a href="{{ route('sistema.procedimentos') }}" class="btn btn-danger">Não Registrar Pagamento</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
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

document.getElementById('forma_pagamento').addEventListener('change', (e)=>{
    if(e.target.value == "Crédito"){
        document.getElementById('parcelas').removeAttribute('disabled');
        document.getElementById('parcelas').setAttribute('required','required');
    }
    else{
        document.getElementById('parcelas').setAttribute('disabled','disabled');
        document.getElementById('parcelas').removeAttribute('required');
    }
});


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
