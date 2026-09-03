@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<style media="screen">
    .select2-selection__rendered{
        line-height: 40px !important;
    }
    .select2-selection{
        height: 40px !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">Bio/Coleta (Procedimento Grátis)</h4>
            <a href="{{ route('sistema.dash') }}" class="btn btn-label-secondary waves-effect">
                <span class="tf-icons mdi mdi-arrow-left me-1"></span>Voltar ao Dash
            </a>
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

        <form id="form_bio_coleta" method="post" action="{{ route('sistema.prescricoes.bio_coleta.insert') }}">
            @csrf

            {{-- DADOS DA PRESCRIÇÃO --}}
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="data_prevista" name="data_prevista" value="{{ date('Y-m-d') }}" required/>
                        <label for="data_prevista">Data Prevista:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="paciente_id" name="paciente_id" class="select2 form-select">
                            <option value=""></option>
                        </select>
                        <label for="paciente_id">Paciente:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="medico" name="medico" class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($medicos as $medico)
                                <option value="{{ $medico['profissional_nome'] }}">{{ $medico['profissional_nome'] }}</option>
                            @endforeach
                        </select>
                        <label for="medico">Médico:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select required id="tipo_atendimento" name="tipo_atendimento" class="form-select">
                            <option value="">Opções</option>
                            <option value="Consulta Tratamento">Consulta Tratamento</option>
                            <option value="Retorno">Retorno</option>
                            <option value="Consulta Nova">Consulta Nova</option>
                            <option value="Coleta/Bio" selected>Coleta/Bio</option>
                            <option value="Implante">Implante</option>
                        </select>
                        <label for="tipo_atendimento">Tipo de Tratamento:</label>
                    </div>
                </div>
            </div>

            {{-- PROCEDIMENTOS (medicamentos grátis) --}}
            <div class="card card-border-shadow-primary mt-4">
                <div class="card-body">
                    <h5 class="card-title">Procedimentos</h5>
                    <p class="text-muted mb-3">Selecione qual(is) procedimento(s) o paciente irá realizar:</p>
                    <div class="row gy-2">
                        @forelse($medicamentos as $med)
                            <div class="col-md-6">
                                <div class="form-check form-check-inline form-switch form-check-lg">
                                    <input class="form-check-input" type="checkbox" name="medicamentos[]" value="{{ $med->id }}" id="medicamento_{{ $med->id }}">
                                    <label class="form-check-label" for="medicamento_{{ $med->id }}">{{ $med->nome }}</label>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">Nenhum medicamento de Bio/Coleta cadastrado (tipo Procedimento com aplicação).</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- DESTINO --}}
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select id="destino" name="destino" class="form-select">
                            <option value="fila" selected>Enviar para Fila de Aplicação</option>
                            <option value="agendada">Somente cadastrar (Agendada)</option>
                        </select>
                        <label for="destino">Destino:</label>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary waves-effect">
                        <span class="tf-icons mdi mdi-content-save me-1"></span>Cadastrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.addEventListener('load', function(){
    let form = document.getElementById('form_bio_coleta');
    if(form){
        form.addEventListener('submit', function(e){
            let marcados = document.querySelectorAll('input[name="medicamentos[]"]:checked');
            if(marcados.length === 0){
                e.preventDefault();
                alert('Selecione ao menos um procedimento (Bioimpedância e/ou Coleta).');
            }
        });
    }
    if(typeof $ !== 'undefined'){
        $('#paciente_id').select2({
            placeholder: ' ',
            allowClear: true,
            minimumInputLength: 2,
            ajax:{
                url: "{{ route('sistema.pacientes.listar_pacientes_ajax') }}",
                dataType: "json",
                type: 'GET',
                delay: 250,
                data: function(params){ return { q: params.term }; },
                processResults: function(data){ return { results: data }; },
                cache: true
            }
        });
        $('#medico').select2({ placeholder: ' ' });
    }
});
</script>
@endsection
