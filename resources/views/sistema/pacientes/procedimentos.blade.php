@php
$template = "layout.".session()->get('layout');
$obs_anterior = '';
@endphp
@extends($template)

@section('conteudo')
@foreach($procedimentos as $procedimento)
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h6 class="card-title">Cadastro: {{ dataDbForm($procedimento->data_cad) }} - Aplicação: {{ dataDbForm($procedimento->data_aplicacao) }}</h6>
        </div>
        <hr>
        <div class="table-responsive mt-3">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Medicamento</th>
                        <th>Unidade</th>
                        <th>Quantidade</th>
                        <th>Valor</th>
                        <th>Total</th>
                        <th>Situação</th>
                        <th>Data Aplicação</th>
                        <th>Lote Aplicação</th>
                        <th>C.Barras</th>
                        <th>Enfermagem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($procedimento->aplicacaos as $aplicacao)
                        @php
                        $dt_aplicacao = null;
                        if($aplicacao->lote){
                            $var = explode(' ',$aplicacao->lote->created_at);
                            $dt_aplicacao = dataDbForm($var[0]);
                        }
                        $obs_anterior = $aplicacao->obs;
                        @endphp
                        <tr>
                            <th>{{ $aplicacao->medicamento->nome }}</th>
                            <th>{{ $aplicacao->medicamento->unidade }}</th>
                            <th>{{ $aplicacao->quantidade }}</th>
                            <th>R$ {{ valorDbForm($aplicacao->valor) }}</th>
                            <th>R$ {{ valorDbForm($aplicacao->total) }}</th>
                            <th>{{ $aplicacao->situacao }}</th>
                            <th>{{ $dt_aplicacao }}</th>
                            <th>{{ $aplicacao->lotes() }}</th>
                            <th>{!! $aplicacao->codigos() !!}</th>
                            <th>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '' }}</th>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="row mt-3">
            <div class="col-md-12 form-group">
                <label for="">Obs Aplicação</label><br>
                <strong>{{ $obs_anterior }}</strong>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
