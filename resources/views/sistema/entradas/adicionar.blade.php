@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Adicionar Entrada</h4>
            <button type="button" id="botao_gerador" class="btn btn-label-secondary waves-effect">
                <span class="tf-icons mdi mdi-cog-outline me-1"></span>
                Gerador
            </button>
        </div>
        <hr>
        <form action="{{ route('sistema.entradas.insert') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="1">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="fornecedor_id" name='fornecedor_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($fornecedores as $fornecedor)
                                <option value="{{ $fornecedor->id }}">{{ $fornecedor->nome }}</option>
                            @endforeach
                        </select>
                        <label for="fornecedor_id">Fornecedor:</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="date" id="data" name="data" max="{{ date('Y-m-d') }}"/>
                        <label for="data">Data:</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="nota" name="nota"/>
                        <label for="nota">Nr. Nota:</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="file" id="arquivo" name="arquivo"/>
                        <label for="arquivo">Arquivo da Nota:</label>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <h5 class="card-title">Medicamentos</h5>
                    <button type="button" id="botao_adicionar_medicamento_old" onclick="adicionar_medicamento()" class="btn btn-sm btn-primary">Adicionar</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nº</th>
                                <th style='width: 25% !important'>Medicamento</th>
                                <th>Quantidade</th>
                                <th>Unitário</th>
                                <th>Total</th>
                                <th>Lote</th>
                                <th>Venc.</th>
                                <th>C. Barras</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tabela_medicamentos">
                            <tr id="linha_adicionar_1">
                                <td>1</td>
                                <td>
                                    <select required name="medicamento_id_1" id="medicamento_id_1" class="form-control">
                                        <option>Opções</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option value="{{ $medicamento->id }}">{{ $medicamento->nome."/".$medicamento->fabricante." - Unidade: ".$medicamento->unidade." ".$medicamento->vasilhame }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input onblur="calcula_total_medicamento(1)" name="quantidade_1" id="quantidade_1" required type="number" class="form-control"></td>
                                <td><input onblur="calcula_total_medicamento(1)" name="valor_1" id="valor_1" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td><input onblur="calcula_total_medicamento(1)" name="total_1" id="total_1" type="text" class="form-control valor" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td><input name='lote_1' id='lote_1' required type="text" class="form-control"></td>
                                <td><input name='dt_vencimento_1' id='dt_vencimento_1' required type="date" class="form-control"></td>
                                <td><input name='codigo_barras_1' id='codigo_barras_1' required type="text" class="form-control"></td>
                                <td><span title="Gerar Codigo de Barras" onclick="get_codigo_barras(1)" class="mdi mdi-cog-outline"></span></td>
                                <td></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th><strong>Total</strong></th>
                                <th><input name="total_entrada" id="total_entrada" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
function get_codigo_barras(linha){
    medicamento_id = document.getElementById('medicamento_id_' + linha).value;
    if(medicamento_id){
        $.getJSON(
            '{{ route("sistema.entradas.gerar_codigo_barras") }}',
            {
                medicamento_id : medicamento_id
            },
            function(json){
                document.getElementById('codigo_barras_' + linha).value = json.codigo;
            }
        );
    }
    else{
        alert('É necessario escolher o medicamento');
    }
}

function calcula_total_medicamento(linha){
    quantidade = parseInt(document.getElementById('quantidade_' + linha).value);
    valor = document.getElementById('valor_' + linha).value;
    if(quantidade && valor){
        valor = valor.replace('.','');
        valor = parseFloat(valor.replace(',','.'));
        total = quantidade * valor;
        total = total.toFixed(2);
        document.getElementById('total_' + linha).value = total.replace('.',',');
        calcula_total_entrada();
    }
}

function adicionar_medicamento(){
    contador = parseInt(document.getElementById('contador_medicamentos').value);
    contador++;
    document.getElementById('contador_medicamentos').value = contador;

    html = `
        <td>${contador}</td>
        <td>
            <select required name="medicamento_id_${contador}" id="medicamento_id_${contador}" class="form-control">
                <option>Opções</option>
                @foreach($medicamentos as $medicamento)
                    <option value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante." - Unidade:".$medicamento->unidade." ".$medicamento->vasilhame }}</option>
                @endforeach
            </select>
        </td>
        <td><input onblur="calcula_total_medicamento(${contador})" name="quantidade_${contador}" id="quantidade_${contador}" required type="number" class="form-control"></td>
        <td><input onblur="calcula_total_medicamento(${contador})" name="valor_${contador}" id="valor_${contador}" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
        <td><input onblur="calcula_total_medicamento(${contador})" name="total_${contador}" id="total_${contador}" type="text" class="form-control valor" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
        <td><input name='lote_${contador}' id='lote_${contador}' required type="text" class="form-control"></td>
        <td><input name='dt_vencimento_${contador}' id='dt_vencimento_${contador}' required type="date" class="form-control"></td>
        <td><input name='codigo_barras_${contador}' id='codigo_barras_${contador}' required type="text" class="form-control"></td>
        <td><span title="Gerar Codigo de Barras" onclick="get_codigo_barras(${contador})" class="mdi mdi-cog-outline"></span></td>
        <td>
            <button title='Excluir Linha' onclick='excluir_linha_medicamento(${contador})' type="button" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
                <span class="tf-icons mdi mdi-delete mdi-24px"></span>
            </button>
        </td>
    `;

    tr = document.createElement('tr');
    tr.setAttribute('id', 'linha_adicionar_' + contador);
    tr.innerHTML = html;
    document.getElementById('tabela_medicamentos').appendChild(tr);
}

function calcula_total_entrada(){
    let somatorio = 0;
    let variavel = "input.valor";

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
    document.getElementById('total_entrada').value = somatorio
}

function excluir_linha_medicamento(linha){
    if(confirm('Tem certeza que deseja excluir este item?')){
        document.getElementById('linha_adicionar_' + linha).remove();
        calcula_total_entrada();
    }
}

</script>


<div class="modal fade" id="modal_gerador" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" method="post">
            <input type="hidden" id="gerador_contador_medicamentos" value='1'>
            <div class="modal-header">
                <h5 class="modal-title" id="backDropModalTitle">Gerador Entradas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style='width: 25% !important'>Medicamento</th>
                                <th>Quantidade</th>
                                <th>Unitário</th>
                                <th>Lote</th>
                                <th>Venc.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <select id="gerador_medicamento" class="form-control">
                                        <option>Opções</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option value="{{ $medicamento->id }}">{{ $medicamento->nome."/".$medicamento->fabricante." - Unidade: ".$medicamento->unidade." ".$medicamento->vasilhame }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input id="gerador_quantidade" type="number" class="form-control"></td>
                                <td><input id="gerador_unitario" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
                                <td><input id='gerador_lote' required type="text" class="form-control"></td>
                                <td><input id='gerador_vencimento' type="date" class="form-control"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mb-3 mt-3">
                    <button class="btn btn-primary" type="button" onclick="gera_entradas_gerador()">Gerar Entradas</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
var modalGerador;

document.getElementById('botao_gerador').addEventListener('click', ()=>{
    modalGerador = new bootstrap.Modal(document.getElementById('modal_gerador'));
    modalGerador.show();
})

function gera_entradas_gerador(){
    let medicamento = document.getElementById('gerador_medicamento').value;
    let quantidade = parseInt(document.getElementById('gerador_quantidade').value);
    let unitario = document.getElementById('gerador_unitario').value;
    let lote = document.getElementById('gerador_lote').value;
    let vencimento = document.getElementById('gerador_vencimento').value;

    if(medicamento != "" && quantidade != "" && unitario != "" && lote != "" && vencimento != ""){
        for(let m=1 ; m<=quantidade ; m++){
            console.log('entrou no for, quantidade ' + quantidade);
            if(m > 1){
                adicionar_medicamento();
            }

            document.getElementById('medicamento_id_' + m).value = medicamento;
            document.getElementById('quantidade_' + m).value = '1';
            document.getElementById('valor_' + m).value = unitario;
            document.getElementById('lote_' + m).value = lote;
            document.getElementById('dt_vencimento_' + m).value = vencimento;
            calcula_total_medicamento(m);
            get_codigo_barras(m);
        }
        modalGerador.hide();
    }
    else{
        alert('É necessario preencher todos os campos');
    }
}
</script>
@endsection
