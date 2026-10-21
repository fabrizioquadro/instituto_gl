<?php
/**
 * Gera relatório EXCEL (.xlsx) dos procedimentos que foram enviados à FILA DE
 * APLICAÇÃO com autorização de administrador por não estarem pagos, informando
 * se após a aplicação foram pagos ou não.
 *
 * Uso:
 *   php gerar_relatorio_autorizados.php                        -> Instituto GL (id 5), todos
 *   php gerar_relatorio_autorizados.php 2026-05-01             -> Instituto GL, a partir de 01/05/2026
 *   php gerar_relatorio_autorizados.php 2026-05-01 2026-08-17  -> Instituto GL, intervalo
 *   php gerar_relatorio_autorizados.php 2026-05-01 2026-08-17 6  -> outra clínica (id 6)
 *
 * O filtro de data é aplicado sobre a data de envio à fila (dt_hr_chegada).
 * O filtro de clínica é aplicado sobre clinica_id_aplicacao (padrão: Instituto GL = 5).
 * O arquivo gerado fica em public/rel_autorizados/Relatorio_Procedimentos_Autorizados_<data>.xlsx
 */

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Procedimento;
use App\Models\Clinica;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dt_inc = isset($argv[1]) && $argv[1] ? $argv[1] : null;
$dt_fn  = isset($argv[2]) && $argv[2] ? $argv[2] : null;
$clinica_id = isset($argv[3]) && $argv[3] ? (int)$argv[3] : 5; // padrão: Instituto GL

$clinica_nome = Clinica::where('id', $clinica_id)->value('nome');
if (!$clinica_nome) {
    $clinica_nome = 'Instituto GL';
}

$query = Procedimento::with(['paciente', 'clinica_aplicacao', 'autorizador'])
    ->whereNotNull('autorizador_sem_pagamento')
    ->where('autorizador_sem_pagamento', '<>', '')
    ->where('clinica_id_aplicacao', $clinica_id)
    ->orderByDesc('dt_hr_chegada');

if ($dt_inc) {
    $query->where('dt_hr_chegada', '>=', $dt_inc . ' 00:00:00');
}
if ($dt_fn) {
    $query->where('dt_hr_chegada', '<=', $dt_fn . ' 23:59:59');
}

$procedimentos = $query->get();

$status = [
    'Sim'      => ['label' => 'Pago',           'total' => 0, 'valor' => 0.0],
    'Parcial'  => ['label' => 'Parcial',        'total' => 0, 'valor' => 0.0],
    'Não'      => ['label' => 'Não Pago',       'total' => 0, 'valor' => 0.0],
    'Pendente' => ['label' => 'Pendente',       'total' => 0, 'valor' => 0.0],
    ''         => ['label' => 'Sem informação', 'total' => 0, 'valor' => 0.0],
];

$valor_total = 0.0;
foreach ($procedimentos as $p) {
    $chave = $p->st_pagamento !== null ? $p->st_pagamento : '';
    if (!isset($status[$chave])) {
        $chave = '';
    }
    $status[$chave]['total']++;
    $status[$chave]['valor'] += (float)$p->valor;
    $valor_total += (float)$p->valor;
}

$total_geral = count($procedimentos);

// ---------- Cores por situação de pagamento ----------
$st_label = ['Sim' => 'Pago', 'Parcial' => 'Parcial', 'Não' => 'Não Pago', 'Pendente' => 'Pendente'];
$st_fill  = ['Sim' => 'C6EFCE', 'Parcial' => 'FFEB9C', 'Não' => 'FFC7CE', 'Pendente' => 'D9D9D9'];
$st_font  = ['Sim' => '006100', 'Parcial' => '9C6500', 'Não' => '9C0006', 'Pendente' => '404040'];

// ---------- Formatação auxiliar ----------
function fmtDataEx($v){
    if (!$v) return '—';
    $p = explode(' ', $v);
    if (count($p) == 2) {
        return dataDbForm($p[0]) . ' ' . $p[1];
    }
    return dataDbForm($v);
}

$filtro_txt = 'Todos os períodos';
if ($dt_inc && !$dt_fn) $filtro_txt = 'Enviados à fila a partir de ' . dataDbForm($dt_inc);
if ($dt_inc && $dt_fn) $filtro_txt = 'Enviados à fila entre ' . dataDbForm($dt_inc) . ' e ' . dataDbForm($dt_fn);

// ---------- Monta planilha ----------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Autorizados');

$headers = [
    'Procedimento', 'Paciente', 'Clínica', 'Médico', 'Enviado à fila em',
    'Aplicado em', 'Situação', 'Valor', 'Pagamento', 'Pago em', 'Autorizador',
];
$lastCol = 'K';

