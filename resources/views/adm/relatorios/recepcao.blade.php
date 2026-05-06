@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Recepção - Tempo de Atendimento</h4>
        </div>
        <hr>
        <form action="{{ route('adm.relatorios.recepcao.gerar') }}" method="post">
            @csrf
            <div class="row mt-2 gy-4">
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select id="clinica_id" name='clinica_id' class="form-select">
                            <option value="">Todas as Clínicas</option>
                            @foreach($clinicas as $clinica)
                                <option value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
                            @endforeach
                        </select>
                        <label for="clinica_id">Clínica:</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating form-floating-outline">
                        <select id="user_id_cadastro" name='user_id_cadastro' class="form-select">
                            <option value="">Todas as Recepcionistas</option>
                            @foreach($recepcionistas as $recepcionista)
                                <option value="{{ $recepcionista->id }}">{{ $recepcionista->nome }}</option>
                            @endforeach
                        </select>
                        <label for="user_id_cadastro">Recepcionista:</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_inc" name="dt_inc" value="{{ date('Y-m-d') }}"/>
                        <label for="dt_inc">Início:</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_fn" name="dt_fn" value="{{ date('Y-m-d') }}"/>
                        <label for="dt_fn">Final:</label>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Gerar Relatório</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
