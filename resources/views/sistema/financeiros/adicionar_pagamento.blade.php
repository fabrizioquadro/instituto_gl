@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h5 class="card-title">Formas de Pagamento</h5>
            <button type="button" onclick="adicionar_forma()" class="btn btn-sm rounded-pill btn-outline-dark waves-effect">
                <span class="tf-icons mdi mdi-plus me-1"></span>
                Forma Pgt
            </button>
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
        <form id='formulario' action="{{ route('sistema.financeiros.insert_pagamento') }}" method="post">
            @csrf
            <input type="hidden" name="financeiro_id" value="{{ $financeiro->id }}">
            <input type="hidden" name="contador_formas" id="contador_formas" value="1">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="obs_pagamento" name="obs_pagamento"/>
                        <label for="obs_pagamento">Obs Pagamento:</label>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
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
                            <button type="submit" class="btn btn-primary me-2" id="botao_salvar">Salvar</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script>

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
</script>
@endsection
