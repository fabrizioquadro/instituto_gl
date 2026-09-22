@extends('layout.admin')

@section('conteudo')
<style media="screen">
    td a {
        color: #828393;
        text-decoration: none;
    }
</style>
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <h4 class="card-title">Relatório de Recepção (Prescrições) - Resultados</h4>
            <button type="button" name="exportar" id="exportar" class="btn btn-sm btn-primary">Exportar</button>
        </div>
        <hr>
        <div class="table-responsive" id="div_dados">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Colaborador</th>
                        <th>Paciente</th>
                        <th>Clínica</th>
                        <th>Data Prescrição</th>
                        <th>Cadastro</th>
                        <th>Semanas</th>
                        <th>Semanas c/ Aplicação</th>
                        <th>Parcelas</th>
                        <th>Valor Tratamento</th>
                        <th>Situação</th>
                        <th>Situação Fin.</th>
                        <th>Prescrição</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @foreach($prescricoes as $prescricao)
                        <tr>
                            <td>{{ $prescricao->userCadastro->nome ?? 'N/A' }}</td>
                            <td>{{ $prescricao->paciente->nm_paciente ?? 'N/A' }}</td>
                            <td>{{ $prescricao->clinica->nome ?? 'N/A' }}</td>
                            <td>{{ dataDbForm($prescricao->data_prescricao) }}</td>
                            <td>{{ dataDbForm(explode(' ', $prescricao->created_at)[0]) }} {{ explode(' ', $prescricao->created_at)[1] ?? '' }}</td>
                            <td>{{ $prescricao->qt_semanas }}</td>
                            <td>{{ $prescricao->qt_semanas_aplicacao }}</td>
                            <td>{{ $prescricao->parcelas_count }}</td>
                            <td>R$ {{ valorDbForm($prescricao->valor_tratamento) }}</td>
                            <td>{{ $prescricao->situacao }}</td>
                            <td>{{ $prescricao->situacao_financeira }}</td>
                            <td>
                                <a href="{{ route('sistema.prescricoes.acessar', $prescricao->id) }}" target="_blank">
                                    {{ $prescricao->codigo_versao1 ?: $prescricao->id }}
                                </a>
                            </td>
                        </tr>
                        @php $total += (float) $prescricao->valor_tratamento; @endphp
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="8" class="text-end">TOTAL GERAL</th>
                        <th>R$ {{ valorDbForm($total) }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<form target="_blank" id='formulario' action="{{ route('adm.relatorios2.exportar_recepcao') }}" method="post">
    @csrf
    <input type="hidden" name="dados" value="{{ json_encode($dados) }}">
</form>

<script>
document.getElementById('exportar').addEventListener('click', () => {
    document.getElementById('formulario').submit();
});
</script>
@endsection
