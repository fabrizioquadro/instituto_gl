@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Visualizar Entrada</h4>
        </div>
        <hr>
        <div class="row mt-2 gy-4 align-items-end">
            <div class="col-md-6 form-group">
                <label for="fornecedor_id">Fornecedor:</label><br>
                <b>{{ $entrada->fornecedor->nome }}</b>
            </div>
            <div class="col-md-2 form-group">
                <label for="data">Data:</label><br>
                <b>{{ dataDbForm($entrada->data) }}</b>
            </div>
            <div class="col-md-2 form-group">
                <label for="nota">Nr. Nota:</label><br>
                <b>{{ $entrada->nota }}</b>
            </div>
            @if($entrada->arquivo)
                <div class="col-md-2 form-group">
                    <label for="arquivo">Arquivo da Nota:</label><br>
                    <a target="_blank" href="/public/img/entradas/notas/{{ $entrada->arquivo }}" class="btn btn-text-primary waves-effect waves-light">Abrir Nota</a>
                </div>
            @endif
        </div>
        <hr>
        <div class="d-flex justify-content-between">
            <h5 class="card-title">Medicamentos</h5>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th><input class="form-check-input" id="check_all" type="checkbox"></th>
                        <th style='width: 25% !important'>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Unitário</th>
                        <th>Total</th>
                        <th>Lote</th>
                        <th>Vencimento</th>
                        <th>Codigo Barras</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entrada->medicamentos as $estoque)
                        <tr>
                            <td><input class="form-check-input estoques" type="checkbox" value="{{ $estoque->id }}" name="estoques[]"></td>
                            <td>{{ $estoque->medicamento->nome }}</td>
                            <td>{{ $estoque->quantidade }}</td>
                            <td>R$ {{ valorDbForm($estoque->valor) }}</td>
                            <td>R$ {{ valorDbForm($estoque->total) }}</td>
                            <td>{{ $estoque->lote }}</td>
                            <td>{{ dataDbForm($estoque->dt_vencimento) }}</td>
                            <td>{{ $estoque->codigo_barras }}</td>
                            <td>
                                <a href='/sistema/entradas/etiquetas_imprimir/["{{ $estoque->id }}"]' target="_blank">
                                    <span title="Imprimir Etiqueta" class="mdi mdi-printer-outline"></span>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">
                            <button id="botao_imprimir_selecionados" type="button" class="btn btn-sm rounded-pill btn-label-secondary waves-effect">
                                <span class="tf-icons mdi mdi-printer-outline me-1"></span> Imprimir Selecionados
                            </button>
                        </th>
                        <th><strong>Total</strong></th>
                        <th> <strong>{{ 'R$ '.valorDbForm($entrada->valor) }}</strong> </th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">
document.getElementById('check_all').addEventListener('click', (e)=>{
    if(document.getElementById('check_all').checked == true){
        inputs = document.querySelectorAll('input.estoques');
        [].forEach.call(inputs, function(input) {
            input.checked = true;
        });
    }
    else{
        inputs = document.querySelectorAll('input.estoques');
        [].forEach.call(inputs, function(input) {
            input.checked = false;
        });
    }
});

document.getElementById('botao_imprimir_selecionados').addEventListener('click', ()=>{
    array_selecionados = [];
    inputs = document.querySelectorAll('input.estoques');
    [].forEach.call(inputs, function(input) {
        if(input.checked == true){
            array_selecionados.push(input.value);
        }
    });

    window.open('/sistema/entradas/etiquetas_imprimir/' + JSON.stringify(array_selecionados));
});


</script>
@endsection
