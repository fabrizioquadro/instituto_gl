@extends('layout.admin')

@section('conteudo')
<div class="card card-border-shadow-primary mb-4">
    <div class="card-body">
        <h4 class="card-title">Dashboard</h4>
        <hr>
        <span>Medicamentos com vencimento nos próximos 60 dias</span>
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Clinica</th>
                        <th>Medicamento</th>
                        <th>Lote</th>
                        <th>Codigo Barras</th>
                        <th>Vencimento</th>
                        <th>Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($array_view) == 0)
                        <tr>
                            <td colspan="6">Não há medicamentos vencendo nos próximos 60 dias</td>
                        </tr>
                    @endif
                    @foreach($array_view as $linha)
                        <tr>
                            <td>{{ $linha['clinica'] }}</td>
                            <td>{{ $linha['medicamento'] }}</td>
                            <td>{{ $linha['lote'] }}</td>
                            <td>{{ $linha['codigo_barras'] }}</td>
                            <td>{{ $linha['vencimento'] }}</td>
                            <td>{{ $linha['quantidade'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


    </div>
</div>
@endsection
