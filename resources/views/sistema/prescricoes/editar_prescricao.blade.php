@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Prescrição #{{ $prescricao->id }}</h4>
            <a href="{{ route('sistema.prescricoes.acessar', $prescricao->id) }}" class="btn btn-outline-dark btn-sm">Voltar</a>
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

        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="mdi mdi-information-outline me-2"></i>
            <div>Paciente: <b>{{ $prescricao->paciente->nm_paciente ?? '-' }}</b></div>
        </div>

        <form action="{{ route('sistema.prescricoes.update_prescricao') }}" method="post">
            @csrf
            <input type="hidden" name="prescricao_id" value="{{ $prescricao->id }}">

            @php
            $medicos_nomes = collect($medicos)->pluck('profissional_nome')->all();
            $tipos_atendimento = ['Consulta Tratamento', 'Retorno', 'Consulta Nova', 'Coleta/Bio', 'Implante'];
            @endphp

            <div class="row mt-2 gy-3">
                <div class="col-md-6">
                    <label class="form-label" for="medico">Médico:</label>
                    <select required id="medico" name="medico" class="form-select">
                        <option value="">Opções</option>
                        @if($prescricao->medico && !in_array($prescricao->medico, $medicos_nomes))
                            <option value="{{ $prescricao->medico }}" selected>{{ $prescricao->medico }}</option>
                        @endif
                        @foreach($medicos as $medico)
                            <option value="{{ $medico['profissional_nome'] }}" @if($prescricao->medico == $medico['profissional_nome']) selected @endif>{{ $medico['profissional_nome'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tipo_atendimento">Tipo de Atendimento:</label>
                    <select required id="tipo_atendimento" name="tipo_atendimento" class="form-select">
                        <option value="">Opções</option>
                        @if($prescricao->tipo_atendimento && !in_array($prescricao->tipo_atendimento, $tipos_atendimento))
                            <option value="{{ $prescricao->tipo_atendimento }}" selected>{{ $prescricao->tipo_atendimento }}</option>
                        @endif
                        @foreach($tipos_atendimento as $tipo)
                            <option value="{{ $tipo }}" @if($prescricao->tipo_atendimento == $tipo) selected @endif>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="clinica_id">Clínica:</label>
                    <select required id="clinica_id" name="clinica_id" class="form-select">
                        @foreach($clinicas as $clinica)
                            <option value="{{ $clinica->id }}" @if($prescricao->clinica_id == $clinica->id) selected @endif>{{ $clinica->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="obs">Obs:</label>
                    <textarea name="obs" id="obs" class="form-control" rows="2">{{ $prescricao->obs }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>
@endsection
