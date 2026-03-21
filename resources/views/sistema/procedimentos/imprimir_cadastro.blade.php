@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h6 class="card-title">Resumo</h6>
        @if($cadastrante)
            <div class="row">
                <div class="col-md-12 form-group">
                    <label for="">Cadastrante:</label><br>
                    <b>{{ $cadastrante }}</b>
                </div>
            </div>
        @endif
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                        <th>Valor</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($array_resumo as $array)
                        <tr>
                            <td>{{ $array['medicamento'] }}</td>
                            <td>{{ $array['quantidade'] }}</td>
                            <td>R$ {{ valorDbForm($array['valor']) }}</td>
                            <td>R$ {{ valorDbForm($array['total']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label for="">Valor Total:</label><br>
                <b>R$ {{ valorDbForm($vl_procedimentos) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Pago:</label><br>
                <b>R$ {{ valorDbForm($vl_pagamentos) }}</b>
            </div>
            <div class="col-md-6 form-group">
                <label for="">Observação Pagamento:</label><br>
                <b>{{ $obs_pagamento }}</b>
            </div>
        </div>
    </div>
</div>
@php
$vl_nao_aplicado = $vl_pagamentos;
$vl_aplicado = 0;
@endphp
@foreach($procedimentos as $procedimento)
    @php
    $obs = $procedimento->aplicacaos->first() ? $procedimento->aplicacaos->first()->obs : '';
    @endphp
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="">Semana</label><br>
                    <b>{{ $procedimento->nr_procedimento }}</b>
                </div>
                <div class="col-md-6 form-group">
                    <label for="">Data Aplicação</label><br>
                    <b>{{ dataDbForm($procedimento->data_aplicacao) }}</b>
                </div>
                <div class="col-md-12 form-group">
                    <label for="">Observação</label><br>
                    <b>{{ $obs }}</b>
                </div>
            </div>
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
                            if($aplicacao->situacao == 'Aplicada'){
                                $vl_nao_aplicado -= $aplicacao->total;
                                $vl_aplicado += $aplicacao->total;
                            }
                            $dt_aplicacao = null;
                            if($aplicacao->lote){
                                $var = explode(' ',$aplicacao->lote->created_at);
                                $dt_aplicacao = dataDbForm($var[0]);
                            }
                            @endphp
                            <tr>
                                <th>{{ $aplicacao->medicamento->nome }}</th>
                                <th>{{ $aplicacao->medicamento->unidade }}</th>
                                <th>{{ $aplicacao->quantidade }}</th>
                                <th>R$ {{ valorDbForm($aplicacao->valor) }}</th>
                                <th>R$ {{ valorDbForm($aplicacao->total) }}</th>
                                <th>{{ $aplicacao->situacao }}</th>
                                <th>{{ $dt_aplicacao }}</th>
                                <th>{!! $aplicacao->lotes() !!}</th>
                                <th>{!! $aplicacao->codigos() !!}</th>
                                <th>{{ $aplicacao->enfermeira ? $aplicacao->enfermeira->nome : '' }}</th>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group">
                <label for="">Valor Pago:</label><br>
                <b>R$ {{ valorDbForm($vl_pagamentos) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Pendente:</label><br>
                <b>R$ {{ valorDbForm($vl_procedimentos - $vl_pagamentos) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor Aplicado:</label><br>
                <b>R$ {{ valorDbForm($vl_aplicado) }}</b>
            </div>
            <div class="col-md-3 form-group">
                <label for="">Valor em Haver:</label><br>
                <b>R$ {{ valorDbForm($vl_nao_aplicado) }}</b>
            </div>
        </div>
    </div>
</div>

@endsection
