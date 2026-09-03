@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)
@section('conteudo')
<style>
/* Os cards do template têm z-index:1 + position:relative, criando um stacking context
   que prende o dropdown dentro do card e deixa o card seguinte por cima.
   Solução: quando um card do dash tiver um dropdown aberto, ele sobe acima dos demais. */
.card:has(.dropdown-menu.show) {
    z-index: 1050 !important;
}
/* o dropdown não pode ser cortado pelo .table-responsive — só libera o overflow
   enquanto um menu estiver aberto (mantém o scroll-x quando não tem menu aberto) */
.table-responsive.dash-dropdown:has(.dropdown-menu.show) {
    overflow: visible !important;
}
.dash-dropdown .dropdown-menu {
    z-index: 1060 !important;
}
</style>
<meta http-equiv="refresh" content="30">
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Enfermagem</h4>
        </div>
        @if($mensagem = Session::get('mensagem'))
            <div class="alert alert-success alert-dismissible mt-3" role="alert">
                {{ $mensagem }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($mensagem = Session::get('mensagem_erro'))
            <div class="alert alert-danger alert-dismissible mt-3" role="alert">
                {{ $mensagem }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <hr>

        {{-- AGUARDANDO (FILA) --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Aguardando</h5>
                <div class="table-responsive dash-dropdown">
                    <table class="tabela-index table table-sm" id="table-aguardando">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Semana</th>
                                <th>Medicações</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fila as $lote)
                                @php
                                $chegada = $lote['chegada'] ? date('d/m/Y H:i', strtotime($lote['chegada'])) : '-';
                                $meds = $lote['meds'] ?: '-';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu" data-popper-placement="bottom-end">
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.enfermagem_acessar', $lote['id']) }}"><i class="mdi mdi-eye me-1"></i> Iniciar Atendimento</a>
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.acessar_semana', $lote['id']) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $chegada }}</td>
                                    <td>
                                        {{ $lote['prescricao']->paciente->nm_paciente }}
                                        <a href="#" onclick="abrir_obs_semana(this);return false;" title="Ver Observações do Paciente e da Semana" style="text-decoration:none;" data-paciente="{{ $lote['prescricao']->paciente->nm_paciente }}" data-obs-paciente="{{ $lote['obs_paciente'] }}" data-obs-semana="{{ $lote['obs_semanas'] }}"><i class="mdi mdi-information-outline text-info" style="font-size:1.2rem;"></i></a>
                                    </td>
                                    <td>{{ $lote['semanas_txt'] }}</td>
                                    <td>{{ $meds }}</td>
                                    <td>{{ $lote['prescricao']->medico ?? '-' }}</td>
                                    <td><span class="badge rounded-pill bg-label-danger">Aguardando</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">Nenhum paciente na fila no momento.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ATENDIMENTOS --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Atendimentos</h5>
                <div class="table-responsive dash-dropdown">
                    <table class="tabela-index table table-sm" id="table-atendimentos">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Semana</th>
                                <th>Medicações</th>
                                <th>Médico</th>
                                <th>Enfermeiro(a)</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($em_atendimento as $lote)
                                @php
                                $chegada = $lote['chegada'] ? date('d/m/Y H:i', strtotime($lote['chegada'])) : '-';
                                $meds = $lote['meds'] ?: '-';
                                $pode_acessar = $lote['todos_mesmo_dono'] && $lote['user_id_aplicacao'] == $user->id;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu" data-popper-placement="bottom-end">
                                                @if($pode_acessar)
                                                    <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.enfermagem_acessar', $lote['id']) }}"><i class="mdi mdi-eye me-1"></i> Acessar Atendimento</a>
                                                @endif
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.prescricoes.acessar_semana', $lote['id']) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $chegada }}</td>
                                    <td>
                                        {{ $lote['prescricao']->paciente->nm_paciente }}
                                        <a href="#" onclick="abrir_obs_semana(this);return false;" title="Ver Observações do Paciente e da Semana" style="text-decoration:none;" data-paciente="{{ $lote['prescricao']->paciente->nm_paciente }}" data-obs-paciente="{{ $lote['obs_paciente'] }}" data-obs-semana="{{ $lote['obs_semanas'] }}"><i class="mdi mdi-information-outline text-info" style="font-size:1.2rem;"></i></a>
                                    </td>
                                    <td>{{ $lote['semanas_txt'] }}</td>
                                    <td>{{ $meds }}</td>
                                    <td>{{ $lote['prescricao']->medico ?? '-' }}</td>
                                    <td>{{ $lote['userAplicacao']->nome ?? '-' }}</td>
                                    <td><span class="badge rounded-pill bg-label-success">Atendimento</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted">Nenhum paciente em atendimento no momento.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- APLICADAS --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Aplicadas</h5>
                <div class="table-responsive">
                    <table class="tabela-index table table-sm" id="table-aplicadas">
                        <thead class="table-light">
                            <tr>
                                <th>Aplicado em</th>
                                <th>Paciente</th>
                                <th>Semana</th>
                                <th>Medicações</th>
                                <th>Médico</th>
                                <th>Aplicado por</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($atendidos_dia as $s)
                                @php
                                $badge = 'bg-label-success';
                                $txt = 'Aplicada';
                                if($s->situacao == 'Aplicação Parcial'){ $badge = 'bg-label-warning'; $txt = 'Aplicação Parcial'; }
                                $meds = $s->medicamentos->filter(function($m){ return $m->medicamento; })->pluck('medicamento.nome')->implode(', ');
                                $aplicado_por = $s->userAplicacao->nome ?? '-';
                                @endphp
                                <tr>
                                    <td>{{ $s->data_aplicada ? dataDbForm($s->data_aplicada) : '-' }}</td>
                                    <td>
                                        {{ $s->prescricao->paciente->nm_paciente }}
                                        <a href="#" onclick="abrir_obs_semana(this);return false;" title="Ver Observações do Paciente e da Semana" style="text-decoration:none;" data-paciente="{{ $s->prescricao->paciente->nm_paciente }}" data-obs-paciente="{{ $s->prescricao->paciente->obs }}" data-obs-semana="{{ $s->obs }}"><i class="mdi mdi-information-outline text-info" style="font-size:1.2rem;"></i></a>
                                    </td>
                                    <td>{{ $s->nr_semana }}/{{ $s->prescricao->semanas_count }}</td>
                                    <td>{{ $meds ?: '-' }}</td>
                                    <td>{{ $s->prescricao->medico ?? '-' }}</td>
                                    <td>{{ $aplicado_por }}</td>
                                    <td><span class="badge rounded-pill {{ $badge }}">{{ $txt }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted">Nenhum atendimento aplicado hoje.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ATENDIMENTOS POR ENFERMEIRA (DIA) --}}
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Atendimentos do Dia por Enfermeira</h5>
                <div class="table-responsive">
                    <table class="tabela-index table table-sm" id="table-por-enfermeira">
                        <thead class="table-light">
                            <tr>
                                <th>Enfermeira(o)</th>
                                <th>Atendimentos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($atendimentos_por_enfermeira as $linha)
                                <tr>
                                    <td>{{ $linha['nome'] }}</td>
                                    <td><span class="badge rounded-pill bg-label-primary">{{ $linha['qtd'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted">Nenhum atendimento com aplicação registrada hoje.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let modal_obs_semana_obj;
function abrir_obs_semana(el){
    let paciente = el.dataset.paciente || 'Paciente';
    let obsPaciente = el.dataset.obsPaciente || '';
    let obsSemana = el.dataset.obsSemana || '';
    document.getElementById('modal_obs_semana_titulo').innerText = 'Observações: ' + paciente;
    document.getElementById('modal_obs_semana_paciente').value = obsPaciente ? obsPaciente : 'Nenhuma observação registrada para este paciente.';
    document.getElementById('modal_obs_semana_semana').value = obsSemana ? obsSemana : 'Nenhuma observação registrada para esta semana.';
    modal_obs_semana_obj = new bootstrap.Modal(document.getElementById('modal_obs_semana'));
    modal_obs_semana_obj.show();
}
</script>

<!-- Modal Observações (Paciente + Semana) -->
<div class="modal fade" id="modal_obs_semana" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_obs_semana_titulo">Observações</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Observações do Paciente</label>
                    <textarea id="modal_obs_semana_paciente" class="form-control" rows="4" readonly style="resize:none; background-color:#f8f9fa;"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Observações da Semana</label>
                    <textarea id="modal_obs_semana_semana" class="form-control" rows="4" readonly style="resize:none; background-color:#f8f9fa;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection
