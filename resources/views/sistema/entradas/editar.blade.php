@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Entrada</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.entradas.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="entrada_id" value="{{ $entrada->id }}">
            <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="{{ $entrada->medicamentos()->count() }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="fornecedor_id" name='fornecedor_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($fornecedores as $fornecedor)
                                <option @if($entrada->fornecedor_id == $fornecedor->id) selected @endif value="{{ $fornecedor->id }}">{{ $fornecedor->nome }}</option>
                            @endforeach
                        </select>
                        <label for="fornecedor_id">Fornecedor:</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="date" id="data" name="data" max="{{ date('Y-m-d') }}" value="{{ $entrada->data }}"/>
                        <label for="data">Data:</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="text" id="nota" name="nota" value="{{ $entrada->nota }}"/>
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
                    <button type="button" id="botao_adicionar_medicamento" class="btn btn-sm btn-primary">Adicionar</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th style='width: 25% !important'>Medicamento</th>
                                <th>Quantidade</th>
                                <th>Unitário</th>
                                <th>Total</th>
                                <th>Lote</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tabela_medicamentos">
                            @foreach($entrada->medicamentos as $estoque)
                                <tr id="linha_adicionar_{{ ++$i }}">
                                    <td>
                                        <select required name="medicamento_id_{{ $i }}" id="medicamento_id_{{ $i }}" class="form-control">
                                            <option>Opções</option>
                                            @foreach($medicamentos as $medicamento)
                                                <option @if($estoque->medicamento_id == $medicamento->id) selected @endif value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante." - Unidade:".$medicamento->unidade." ".$medicamento->vasilhame }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input onblur="calcula_total_medicamento({{ $i }})" name="quantidade_{{ $i }}" id="quantidade_{{ $i }}1" required type="number" class="form-control" value="{{ $estoque->quantidade }}"></td>
                                    <td><input onblur="calcula_total_medicamento({{ $i }})" name="valor_{{ $i }}" id="valor_{{ $i }}" type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($estoque->valor) }}"></td>
                                    <td><input onblur="calcula_total_medicamento({{ $i }})" name="total_{{ $i }}" id="total_{{ $i }}" type="text" class="form-control valor" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($estoque->total) }}"></td>
                                    <td><input name='lote_{{ $i }}' required type="text" class="form-control" value="{{ $estoque->lote }}"></td>
                                    <td><input name='dt_vencimento_1' id='dt_vencimento_1' required type="date" class="form-control" value="{{ $estoque->dt_vencimento }}"></td>
                                    <td><input name='codigo_barras_1' id='codigo_barras_1' required type="text" class="form-control" value="{{ $estoque->codigo_barras }}"></td>
                                    <td>
                                        <button title='Excluir Linha' onclick='excluir_linha_medicamento({{ $i }})' type="button" class="btn btn-sm rounded-pill btn-icon btn-label-danger btn-fab demo waves-effect">
                                            <span class="tf-icons mdi mdi-delete mdi-24px"></span>
                                        </button>
                                    </td>
                                </tr>

                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th></th>
                                <th><strong>Total</strong></th>
                                <th><input name="total_entrada" id="total_entrada" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))" value="{{ valorDbForm($entrada->valor) }}"></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-secondary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
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

document.getElementById('botao_adicionar_medicamento').addEventListener('click', ()=>{
    contador = parseInt(document.getElementById('contador_medicamentos').value);
    contador++;
    document.getElementById('contador_medicamentos').value = contador;

    html = `
        <td>
            <select required name="medicamento_id_${contador}" id="medicamento_id_${contador}" class="form-control">
                <option>Opções</option>
                @foreach($medicamentos as $medicamento)
                    <option value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante." - Unidade:".$medicamento->unidade." ".$medicamento->vasilhame }}</option>
                @endforeach
            </select>
        </td>
        <td><input onblur="calcula_total_medicamento(${contador})" name="quantidade_${contador}" id="quantidade_${contador}" required type="number" class="form-control"></td>
        <td><input onblur="calcula_total_medicamento(${contador})" name="valor_${contador}" id="valor_${contador}" required type="text" class="form-control" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
        <td><input onblur="calcula_total_medicamento(${contador})" name="total_${contador}" id="total_${contador}" required type="text" class="form-control valor" onkeypress="return(MascaraMoeda(this,'.',',',event))"></td>
        <td><input name='lote_${contador}' required type="text" class="form-control"></td>
        <td><input name='dt_vencimento_${contador}' id='dt_vencimento_${contador}' required type="date" class="form-control"></td>
        <td><input name='codigo_barras_${contador}' id='codigo_barras_${contador}' required type="text" class="form-control"></td>
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
})

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
@endsection
