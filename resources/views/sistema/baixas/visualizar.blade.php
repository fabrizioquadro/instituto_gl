@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Visualizar Baixa</h4>
        </div>
        <hr>
        <div class="row mt-2 gy-4 align-items-end">
            <div class="col-md-6 form-group">
                <label for="data">Data:</label><br>
                <b>{{ dataDbForm($baixa->data) }}</b>
            </div>
            <div class="col-md-12 form-group">
                <label for="motivo">Motivo:</label><br>
                <b>{{ $baixa->motivo }}</b>
            </div>
        </div>
        <hr>
        <div class="d-flex justify-content-between">
            <h5 class="card-title">Medicamentos</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th style='width: 30% !important'>Medicamento</th>
                        <th>Lote</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($baixa->medicamentos as $estoque)
                        <tr>
                            <td>{{ $estoque->medicamento->nome }}</td>
                            <td>{{ $estoque->lote }}</td>
                            <td>{{ $estoque->quantidade }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