// Título
$sheet->setCellValue('A1', 'Procedimentos Aplicados com Autorização (Sem Pagamento)');
$sheet->mergeCells('A1:' . $lastCol . '1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('0F172A'));

// Subtítulo
$sheet->setCellValue('A2', "Clínica: {$clinica_nome} | {$filtro_txt} | Gerado em " . date('d/m/Y H:i'));
$sheet->mergeCells('A2:' . $lastCol . '2');
$sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new Color('64748B'));

// Cabeçalho (linha 3)
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '3', $h);
    $col++;
}
$headerRange = 'A3:' . $lastCol . '3';
$sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(11)->setColor(new Color('FFFFFF'));
$sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
$sheet->getStyle($headerRange)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle($headerRange)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
$sheet->getRowDimension(3)->setRowHeight(22);

// Dados
$row = 4;
foreach ($procedimentos as $p) {
    $sheet->setCellValue('A' . $row, $p->codigo . '/' . $p->nr_procedimento);
    $sheet->setCellValue('B' . $row, $p->paciente->nm_paciente ?? '—');
    $sheet->setCellValue('C' . $row, $p->clinica_aplicacao->nome ?? '—');
    $sheet->setCellValue('D' . $row, $p->medico ?: '—');
    $sheet->setCellValue('E' . $row, fmtDataEx($p->dt_hr_chegada));
    $sheet->setCellValue('F' . $row, fmtDataEx($p->dt_hr_finalizacao));
    $sheet->setCellValue('G' . $row, $p->situacao ?: '—');

    $sheet->setCellValue('H' . $row, (float)$p->valor);
    $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('"R$ "#,##0.00');
    $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    $chave = $p->st_pagamento !== null ? $p->st_pagamento : '';
    if (!isset($st_label[$chave])) $chave = '';
    $label = $st_label[$chave] ?? 'Sem informação';
    $sheet->setCellValue('I' . $row, $label);
    if (isset($st_fill[$chave])) {
        $sheet->getStyle('I' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($st_fill[$chave]);
        $sheet->getStyle('I' . $row)->getFont()->getColor()->setRGB($st_font[$chave]);
    }
    $sheet->getStyle('I' . $row)->getFont()->setBold(true);
    $sheet->getStyle('I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('J' . $row, $p->data_pagamento ? dataDbForm($p->data_pagamento) : '—');
    $sheet->setCellValue('K' . $row, $p->autorizador->nome ?? '—');

    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->getAlignment()
        ->setVertical(Alignment::VERTICAL_TOP);
    $row++;
}

$lastDataRow = $row - 1;

// Bordas na área de dados
$sheet->getStyle('A3:' . $lastCol . $lastDataRow)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

// Largura das colunas
$widths = ['A' => 20, 'B' => 30, 'C' => 22, 'D' => 22, 'E' => 20, 'F' => 20, 'G' => 18, 'H' => 15, 'I' => 13, 'J' => 13, 'K' => 24];
foreach ($widths as $c => $w) {
    $sheet->getColumnDimension($c)->setWidth($w);
}

// Congela cabeçalho + filtro
$sheet->freezePane('A4');
if ($lastDataRow >= 3) {
    $sheet->setAutoFilter('A3:' . $lastCol . $lastDataRow);
}

// ---------- Resumo por situação de pagamento ----------
$row += 1;
$sheet->setCellValue('A' . $row, 'Resumo por situação de pagamento');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12)->setColor(new Color('0F172A'));
$row++;
$sheet->setCellValue('A' . $row, 'Situação');
$sheet->setCellValue('B' . $row, 'Quantidade');
$sheet->setCellValue('C' . $row, 'Valor');
$sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
$resumoIni = $row;
$row++;

$ordem = ['Sim', 'Parcial', 'Não', 'Pendente', ''];
foreach ($ordem as $k) {
    $s = $status[$k];
    if ($s['total'] == 0 && $k !== '') continue;
    $sheet->setCellValue('A' . $row, $s['label']);
    $sheet->setCellValue('B' . $row, $s['total']);
    $sheet->setCellValue('C' . $row, (float)$s['valor']);
    $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('"R$ "#,##0.00');
    $row++;
}
// Total
$sheet->setCellValue('A' . $row, 'Total');
$sheet->setCellValue('B' . $row, $total_geral);
$sheet->setCellValue('C' . $row, (float)$valor_total);
$sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('"R$ "#,##0.00');
$sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':C' . $row)->getBorders()->getTop()
    ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB('0F172A');
$resumoFim = $row;
$sheet->getStyle('A' . $resumoIni . ':C' . $resumoFim)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

// ---------- Salva arquivo ----------
$dir = public_path('rel_autorizados');
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$arq = $dir . '/Relatorio_Procedimentos_Autorizados_' . date('Ymd_His') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($arq);

echo "Relatório Excel gerado com sucesso!\n";
echo "Clínica: {$clinica_nome}\n";
echo "Procedimentos: " . number_format($total_geral, 0, ',', '.') . "\n";
echo "Valor total: R$ " . number_format($valor_total, 2, ',', '.') . "\n";
echo "Arquivo: {$arq}\n";
