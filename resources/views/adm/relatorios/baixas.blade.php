@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Baixas Consolidado</h4>
        </div>
        <hr>
        <form action="{{ route('adm.relatorios.baixas.gerar') }}" method="post">
            @csrf
            <div class="row mt-2 gy-4">
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="clinica_id" name='clinica_id' class="select2 form-select">
                            <option value="">Todas as Clínicas</option>
                            @foreach($clinicas as $clinica)
                                <option value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
                            @endforeach
                        </select>
                        <label for="clinica_id">Clínica:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="medicamento_id" name='medicamento_id' class="select2 form-select">
                            <option value="">Todos os Medicamentos</option>
                            @foreach($medicamentos as $medicamento)
                                <option value="{{ $medicamento->id }}">{{ $medicamento->nome }}</option>
                            @endforeach
                        </select>
                        <label for="medicamento_id">Medicamento:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_inc" name="dt_inc"/>
                        <label for="dt_inc">Início:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_fn" name="dt_fn"/>
                        <label for="dt_fn">Final:</label>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Gerar Relatório</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
