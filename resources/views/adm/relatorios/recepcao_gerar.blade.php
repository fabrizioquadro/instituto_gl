@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Recepção - Resultados</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Recepcionista</th>
                        <th>Paciente</th>
                        <th>Clínica</th>
                        <th>Início Cadastro</th>
                        <th>Finalização (Financeiro)</th>
                        <th>Tempo Total</th>
                        <th>Código Grupo</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $procedimentosAgrupados = $procedimentos->groupBy('codigo');
                    @endphp
                    @foreach($procedimentosAgrupados as $codigo => $grupo)
                        @php 
                            $primeiro = $grupo->first();
                            $inicio = \Carbon\Carbon::parse($primeiro->inicio_cadastro);
                            $fim = \Carbon\Carbon::parse($primeiro->finalizacao_cadastro);
                            $diff = $inicio->diff($fim);
                            $duracao = $diff->format('%H:%I:%S');
                        @endphp
                        <tr>
                            <td>{{ $primeiro->cadastrante->nome ?? 'N/A' }}</td>
                            <td>{{ $primeiro->paciente->nm_paciente ?? 'N/A' }}</td>
                            <td>{{ $primeiro->clinica->nome ?? 'N/A' }}</td>
                            <td>{{ $inicio->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $fim->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <span class="badge bg-label-primary">
                                    {{ $duracao }}
                                </span>
                            </td>
                            <td>{{ $codigo }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<form target="_blank" id='formulario' action="{{ route('adm.relatorios.exportar') }}" method="post">
    @csrf
    <input type="hidden" name="data" id="data">
</form>

<script>
document.getElementById('exportar').addEventListener('click', ()=>{
    let dados = document.getElementById('div_dados').innerHTML;
    document.getElementById('data').value = dados;
    document.getElementById('formulario').submit();
})
</script>
@endsection
