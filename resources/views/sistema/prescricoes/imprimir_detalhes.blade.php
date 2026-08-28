<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8" />
    <title>Detalhes da Prescrição - #{{ $prescricao->id }}</title>
    <style>
        @page { margin: 15mm 10mm; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #0d6efd; font-size: 18px; margin: 0 0 5px; }
        .header p { margin: 0; font-size: 11px; color: #666; }
        .card { border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px; }
        .card-title { font-size: 14px; font-weight: 700; color: #0d6efd; margin: 0 0 10px; padding-bottom: 5px; border-bottom: 1px solid #dee2e6; }
        .row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 8px; }
        .col { flex: 1; min-width: 200px; }
        label { font-size: 10px; color: #666; display: block; }
        strong { font-size: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        table th { background: #f0f4f8; padding: 6px 8px; text-align: left; border: 1px solid #dee2e6; font-size: 10px; text-transform: uppercase; }
        table td { padding: 5px 8px; border: 1px solid #dee2e6; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 10px; font-weight: 600; }
        .bg-info { background: #cff4fc; color: #055160; }
        .text-danger { color: #dc3545; }
        .text-success { color: #198754; }
        del { color: #dc3545; }
        .footer { text-align: center; font-size: 10px; color: #999; margin-top: 30px; border-top: 1px solid #dee2e6; padding-top: 10px; }
        @media print {
            .btn { display: none; }
        }
        .btn { display: inline-block; padding: 6px 16px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 4px; font-size: 12px; border: none; cursor: pointer; margin-bottom: 15px; }
        .btn:hover { background: #0b5ed7; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Detalhes da Prescrição</h1>
        <p>Prescrição: #{{ $prescricao->id }} | Código V1: {{ $prescricao->codigo_versao1 ?? '-' }}</p>
    </div>

    <button class="btn" onclick="window.print()">🖨️ Imprimir</button>

    <div class="card">
        <div class="card-title">Informações do Paciente</div>
        <div class="row">
            <div class="col">
                <label>Paciente</label>
                <strong>{{ $prescricao->paciente->nm_paciente ?? '-' }}</strong>
            </div>
            <div class="col">
                <label>CPF</label>
                <strong>{{ $prescricao->paciente->cpf ?? '-' }}</strong>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">Informações da Prescrição</div>
        <div class="row">
            <div class="col">
                <label>Prescrição</label>
                <strong>#{{ $prescricao->id }}</strong>
            </div>
            <div class="col">
                <label>Qt. Semanas</label>
                <strong>{{ $prescricao->qt_semanas }}</strong>
            </div>
            <div class="col">
                <label>Data Prescrição</label>
                <strong>{{ $prescricao->data_prescricao ? date('d/m/Y', strtotime($prescricao->data_prescricao)) : '-' }}</strong>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <label>Médico</label>
                <strong>{{ $prescricao->medico ?? '-' }}</strong>
            </div>
            <div class="col">
                <label>Clínica</label>
                <strong>{{ $prescricao->clinica->nome ?? '-' }}</strong>
            </div>
            <div class="col">
                <label>Situação</label>
                <strong>{{ $prescricao->situacao }}</strong>
            </div>
        </div>
        @if($prescricao->obs)
        <div class="row">
            <div class="col">
                <label>Observação</label>
                <strong>{{ $prescricao->obs }}</strong>
            </div>
        </div>
        @endif
        @if($prescricao->paciente && $prescricao->paciente->obs)
        <div class="row">
            <div class="col">
                <label>Observação do Paciente</label>
                <strong>{{ $prescricao->paciente->obs }}</strong>
            </div>
        </div>
        @endif
    </div>

    @if($prescricao->semanas->count() > 0)
    <div class="card">
        <div class="card-title">Medicamentos / Aplicações</div>
        @foreach($prescricao->semanas as $semana)
            <h5 style="margin: 12px 0 6px; font-size: 12px; color: #0d6efd;">Semana {{ $semana->nr_semana }} <small style="color:#666">({{ $semana->situacao }})</small></h5>
            <table style="margin-bottom: 8px;">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Unidade</th>
                        <th>Quantidade</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($semana->medicamentos as $med)
                    <tr>
                        <td>{{ $med->medicamento->nome ?? '-' }}</td>
                        <td>{{ $med->medicamento->unidade ?? '-' }}</td>
                        <td>{{ $med->quantidade }}</td>
                        <td>{{ $med->situacao }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
    @endif

    <div class="card">
        <div class="card-title">Histórico de Alterações</div>
        @if($logs->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Autor</th>
                    <th>Ação</th>
                    <th>Descrição</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user ? $log->user->nome : 'Sistema' }}</td>
                    <td><span class="badge bg-info">{{ $log->acao }}</span></td>
                    <td>{{ $log->descricao }}</td>
                    <td style="font-size: 10px; max-width: 250px;">
                        @if($log->dados_novos)
                            @foreach($log->dados_novos as $campo => $novo)
                                @php $antigo = $log->dados_antigos[$campo] ?? 'N/A'; @endphp
                                <strong>{{ ucfirst(str_replace('_', ' ', $campo)) }}:</strong>
                                <span class="text-danger"><del>{{ is_array($antigo) ? json_encode($antigo) : $antigo }}</del></span>
                                <i class="text-muted">→</i>
                                <span class="text-success">{{ is_array($novo) ? json_encode($novo) : $novo }}</span><br>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #999; text-align: center;">Nenhuma alteração registrada.</p>
        @endif
    </div>

    <div class="footer">
        Documento gerado em {{ date('d/m/Y H:i:s') }} - Instituto GL
    </div>
</body>
</html>
