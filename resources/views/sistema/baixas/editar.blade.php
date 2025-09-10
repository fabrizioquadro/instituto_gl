@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Baixa</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.baixas.update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="contador_medicamentos" id="contador_medicamentos" value="{{ $baixa->medicamentos()->count() }}">
            <input type="hidden" name="baixa_id" value="{{ $baixa->id }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <input required class="form-control" type="date" id="data" name="data" max="{{ date('Y-m-d') }}" value="{{ $baixa->data }}"/>
                        <label for="data">Data:</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-floating form-floating-outline mb-4">
                        <textarea class="form-control h-px-100" id="motivo" name='motivo' required>{{ $baixa->motivo }}</textarea>
                        <label for="motivo">Motivo:</label>
                    </div>
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
                            <th style='width: 30% !important'>Medicamento</th>
                            <th>Lote</th>
                            <th>Quantidade</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabela_medicamentos">
                        @foreach($baixa->medicamentos as $estoque)
                            <tr id="linha_adicionar_{{ ++$i }}">
                                <td>
                                    <select onchange="get_lotes_medicamento({{ $i }})" required name="medicamento_id_{{ $i }}" id="medicamento_id_{{ $i }}" class="form-control">
                                        <option value="">Opções</option>
                                        @foreach($medicamentos as $medicamento)
                                            <option @if($estoque->medicamento_id == $medicamento->id) selected @endif value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select onchange="seta_quantidade_estoque({{ $i }})" required name="lote_{{ $i }}" id="lote_{{ $i }}" class="form-control">
                                        <option>Opções</option>
                                    </select>
                                </td>
                                <td><input name="quantidade_{{ $i }}" id="quantidade_{{ $i }}" required type="number" class="form-control"></td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="row mt-5">
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-secondary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('botao_adicionar_medicamento').addEventListener('click', ()=>{
    contador = parseInt(document.getElementById('contador_medicamentos').value);
    contador++;
    document.getElementById('contador_medicamentos').value = contador;

    html = `
    <td>
        <select onchange="get_lotes_medicamento(${contador})" required name="medicamento_id_${contador}" id="medicamento_id_${contador}" class="form-control">
            <option value="">Opções</option>
            @foreach($medicamentos as $medicamento)
                <option value="{{ $medicamento->id }}">{{ $medicamento->nome." - ".$medicamento->fabricante }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <select onchange="seta_quantidade_estoque(${contador})" required name="lote_${contador}" id="lote_${contador}" class="form-control">
            <option>Opções</option>
        </select>
    </td>
    <td><input name="quantidade_${contador}" id="quantidade_${contador}" required type="number" class="form-control"></td>
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

function get_lotes_medicamento(linha){
    medicamento_id = document.getElementById('medicamento_id_' + linha).value;
    if(medicamento_id){
        $.getJSON(
            "{{ route('sistema.baixas.get_lotes_medicamento') }}",
            {
                medicamento_id : medicamento_id
            },
            function(json){
                document.getElementById('lote_' + linha).innerHTML = json.lotes;
            }
        );
    }
    else{
        document.getElementById('lote_' + linha).innerHTML = "<option value=''>Opções</option>";
        document.getElementById('quantidade_' + linha).removeAttribute('max');
    }
}

function seta_quantidade_estoque(linha){
    select = document.getElementById('lote_' + linha);
    selectedOption = select.options[select.selectedIndex];
    quantidade = parseInt(selectedOption.dataset.quantidade);
    document.getElementById('quantidade_' + linha).setAttribute('max', quantidade);
}

function excluir_linha_medicamento(linha){
    if(confirm('Tem certeza que deseja excluir este item?')){
        document.getElementById('linha_adicionar_' + linha).remove();
        calcula_total_entrada();
    }
}

</script>
@endsection
