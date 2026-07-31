<?php
/**
 * Script para buscar agendamentos da Feegow em uma data específica
 * e gerar um arquivo XLSX na pasta scripts_recuperacao/
 * 
 * Uso: php scripts_recuperacao/agendamentos_feegow.php [data]
 * 
 * Exemplos:
 *   php scripts_recuperacao/agendamentos_feegow.php          # pergunta a data
 *   php scripts_recuperacao/agendamentos_feegow.php 30-07-2026
 *   php scripts_recuperacao/agendamentos_feegow.php 2026-07-30
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Paciente;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ==============================================
// DEFINIR DATA
// ==============================================
$data = $argv[1] ?? null;

if (!$data) {
    echo "Digite a data (dd-mm-aaaa ou aaaa-mm-dd): ";
    $handle = fopen("php://stdin", "r");
    $data = trim(fgets($handle));
    fclose($handle);
}

// Normalizar formato para dd-mm-aaaa (formato que a Feegow aceita)
$data_limpa = str_replace('/', '-', $data);
$partes = explode('-', $data_limpa);

if (count($partes) === 3) {
    if (strlen($partes[0]) === 4) {
        // Formato aaaa-mm-dd -> dd-mm-aaaa
        $data_feegow = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    } else {
        $data_feegow = $data_limpa;
    }
} else {
    die("Formato de data inválido. Use dd-mm-aaaa ou aaaa-mm-dd.\n");
}

echo "=============================================\n";
echo " BUSCANDO AGENDAMENTOS DA FEEGOW\n";
echo " Data: $data_feegow\n";
echo "=============================================\n\n";

// ==============================================
// TOKEN FEEGOW
// ==============================================
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";

// ==============================================
// BUSCAR AGENDAMENTOS NA FEEGOW
// ==============================================
echo "Buscando agendamentos...\n";

$parametros = [
    'data_start' => $data_feegow,
    'data_end' => $data_feegow,
];

$apiUrl = "https://api.feegow.com/v1/api/appoints/search?" . http_build_query($parametros);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-access-token: $token",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Erro na requisição: " . curl_error($ch) . "\n");
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$retorno = json_decode($response, true);

if ($httpCode !== 200 || !isset($retorno['success']) || $retorno['success'] !== true) {
    echo "Erro na resposta da API (HTTP $httpCode):\n";
    print_r($retorno);
    exit(1);
}

$agendamentos = $retorno['content'] ?? [];

echo "Total de agendamentos encontrados: " . count($agendamentos) . "\n\n";

if (empty($agendamentos)) {
    echo "Nenhum agendamento encontrado para esta data.\n";
    exit(0);
}

// ==============================================
// ENRIQUECER DADOS: buscar nome do paciente
// ==============================================
echo "Buscando informações dos pacientes...\n";

// Primeiro, vamos ver quais pacientes temos localmente
$pacientes_local = Paciente::whereNotNull('paciente_id_feegow')
    ->pluck('nm_paciente', 'paciente_id_feegow')
    ->toArray();

$total = count($agendamentos);
$contador = 0;

foreach ($agendamentos as $i => $agenda) {
    $contador++;
    $paciente_id = $agenda['paciente_id'];
    
    // Verificar se temos o nome localmente
    if (isset($pacientes_local[$paciente_id])) {
        $agendamentos[$i]['nome_paciente'] = $pacientes_local[$paciente_id];
        echo "  [$contador/$total] ID $paciente_id: " . $pacientes_local[$paciente_id] . " (local)\n";
        continue;
    }

    // Buscar nome na Feegow
    echo "  [$contador/$total] ID $paciente_id: buscando na Feegow... ";
    
    $url_paciente = "https://api.feegow.com/v1/api/patient/search?paciente_id=" . $paciente_id;
    
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $url_paciente);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "x-access-token: $token",
        "Content-Type: application/json"
    ]);
    
    $resp2 = curl_exec($ch2);
    curl_close($ch2);
    
    $dados_paciente = json_decode($resp2, true);
    
    if (isset($dados_paciente['success']) && $dados_paciente['success'] && isset($dados_paciente['content'])) {
        $content = $dados_paciente['content'];
        $nome = $content['nome'] ?? $content['nome_social'] ?? 'N/A';
        $agendamentos[$i]['nome_paciente'] = $nome;
        echo "$nome\n";
    } else {
        $agendamentos[$i]['nome_paciente'] = 'N/A';
        echo "NÃO ENCONTRADO\n";
    }
}

echo "\n";

// ==============================================
// ORDENAR POR HORÁRIO
// ==============================================
usort($agendamentos, function($a, $b) {
    return strcmp($a['horario'] ?? '00:00:00', $b['horario'] ?? '00:00:00');
});

// ==============================================
// GERAR XLSX
// ==============================================
echo "Gerando arquivo XLSX...\n";

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Agendamentos $data_feegow");

// Cabeçalhos
$headers = [
    'A' => 'ID Agendamento',
    'B' => 'Data',
    'C' => 'Horário',
    'D' => 'ID Paciente',
    'E' => 'Nome do Paciente',
    'F' => 'ID Procedimento',
    'G' => 'Valor',
    'H' => 'Status ID',
    'I' => 'Local ID',
    'J' => 'Profissional ID',
    'K' => 'Agendado Por',
    'L' => 'Notas',
    'M' => 'Agendado Em',
    'N' => 'Especialidade ID',
];

// Estilo do cabeçalho
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4472C4'],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];

// Escrever cabeçalhos
$col = 'A';
foreach ($headers as $letra => $nome) {
    $sheet->setCellValue($letra . '1', $nome);
    $sheet->getStyle($letra . '1')->applyFromArray($headerStyle);
}

// Largura das colunas
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(14);
$sheet->getColumnDimension('C')->setWidth(10);
$sheet->getColumnDimension('D')->setWidth(14);
$sheet->getColumnDimension('E')->setWidth(40);
$sheet->getColumnDimension('F')->setWidth(16);
$sheet->getColumnDimension('G')->setWidth(14);
$sheet->getColumnDimension('H')->setWidth(10);
$sheet->getColumnDimension('I')->setWidth(10);
$sheet->getColumnDimension('J')->setWidth(16);
$sheet->getColumnDimension('K')->setWidth(30);
$sheet->getColumnDimension('L')->setWidth(30);
$sheet->getColumnDimension('M')->setWidth(20);
$sheet->getColumnDimension('N')->setWidth(16);

// Estilo dos dados
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
];

// Escrever dados
$linha = 2;
foreach ($agendamentos as $agenda) {
    $sheet->setCellValue('A' . $linha, $agenda['agendamento_id'] ?? '');
    $sheet->setCellValue('B' . $linha, $agenda['data'] ?? '');
    $sheet->setCellValue('C' . $linha, $agenda['horario'] ?? '');
    $sheet->setCellValue('D' . $linha, $agenda['paciente_id'] ?? '');
    $sheet->setCellValue('E' . $linha, $agenda['nome_paciente'] ?? '');
    $sheet->setCellValue('F' . $linha, $agenda['procedimento_id'] ?? '');
    $sheet->setCellValue('G' . $linha, $agenda['valor'] ?? '');
    $sheet->setCellValue('H' . $linha, $agenda['status_id'] ?? '');
    $sheet->setCellValue('I' . $linha, $agenda['local_id'] ?? '');
    $sheet->setCellValue('J' . $linha, $agenda['profissional_id'] ?? '');
    $sheet->setCellValue('K' . $linha, $agenda['agendado_por'] ?? '');
    $sheet->setCellValue('L' . $linha, $agenda['notas'] ?? '');
    $sheet->setCellValue('M' . $linha, $agenda['agendado_em'] ?? '');
    $sheet->setCellValue('N' . $linha, $agenda['especialidade_id'] ?? '');
    
    $sheet->getStyle('A' . $linha . ':N' . $linha)->applyFromArray($dataStyle);
    $linha++;
}

// Auto filter
$sheet->setAutoFilter('A1:N' . ($linha - 1));

// Salvar arquivo
$nome_arquivo = 'agendamentos_feegow_' . str_replace('-', '', $data_feegow) . '.xlsx';
$caminho = __DIR__ . '/' . $nome_arquivo;

$writer = new Xlsx($spreadsheet);
$writer->save($caminho);

echo "\n=============================================\n";
echo " ARQUIVO GERADO COM SUCESSO!\n";
echo "=============================================\n";
echo "Local: $caminho\n";
echo "Total de agendamentos: " . count($agendamentos) . "\n";
echo "=============================================\n";
