@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)
@section('conteudo')
<meta http-equiv="refresh" content="30">
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title">Enfermagem</h4>
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
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h4 class="card-title">Estoques Abertos</h4>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Clinica</th>
                                <th>Medicamento</th>
                                <th>Abertura</th>
                                <th>Usuário</th>
                                <th>Lote</th>
                                <th>C. Barras</th>
                                <th>Frasco</th>
                                <th>Restante</th>
                            </tr>
                        </thead>
                        @foreach($array_abertos as $linha)
                            <tr>
                                <td>{{ $linha['clinica'] }}</td>
                                <td>{{ $linha['medicamento'] }}</td>
                                <td>{{ $linha['abertura'] }}</td>
                                <td>{{ $linha['usuario'] }}</td>
                                <td>{{ $linha['lote'] }}</td>
                                <td>{{ $linha['codigo_barras'] }}</td>
                                <td>{{ $linha['frasco'] }}</td>
                                <td>{{ $linha['restante'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Aguardando</h5>
                <div class="table-responsive">
                    <table class="tabela-index table" id="table-index">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Procedimentos</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos_aguardando as $procedimento)
                            @php
                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($procedimento->aplicacaos->count() > 0){
                                $ds_procedimentos .= ", Aplicação";
                            }
                            if($procedimento->st_biopedancia == 'Sim'){
                                $ds_procedimentos .= ", Biopedância";
                            }
                            if($procedimento->st_coleta == 'Sim'){
                                $ds_procedimentos .= ", Coleta";
                            }
                            if($procedimento->st_retirada == 'Sim'){
                                $ds_procedimentos .= ", Retirada";
                            }
                            $ds_procedimentos = substr($ds_procedimentos, 2);

                            if($procedimento->situacao == "Atendimento"){
                                $situacao = '<span class="badge rounded-pill bg-label-success">Atendimento</span>';
                            }
                            elseif($procedimento->situacao == "Fila de Aplicação"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Aguardando</span>';
                            }

                            @endphp
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu" data-popper-placement="bottom-end">
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Abrir Atendimento</a>
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_visualizar_procedimento', $procedimento->id) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                        </div>
                                    </div>
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>{{ $procedimento->paciente->nm_paciente }}</td>
                                <td>{{ $ds_procedimentos }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{!! $situacao !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Atendimentos</h5>
                <div class="table-responsive">
                    <table class="tabela-index table" id="table-index">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Procedimentos</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos_atendimento as $procedimento)
                            @php
                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($procedimento->aplicacaos->count() > 0){
                                $ds_procedimentos .= ", Aplicação";
                            }
                            if($procedimento->st_biopedancia == 'Sim'){
                                $ds_procedimentos .= ", Biopedância";
                            }
                            if($procedimento->st_coleta == 'Sim'){
                                $ds_procedimentos .= ", Coleta";
                            }
                            if($procedimento->st_retirada == 'Sim'){
                                $ds_procedimentos .= ", Retirada";
                            }
                            $ds_procedimentos = substr($ds_procedimentos, 2);

                            if($procedimento->situacao == "Atendimento"){
                                $situacao = '<span class="badge rounded-pill bg-label-success">Atendimento</span>';
                            }
                            elseif($procedimento->situacao == "Fila de Aplicação"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Aguardando</span>';
                            }

                            @endphp
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu" data-popper-placement="bottom-end">
                                            @if($procedimento->user_id_aplicacao == $user->id)
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Abrir Atendimento</a>
                                            @endif
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_visualizar_procedimento', $procedimento->id) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                        </div>
                                    </div>
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>{{ $procedimento->paciente->nm_paciente }}</td>
                                <td>{{ $ds_procedimentos }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{!! $situacao !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
        <div class="card card-border-shadow-primary mb-4">
            <div class="card-body">
                <h5 class="card-title">Aplicadas</h5>
                <div class="table-responsive">
                    <table class="tabela-index table" id="table-index">
                        <thead class="table-light">
                            <tr>
                                <th>Chegada</th>
                                <th>Paciente</th>
                                <th>Procedimentos</th>
                                <th>Médico</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        @foreach($procedimentos_aplicadas as $procedimento)
                            @php
                            $var = explode(' ', $procedimento->updated_at);
                            $chegada = dataDbForm($var[0])." ".$var[1];
                            $ds_procedimentos = "";
                            if($procedimento->aplicacaos->count() > 0){
                                $ds_procedimentos .= ", Aplicação";
                            }
                            if($procedimento->st_biopedancia == 'Sim'){
                                $ds_procedimentos .= ", Biopedância";
                            }
                            if($procedimento->st_coleta == 'Sim'){
                                $ds_procedimentos .= ", Coleta";
                            }
                            if($procedimento->st_retirada == 'Sim'){
                                $ds_procedimentos .= ", Retirada";
                            }
                            $ds_procedimentos = substr($ds_procedimentos, 2);

                            if($procedimento->situacao == "Atendimento"){
                                $situacao = '<span class="badge rounded-pill bg-label-success">Atendimento</span>';
                            }
                            elseif($procedimento->situacao == "Fila de Aplicação"){
                                $situacao = '<span class="badge rounded-pill bg-label-danger">Aguardando</span>';
                            }
                            elseif($procedimento->situacao == "Aplicado"){
                                $situacao = '<span class="badge rounded-pill bg-label-primary">Aplicado</span>';
                            }

                            @endphp
                            <tr>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow show" data-bs-toggle="dropdown" aria-expanded="true">
                                            <i class="mdi mdi-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu" data-popper-placement="bottom-end">
                                            @if($procedimento->user_id_aplicacao == $user->id)
                                                <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_acessar_procedimento', $procedimento->id) }}"><i class="mdi mdi-eye me-1"></i> Abrir Atendimento</a>
                                            @endif
                                            <a class="dropdown-item waves-effect" href="{{ route('sistema.dashboard.enfermagem_visualizar_procedimento', $procedimento->id) }}"><i class="mdi mdi-book-open-page-variant me-1"></i> Visualizar (Sem Vincular)</a>
                                        </div>
                                    </div>
                                </td>
                                <td> <span style='display: none'>{{ $procedimento->updated_at }}</span> {{ $chegada }}</td>
                                <td>{{ $procedimento->paciente->nm_paciente }}</td>
                                <td>{{ $ds_procedimentos }}</td>
                                <td>{{ $procedimento->medico }}</td>
                                <td>{!! $situacao !!}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
window.addEventListener('load',()=>{
    // Inicialização do DataTable
    $('#table-index').DataTable({
        order: [[1, 'asc']],
        // ... configurações de linguagem ...
        "language": {
            "sEmptyTable": "Nenhum registro encontrado",
            "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
            "sInfoFiltered": "(Filtrados de _MAX_ registros)",
            "sSearch": "Pesquisar",
            "oPaginate": {
                "sNext": "Próximo",
                "sPrevious": "Anterior"
            }
        }
    });

    // Lógica para Alarme Sonoro de Novos Atendimentos
    try {
        const currentAwaitingIds = @json($procedimentos_aguardando->pluck('id'));
        const storageKey = 'gl_last_awaiting_ids';
        const previousAwaitingIds = JSON.parse(localStorage.getItem(storageKey) || '[]');

        // Verifica se há IDs na lista atual que não estavam na lista anterior
        const hasNewAttendances = currentAwaitingIds.some(id => !previousAwaitingIds.includes(id));

        if (hasNewAttendances) {
            playAlertSound();
        }

        // Atualiza o cache para a próxima atualização
        localStorage.setItem(storageKey, JSON.stringify(currentAwaitingIds));
    } catch (e) {
        console.error("Erro na lógica de alarme sonoro:", e);
    }
});

function playAlertSound() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        
        const context = new AudioContext();
        const oscillator = context.createOscillator();
        const gainNode = context.createGain();

        oscillator.type = 'sine'; // Som suave
        oscillator.frequency.setValueAtTime(880, context.currentTime); // Nota lá (A5)
        
        gainNode.gain.setValueAtTime(0, context.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.5, context.currentTime + 0.1);
        gainNode.gain.linearRampToValueAtTime(0, context.currentTime + 0.8);

        oscillator.connect(gainNode);
        gainNode.connect(context.destination);

        oscillator.start();
        oscillator.stop(context.currentTime + 0.8);
    } catch (error) {
        console.warn('Não foi possível reproduzir o som de alerta (pode exigir interação prévia do usuário):', error);
    }
}
</script>
@endsection
