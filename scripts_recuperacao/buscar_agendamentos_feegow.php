<?php
/**
 * Script para buscar todos os agendamentos da Feegow para uma determinada data
 * e gerar um arquivo XLSX com os resultados.
 * 
 * Uso: php scripts_recuperacao/buscar_agendamentos_feegow.php [data]
 * 
 * Exemplos:
 *   php scripts_recuperacao/buscar_agendamentos_feegow.php           # usa hoje (30-07-2026)
 *   php scripts_recuperacao/buscar_agendamentos_feegow.php 30-07-2026
 *   php scripts_recuperacao/buscar_agendamentos_feegow.php 2026-07-30
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ==============================================
// CONFIGURAÇÃO
// ==============================================
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmZWVnb3ciLCJhdWQiOiJwdWJsaWNhcGkiLCJpYXQiOjE3NTE4OTczODYsImxpY2Vuc2VJRCI6MjMyMjR9.ZC8gSWEiCJsLa7AoFUOT074zaRNECddfXJNT_zi8RvI";
$saida_pasta = __DIR__;

// ==============================================
// PASSO 1: Definir a data
// ==============================================
$data_input = $argv[1] ?? date('d-m-Y');

// Normaliza formato: se vier yyyy-mm-dd, converte para dd-mm-yyyy
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_input)) {
    $partes = explode('-', $data_input);
    $data_feegow = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    $data_arquivo = $data_input;
} elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $data_input)) {
    $data_feegow = $data_input;
    $partes = explode('-', $data_input);
    $data_arquivo = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
} else {
    echo "Formato de data inválido. Use dd-mm-yyyy ou yyyy-mm-dd.\n";
    exit(1);
}

echo "============================================\n";
echo " BUSCAR AGENDAMENTOS FEEGOW\n";
echo "============================================\n";
echo "Data: $data_feegow\n\n";

// ==============================================
// PASSO 2: Buscar agendamentos na Feegow (com paginação)
// ==============================================
echo "[1/3] Buscando agendamentos na Feegow...\n";

$todos_agendamentos = [];
$limit = 1000;
$offset = 0;

while (true) {
    $parametros = [
        'data' => $data_feegow,
        'limit' => $limit,
        'offset' => $offset,
    ];

    $apiUrl = "https://api.feegow.com/v1/api/appoints/list?" . http_build_query($parametros);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-access-token: $token",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Erro na requisição: " . curl_error($ch) . "\n";
        break;
    }

    $retorno = json_decode($response, true);
    curl_close($ch);

    if (!isset($retorno['success']) || $retorno['success'] !== true) {
        echo "API retornou erro: " . ($retorno['message'] ?? 'desconhecido') . "\n";
        break;
    }

    $conteudo = $retorno['content'] ?? [];

    if (empty($conteudo)) {
        break;
    }

    // Verifica se é um array de agendamentos ou objeto paginado
    if (is_array($conteudo)) {
        // Pode vir como array direto ou com chave 'appoints'
        $agendamentos = $conteudo['appoints'] ?? $conteudo['agendamentos'] ?? $conteudo;
        if (is_array($agendamentos)) {
            $todos_agendamentos = array_merge($todos_agendamentos, $agendamentos);
        }
    }

    // Se veio menos que o limit, é a última página
    $qtd = is_array($agendamentos ?? null) ? count($agendamentos) : (is_array($conteudo) ? count($conteudo) : 0);
    if ($qtd < $limit) {
        break;
    }

    $offset += $limit;
    echo "  ... página com $qtd registros (offset $offset)\n";
}

$total = count($todos_agendamentos);
echo "Total de agendamentos encontrados: $total\n\n";

if ($total === 0) {
    echo "Nenhum agendamento encontrado para esta data.\n";
    exit(0);
}

// ==============================================
// PASSO 3: Gerar arquivo XLSX
// ==============================================
echo "[2/3] Gerando arquivo XLSX...\n";

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Agendamentos $data_feegow");

// Cabeçalhos
$cabecalhos = [
    'A' => 'ID Agendamento',
    'B' => 'Paciente ID',
    'C' => 'Nome do Paciente',
    'D' => 'CPF',
    'E' => 'Data',
    'F' => 'Horário',
    'G' => 'Profissional',
    'H' => 'Procedimento',
    'I' => 'Local',
    'J' => 'Status',
    'K' => 'Valor',
    'L' => 'Observações',
    'M' => 'Plano',
];

// Estilo do cabeçalho
$estilo_cabecalho = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

// Escreve cabeçalhos
foreach ($cabecalhos as $col => $titulo) {
    $sheet->setCellValue($col . '1', $titulo);
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
$sheet->getRowDimension('1')->setRowHeight(20);
$sheet->getStyle('A1:' . array_key_last($cabecalhos) . '1')->applyFromArray($estilo_cabecalho);

// Escreve dados
$linha = 2;
foreach ($todos_agendamentos as $agendamento) {
    // Navega na estrutura de resposta da Feegow
    $paciente_nome = '';
    if (isset($agendamento['paciente']['nome'])) {
        $paciente_nome = $agendamento['paciente']['nome'];
    } elseif (isset($agendamento['paciente_nome'])) {
        $paciente_nome = $agendamento['paciente_nome'];
    } elseif (isset($agendamento['nome_paciente'])) {
        $paciente_nome = $agendamento['nome_paciente'];
    }

    $paciente_id = $agendamento['paciente_id']
        ?? $agendamento['paciente']['id']
        ?? $agendamento['paciente']['paciente_id']
        ?? '';

    $cpf = $agendamento['paciente']['cpf']
        ?? $agendamento['cpf']
        ?? '';

    $profissional = $agendamento['profissional']['nome']
        ?? $agendamento['profissional_nome']
        ?? $agendamento['profissional']
        ?? '';

    $procedimento = $agendamento['procedimento']['nome']
        ?? $agendamento['procedimento_nome']
        ?? $agendamento['procedimento']
        ?? '';

    $local = $agendamento['local']['nome']
        ?? $agendamento['local_nome']
        ?? $agendamento['local']
        ?? '';

    $status = $agendamento['status']['nome']
        ?? $agendamento['status_nome']
        ?? $agendamento['status']
        ?? '';

    $valor = $agendamento['valor'] ?? '';
    $observacoes = $agendamento['observacoes'] ?? $agendamento['obs'] ?? '';
    $plano = $agendamento['plano'] ?? '';

    $sheet->setCellValue('A' . $linha, $agendamento['agendamento_id'] ?? $agendamento['id'] ?? '');
    $sheet->setCellValue('B' . $linha, $paciente_id);
    $sheet->setCellValue('C' . $linha, $paciente_nome);
    $sheet->setCellValue('D' . $linha, $cpf);
    $sheet->setCellValue('E' . $linha, $agendamento['data'] ?? $data_feegow);
    $sheet->setCellValue('F' . $linha, $agendamento['horario'] ?? '');
    $sheet->setCellValue('G' . $linha, $profissional);
    $sheet->setCellValue('H' . $linha, $procedimento);
    $sheet->setCellValue('I' . $linha, $local);
    $sheet->setCellValue('J' . $linha, $status);
    $sheet->setCellValue('K' . $linha, $valor);
    $sheet->setCellValue('L' . $linha, $observacoes);
    $sheet->setCellValue('M' . $linha, $plano);

    $linha++;
}

// Estilo zebrado nas linhas de dados
$estilo_linha = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
];
for ($i = 2; $i < $linha; $i++) {
    if ($i % 2 == 0) {
        $sheet->getStyle('A' . $i . ':M' . $i)->applyFromArray($estilo_linha);
    } else {
        $sheet->getStyle('A' . $i . ':M' . $i)->applyFromArray($estilo_linha);
        $sheet->getStyle('A' . $i . ':M' . $i)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->setStartColor(['rgb' => 'F2F2F2']);
    }
}

// ==============================================
// PASSO 4: Salvar arquivo
// ==============================================
echo "[3/3] Salvando arquivo...\n";

$nome_arquivo = "agendamentos_feegow_{$data_arquivo}.xlsx";
$caminho_arquivo = $saida_pasta . DIRECTORY_SEPARATOR . $nome_arquivo;

$writer = new Xlsx($spreadsheet);
$writer->save($caminho_arquivo);

echo "\n============================================\n";
echo "           RELATÓRIO FINAL\n";
echo "============================================\n";
echo "Data consultada:     $data_feegow\n";
echo "Total agendamentos:  $total\n";
echo "Arquivo gerado:      $nome_arquivo\n";
echo "Caminho:             $caminho_arquivo\n";
echo "============================================\n";
