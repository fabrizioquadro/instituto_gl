@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')

<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="card-title mb-0">Resumo</h6>
            <a href="{{ route('sistema.prescricoes.financeiro', $prescricao->id) }}" class="btn btn-sm btn-primary" target="_blank">Acessar Financeiro</a>
        </div>
        <div class="row">
            <div class="col-md-4 form-group">
                <label for="">Paciente:</label><br>
                <b>{{ $prescricao->paciente->nm_paciente ?? '-' }}</b>
            </div>
            <div class="col-md-4 form-group">
                <label for="">Médico:</label><br>
                <b>{{ $prescricao->medico ?? '-' }}</b>
            </div>
            @if($cadastrante)
                <div class="col-md-4 form-group">
                    <label for="">Cadastrante:</label><br>
                    <b>{{ $cadastrante }}</b>
                </div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($array_resumo as $array)
                        <tr>
                            <td>{{ $array['medicamento'] }}</td>
                            <td>{{ $array['quantidade'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <hr>
        <h6 class="card-title">Anexos</h6>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescricao->anexos as $arquivo)
                        <tr>
                            <td>
                                {{ $arquivo->nm_anexo }}<br>
                                <small class="text-muted">Enviado em: {{ $arquivo->created_at ? $arquivo->created_at->format('d/m/Y H:i') : '' }}</small>
                            </td>
                            <td>
                                <a href="{{ asset('public/prescricoes/'.$arquivo->prescricao_id.'/anexos/'.$arquivo->arquivo) }}" target="_blank" class="btn btn-sm btn-label-primary waves-effect">
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
            <div class="col-md-3 form-group mb-2">
                <label for="">Valor Total:</label><br>
                <b>R$ {{ valorDbForm($vl_procedimentos) }}</b>
            </div>
            <div class="col-md-3 form-group mb-2">
                <label for="">Valor Pago:</label><br>
                <b>R$ {{ valorDbForm($vl_pagamentos) }}</b>
            </div>
            <div class="col-md-3 form-group mb-2">
                <label for="">Valor Pendente:</label><br>
                <b>R$ {{ valorDbForm(max(0, $vl_procedimentos - $vl_pagamentos)) }}</b>
            </div>
            <div class="col-md-3 form-group mb-2">
                <label for="">Observação Pagamento:</label><br>
                <b>{{ $obs_pagamento }}</b>
            </div>
        </div>
        <hr>
        <h6 class="card-title">Resumo de Parcelas</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Parcela</th>
                        <th>Semana</th>
                        <th>Valor</th>
                        <th>Valor Pago</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prescricao->parcelas as $parcela)
                        @php
                        $badge_parcela = 'bg-danger';
                        if($parcela->situacao == 'Paga'){ $badge_parcela = 'bg-success'; }
                        elseif($parcela->situacao == 'Parcial'){ $badge_parcela = 'bg-warning'; }
                        @endphp
                        <tr>
                            <td>{{ $parcela->nr_parcela }}</td>
                            <td>Semana {{ $parcela->semana ? $parcela->semana->nr_semana : '-' }}</td>
                            <td>R$ {{ valorDbForm($parcela->valor_parcela) }}</td>
                            <td>R$ {{ valorDbForm($parcela->valor_pago) }}</td>
                            <td><span class="badge rounded-pill {{ $badge_parcela }}">{{ $parcela->situacao }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($prescricao->semanas as $semana)
    @php
    $semana_logs = $prescricao->logs->where('entidade', 'semana')->where('entidade_id', $semana->id)->sortByDesc('created_at')->values();
    @endphp
    <div class="card card-border-shadow-primary mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="">Semana</label><br>
                    <b>{{ $semana->nr_semana }}</b>
                </div>
                <div class="col-md-4 form-group">
                    <label for="">Data Aplicação</label><br>
                    <b>{{ ($semana->data_aplicada ?: $semana->data_prevista) ? dataDbForm($semana->data_aplicada ?: $semana->data_prevista) : '-' }}</b>
                </div>
                <div class="col-md-4 form-group">
                    <label for="">Situação do Pagamento</label><br>
                    @if($semana->parcela)
                        @php
                        $badge_pag = 'bg-danger';
                        if($semana->parcela->situacao == 'Paga'){ $badge_pag = 'bg-success'; }
                        elseif($semana->parcela->situacao == 'Parcial'){ $badge_pag = 'bg-warning'; }
                        @endphp
                        <span class="badge rounded-pill {{ $badge_pag }}">{{ $semana->parcela->situacao }}</span>
                        <small class="text-muted">R$ {{ valorDbForm($semana->parcela->valor_pago) }} / R$ {{ valorDbForm($semana->parcela->valor_parcela) }}</small>
                    @else
                        <span class="badge rounded-pill bg-secondary">Sem Parcela</span>
                    @endif
                </div>
                <div class="col-md-12 form-group">
                    <label for="">ANOTAÇÕES DE ENFERMAGEM</label><br>
                    <b>{{ $semana->medicamentos->first() ? ($semana->medicamentos->first()->obs ?: $semana->obs) : $semana->obs }}</b>
                </div>
            </div>
            <div class="row align-items-center mt-2">
                <div class="col-md-3 form-group">
                    <label class="switch">
                        <input type="checkbox" class="switch-input flag-checkbox" data-id="{{ $semana->id }}" data-flag="flag_coordenacao" {{ $semana->flag_coordenacao ? 'checked' : '' }}>
                        <span class="switch-toggle-slider">
                            <span class="switch-on"></span>
                            <span class="switch-off"></span>
                        </span>
                        <span class="switch-label">Coordenação <span id="user_flag_coordenacao_{{ $semana->id }}" class="text-muted" style="font-size: 0.7rem">{{ $semana->user_nome_coordenacao ? '('.$semana->user_nome_coordenacao.')' : '' }}</span></span>
                    </label>
                </div>
                <div class="col-md-3 form-group">
                    <label class="switch">
                        <input type="checkbox" class="switch-input flag-checkbox" data-id="{{ $semana->id }}" data-flag="flag_qualidade" {{ $semana->flag_qualidade ? 'checked' : '' }}>
                        <span class="switch-toggle-slider">
                            <span class="switch-on"></span>
                            <span class="switch-off"></span>
                        </span>
                        <span class="switch-label">Qualidade <span id="user_flag_qualidade_{{ $semana->id }}" class="text-muted" style="font-size: 0.7rem">{{ $semana->user_nome_qualidade ? '('.$semana->user_nome_qualidade.')' : '' }}</span></span>
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
                            <th>Situação</th>
                            <th>Data Aplicação</th>
                            <th>Lote Aplicação</th>
                            <th>C.Barras</th>
                            <th>Enfermagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($semana->medicamentos as $med)
                            @php
                            $dt_aplicacao = null;
                            if($med->aplicado_em){
                                $var = explode(' ', $med->aplicado_em);
                                $dt_aplicacao = dataDbForm($var[0]);
                            }
                            $lotes_med = $med->lotes->pluck('lote')->implode(', ');
                            $codigos_med = $med->lotes->pluck('codigo_barras')->implode(', ');
                            @endphp
                            <tr>
                                <th>{{ $med->medicamento->nome ?? '-' }}</th>
                                <th>{{ $med->medicamento->unidade ?? '-' }}</th>
                                <th>{{ $med->quantidade }}</th>
                                <th>{{ $med->situacao }}</th>
                                <th>{{ $dt_aplicacao }}</th>
                                <th>{{ $lotes_med }}</th>
                                <th>{{ $codigos_med }}</th>
                                <th>{{ $med->userAplicacao ? $med->userAplicacao->nome : '' }}</th>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <hr>
            <h6 class="card-title">Observações Avulsas</h6>

            <form action="{{ route('sistema.prescricoes.salvar_observacao') }}" method="POST">
                @csrf
                <input type="hidden" name="prescricao_semana_id" value="{{ $semana->id }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="form-floating form-floating-outline mb-3">
                            <textarea required class="form-control" name="observacao" style="height: 60px" placeholder="Digite a observação..."></textarea>
                            <label>Nova Observação</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <button type="submit" class="btn btn-primary w-100 mb-3" style="height: 60px">Salvar</button>
                    </div>
                </div>
            </form>

            @php
            $observacoes = $semana->observacoes->sortByDesc('created_at');
            @endphp
            @if($observacoes->count() > 0)
            <div class="table-responsive mt-3 mb-4">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%">Data / Hora</th>
                            <th style="width: 20%">Usuário</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($observacoes as $obs)
                        <tr>
                            <td>{{ $obs->created_at ? $obs->created_at->format('d/m/Y H:i') : '' }}</td>
                            <td>{{ $obs->user ? $obs->user->nome : 'Sistema' }}</td>
                            <td style="white-space: pre-wrap;">{{ $obs->observacao }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

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
                        @foreach($semana_logs as $index => $log)
                            <tr class="{{ $index > 3 ? 'd-none more-logs-'.$semana->id : '' }}">
                                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $log->user ? $log->user->nome : 'Sistema' }}</td>
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
                @if($semana_logs->count() > 4)
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="document.querySelectorAll('.more-logs-{{ $semana->id }}').forEach(el => el.classList.remove('d-none')); this.style.display='none'">
                            Ver mais ({{ $semana_logs->count() - 4 }})
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach

<style>
.table.table-sm th, .table.table-sm td {
    padding: 0.3125rem 0.625rem !important;
    white-space: nowrap !important;
}
</style>

<script>
window.addEventListener('load', function() {
    if (typeof $ === 'undefined') return;
    $('.flag-checkbox').on('change', function() {
        var id = $(this).data('id');
        var flag = $(this).data('flag');
        var value = $(this).is(':checked') ? 1 : 0;

        $.post('{{ route("sistema.prescricoes.update_flag") }}', {
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
