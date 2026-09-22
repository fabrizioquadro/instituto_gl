@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Recepção (Prescrições)</h4>
        </div>
        <hr>
        <form action="{{ route('adm.relatorios2.recepcao.gerar') }}" method="post">
            @csrf
            <div class="row mt-2 gy-4">
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="clinica_id" name='clinica_id' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($clinicas as $clinica)
                                <option value="{{ $clinica->id }}">{{ $clinica->nome }}</option>
                            @endforeach
                        </select>
                        <label for="clinica_id">Clínica:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="user_id_cadastro" name='user_id_cadastro' class="select2 form-select">
                            <option value="">Opções</option>
                            @foreach($recepcionistas as $usuario)
                                <option value="{{ $usuario->id }}">{{ $usuario->nome }}</option>
                            @endforeach
                        </select>
                        <label for="user_id_cadastro">Colaborador(a):</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="situacao" name='situacao' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Em Andamento">Em Andamento</option>
                            <option value="Concluída">Concluída</option>
                            <option value="Encerrada">Encerrada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                        <label for="situacao">Situação da Prescrição:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <select id="situacao_financeira" name='situacao_financeira' class="select2 form-select">
                            <option value="">Opções</option>
                            <option value="Em Aberto">Em Aberto</option>
                            <option value="Parcial">Parcial</option>
                            <option value="Pago">Pago</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                        <label for="situacao_financeira">Situação Financeira:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_inc" name="dt_inc"/>
                        <label for="dt_inc">Prescrição de:</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-floating form-floating-outline">
                        <input class="form-control" type="date" id="dt_fn" name="dt_fn"/>
                        <label for="dt_fn">Prescrição até:</label>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Gerar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
