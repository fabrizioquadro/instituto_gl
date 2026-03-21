@php
$template = "layout.".session()->get('layout');
@endphp
@extends($template)

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Editar Médico</h4>
        </div>
        <hr>
        <form action="{{ route('sistema.procedimentos.editar_medico_set') }}" method="post">
            @csrf
            <input type="hidden" name="codigo" value="{{ $procedimento->codigo }}">
            <div class="row mt-2 gy-4 align-items-end">
                <div class="col-md-6">
                    <div class="form-floating form-floating-outline">
                        <select required id="medico" name='medico' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($medicos as $medico)
                                <option @if($medico['profissional_nome'] == $procedimento->medico) selected @endif value="{{ $medico['profissional_nome'] }}">{{ $medico['profissional_nome'] }}</option>
                            @endforeach
                        </select>
                        <label for="medico">Médico:</label>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <button type="submit" class="btn btn-primary me-2">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
