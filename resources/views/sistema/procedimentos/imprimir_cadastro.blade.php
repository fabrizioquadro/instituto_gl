@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="card-title mb-0">Resumo</h6>
            @if($financeiro)
                <a href="{{ route('sistema.financeiros.acessar', $financeiro->id) }}" class="btn btn-sm btn-primary" target="_blank">Acessar Financeiro</a>
            @endif
        </div>
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
        <hr>
        <h6 class="card-title">Anexos</h6>
        @php
            $procedimento_primeiro = $procedimentos->first();
            $arquivos = collect();
            if($procedimento_primeiro){
                $procedimentos_arqs = App\Models\Procedimento::where('codigo', $procedimento_primeiro->codigo)->get();
                $in = array();
                foreach($procedimentos_arqs as $proc){
                    $in[] = $proc->id;
                }
                $arquivos = App\Models\ProcedimentoAnexo::whereIn('procedimento_id', $in)->get();
            }
        @endphp
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($arquivos as $arquivo)
                        <tr>
                             <td>
                                {{ $arquivo->nm_anexo }}<br>
                                <small class="text-muted">Enviado em: {{ $arquivo->created_at->format('d/m/Y H:i') }}</small>
                            </td>
                            <td>
                                <a href="{{ asset('public/procedimentos/'.$arquivo->procedimento_id.'/anexos/'.$arquivo->anexo) }}" target="_blank" class="btn btn-sm btn-label-primary waves-effect">
                                    <span class="tf-icons mdi mdi-download me-1"></span>
                                    Visualizar
                                </a>
                            </td>
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
            <div class="row align-items-center mt-2">
                <div class="col-md-3 form-group">
                    <label class="switch">
                        <input type="checkbox" class="switch-input flag-checkbox" data-id="{{ $procedimento->id }}" data-flag="flag_coordenacao" {{ $procedimento->flag_coordenacao == 1 ? 'checked' : '' }}>
                        <span class="switch-toggle-slider">
                            <span class="switch-on"></span>
                            <span class="switch-off"></span>
                        </span>
                        <span class="switch-label">Coordenação <span id="user_flag_coordenacao_{{ $procedimento->id }}" class="text-muted" style="font-size: 0.7rem">{{ $procedimento->user_nome_coordenacao ? '('.$procedimento->user_nome_coordenacao.')' : '' }}</span></span>
                    </label>
                </div>
                <div class="col-md-3 form-group">
                    <label class="switch">
                        <input type="checkbox" class="switch-input flag-checkbox" data-id="{{ $procedimento->id }}" data-flag="flag_qualidade" {{ $procedimento->flag_qualidade == 1 ? 'checked' : '' }}>
                        <span class="switch-toggle-slider">
                            <span class="switch-on"></span>
                            <span class="switch-off"></span>
                        </span>
                        <span class="switch-label">Qualidade <span id="user_flag_qualidade_{{ $procedimento->id }}" class="text-muted" style="font-size: 0.7rem">{{ $procedimento->user_nome_qualidade ? '('.$procedimento->user_nome_qualidade.')' : '' }}</span></span>
                    </label>
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
            <hr>
            <h6 class="card-title">Histórico de Alterações</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Autor</th>
                            <th>Ação</th>
                            <th>Descrição</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($procedimento->logs->sortByDesc('created_at') as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $log->autor() }}</td>
                                <td><span class="badge bg-label-info">{{ $log->acao }}</span></td>
                                <td>{{ $log->descricao }}</td>
                                <td>
                                    @if($log->dados_novos)
                                        <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#log_{{ $log->id }}">Ver Detalhes</button>
                                        <div class="collapse" id="log_{{ $log->id }}">
                                            <div class="mt-2 text-wrap" style="font-size: 0.8rem; min-width: 200px">
                                                @foreach($log->dados_novos as $campo => $novo)
                                                    @php $antigo = $log->dados_antigos[$campo] ?? 'N/A'; @endphp
                                                    <strong>{{ ucfirst(str_replace('_', ' ', $campo)) }}:</strong> 
                                                    <span class="text-danger"><del>{{ is_array($antigo) ? json_encode($antigo) : $antigo }}</del></span> 
                                                    <i class="mdi mdi-arrow-right"></i> 
                                                    <span class="text-success">{{ is_array($novo) ? json_encode($novo) : $novo }}</span><br>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </td>
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

<script>
window.addEventListener('load', function() {
    $('.flag-checkbox').on('change', function() {
        var id = $(this).data('id');
        var flag = $(this).data('flag');
        var value = $(this).is(':checked') ? 1 : 0;

        $.post('{{ route("sistema.procedimentos.update_flag") }}', {
            _token: '{{ csrf_token() }}',
            id: id,
            flag: flag,
            value: value
        }, function(response) {
            if (response.success) {
                console.log('Flag atualizada com sucesso');
                var spanId = '#user_flag_' + (flag == 'flag_coordenacao' ? 'coordenacao' : 'qualidade') + '_' + id;
                if(value == 1){
                    $(spanId).text('(' + response.user_nome + ')');
                } else {
                    $(spanId).text('');
                }
            } else {
                alert('Erro ao atualizar flag: ' + response.message);
            }
        });
    });
});
</script>
@endsection
