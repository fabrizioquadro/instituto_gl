@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Visualizar Transferência</h4>
        </div>
        <hr>
        <div class="row mt-2 gy-4 align-items-end">
            <div class="col-md-4 form-group">
                <label for="clinica_destino_id">Clinica Origem:</label><br>
                <b>{{ $transferencia->origem->nome }}</b>
            </div>
            <div class="col-md-4 form-group">
                <label for="clinica_destino_id">Clinica Destino:</label><br>
                <b>{{ $transferencia->destino->nome }}</b>
            </div>
            <div class="col-md-4 form-group">
                <label for="data">Data:</label><br>
                <b>{{ dataDbForm($transferencia->data) }}</b>
            </div>
            <div class="col-md-12 form-group">
                <label for="motivo">Motivo:</label><br>
                <b>{{ $transferencia->motivo }}</b>
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
                    @foreach($transferencia->medicamentos($user->clinica_id) as $linha)
                        <tr>
                            <td>{{ $linha->medicamento->nome }}</td>
                            <td>{{ $linha->lote }}</td>
                            <td>{{ $linha->quantidade }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
