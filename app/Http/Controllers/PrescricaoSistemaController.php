<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prescricao;
use App\Models\PrescricaoSemana;
use App\Models\PrescricaoSemanaMedicamento;
use App\Models\PrescricaoLote;
use App\Models\PrescricaoObservacao;
use App\Models\PrescricaoLog;
use App\Models\FinanceiroParcela;
use App\Models\PrescricaoPagamento;
use App\Models\PrescricaoPagamentoForma;
use App\Models\PagamentoParcela;
use App\Models\Anexo;
use App\Models\Paciente;
use App\Models\Medicamento;
use App\Models\Combo;
use App\Models\Clinica;
use App\Models\User;
use App\Models\Estoque;
use App\Models\EstoqueAberto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Helpers\GerarPdf;

class PrescricaoSistemaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            $user = session()->get('user');
        }

        return view('sistema/prescricoes/index');
    }

    public function index_pesq()
    {
        $requestData = $_REQUEST;

        $qt_linhas = Prescricao::count();
        $retorno = Prescricao::index_pesq($requestData);

        $prescricoes = $retorno['prescricoes'];
        $totalFiltered = $retorno['totalFiltered'];

        $dados = array();
        foreach ($prescricoes as $prescricao) {
            $dado = array();

            // Situação da prescrição
            $situacao = $this->badgeSituacao($prescricao->situacao);

            // Situação financeira
            $situacao_financeira = $this->badgeSituacaoFinanceira($prescricao->situacao_financeira);

            $botao = "
            <div class='dropdown'>
                <button type='button' class='btn p-0 dropdown-toggle hide-arrow' data-bs-toggle='dropdown' aria-expanded='true'>
                    <i class='mdi mdi-dots-vertical'></i>
                </button>
                <div class='dropdown-menu' data-popper-placement='bottom-end'>
                    <a class='dropdown-item waves-effect' href='" . route('sistema.prescricoes.acessar', $prescricao->id) . "'><i class='mdi mdi-eye me-1'></i> Acessar</a>
                    <a class='dropdown-item waves-effect' href='" . route('sistema.prescricoes.imprimir_paciente', $prescricao->id) . "'><i class='mdi mdi-cloud-print me-1'></i> Imprimir Prontuário</a>
                    <a class='dropdown-item waves-effect' href='" . route('sistema.prescricoes.imprimir_cadastro', $prescricao->id) . "'><i class='mdi mdi-folder-open me-1'></i> Imprimir Cadastro</a>
                    <a class='dropdown-item waves-effect' href='" . route('sistema.prescricoes.imprimir_detalhes', $prescricao->id) . "' target='_blank'><i class='mdi mdi-printer me-1'></i> Imprimir Detalhes</a>
                </div>
            </div>
            ";

            $dado[] = $botao;
            $dado[] = $prescricao->data_prescricao ? dataDbForm($prescricao->data_prescricao) : '-';
            $dado[] = $prescricao->paciente->nm_paciente ?? '-';
            $dado[] = $prescricao->paciente->dt_nascimento ? dataDbForm($prescricao->paciente->dt_nascimento) : '-';

            $semana_aplicada = $prescricao->get_semana_aplicada();
            $dado[] = $semana_aplicada ? $semana_aplicada->nr_semana . '/' . $prescricao->qt_semanas : '0/' . $prescricao->qt_semanas;

            $semana_aplicar = $prescricao->get_semana_aplicar();
            $dado[] = $semana_aplicar && $semana_aplicar->data_prevista ? dataDbForm($semana_aplicar->data_prevista) : '-';

            $dado[] = $prescricao->medico ?? '-';
            $dado[] = $prescricao->tipo_atendimento ?? '-';
            $dado[] = 'R$ ' . number_format($prescricao->valor_tratamento, 2, ',', '.');
            $dado[] = $situacao;
            $dado[] = $situacao_financeira;

            $dado[] = $prescricao->userCadastro->nome ?? '-';

            $ultima_edicao = $prescricao->get_ultima_edicao();
            $dado[] = $ultima_edicao ? date('d/m/Y H:i:s', strtotime($ultima_edicao)) : '-';

            $dados[] = $dado;
        }

        $json_data = array(
            "draw" => intval($requestData['draw']),
            "recordsTotal" => intval($qt_linhas),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $dados
        );

        echo json_encode($json_data);
    }

    public function adicionar()
    {
        $user = auth()->user();
        if (!$user) {
            $user = session()->get('user');
        }

        $api = api();
        $medicos = $api->get_medicos();
        $medicamentos = Medicamento::all()->sortBy('nome');
        $combos = Combo::all()->sortBy('nome');

        return view('sistema/prescricoes/adicionar', compact('medicos', 'medicamentos', 'combos'));
    }

    public function insert(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                $user = session()->get('user');
            }

            $contador_procedimentos = intval($request->contador_procedimentos ?? 0);

            // ---- monta as semanas e valida - (mesma regra do preview do front) ----
            $precisa_anexo = false;
            $semanasDados = [];

            for ($i = 1; $i <= $contador_procedimentos; $i++) {
                $data_prevista = $request->{'data_prevista_' . $i} ?? null;
                $pausa = ($request->{'pausa_' . $i} ?? null) === 'true';
                $obs = $request->{'obs_' . $i} ?? null;

                if ($data_prevista && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_prevista)) {
                    throw new \Exception("Semana {$i}: Data Prevista inválida.");
                }
                if ($data_prevista && $data_prevista < date('Y-m-d')) {
                    throw new \Exception("Semana {$i}: a Data Prevista ({$data_prevista}) está no passado. Não é permitido cadastrar prescrição com data retroativa.");
                }

                $contador = intval($request->{'contador_medicamentos_' . $i} ?? 0);
                $meds = [];
                for ($j = 1; $j <= $contador; $j++) {
                    $medicamento_id = $request->{'medicamento_id_' . $i . '_' . $j} ?? null;
                    if (!$medicamento_id) {
                        continue;
                    }
                    $quantidade = (float) str_replace(',', '.', $request->{'quantidade_' . $i . '_' . $j} ?? 1);
                    if ($quantidade <= 0) {
                        $quantidade = 1;
                    }

                    $med = Medicamento::find($medicamento_id);
                    $meds[] = [
                        'medicamento_id' => $medicamento_id,
                        'quantidade' => $quantidade,
                        'gera_aplicacao' => $med && $med->aplicacao == 'Sim',
                        'is_soro' => $med && str_starts_with(strtolower($med->nome), 'soro'),
                        'nome' => $med ? $med->nome : ('Medicamento #' . $medicamento_id),
                    ];

                    if ($med && in_array($med->unidade, ['Ampola', 'Miligrama'])) {
                        $precisa_anexo = true;
                    }
                }

                // ignora semana completamente vazia (sem data, sem pausa e sem medicação)
                if (!$data_prevista && !$pausa && count($meds) === 0) {
                    continue;
                }

                // toda semana mantida precisa de Data Prevista (coluna NOT NULL)
                if (!$data_prevista) {
                    throw new \Exception("Semana {$i}: informe a Data Prevista.");
                }

                $semanasDados[] = compact('data_prevista', 'pausa', 'obs', 'meds');
            }

            if (!$request->paciente_id) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'Informe o paciente.');
            }
            if (count($semanasDados) === 0) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'Adicione pelo menos uma semana com data, pausa ou medicação.');
            }
            if ($precisa_anexo && !$request->hasFile('anexos')) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'É obrigatório inserir o anexo (prescrição médica) pois a prescrição contém medicamentos em Ampola ou Miligrama.');
            }

            // obs do paciente
            if ($request->paciente_obs) {
                $paciente = Paciente::find($request->paciente_id);
                if ($paciente) {
                    $paciente->obs = $request->paciente_obs;
                    $paciente->save();
                }
            }

            $valor_tratamento = (float) valorFormDb($request->valor_tratamento ?? '0');
            $credito_em_aberto = (float) valorFormDb($request->credito_em_aberto ?? '0');
            // valor efetivamente a parcelar = tratamento - crédito em aberto
            $valor_parcelar = max(0, round($valor_tratamento - $credito_em_aberto, 2));

            $prescricaoId = null;
            DB::transaction(function () use ($request, $user, $semanasDados, $valor_tratamento, $credito_em_aberto, $valor_parcelar, &$prescricaoId) {
                $prescricao = Prescricao::create([
                    'paciente_id' => $request->paciente_id,
                    'clinica_id' => $user->clinica_id ?? null,
                    'user_id_cadastro' => $user->id ?? null,
                    'medico' => $request->medico,
                    'tipo_atendimento' => $request->tipo_atendimento,
                    'agendamento' => $request->agendamento,
                    'obs' => null,
                    'data_prescricao' => date('Y-m-d'),
                    'qt_semanas' => 0,
                    'qt_semanas_aplicacao' => 0,
                    'qt_parcelas' => 0,
                    'semana_atual' => 0,
                    'valor_tratamento' => $valor_tratamento,
                    'credito_em_aberto' => $credito_em_aberto,
                    'situacao' => 'Agendada',
                    'situacao_financeira' => 'Em Aberto',
                ]);
                $prescricaoId = $prescricao->id;

                $nr_semana = 0;
                $qt_semanas_aplicacao = 0;
                $semanasParcela = []; // semanas que geram parcela (não pausa + com medicação)

                foreach ($semanasDados as $d) {
                    $nr_semana++;

                    $tem_aplicacao = false;
                    foreach ($d['meds'] as $m) {
                        if ($m['gera_aplicacao']) {
                            $tem_aplicacao = true;
                            break;
                        }
                    }
                    $tem_aplicacao = $tem_aplicacao && !$d['pausa'];
                    if ($tem_aplicacao) {
                        $qt_semanas_aplicacao++;
                    }

                    $semana = PrescricaoSemana::create([
                        'prescricao_id' => $prescricao->id,
                        'nr_semana' => $nr_semana,
                        'data_prevista' => $d['data_prevista'] ?: null,
                        'data_aplicada' => null,
                        'tem_aplicacao' => $tem_aplicacao,
                        'situacao' => 'Agendada',
                        'obs' => $d['obs'] ?: null,
                    ]);

                    $this->registrar_log($prescricao->id, 'semana', $semana->id, 'Criação', 'Semana ' . $nr_semana . ' criada' . ($d['pausa'] ? ' (pausa)' : '') . ($d['obs'] ? ' — obs: ' . $d['obs'] : ''));

                    foreach ($d['meds'] as $m) {
                        $med = PrescricaoSemanaMedicamento::create([
                            'prescricao_semana_id' => $semana->id,
                            'medicamento_id' => $m['medicamento_id'],
                            'combo_id' => null,
                            'clinica_id_aplicacao' => $user->clinica_id ?? null,
                            'is_soro' => $m['is_soro'],
                            'gera_aplicacao' => $m['gera_aplicacao'],
                            'quantidade' => $m['quantidade'],
                            'situacao' => 'Aberta',
                            'data_prevista' => $d['data_prevista'] ?: null,
                        ]);
                        $this->registrar_log($prescricao->id, 'semana', $semana->id, 'Adição de Medicamento', 'Medicação "' . $m['nome'] . '" (qtd ' . $m['quantidade'] . ') adicionada na semana ' . $nr_semana);
                    }

                    if (!$d['pausa'] && count($d['meds']) > 0) {
                        $semanasParcela[] = ['semana' => $semana, 'data_prevista' => $d['data_prevista']];
                    }
                }

                // parcelas (mesma regra do preview: uma por semana com medicação, pausa fora)
                // o valor a parcelar já vem descontado do crédito em aberto
                $total = count($semanasParcela);
                if ($total > 0 && $valor_parcelar > 0) {
                    $base = floor(($valor_parcelar / $total) * 100) / 100;
                    $resto = round($valor_parcelar - $base * $total, 2);
                    $nr_parcela = 0;
                    foreach ($semanasParcela as $idx => $sp) {
                        $nr_parcela++;
                        $valor = $idx === $total - 1 ? round($base + $resto, 2) : $base;
                        $parcela = FinanceiroParcela::create([
                            'prescricao_id' => $prescricao->id,
                            'prescricao_semana_id' => $sp['semana']->id,
                            'nr_parcela' => $nr_parcela,
                            'valor_parcela' => $valor,
                            'valor_pago' => 0,
                            'situacao' => 'Em Aberto',
                            'dt_vencimento' => $sp['data_prevista'] ?: null,
                        ]);
                        $this->registrar_log($prescricao->id, 'financeiro', $parcela->id, 'Financeiro', 'Parcela ' . $nr_parcela . ' gerada (Semana ' . $sp['semana']->nr_semana . ') — R$ ' . number_format($valor, 2, ',', '.'));
                    }
                }

                // anexos (prescrição médica)
                if ($request->hasFile('anexos')) {
                    foreach ($request->file('anexos') as $arquivo) {
                        if ($arquivo && $arquivo->isValid()) {
                            // capturar mime ANTES do move (após mover, o tmp não existe mais)
                            $extensao = strtolower($arquivo->extension());
                            $mime = $arquivo->getMimeType();
                            $nm_arquivo = str_replace('.' . $extensao, '', $arquivo->getClientOriginalName());
                            $arquivo_link = $arquivo->getClientOriginalName();
                            $arquivo->move(public_path('prescricoes/' . $prescricao->id . '/anexos/'), $arquivo_link);
                            $anexo = Anexo::create([
                                'tipo' => 'prescricao_medica',
                                'prescricao_id' => $prescricao->id,
                                'pagamento_id' => null,
                                'user_id' => $user->id ?? null,
                                'nm_anexo' => $nm_arquivo,
                                'arquivo' => $arquivo_link,
                                'mime' => $mime,
                                'extensao' => $extensao,
                            ]);
                            $this->registrar_log($prescricao->id, 'anexo', $anexo->id, 'Anexo', 'Anexo "' . $nm_arquivo . '" adicionado');
                        }
                    }
                }

                // atualiza contadores do mestre
                $prescricao->qt_semanas = count($semanasDados);
                $prescricao->qt_semanas_aplicacao = $qt_semanas_aplicacao;
                $prescricao->qt_parcelas = ($total > 0 && $valor_parcelar > 0) ? count($semanasParcela) : 0;
                $prescricao->save();

                $this->registrar_log(
                    $prescricao->id,
                    'prescricao',
                    $prescricao->id,
                    'Criação',
                    'Prescrição cadastrada — paciente ' . ($prescricao->paciente->nm_paciente ?? '') . ' (' . count($semanasDados) . ' semanas, ' . (($total > 0 && $valor_parcelar > 0) ? count($semanasParcela) : 0) . ' parcelas)',
                    null,
                    [
                        'paciente' => $prescricao->paciente->nm_paciente ?? null,
                        'medico' => $request->medico,
                        'tipo_atendimento' => $request->tipo_atendimento,
                        'valor_tratamento' => $valor_tratamento,
                        'credito_em_aberto' => $credito_em_aberto,
                        'qt_semanas' => count($semanasDados),
                        'qt_parcelas' => ($total > 0 && $valor_parcelar > 0) ? count($semanasParcela) : 0,
                    ]
                );
            });

            return redirect()->route('sistema.prescricoes.acessar', $prescricaoId)
                ->with('mensagem', 'Prescrição cadastrada com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function acessar($id)
    {
        $user = auth()->user();
        if (!$user) {
            $user = session()->get('user');
        }

        $prescricao = Prescricao::with([
            'paciente',
            'clinica',
            'userCadastro',
            'semanas.medicamentos.medicamento',
            'semanas.observacoes',
            'semanas.parcela',
            'parcelas',
            'pagamentos.formas',
            'anexos',
        ])->find($id);

        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        return view('sistema/prescricoes/acessar', compact('prescricao'));
    }

    public function acessar_semana($id)
    {
        $semana = PrescricaoSemana::with('prescricao.paciente', 'medicamentos.medicamento', 'medicamentos.lotes', 'observacoes.user')->find($id);

        if (!$semana) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Semana não encontrada.');
        }

        // demais semanas da mesma prescrição (para navegação)
        $semanas = $semana->prescricao->semanas()->with('medicamentos.medicamento')->get();

        // financeiro da semana (parcela)
        $parcela = FinanceiroParcela::where('prescricao_semana_id', $semana->id)->first();
        $semana_paga = $this->semana_esta_paga($semana, $parcela);

        // logs desta semana
        $logs = PrescricaoLog::with('user')
            ->where('prescricao_id', $semana->prescricao_id)
            ->where('entidade', 'semana')
            ->where('entidade_id', $semana->id)
            ->orderByDesc('created_at')
            ->get();

        // regra de fila: a semana anterior (com medicação) precisa estar Aplicada/Aplicação Parcial
        $motivo_fila = null;
        $pode_enviar_fila = $this->pode_enviar_para_fila($semana, $motivo_fila);

        return view('sistema/prescricoes/acessar_semana', compact('semana', 'semanas', 'parcela', 'semana_paga', 'logs', 'pode_enviar_fila', 'motivo_fila'));
    }

    public function financeiro($prescricao_id)
    {
        $prescricao = Prescricao::with('paciente', 'clinica', 'parcelas.semana', 'pagamentos.formas', 'pagamentos.user', 'anexos')->find($prescricao_id);

        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        // dados dos pagamentos para o modal de edição (JSON)
        $pagamentos_json = $prescricao->pagamentos->map(function ($p) {
            return [
                'id' => $p->id,
                'dt_pagamento' => $p->dt_pagamento,
                'obs' => $p->obs,
                'formas' => $p->formas->map(function ($f) {
                    return [
                        'forma' => $f->forma_pagamento,
                        'valor' => (float) $f->vl_pagamento,
                        'parcelas' => (int) $f->parcelas,
                        'id_transacao' => $f->id_transacao,
                    ];
                })->values(),
            ];
        })->values();

        // logs financeiros da prescrição
        $logs_financeiro = PrescricaoLog::with('user')
            ->where('prescricao_id', $prescricao_id)
            ->where('entidade', 'financeiro')
            ->orderByDesc('created_at')
            ->get();

        return view('sistema/prescricoes/financeiro', compact('prescricao', 'pagamentos_json', 'logs_financeiro'));
    }

    public function lancar_pagamento(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $parcela = FinanceiroParcela::find($request->parcela_id);
            if (!$parcela) {
                throw new \Exception('Parcela não encontrada.');
            }
            $prescricao_id = $parcela->prescricao_id;

            // formas de pagamento
            [$formas, $vl_total] = $this->coletar_formas($request);
            if (count($formas) === 0) {
                throw new \Exception('Informe pelo menos uma forma de pagamento com valor maior que zero.');
            }

            // não pode pagar mais do que falta nesta parcela
            $saldo_parcela = max(0, (float) $parcela->valor_parcela - (float) $parcela->valor_pago);
            if ($vl_total > $saldo_parcela + 0.005) {
                throw new \Exception('O valor do pagamento (R$ ' . number_format($vl_total, 2, ',', '.') . ') é maior que o saldo da parcela desta semana (R$ ' . number_format($saldo_parcela, 2, ',', '.') . '). Informe um valor menor ou igual ao saldo.');
            }

            DB::transaction(function () use ($request, $user, $parcela, $prescricao_id, $formas, $vl_total) {
                $pagamento = PrescricaoPagamento::create([
                    'prescricao_id' => $prescricao_id,
                    'dt_pagamento' => $request->dt_pagamento ?: date('Y-m-d'),
                    'vl_total' => $vl_total,
                    'obs' => $request->obs_pagamento ?? null,
                    'user_id' => $user->id ?? null,
                ]);

                $this->gravar_formas($pagamento, $formas, $prescricao_id, $user);

                // aplica o pagamento somente na parcela desta semana (não pode exceder o saldo)
                PagamentoParcela::create([
                    'pagamento_id' => $pagamento->id,
                    'financeiro_parcela_id' => $parcela->id,
                    'valor' => $vl_total,
                ]);
                $novo_pago = round((float) $parcela->valor_pago + $vl_total, 2);
                $parcela->valor_pago = $novo_pago;
                $parcela->situacao = $novo_pago >= (float) $parcela->valor_parcela - 0.005 ? 'Paga' : 'Parcial';
                $parcela->save();

                $this->recalcular_situacao_financeira($prescricao_id);

                $nr_semana = $parcela->semana->nr_semana ?? '-';
                $this->registrar_log($prescricao_id, 'financeiro', $pagamento->id, 'Pagamento', 'Pagamento de R$ ' . number_format($vl_total, 2, ',', '.') . ' registrado (Semana ' . $nr_semana . ')');
            });

            return redirect()->route('sistema.prescricoes.acessar_semana', $parcela->prescricao_semana_id)
                ->with('mensagem', 'Pagamento registrado com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', $e->getMessage());
        }
    }

    // ---------- helpers de pagamento ----------

    private function coletar_formas($request)
    {
        $formas = [];
        $vl_total = 0;
        $contador = intval($request->contador_formas ?? 0);
        for ($i = 1; $i <= $contador; $i++) {
            $forma = $request->{'forma_pagamento_' . $i} ?? null;
            if (!$forma) {
                continue;
            }
            $vl = (float) valorFormDb($request->{'vl_pagamento_' . $i} ?? '0');
            if ($vl <= 0) {
                continue;
            }
            $parcelas = in_array($forma, ['Crédito', 'Link de Pagamento']) ? max(1, intval($request->{'parcelas_' . $i} ?? 1)) : 1;
            $formas[] = [
                'forma_pagamento' => $forma,
                'vl_pagamento' => $vl,
                'parcelas' => $parcelas,
                'id_transacao' => $request->{'id_transacao_' . $i} ?? null,
                'arquivo' => $request->file('arquivo_' . $i),
            ];
            $vl_total += $vl;
        }
        return [$formas, round($vl_total, 2)];
    }

    private function gravar_formas($pagamento, $formas, $prescricao_id, $user)
    {
        foreach ($formas as $f) {
            PrescricaoPagamentoForma::create([
                'pagamento_id' => $pagamento->id,
                'forma_pagamento' => $f['forma_pagamento'],
                'vl_pagamento' => $f['vl_pagamento'],
                'parcelas' => $f['parcelas'],
                'id_transacao' => $f['id_transacao'],
                'obs' => null,
            ]);

            // comprovante anexado à forma de pagamento
            $arquivo = $f['arquivo'] ?? null;
            if ($arquivo && $arquivo->isValid()) {
                $extensao = strtolower($arquivo->extension());
                $mime = $arquivo->getMimeType(); // capturar ANTES do move (tmp some)
                $nm_arquivo = str_replace('.' . $extensao, '', $arquivo->getClientOriginalName());
                $arquivo_link = $arquivo->getClientOriginalName();
                $arquivo->move(public_path('prescricoes/' . $prescricao_id . '/comprovantes/'), $arquivo_link);
                Anexo::create([
                    'tipo' => 'comprovante_pagamento',
                    'prescricao_id' => $prescricao_id,
                    'pagamento_id' => $pagamento->id,
                    'user_id' => $user->id ?? null,
                    'nm_anexo' => $nm_arquivo,
                    'arquivo' => $arquivo_link,
                    'mime' => $mime,
                    'extensao' => $extensao,
                ]);
            }
        }
    }

    public function registrar_pagamento(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $prescricao = Prescricao::find($request->prescricao_id);
            if (!$prescricao) {
                throw new \Exception('Prescrição não encontrada.');
            }
            $prescricao_id = $prescricao->id;

            [$formas, $vl_total] = $this->coletar_formas($request);
            if (count($formas) === 0) {
                throw new \Exception('Informe pelo menos uma forma de pagamento com valor maior que zero.');
            }

            $modo = intval($request->modo_pagamento ?? 2);

            $parcelas = FinanceiroParcela::where('prescricao_id', $prescricao_id)->orderBy('nr_parcela')->get();
            $abertas = $parcelas->filter(fn($p) => (float) $p->valor_parcela - (float) $p->valor_pago > 0.005);
            if ($abertas->count() === 0) {
                throw new \Exception('Não há parcelas em aberto para receber o pagamento.');
            }

            if ($modo == 1) {
                // reestrutura: valor informado vira a 1ª parcela aberta
                // o valor efetivamente devido é o tratamento MENOS o crédito em aberto
                $valor_devido = round((float) $prescricao->valor_tratamento - (float) $prescricao->credito_em_aberto, 2);
                $total_pago_existente = round($parcelas->sum('valor_pago'), 2);
                $restante_tratamento = round($valor_devido - $total_pago_existente, 2);
                if ($vl_total > $restante_tratamento + 0.005) {
                    throw new \Exception('O valor do pagamento (R$ ' . number_format($vl_total, 2, ',', '.') . ') é maior que o valor restante do tratamento (R$ ' . number_format($restante_tratamento, 2, ',', '.') . ').');
                }
            } else {
                // parcela por parcela: não pode ultrapassar o total em aberto
                $total_aberto = round($abertas->sum(fn($p) => max(0, (float) $p->valor_parcela - (float) $p->valor_pago)), 2);
                if ($vl_total > $total_aberto + 0.005) {
                    throw new \Exception('O valor do pagamento (R$ ' . number_format($vl_total, 2, ',', '.') . ') é maior que o total em aberto das parcelas (R$ ' . number_format($total_aberto, 2, ',', '.') . ').');
                }
            }

            DB::transaction(function () use ($request, $user, $prescricao, $prescricao_id, $formas, $vl_total, $modo, $parcelas) {
                $pagamento = PrescricaoPagamento::create([
                    'prescricao_id' => $prescricao_id,
                    'dt_pagamento' => $request->dt_pagamento ?: date('Y-m-d'),
                    'vl_total' => $vl_total,
                    'obs' => $request->obs_pagamento ?? null,
                    'user_id' => $user->id ?? null,
                ]);

                $this->gravar_formas($pagamento, $formas, $prescricao_id, $user);

                if ($modo == 1) {
                    $this->aplicar_pagamento_reestruturar($pagamento, $prescricao, $parcelas, $vl_total);
                } else {
                    $this->aplicar_pagamento_sequencial($pagamento, $parcelas, $vl_total);
                }

                $this->recalcular_situacao_financeira($prescricao_id);

                $descricao = 'Pagamento de R$ ' . number_format($vl_total, 2, ',', '.') . ' registrado (' . ($modo == 1 ? 'reestruturado' : 'parcela a parcela') . ')';
                $this->registrar_log($prescricao_id, 'financeiro', $pagamento->id, 'Pagamento', $descricao);
            });

            return redirect()->route('sistema.prescricoes.financeiro', $prescricao_id)
                ->with('mensagem', 'Pagamento registrado com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', $e->getMessage());
        }
    }

    // ---------- editar / excluir pagamento ----------

    private function estornar_pagamento_parcelas($pagamento)
    {
        foreach ($pagamento->parcelas as $pp) {
            $parcela = $pp->financeiroParcela;
            if (!$parcela) {
                continue;
            }
            $novo_pago = round(max(0, (float) $parcela->valor_pago - (float) $pp->valor), 2);
            $parcela->valor_pago = $novo_pago;
            $parcela->situacao = $novo_pago >= (float) $parcela->valor_parcela - 0.005 ? 'Paga' : ($novo_pago > 0 ? 'Parcial' : 'Em Aberto');
            $parcela->save();
        }
    }

    private function restaurar_parcelas_snapshot($pagamento)
    {
        $snapshot = $pagamento->snapshot_parcelas;
        if (!is_array($snapshot) || count($snapshot) === 0) {
            // pagamento NÃO reestruturado: estorno clássico apenas do valor_pago
            $this->estornar_pagamento_parcelas($pagamento);
            return;
        }
        // pagamento reestruturado: restaura o estado completo das parcelas (valor_parcela, valor_pago e situação)
        foreach ($snapshot as $item) {
            $parcela = FinanceiroParcela::find($item['id'] ?? null);
            if (!$parcela) {
                continue;
            }
            $parcela->valor_parcela = $item['valor_parcela'] ?? $parcela->valor_parcela;
            $parcela->valor_pago = $item['valor_pago'] ?? 0;
            $parcela->situacao = $item['situacao']
                ?? ($parcela->valor_pago >= (float) $parcela->valor_parcela - 0.005 ? 'Paga' : ($parcela->valor_pago > 0 ? 'Parcial' : 'Em Aberto'));
            $parcela->save();
        }
    }

    private function apagar_registros_pagamento($pagamento)
    {
        foreach ($pagamento->anexos as $anexo) {
            $path = public_path('prescricoes/' . $pagamento->prescricao_id . '/comprovantes/' . $anexo->arquivo);
            if (is_file($path)) {
                @unlink($path);
            }
            $anexo->delete();
        }
        PrescricaoPagamentoForma::where('pagamento_id', $pagamento->id)->delete();
        PagamentoParcela::where('pagamento_id', $pagamento->id)->delete();
    }

    public function excluir_pagamento(Request $request)
    {
        try {
            $pagamento = PrescricaoPagamento::with('parcelas.financeiroParcela', 'anexos')->find($request->pagamento_id);
            if (!$pagamento) {
                throw new \Exception('Pagamento não encontrado.');
            }
            $prescricao_id = $pagamento->prescricao_id;
            $vl_estornado = $pagamento->vl_total;

            // Regra LIFO: somente o ÚLTIMO pagamento da prescrição pode ser excluído.
            // Os pagamentos posteriores foram calculados sobre o estado que este pagamento criou;
            // excluí-lo fora de ordem deixaria as parcelas inconsistentes.
            $ultimo = PrescricaoPagamento::where('prescricao_id', $prescricao_id)->orderByDesc('id')->first();
            if (!$ultimo || $ultimo->id != $pagamento->id) {
                throw new \Exception('Somente o último pagamento pode ser excluído. Para excluir este, exclua antes os pagamentos mais recentes.');
            }

            DB::transaction(function () use ($pagamento, $prescricao_id, $vl_estornado) {
                $this->restaurar_parcelas_snapshot($pagamento);
                $this->apagar_registros_pagamento($pagamento);
                $this->registrar_log($prescricao_id, 'financeiro', $pagamento->id, 'Pagamento', 'Pagamento excluído (estorno de R$ ' . number_format($vl_estornado, 2, ',', '.') . ')');
                $pagamento->delete();
                $this->recalcular_situacao_financeira($prescricao_id);
            });

            return redirect()->back()->with('mensagem', 'Pagamento excluído e valores estornados.');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao excluir pagamento: ' . $e->getMessage());
        }
    }

    public function update_pagamento(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $pagamento = PrescricaoPagamento::with('formas')->find($request->pagamento_id);
            if (!$pagamento) {
                throw new \Exception('Pagamento não encontrado.');
            }

            DB::transaction(function () use ($request, $user, $pagamento) {
                // edita SOMENTE dados não críticos (data, obs e detalhes das formas).
                // NÃO mexe no valor, NÃO estorna e NÃO reaplica nas parcelas.
                $pagamento->dt_pagamento = $request->dt_pagamento ?: $pagamento->dt_pagamento;
                $pagamento->obs = $request->obs_pagamento ?? null;
                $pagamento->save();

                $formas = $pagamento->formas->values();
                $i = 0;
                foreach ($formas as $forma) {
                    $i++;
                    $forma->forma_pagamento = $request->{'forma_pagamento_' . $i} ?? $forma->forma_pagamento;
                    $forma->parcelas = in_array($forma->forma_pagamento, ['Crédito', 'Link de Pagamento'])
                        ? max(1, intval($request->{'parcelas_' . $i} ?? $forma->parcelas))
                        : 1;
                    $forma->id_transacao = $request->{'id_transacao_' . $i} ?? $forma->id_transacao;
                    $forma->save();

                    // novo comprovante, se enviado (mantém os antigos)
                    $arquivo = $request->file('arquivo_' . $i);
                    if ($arquivo && $arquivo->isValid()) {
                        $extensao = strtolower($arquivo->extension());
                        $mime = $arquivo->getMimeType();
                        $nm_arquivo = str_replace('.' . $extensao, '', $arquivo->getClientOriginalName());
                        $arquivo_link = $arquivo->getClientOriginalName();
                        $arquivo->move(public_path('prescricoes/' . $pagamento->prescricao_id . '/comprovantes/'), $arquivo_link);
                        Anexo::create([
                            'tipo' => 'comprovante_pagamento',
                            'prescricao_id' => $pagamento->prescricao_id,
                            'pagamento_id' => $pagamento->id,
                            'user_id' => $user->id ?? null,
                            'nm_anexo' => $nm_arquivo,
                            'arquivo' => $arquivo_link,
                            'mime' => $mime,
                            'extensao' => $extensao,
                        ]);
                    }
                }

                $this->registrar_log($pagamento->prescricao_id, 'financeiro', $pagamento->id, 'Pagamento', 'Pagamento editado (dados), valor mantido R$ ' . number_format($pagamento->vl_total, 2, ',', '.'));
            });

            return redirect()->back()->with('mensagem', 'Pagamento atualizado com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao atualizar pagamento: ' . $e->getMessage());
        }
    }

    private function aplicar_pagamento_sequencial($pagamento, $parcelas, $vl_total)
    {
        $restante = $vl_total;
        foreach ($parcelas as $p) {
            if ($restante <= 0) {
                break;
            }
            $saldo = max(0, (float) $p->valor_parcela - (float) $p->valor_pago);
            if ($saldo <= 0.005) {
                continue;
            }
            $aplicar = min($restante, $saldo);
            PagamentoParcela::create([
                'pagamento_id' => $pagamento->id,
                'financeiro_parcela_id' => $p->id,
                'valor' => round($aplicar, 2),
            ]);
            $novo_pago = round((float) $p->valor_pago + $aplicar, 2);
            $p->valor_pago = $novo_pago;
            $p->situacao = $novo_pago >= (float) $p->valor_parcela - 0.005 ? 'Paga' : 'Parcial';
            $p->save();
            $restante = round($restante - $aplicar, 2);
        }
    }

    private function aplicar_pagamento_reestruturar($pagamento, $prescricao, $parcelas, $vl_total)
    {
        // snapshot do estado COMPLETO das parcelas ANTES da reestruturação,
        // para permitir restaurar os valores originais ao excluir o pagamento (LIFO).
        $pagamento->snapshot_parcelas = $parcelas->map(fn($p) => [
            'id' => $p->id,
            'valor_parcela' => (float) $p->valor_parcela,
            'valor_pago' => (float) $p->valor_pago,
            'situacao' => $p->situacao,
        ])->values()->toArray();
        $pagamento->save();

        $abertas = $parcelas->filter(fn($p) => (float) $p->valor_parcela - (float) $p->valor_pago > 0.005)->values();
        $first = $abertas->first();
        $demais = $abertas->slice(1)->values();

        // total já pago ANTES deste pagamento (não pode incluir o valor atual)
        $total_pago_existente = round($parcelas->sum('valor_pago'), 2);

        // 1ª parcela aberta recebe o valor informado (soma ao que já tinha) e é quitada
        $first->valor_pago = round((float) $first->valor_pago + $vl_total, 2);
        $first->valor_parcela = $first->valor_pago;
        $first->situacao = 'Paga';
        $first->save();

        PagamentoParcela::create([
            'pagamento_id' => $pagamento->id,
            'financeiro_parcela_id' => $first->id,
            'valor' => $vl_total,
        ]);

        // divide a diferença (restante do valor devido = tratamento - crédito em aberto) nas demais parcelas abertas
        $valor_devido = round((float) $prescricao->valor_tratamento - (float) $prescricao->credito_em_aberto, 2);
        $restante = round($valor_devido - ($total_pago_existente + $vl_total), 2);
        $n = $demais->count();
        if ($n > 0) {
            if ($restante > 0) {
                $base = floor(($restante / $n) * 100) / 100;
                $resto = round($restante - $base * $n, 2);
                foreach ($demais as $idx => $p) {
                    $valor = $idx === $n - 1 ? round($base + $resto, 2) : $base;
                    $p->valor_parcela = $valor;
                    $p->valor_pago = 0;
                    $p->situacao = 'Em Aberto';
                    $p->save();
                }
            } else {
                foreach ($demais as $p) {
                    $p->valor_parcela = 0;
                    $p->valor_pago = 0;
                    $p->situacao = 'Paga';
                    $p->save();
                }
            }
        }
    }

    private function recalcular_situacao_financeira($prescricao_id)
    {
        $prescricao = Prescricao::find($prescricao_id);
        if (!$prescricao) {
            return;
        }
        // valor efetivamente devido (tratamento MENOS o crédito em aberto)
        $valor = round((float) $prescricao->valor_tratamento - (float) $prescricao->credito_em_aberto, 2);
        $total_pago = (float) $prescricao->parcelas()->sum('valor_pago');
        $sit = 'Em Aberto';
        if ($prescricao->situacao === 'Cancelada') {
            $sit = 'Cancelado';
        } elseif ($valor > 0 && $total_pago >= $valor - 0.005) {
            $sit = 'Pago';
        } elseif ($total_pago > 0) {
            $sit = 'Parcial';
        }
        $prescricao->situacao_financeira = $sit;
        $prescricao->save();
    }

    public function dash()
    {
        $user = auth()->user();
        if (!$user) {
            $user = session()->get('user');
        }
        $clinica_id = $user->clinica_id;

        $base = function ($situacao) use ($clinica_id) {
            return PrescricaoSemana::with([
                    'prescricao' => function ($q) {
                        $q->withCount('semanas')->with('paciente');
                    },
                    'medicamentos.medicamento',
                    'userAplicacao',
                ])
                ->whereHas('prescricao', function ($q) use ($clinica_id) {
                    $q->where('clinica_id', $clinica_id);
                })
                ->whereIn('situacao', (array) $situacao)
                ->orderBy('updated_at')
                ->get()
                ->filter(function ($s) {
                    return $s->prescricao && $s->prescricao->paciente;
                })
                ->values();
        };

        // Aguardando (fila de aplicação)
        $fila = $base('Fila de Aplicação');

        // Atendimentos
        $em_atendimento = $base('Em Atendimento');

        // Aplicadas do dia
        $atendidos_dia = $base(['Aplicada', 'Aplicação Parcial'])
            ->filter(function ($s) {
                return $s->data_aplicada == date('Y-m-d');
            })
            ->values();

        // Atendimentos do dia por enfermeira (somente quem teve aplicação REAL de medicamento)
        $atendimentos_por_enfermeira = $atendidos_dia
            ->filter(function ($s) {
                // só conta se a semana teve pelo menos um medicamento efetivamente aplicado
                return $s->medicamentos->contains(function ($m) {
                    return $m->situacao == 'Aplicada' && $m->user_id_aplicacao;
                });
            })
            ->groupBy(function ($s) {
                return $s->user_id_aplicacao;
            })
            ->map(function ($semanas, $userId) {
                $user = $semanas->first()->userAplicacao;
                return [
                    'user_id' => $userId,
                    'nome' => $user ? $user->nome : 'Enfermeiro(a) não identificado',
                    'qtd' => $semanas->count(),
                ];
            })
            ->sortByDesc('qtd')
            ->values();

        return view('sistema/prescricoes/dash', compact('fila', 'em_atendimento', 'atendidos_dia', 'atendimentos_por_enfermeira', 'user'));
    }

    public function iniciar_atendimento($id)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $semana = PrescricaoSemana::find($id);
            if (!$semana) {
                throw new \Exception('Semana não encontrada.');
            }

            if (in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial', 'Cancelada'])) {
                return redirect()->route('sistema.prescricoes.acessar_semana', $id);
            }

            if ($semana->situacao != 'Fila de Aplicação' && $semana->user_id_aplicacao != $user->id) {
                return redirect()->route('sistema.dash')->with('mensagem_erro', 'Este paciente já está sendo atendido!');
            }

            if (!$this->semana_esta_paga($semana) && floatval($semana->prescricao->valor_tratamento) > 0) {
                return redirect()->route('sistema.dash')->with('mensagem_erro', 'Esta semana não está paga para fazer a aplicação.');
            }

            $semana->situacao = 'Em Atendimento';
            $semana->dt_hr_atendimento = now();
            $semana->user_id_aplicacao = $user->id;
            $semana->save();

            $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Atendimento', 'Atendimento iniciado por ' . $user->nome);

            return redirect()->route('sistema.prescricoes.enfermagem_acessar', $id)->with('mensagem', 'Atendimento iniciado!');
        } catch (\Exception $e) {
            return redirect()->route('sistema.dash')->with('mensagem_erro', $e->getMessage());
        }
    }

    // ---------- APLICAÇÃO (ENFERMAGEM) ----------

    public function enfermagem_acessar($id)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $semana = PrescricaoSemana::with('prescricao.paciente', 'prescricao.anexos')->find($id);
            if (!$semana) {
                throw new \Exception('Semana não encontrada.');
            }
            $prescricao = $semana->prescricao;

            if (in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial', 'Cancelada'])) {
                return redirect()->route('sistema.prescricoes.acessar_semana', $id);
            }

            // LOTE: todas as semanas da prescrição que estão em Fila de Aplicação / Em Atendimento
            // (caso de entrega de várias semanas de uma vez — uma tabela por semana na tela)
            $semanas = PrescricaoSemana::with('medicamentos.medicamento', 'medicamentos.lotes', 'prescricao.anexos')
                ->where('prescricao_id', $semana->prescricao_id)
                ->whereIn('situacao', ['Fila de Aplicação', 'Em Atendimento'])
                ->orderBy('nr_semana')
                ->get();

            if ($semanas->isEmpty()) {
                return redirect()->route('sistema.dash')->with('mensagem_erro', 'Nenhuma semana em fila de aplicação para esta prescrição.');
            }

            // bloqueios por lote: alguma semana em atendimento por outro usuário / alguma não paga
            foreach ($semanas as $s) {
                if ($s->situacao == 'Em Atendimento' && $s->user_id_aplicacao && $s->user_id_aplicacao != $user->id) {
                    return redirect()->route('sistema.dash')->with('mensagem_erro', 'Este paciente já está sendo atendido!');
                }
                if (!$this->semana_esta_paga($s) && floatval($prescricao->valor_tratamento) > 0) {
                    return redirect()->route('sistema.dash')->with('mensagem_erro', 'A Semana ' . $s->nr_semana . ' não está paga para fazer a aplicação.');
                }
            }

            // marca todas as semanas do lote como Em Atendimento (dono = usuário logado)
            foreach ($semanas as $s) {
                if ($s->situacao != 'Em Atendimento') {
                    $s->situacao = 'Em Atendimento';
                    $s->dt_hr_atendimento = now();
                    $s->user_id_aplicacao = $user->id;
                    $s->save();
                    $this->registrar_log($s->prescricao_id, 'semana', $s->id, 'Atendimento', 'Atendimento iniciado por ' . $user->nome);
                }
            }

            $estoques_abertos = EstoqueAberto::where('clinica_id', $user->clinica_id)
                ->where('situacao', 'Aberto')
                ->with('medicamento')
                ->orderBy('dt_cadastro', 'desc')
                ->get();

            return view('sistema/prescricoes/enfermagem_acessar', compact('semanas', 'prescricao', 'estoques_abertos', 'user'));
        } catch (\Exception $e) {
            return redirect()->route('sistema.dash')->with('mensagem_erro', $e->getMessage());
        }
    }

    public function busca_lote_por_codigo(Request $request)
    {
        $estoque = Estoque::where('codigo_barras', $request->codigo)
            ->where('clinica_id', $request->clinica_id)
            ->where('medicamento_id', $request->medicamento_id)
            ->first();

        if ($estoque) {
            if ($estoque->dt_vencimento && $estoque->dt_vencimento < date('Y-m-d')) {
                return response()->json(['controle' => 'vencido', 'lote' => $estoque->lote, 'mensagem' => 'Este medicamento está VENCIDO desde ' . dataDbForm($estoque->dt_vencimento) . '. Não é possível aplicar.']);
            }
            $saldo = Estoque::get_saldo_med_cb_clinica($request->codigo, $request->clinica_id);
            if ($request->quantidade > $saldo) {
                return response()->json(['controle' => 'insuficiente', 'lote' => '']);
            }
            return response()->json(['lote' => $estoque->lote, 'controle' => 'true']);
        }
        return response()->json(['lote' => '', 'controle' => 'false']);
    }

    public function busca_lote_por_codigo_frasco(Request $request)
    {
        $user = auth()->user() ?? session()->get('user');
        $medicamento = Medicamento::find($request->medicamento_id);

        if ($medicamento && $medicamento->grupo_id) {
            $in = Medicamento::where('grupo_id', $medicamento->grupo_id)->pluck('id')->toArray();
            $estoque = EstoqueAberto::where('clinica_id', $user->clinica_id)
                ->whereIn('medicamento_id', $in)
                ->where('codigo_barras', $request->codigo)
                ->where('situacao', 'Aberto')
                ->first();
        } else {
            $estoque = EstoqueAberto::where('clinica_id', $user->clinica_id)
                ->where('medicamento_id', $request->medicamento_id)
                ->where('codigo_barras', $request->codigo)
                ->where('situacao', 'Aberto')
                ->first();
        }

        if ($estoque) {
            $estoque_original = Estoque::where('codigo_barras', $request->codigo)
                ->where('medicamento_id', $request->medicamento_id)
                ->where('clinica_id', $user->clinica_id)
                ->where('lote', $estoque->lote)
                ->first();
            if ($estoque_original && $estoque_original->dt_vencimento && $estoque_original->dt_vencimento < date('Y-m-d')) {
                return response()->json(['controle' => 'vencido', 'lote' => $estoque->lote, 'mensagem' => 'Este medicamento está VENCIDO desde ' . dataDbForm($estoque_original->dt_vencimento) . '. Não é possível aplicar.']);
            }
            if ($estoque->qt_restante < $request->quantidade) {
                return response()->json(['controle' => 'false', 'mensagem' => 'Este frasco não possui a quantidade necessária para esta aplicação. Faça o cadastro através da aplicação com 2 códigos.']);
            }
            return response()->json(['controle' => 'true', 'lote' => $estoque->lote]);
        }
        return response()->json(['controle' => 'false', 'mensagem' => 'Código de Barras Inválido']);
    }

    public function get_lotes_medicamento_mg(Request $request)
    {
        $user = auth()->user() ?? session()->get('user');
        $estoques = Estoque::get_lotes_medicamento_mg($request->medicamento_id, $user->clinica_id);
        $html = "<option value=''>Opções</option>";
        foreach ($estoques as $e) {
            $html .= "<option value='{$e['codigo_barras']}' data-lote='{$e['lote']}' data-quantidade='{$e['estoque']}'>{$e['codigo_barras']} - Lote {$e['lote']} - Estoque {$e['estoque']}</option>";
        }
        return $html;
    }

    public function abrir_frasco(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');
            $medicamento = Medicamento::find($request->medicamento_id);
            $estoque = Estoque::where('medicamento_id', $request->medicamento_id)
                ->where('lote', $request->lote)
                ->where('codigo_barras', $request->codigo_barras)
                ->where('clinica_id', $user->clinica_id)
                ->first();
            if (!$estoque) {
                throw new \Exception('Estoque não encontrado para o medicamento ' . ($medicamento->nome ?? '') . ' com o lote e código de barras informados.');
            }
            if ($estoque->dt_vencimento && $estoque->dt_vencimento < date('Y-m-d')) {
                throw new \Exception('O lote ' . $request->lote . ' do medicamento ' . $medicamento->nome . ' está vencido desde ' . dataDbForm($estoque->dt_vencimento) . ' e não pode ser aberto.');
            }

            EstoqueAberto::create([
                'medicamento_id' => $medicamento->id,
                'procedimento_id' => $request->prescricao_semana_id,
                'clinica_id' => $user->clinica_id,
                'user_id' => $user->id,
                'identificador' => 'xx',
                'dt_cadastro' => date('Y-m-d'),
                'qt_inical' => $medicamento->vasilhame,
                'qt_utilizado' => 0,
                'qt_restante' => $medicamento->vasilhame,
                'lote' => $estoque->lote,
                'codigo_barras' => $request->codigo_barras,
                'situacao' => 'Aberto',
            ]);
            Estoque::create([
                'clinica_id' => $user->clinica_id,
                'medicamento_id' => $medicamento->id,
                'origem' => 'Procedimento',
                'tipo' => 'Saida',
                'quantidade' => 1,
                'valor' => 0,
                'total' => 0,
                'lote' => $estoque->lote,
                'dt_vencimento' => $estoque->dt_vencimento,
                'codigo_barras' => $request->codigo_barras,
            ]);
            return redirect()->route('sistema.prescricoes.enfermagem_acessar', $request->prescricao_semana_id)->with('mensagem', 'Frasco Aberto');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function marcar_anexo_visualizado(Request $request)
    {
        $user = auth()->user() ?? session()->get('user');
        $anexo = Anexo::find($request->anexo_id);
        if ($anexo) {
            $anexo->visualizado_em = now();
            $anexo->visualizado_por = $user->id ?? null;
            $anexo->save();
            return response()->json(['ok' => true]);
        }
        return response()->json(['ok' => false]);
    }

    public function set_aplicacao_enfermagem(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            // semanas a aplicar (lote multi-semana). Aceita 'semanas[]' (lote) ou 'semana_id' (retrocompat)
            $ids = $request->semanas ?: [$request->semana_id];
            $ids = array_values(array_filter(array_map('intval', (array) $ids)));
            if (count($ids) === 0) {
                throw new \Exception('Nenhuma semana informada.');
            }

            $semanas = PrescricaoSemana::with('medicamentos.medicamento')
                ->whereIn('id', $ids)
                ->orderBy('nr_semana')
                ->get();
            if ($semanas->count() !== count(array_unique($ids))) {
                throw new \Exception('Alguma semana não foi encontrada.');
            }

            $prescricao_id = $semanas->first()->prescricao_id;
            foreach ($semanas as $sem) {
                if ($sem->prescricao_id != $prescricao_id) {
                    throw new \Exception('As semanas informadas pertencem a prescrições diferentes.');
                }
                // somente o usuário que iniciou o atendimento pode registrar aplicações
                if ($sem->user_id_aplicacao && $sem->user_id_aplicacao != $user->id) {
                    throw new \Exception('Este atendimento está sendo realizado por outro profissional. Apenas o usuário que iniciou pode registrar as aplicações.');
                }
            }

            // REGRA: obrigar a conferir o pedido médico (abrir anexo) antes de aplicar — vale para o lote
            $vai_aplicar_controlado = false;
            foreach ($semanas as $sem) {
                foreach ($sem->medicamentos as $m) {
                    if ($m->situacao != 'Aberta') {
                        continue;
                    }
                    $pend = $request->{'controle_pendente_' . $m->id} ?? null;
                    if ($pend == 'Sim') {
                        continue;
                    }
                    if ($m->medicamento && in_array($m->medicamento->unidade, ['Ampola', 'Miligrama'])) {
                        $vai_aplicar_controlado = true;
                        break 2;
                    }
                }
            }
            if ($vai_aplicar_controlado) {
                $anexo_visto = Anexo::where('prescricao_id', $prescricao_id)
                    ->where('tipo', 'prescricao_medica')
                    ->whereNotNull('visualizado_em')
                    ->exists();
                if (!$anexo_visto) {
                    throw new \Exception('É obrigatório abrir/conferir o pedido médico (anexo) antes de registrar a aplicação.');
                }
            }

            $obs_aplicacao = $request->obs_aplicacao;

            // aplica todas as semanas do lote de forma atômica (se algo falhar, desfaz tudo)
            $semanas_aplicadas = [];
            \DB::transaction(function () use ($request, $user, $semanas, $prescricao_id, $obs_aplicacao, &$semanas_aplicadas) {
                foreach ($semanas as $semana) {
                    [$aplicou, $pendente] = $this->aplicar_semana($request, $user, $semana, $obs_aplicacao);
                    if (!$aplicou && !$pendente) {
                        continue;
                    }
                    $semana->situacao = $pendente ? 'Aplicação Parcial' : 'Aplicada';
                    $semana->data_aplicada = date('Y-m-d');
                    $semana->dt_hr_finalizacao = now();
                    $semana->save();
                    $this->registrar_log($prescricao_id, 'semana', $semana->id, 'Aplicação', 'Aplicação realizada' . ($pendente ? ' (parcial)' : ''));
                    $semanas_aplicadas[] = $semana->id;
                }
                $this->recalcular_situacao_prescricao($prescricao_id);
            });

            if (count($semanas_aplicadas) === 0) {
                throw new \Exception('Nenhuma aplicação foi registrada.');
            }

            // enfileira o envio para a Feegow — 1 envio por semana aplicada (assíncrono, não bloqueia o sistema)
            foreach ($semanas_aplicadas as $semana_id) {
                try {
                    (new \App\Http\Controllers\ApiFlegowController())->enfileirar_aplicacao_prescricao($semana_id);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Falha ao enfileirar envio Feegow da semana ' . $semana_id . ': ' . $e->getMessage());
                }
            }

            return redirect()->route('sistema.dash')->with('mensagem', 'Aplicação Realizada');
        } catch (\Exception $e) {
            return redirect()->route('sistema.dash')->with('mensagem_erro', $e->getMessage());
        }
    }

    /**
     * Processa as medicações abertas de UMA semana (marca aplicadas/pendentes e dá baixa de estoque/lote).
     * Retorna [aplicou, pendente].
     */
    private function aplicar_semana($request, $user, $semana, $obs_aplicacao)
    {
        $pendente = false;
        $aplicou = false;

        foreach ($semana->medicamentos as $medAplic) {
            if ($medAplic->situacao != 'Aberta') {
                continue;
            }
            $medicamento = $medAplic->medicamento;
            if (!$medicamento) {
                continue;
            }

            $var_pendente = 'controle_pendente_' . $medAplic->id;
            $controle_pendente = $request->$var_pendente ?? null;
            if ($controle_pendente == 'Sim') {
                $pendente = true;
                $medAplic->situacao = 'Pendente';
                $medAplic->save();
                continue;
            }

            // copia os horários da semana para a medicação (podem variar por aplicação)
            $medAplic->dt_hr_chegada = $semana->dt_hr_chegada;
            $medAplic->dt_hr_atendimento = $semana->dt_hr_atendimento;

            $var_lote = 'lote_' . $medAplic->id;
            $lote = $request->$var_lote ?? null;
            $var_codigo = 'codigo_barras_' . $medAplic->id;
            $codigo_barras = $request->$var_codigo ?? null;

            // quantidade a retirar do estoque (regra 0.5: ampola inteira vs fração)
            $quantidade_retirar = $request->{'quantidade_retirar_' . $medAplic->id} ?? $medAplic->quantidade;
            $quantidade_retirar = (float) $quantidade_retirar;

            if ($medicamento->unidade == 'Ampola') {
                if (empty($lote) || empty($codigo_barras)) {
                    throw new \Exception('O campo Lote e Código de Barras são obrigatórios para a aplicação de ' . $medicamento->nome);
                }
                $estoque = Estoque::where('medicamento_id', $medicamento->id)
                    ->where('lote', $lote)
                    ->where('codigo_barras', $codigo_barras)
                    ->where('clinica_id', $user->clinica_id)
                    ->first();
                if ($estoque && $estoque->dt_vencimento && $estoque->dt_vencimento < date('Y-m-d')) {
                    throw new \Exception('O lote ' . $lote . ' do medicamento ' . $medicamento->nome . ' está vencido desde ' . dataDbForm($estoque->dt_vencimento) . '.');
                }

                PrescricaoLote::create([
                    'prescricao_semana_medicamento_id' => $medAplic->id,
                    'quantidade' => $quantidade_retirar,
                    'lote' => $lote,
                    'codigo_barras' => $codigo_barras,
                    'estoque_aberto_id' => null,
                ]);
                $medAplic->user_id_aplicacao = $user->id;
                $medAplic->situacao = 'Aplicada';
                $medAplic->obs = $obs_aplicacao;
                $medAplic->aplicado_em = now();
                $medAplic->save();

                Estoque::create([
                    'clinica_id' => $user->clinica_id,
                    'medicamento_id' => $medicamento->id,
                    'origem' => 'Procedimento',
                    'tipo' => 'Saida',
                    'quantidade' => $quantidade_retirar,
                    'valor' => 0,
                    'total' => 0,
                    'lote' => $lote,
                    'dt_vencimento' => $estoque->dt_vencimento ?? null,
                    'codigo_barras' => $codigo_barras,
                ]);
                $aplicou = true;
            } elseif ($medicamento->unidade == 'Miligrama') {
                $var_controle = 'controle_med_' . $medAplic->id;
                $controle = $request->$var_controle ?? null;
                if ($controle != '2_codigo' && (empty($lote) || empty($codigo_barras))) {
                    throw new \Exception('O campo Lote e Código de Barras são obrigatórios para a aplicação de ' . $medicamento->nome);
                }
                if ($lote && $codigo_barras && $controle != '2_codigo') {
                    $aberto = EstoqueAberto::where('codigo_barras', $codigo_barras)->where('clinica_id', $user->clinica_id)->first();
                    if (!$aberto) {
                        throw new \Exception('Frasco aberto não encontrado para o código ' . $codigo_barras);
                    }
                    $estoque_lote = Estoque::where('medicamento_id', $aberto->medicamento_id)
                        ->where('lote', $aberto->lote)
                        ->where('codigo_barras', $aberto->codigo_barras)
                        ->where('clinica_id', $aberto->clinica_id)
                        ->first();
                    if ($estoque_lote && $estoque_lote->dt_vencimento && $estoque_lote->dt_vencimento < date('Y-m-d')) {
                        throw new \Exception('O lote ' . $aberto->lote . ' do medicamento ' . $medicamento->nome . ' está vencido desde ' . dataDbForm($estoque_lote->dt_vencimento) . '.');
                    }
                    $aberto->qt_utilizado += $medAplic->quantidade;
                    $aberto->qt_restante -= $medAplic->quantidade;
                    if ($aberto->qt_restante <= 0) {
                        $aberto->situacao = 'Finalizado';
                    }
                    $aberto->save();
                    PrescricaoLote::create([
                        'prescricao_semana_medicamento_id' => $medAplic->id,
                        'quantidade' => $medAplic->quantidade,
                        'lote' => $aberto->lote,
                        'codigo_barras' => $aberto->codigo_barras,
                        'estoque_aberto_id' => $aberto->id,
                    ]);
                    $medAplic->user_id_aplicacao = $user->id;
                    $medAplic->situacao = 'Aplicada';
                    $medAplic->obs = $obs_aplicacao;
                    $medAplic->aplicado_em = now();
                    if ($aberto->medicamento_id != $medAplic->medicamento_id) {
                        $medAplic->medicamento_id = $aberto->medicamento_id;
                    }
                    $medAplic->save();
                    $aplicou = true;
                } elseif ($controle == '2_codigo') {
                    $codigo_b1 = $request->{'cod_med_1_' . $medAplic->id} ?? null;
                    $quantidade1 = (float) ($request->{'quant_med_1_' . $medAplic->id} ?? 0);
                    $codigo_b2 = $request->{'cod_med_2_' . $medAplic->id} ?? null;
                    $quantidade2 = (float) ($request->{'quant_med_2_' . $medAplic->id} ?? 0);
                    if (empty($codigo_b1) || empty($codigo_b2)) {
                        throw new \Exception('O Código de Barras dos dois frascos são obrigatórios para a aplicação de ' . $medicamento->nome);
                    }
                    foreach ([[$codigo_b1, $quantidade1], [$codigo_b2, $quantidade2]] as [$cod, $qtd]) {
                        $aberto = EstoqueAberto::where('codigo_barras', $cod)->where('clinica_id', $user->clinica_id)->first();
                        if (!$aberto) {
                            throw new \Exception('Frasco aberto não encontrado para o código ' . $cod);
                        }
                        $estoque_lote = Estoque::where('medicamento_id', $aberto->medicamento_id)
                            ->where('lote', $aberto->lote)
                            ->where('codigo_barras', $aberto->codigo_barras)
                            ->where('clinica_id', $aberto->clinica_id)
                            ->first();
                        if ($estoque_lote && $estoque_lote->dt_vencimento && $estoque_lote->dt_vencimento < date('Y-m-d')) {
                            throw new \Exception('O lote ' . $aberto->lote . ' de um dos frascos de ' . $medicamento->nome . ' está vencido.');
                        }
                        $aberto->qt_utilizado += $qtd;
                        $aberto->qt_restante -= $qtd;
                        if ($aberto->qt_restante <= 0) {
                            $aberto->situacao = 'Finalizado';
                        }
                        $aberto->save();
                        PrescricaoLote::create([
                            'prescricao_semana_medicamento_id' => $medAplic->id,
                            'quantidade' => $qtd,
                            'lote' => $aberto->lote,
                            'codigo_barras' => $aberto->codigo_barras,
                            'estoque_aberto_id' => $aberto->id,
                        ]);
                    }
                    $medAplic->user_id_aplicacao = $user->id;
                    $medAplic->situacao = 'Aplicada';
                    $medAplic->obs = $obs_aplicacao;
                    $medAplic->aplicado_em = now();
                    if ($aberto->medicamento_id != $medAplic->medicamento_id) {
                        $medAplic->medicamento_id = $aberto->medicamento_id;
                    }
                    $medAplic->save();
                    $aplicou = true;
                }
            } elseif ($medicamento->unidade == 'Procedimento') {
                $medAplic->user_id_aplicacao = $user->id;
                $medAplic->situacao = 'Aplicada';
                $medAplic->obs = $codigo_barras;
                $medAplic->aplicado_em = now();
                $medAplic->save();
                $aplicou = true;
            }
        }

        return [$aplicou, $pendente];
    }

    private function recalcular_situacao_prescricao($prescricao_id)
    {
        $prescricao = Prescricao::find($prescricao_id);
        if (!$prescricao) {
            return;
        }
        $semanas = $prescricao->semanas;
        $tot = $semanas->count();
        $canceladas = $semanas->where('situacao', 'Cancelada')->count();
        $aplicadas = $semanas->where('situacao', 'Aplicada')->count();
        $andamento = $semanas->whereIn('situacao', ['Fila de Aplicação', 'Em Atendimento', 'Aplicação Parcial'])->count();
        $sit = 'Agendada';
        if ($canceladas === $tot) {
            $sit = 'Cancelada';
        } elseif (($aplicadas + $canceladas) === $tot) {
            $sit = 'Concluída';
        } elseif ($andamento > 0) {
            $sit = 'Em Andamento';
        }
        $prescricao->situacao = $sit;
        $prescricao->save();
    }

    /**
     * Retorna a semana anterior (por nr_semana) que possui medicações (pula semanas de pausa).
     */
    private function semana_anterior_aplicacao($semana)
    {
        return PrescricaoSemana::where('prescricao_id', $semana->prescricao_id)
            ->where('nr_semana', '<', $semana->nr_semana)
            ->whereHas('medicamentos')
            ->orderByDesc('nr_semana')
            ->first();
    }

    /**
     * Regra: para enviar uma semana à fila de aplicação, a semana anterior (com medicação)
     * precisa estar como 'Aplicada', 'Aplicação Parcial', 'Fila de Aplicação' ou 'Em Atendimento'
     * (não pode estar 'Agendada') — assim várias semanas da prescrição podem ficar na fila
     * (caso de entrega de medicação para aplicar em casa / adiantamento durante atendimento).
     */
    private function pode_enviar_para_fila($semana, &$motivo = null)
    {
        $anterior = $this->semana_anterior_aplicacao($semana);
        if ($anterior && !in_array($anterior->situacao, ['Aplicada', 'Aplicação Parcial', 'Fila de Aplicação', 'Em Atendimento'])) {
            $motivo = 'A semana anterior (Semana ' . $anterior->nr_semana . ') está como "' . $anterior->situacao . '". Para enviar esta semana à fila de aplicação, a semana anterior precisa estar como Aplicada, Aplicação Parcial, Fila de Aplicação ou Em Atendimento.';
            return false;
        }
        return true;
    }

    public function enviar_fila_aplicacao(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $semana = PrescricaoSemana::find($request->semana_id);
            if (!$semana) {
                throw new \Exception('Semana não encontrada.');
            }

            $motivo = null;
            if (!$this->pode_enviar_para_fila($semana, $motivo)) {
                throw new \Exception($motivo);
            }

            $semana->situacao = 'Fila de Aplicação';
            $semana->dt_hr_chegada = now();
            $semana->save();

            $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Fila de Aplicação', 'Semana ' . $semana->nr_semana . ' enviada para a fila de aplicação');

            return redirect()->route('sistema.prescricoes.acessar_semana', $semana->id)->with('mensagem', 'Semana enviada para a fila de aplicação!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function enviar_fila_aplicacao_sem_pagamento(Request $request)
    {
        try {
            $autorizador = User::where('email', $request->autorizador_email)
                ->where('tipo', 'Administrador')
                ->where('st_usuario', 'Ativo')
                ->first();

            if (!$autorizador || !Hash::check($request->autorizador_senha, $autorizador->password)) {
                throw new \Exception('Autorizador ou senha inválidos.');
            }

            $user = auth()->user() ?? session()->get('user');

            $semana = PrescricaoSemana::find($request->semana_id);
            if (!$semana) {
                throw new \Exception('Semana não encontrada.');
            }

            $motivo = null;
            if (!$this->pode_enviar_para_fila($semana, $motivo)) {
                throw new \Exception($motivo);
            }

            $semana->situacao = 'Fila de Aplicação';
            $semana->dt_hr_chegada = now();
            $semana->save();

            $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Fila de Aplicação', 'Semana ' . $semana->nr_semana . ' enviada para a fila SEM PAGAMENTO, autorizado por ' . $autorizador->nome);

            return redirect()->route('sistema.prescricoes.acessar_semana', $semana->id)->with('mensagem', 'Semana enviada para a fila de aplicação (com autorização)!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function editar_prescricao($id)
    {
        $prescricao = Prescricao::with('paciente', 'clinica')->find($id);
        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        $api = api();
        $medicos = $api->get_medicos();
        $clinicas = Clinica::all()->sortBy('nome');

        return view('sistema/prescricoes/editar_prescricao', compact('prescricao', 'medicos', 'clinicas'));
    }

    public function update_prescricao(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $prescricao = Prescricao::find($request->prescricao_id);
            if (!$prescricao) {
                throw new \Exception('Prescrição não encontrada.');
            }

            $dados_antigos = [
                'medico' => $prescricao->medico,
                'tipo_atendimento' => $prescricao->tipo_atendimento,
                'clinica_id' => $prescricao->clinica_id,
                'obs' => $prescricao->obs,
            ];

            $prescricao->medico = $request->medico;
            $prescricao->tipo_atendimento = $request->tipo_atendimento;
            $prescricao->clinica_id = $request->clinica_id;
            $prescricao->obs = $request->obs;
            $prescricao->save();

            $dados_novos = [
                'medico' => $prescricao->medico,
                'tipo_atendimento' => $prescricao->tipo_atendimento,
                'clinica_id' => $prescricao->clinica_id,
                'obs' => $prescricao->obs,
            ];

            $this->registrar_log($prescricao->id, 'prescricao', $prescricao->id, 'Atualização', 'Dados da prescrição atualizados', $dados_antigos, $dados_novos);

            return redirect()->route('sistema.prescricoes.acessar', $prescricao->id)->with('mensagem', 'Prescrição atualizada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao atualizar prescrição: ' . $e->getMessage());
        }
    }

    public function editar_semana($id)
    {
        $semana = PrescricaoSemana::with('prescricao', 'medicamentos.medicamento')->find($id);

        if (!$semana) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Semana não encontrada.');
        }

        $medicamentos = Medicamento::all()->sortBy('nome');
        $combos = Combo::all()->sortBy('nome');

        return view('sistema/prescricoes/editar_semana', compact('semana', 'medicamentos', 'combos'));
    }

    public function update_semana(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $semana = PrescricaoSemana::find($request->semana_id);
            if (!$semana) {
                throw new \Exception('Semana não encontrada.');
            }

            // data aplicada e situação são controladas pelo sistema (aplicação), não editáveis aqui
            $dados_semana = [
                'data_prevista' => $request->data_prevista,
                'obs' => $request->obs,
            ];

            $dados_antigos = [
                'data_prevista' => $semana->data_prevista,
                'obs' => $semana->obs,
            ];

            $semana->update($dados_semana);

            // medicações marcadas para exclusão (exclui somente ao salvar)
            $ids_excluir = array_map('intval', (array) ($request->excluir_medicamento ?? []));
            $excluidas = 0;

            // atualiza medicações existentes
            for ($i = 1; $i <= $request->contador_medicamentos; $i++) {
                $var_id = 'medicamento_editar_id_' . $i;
                $var_qtd = 'quantidade_' . $i;
                $var_sit = 'situacao_medicamento_' . $i;

                if ($request->$var_id) {
                    $med = PrescricaoSemanaMedicamento::find($request->$var_id);
                    if ($med) {
                        if (in_array((int) $med->id, $ids_excluir)) {
                            if ($med->situacao != 'Aplicada') {
                                PrescricaoLote::where('prescricao_semana_medicamento_id', $med->id)->delete();
                                $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Remoção de Medicamento', 'Medicação "' . ($med->medicamento->nome ?? $med->id) . '" removida da semana ' . $semana->nr_semana);
                                $med->delete();
                                $excluidas++;
                            }
                        } else {
                            // o medicamento não pode ser trocado na edição — apenas quantidade e situação
                            $med->update([
                                'quantidade' => $request->$var_qtd,
                                'situacao' => $request->$var_sit,
                            ]);
                        }
                    }
                }
            }

            // medicações novas adicionadas na edição (medicamento ou combo)
            $contador_novos = intval($request->contador_novos_medicamentos ?? 0);
            if ($contador_novos > 0) {
                $ja_aplicada = in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial'])
                    || $semana->medicamentos()->where('situacao', 'Aplicada')->exists();
                $situacao_med = $ja_aplicada ? 'Pendente' : 'Aberta';

                for ($k = 1; $k <= $contador_novos; $k++) {
                    $novo_medicamento_id = $request->{'novo_medicamento_id_' . $k} ?? null;
                    if (!$novo_medicamento_id) {
                        continue;
                    }
                    $nova_quantidade = (float) str_replace(',', '.', $request->{'nova_quantidade_' . $k} ?? 1);
                    if ($nova_quantidade <= 0) {
                        $nova_quantidade = 1;
                    }

                    $medicamento = Medicamento::find($novo_medicamento_id);
                    $gera_aplicacao = $medicamento && $medicamento->aplicacao == 'Sim';

                    $novo_med = PrescricaoSemanaMedicamento::create([
                        'prescricao_semana_id' => $semana->id,
                        'medicamento_id' => $novo_medicamento_id,
                        'combo_id' => null,
                        'clinica_id_aplicacao' => $user->clinica_id ?? null,
                        'is_soro' => $medicamento && str_starts_with(strtolower($medicamento->nome), 'soro'),
                        'gera_aplicacao' => $gera_aplicacao,
                        'quantidade' => $nova_quantidade,
                        'situacao' => $situacao_med,
                        'data_prevista' => $semana->data_prevista,
                    ]);

                    $nome_med = $medicamento ? $medicamento->nome : ('Medicamento #' . $novo_medicamento_id);
                    $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Adição de Medicamento', 'Medicação "' . $nome_med . '" (qtd ' . $nova_quantidade . ') adicionada na semana ' . $semana->nr_semana . ($situacao_med == 'Pendente' ? ' — pendente' : ''));
                }
            }

            $this->recalcular_tem_aplicacao($semana);

            $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Atualização', 'Semana ' . $semana->nr_semana . ' atualizada', $dados_antigos, $dados_semana);

            return redirect()->route('sistema.prescricoes.acessar', $semana->prescricao_id)->with('mensagem', 'Semana atualizada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao atualizar semana: ' . $e->getMessage());
        }
    }

    public function excluir_semana($id)
    {
        $semana = PrescricaoSemana::with('prescricao')->find($id);
        if (!$semana) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Semana não encontrada.');
        }

        if (!session()->has('administrador')) {
            return redirect()->route('sistema.prescricoes.acessar', $semana->prescricao_id)->with('mensagem_erro', 'Apenas administradores podem excluir semanas.');
        }

        if ($this->semana_ja_aplicada($semana)) {
            return redirect()->route('sistema.prescricoes.acessar', $semana->prescricao_id)->with('mensagem_erro', 'Não é possível excluir uma semana que já foi aplicada ou está em atendimento.');
        }

        return view('sistema/prescricoes/excluir_semana', compact('semana'));
    }

    public function delete_semana(Request $request)
    {
        try {
            $semana = PrescricaoSemana::with('medicamentos')->find($request->semana_id);
            if (!$semana) {
                throw new \Exception('Semana não encontrada.');
            }

            if (!session()->has('administrador')) {
                throw new \Exception('Apenas administradores podem excluir semanas.');
            }

            if ($this->semana_ja_aplicada($semana)) {
                throw new \Exception('Não é possível excluir uma semana que já foi aplicada ou está em atendimento.');
            }

            $prescricao_id = $semana->prescricao_id;
            $nr_semana = $semana->nr_semana;

            DB::transaction(function () use ($semana) {
                foreach ($semana->medicamentos as $med) {
                    PrescricaoLote::where('prescricao_semana_medicamento_id', $med->id)->delete();
                    $med->delete();
                }
                PrescricaoObservacao::where('prescricao_semana_id', $semana->id)->delete();
                FinanceiroParcela::where('prescricao_semana_id', $semana->id)->delete();
                $semana->delete();
            });

            $prescricao = Prescricao::find($prescricao_id);
            $this->recalcular_semanas($prescricao);

            $this->registrar_log($prescricao_id, 'semana', $semana->id, 'Exclusão', 'Semana ' . $nr_semana . ' excluída');

            return redirect()->route('sistema.prescricoes.acessar', $prescricao_id)->with('mensagem', 'Semana excluída com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    public function adicionar_semana($prescricao_id)
    {
        $prescricao = Prescricao::with('paciente', 'semanas.medicamentos.medicamento', 'semanas.parcela')->find($prescricao_id);
        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        $medicamentos = Medicamento::all()->sortBy('nome');
        $combos = Combo::all()->sortBy('nome');

        // parcelas existentes com saldo em aberto (para o modo reestruturar)
        $parcelas_abertas = $prescricao->semanas->filter(function ($s) {
            return $s->parcela && ((float) $s->parcela->valor_parcela - (float) $s->parcela->valor_pago) > 0.005;
        })->map(function ($s) {
            return [
                'nr' => $s->parcela->nr_parcela,
                'semana' => $s->nr_semana,
                'saldo' => round((float) $s->parcela->valor_parcela - (float) $s->parcela->valor_pago, 2),
                'pago' => (float) $s->parcela->valor_pago,
                'dt' => $s->data_prevista ? date('Y-m-d', strtotime($s->data_prevista)) : null,
            ];
        })->values();

        // numeração para visualização (semana/parcela seguem as já existentes)
        $qt_semanas_existentes = $prescricao->semanas->count();
        $ultima_parcela_nr = (int) FinanceiroParcela::where('prescricao_id', $prescricao->id)->max('nr_parcela');

        return view('sistema/prescricoes/adicionar_semana', compact('prescricao', 'medicamentos', 'combos', 'parcelas_abertas', 'qt_semanas_existentes', 'ultima_parcela_nr'));
    }

    public function insert_semana(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            $prescricao = Prescricao::find($request->prescricao_id);
            if (!$prescricao) {
                throw new \Exception('Prescrição não encontrada.');
            }

            $contador_procedimentos = intval($request->contador_procedimentos ?? 0);

            // ---- monta as semanas (mesma regra do cadastro) ----
            $semanasDados = [];
            for ($i = 1; $i <= $contador_procedimentos; $i++) {
                $data_prevista = $request->{'data_prevista_' . $i} ?? null;
                $pausa = ($request->{'pausa_' . $i} ?? null) === 'true';
                $obs = $request->{'obs_' . $i} ?? null;

                if ($data_prevista && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_prevista)) {
                    throw new \Exception("Semana {$i}: Data Prevista inválida.");
                }
                if ($data_prevista && $data_prevista < date('Y-m-d')) {
                    throw new \Exception("Semana {$i}: a Data Prevista ({$data_prevista}) está no passado. Não é permitido adicionar semana com data retroativa.");
                }

                $contador = intval($request->{'contador_medicamentos_' . $i} ?? 0);
                $meds = [];
                for ($j = 1; $j <= $contador; $j++) {
                    $medicamento_id = $request->{'medicamento_id_' . $i . '_' . $j} ?? null;
                    if (!$medicamento_id) {
                        continue;
                    }
                    $quantidade = (float) str_replace(',', '.', $request->{'quantidade_' . $i . '_' . $j} ?? 1);
                    if ($quantidade <= 0) {
                        $quantidade = 1;
                    }
                    $med = Medicamento::find($medicamento_id);
                    $meds[] = [
                        'medicamento_id' => $medicamento_id,
                        'quantidade' => $quantidade,
                        'gera_aplicacao' => $med && $med->aplicacao == 'Sim',
                        'is_soro' => $med && str_starts_with(strtolower($med->nome), 'soro'),
                        'nome' => $med ? $med->nome : ('Medicamento #' . $medicamento_id),
                    ];
                }

                // ignora semana completamente vazia (sem data, sem pausa e sem medicação)
                if (!$data_prevista && !$pausa && count($meds) === 0) {
                    continue;
                }
                if (!$data_prevista) {
                    throw new \Exception("Semana {$i}: informe a Data Prevista.");
                }

                $semanasDados[] = compact('data_prevista', 'pausa', 'obs', 'meds');
            }

            if (count($semanasDados) === 0) {
                return redirect()->back()->withInput()->with('mensagem_erro', 'Adicione pelo menos uma semana com data, pausa ou medicação.');
            }

            // preenche automaticamente as semanas de pausa entre a última semana existente e as novas
            $semanasDados = $this->preencher_lacunas_semanais($prescricao, $semanasDados);

            DB::transaction(function () use ($request, $user, $prescricao, $semanasDados) {
                $nr_semana = $prescricao->semanas()->count();
                $semanasParcela = [];

                foreach ($semanasDados as $d) {
                    $nr_semana++;

                    $tem_aplicacao = false;
                    foreach ($d['meds'] as $m) {
                        if ($m['gera_aplicacao']) {
                            $tem_aplicacao = true;
                            break;
                        }
                    }
                    $tem_aplicacao = $tem_aplicacao && !$d['pausa'];

                    $semana = PrescricaoSemana::create([
                        'prescricao_id' => $prescricao->id,
                        'nr_semana' => $nr_semana,
                        'data_prevista' => $d['data_prevista'] ?: null,
                        'data_aplicada' => null,
                        'tem_aplicacao' => $tem_aplicacao,
                        'situacao' => 'Agendada',
                        'obs' => $d['obs'] ?: null,
                    ]);

                    $this->registrar_log($prescricao->id, 'semana', $semana->id, 'Criação', 'Semana ' . $nr_semana . ' criada' . ($d['pausa'] ? ' (pausa)' : '') . ($d['obs'] ? ' — obs: ' . $d['obs'] : ''));

                    foreach ($d['meds'] as $m) {
                        PrescricaoSemanaMedicamento::create([
                            'prescricao_semana_id' => $semana->id,
                            'medicamento_id' => $m['medicamento_id'],
                            'combo_id' => null,
                            'clinica_id_aplicacao' => $user->clinica_id ?? null,
                            'is_soro' => $m['is_soro'],
                            'gera_aplicacao' => $m['gera_aplicacao'],
                            'quantidade' => $m['quantidade'],
                            'situacao' => 'Aberta',
                            'data_prevista' => $d['data_prevista'] ?: null,
                        ]);
                        $this->registrar_log($prescricao->id, 'semana', $semana->id, 'Adição de Medicamento', 'Medicação "' . $m['nome'] . '" (qtd ' . $m['quantidade'] . ') adicionada na semana ' . $nr_semana);
                    }

                    if (!$d['pausa'] && count($d['meds']) > 0) {
                        $semanasParcela[] = ['semana' => $semana, 'data_prevista' => $d['data_prevista']];
                    }
                }

                $this->recalcular_semanas($prescricao);

                // ---- financeiro das semanas novas (2 modos) ----
                $valor_adicional = (float) valorFormDb($request->valor_adicional ?? '0');
                $modo = ($request->modo_financeiro == '2') ? 2 : 1;
                $novas_com_medicacao = $semanasParcela;
                $total_novas = count($novas_com_medicacao);

                if ($valor_adicional > 0) {
                    if ($modo == 2) {
                        // opção 2: soma o aberto existente + novo e divide por todas as semanas não totalmente pagas
                        $existentes_abertas = FinanceiroParcela::where('prescricao_id', $prescricao->id)
                            ->whereRaw('valor_parcela - valor_pago > 0.005')
                            ->get();

                        $total_aberto = $existentes_abertas->sum(function ($p) {
                            return (float) $p->valor_parcela - (float) $p->valor_pago;
                        });

                        $total_distribuir = round($total_aberto + $valor_adicional, 2);
                        $alvo = $existentes_abertas->count() + $total_novas;

                        if ($alvo > 0 && $total_distribuir > 0) {
                            $quota = floor(($total_distribuir / $alvo) * 100) / 100;
                            $resto = round($total_distribuir - $quota * $alvo, 2);
                            $i = 0;

                            // redefine as parcelas existentes em aberto (valor_parcela = cota única)
                            foreach ($existentes_abertas as $parcela) {
                                $i++;
                                $valor = ($i === $alvo) ? round($quota + $resto, 2) : $quota;
                                $parcela->valor_parcela = $valor;
                                if ((float) $parcela->valor_pago >= $valor - 0.005) {
                                    $parcela->situacao = 'Paga';
                                } elseif ((float) $parcela->valor_pago > 0) {
                                    $parcela->situacao = 'Parcial';
                                } else {
                                    $parcela->situacao = 'Em Aberto';
                                }
                                $parcela->save();
                                $this->registrar_log($prescricao->id, 'financeiro', $parcela->id, 'Financeiro', 'Parcela ' . $parcela->nr_parcela . ' reestruturada — valor R$ ' . number_format($valor, 2, ',', '.'));
                            }

                            // cria as parcelas novas com a mesma cota
                            $nr_parcela = FinanceiroParcela::where('prescricao_id', $prescricao->id)->count();
                            foreach ($novas_com_medicacao as $sp) {
                                $i++;
                                $nr_parcela++;
                                $valor = ($i === $alvo) ? round($quota + $resto, 2) : $quota;
                                $parcela = FinanceiroParcela::create([
                                    'prescricao_id' => $prescricao->id,
                                    'prescricao_semana_id' => $sp['semana']->id,
                                    'nr_parcela' => $nr_parcela,
                                    'valor_parcela' => $valor,
                                    'valor_pago' => 0,
                                    'situacao' => 'Em Aberto',
                                    'dt_vencimento' => $sp['data_prevista'] ?: null,
                                ]);
                                $this->registrar_log($prescricao->id, 'financeiro', $parcela->id, 'Financeiro', 'Parcela ' . $nr_parcela . ' gerada (Semana ' . $sp['semana']->nr_semana . ') — R$ ' . number_format($valor, 2, ',', '.'));
                            }
                        }
                    } elseif ($total_novas > 0) {
                        // opção 1: rateia o valor adicional somente nas parcelas novas
                        $nr_parcela = FinanceiroParcela::where('prescricao_id', $prescricao->id)->count();
                        $base = floor(($valor_adicional / $total_novas) * 100) / 100;
                        $resto = round($valor_adicional - $base * $total_novas, 2);
                        foreach ($novas_com_medicacao as $idx => $sp) {
                            $nr_parcela++;
                            $valor = ($idx === $total_novas - 1) ? round($base + $resto, 2) : $base;
                            $parcela = FinanceiroParcela::create([
                                'prescricao_id' => $prescricao->id,
                                'prescricao_semana_id' => $sp['semana']->id,
                                'nr_parcela' => $nr_parcela,
                                'valor_parcela' => $valor,
                                'valor_pago' => 0,
                                'situacao' => 'Em Aberto',
                                'dt_vencimento' => $sp['data_prevista'] ?: null,
                            ]);
                            $this->registrar_log($prescricao->id, 'financeiro', $parcela->id, 'Financeiro', 'Parcela ' . $nr_parcela . ' gerada (Semana ' . $sp['semana']->nr_semana . ') — R$ ' . number_format($valor, 2, ',', '.'));
                        }
                    }

                    $prescricao->valor_tratamento = round((float) $prescricao->valor_tratamento + $valor_adicional, 2);
                    $prescricao->save();
                    $this->recalcular_situacao_financeira($prescricao->id);
                }
            });

            return redirect()->route('sistema.prescricoes.acessar', $prescricao->id)->with('mensagem', 'Semanas adicionadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao adicionar semanas: ' . $e->getMessage());
        }
    }

    public function adicionar_medicamentos($prescricao_id)
    {
        $prescricao = Prescricao::with('paciente', 'semanas', 'parcelas.semana')->find($prescricao_id);
        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        $medicamentos = Medicamento::all()->sortBy('nome');
        $combos = Combo::all()->sortBy('nome');

        return view('sistema/prescricoes/adicionar_medicamentos', compact('prescricao', 'medicamentos', 'combos'));
    }

    public function insert_medicamentos(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');

            if (empty($request->semanas) || !is_array($request->semanas)) {
                throw new \Exception('Selecione ao menos uma semana.');
            }

            $prescricao_id = null;

            DB::transaction(function () use ($request, $user, &$prescricao_id) {
                foreach ($request->semanas as $semana_id) {
                    $semana = PrescricaoSemana::find($semana_id);
                    if (!$semana) {
                        continue;
                    }
                    $prescricao_id = $semana->prescricao_id;

                    // semana já aplicada (total ou parcial): novo medicamento entra como Pendente
                    $ja_aplicada = in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial'])
                        || $semana->medicamentos()->where('situacao', 'Aplicada')->exists();

                    $situacao_med = $ja_aplicada ? 'Pendente' : 'Aberta';

                    $this->inserir_medicamentos_semana($semana, $request, $situacao_med);
                    $this->recalcular_tem_aplicacao($semana);

                    if ($ja_aplicada) {
                        $semana->situacao = 'Aplicação Parcial';
                        $semana->save();
                    }
                }

                // ---- valor adicional rateado nas parcelas existentes ----
                $valor_adicional = (float) valorFormDb($request->valor_adicional ?? '0');
                $parcelas_rateio = $request->parcelas_rateio ?? [];

                if ($valor_adicional > 0) {
                    if (empty($parcelas_rateio) || !is_array($parcelas_rateio)) {
                        throw new \Exception('Informe o valor adicional e selecione ao menos uma parcela para o rateio.');
                    }

                    $parcelas = FinanceiroParcela::whereIn('id', $parcelas_rateio)->get();
                    if ($parcelas->count() === 0) {
                        throw new \Exception('Nenhuma parcela válida selecionada para o rateio.');
                    }

                    $total = $parcelas->count();
                    $base = floor(($valor_adicional / $total) * 100) / 100;
                    $resto = round($valor_adicional - $base * $total, 2);
                    $idx = 0;

                    foreach ($parcelas as $parcela) {
                        $idx++;
                        $parte = ($idx === $total) ? round($base + $resto, 2) : $base;
                        $parcela->valor_parcela = round((float) $parcela->valor_parcela + $parte, 2);

                        // recalcula a situação da parcela com o novo valor
                        if ((float) $parcela->valor_pago >= (float) $parcela->valor_parcela - 0.005) {
                            $parcela->situacao = 'Paga';
                        } elseif ((float) $parcela->valor_pago > 0) {
                            $parcela->situacao = 'Parcial';
                        } else {
                            $parcela->situacao = 'Em Aberto';
                        }
                        $parcela->save();

                        $nr_semana_p = $parcela->semana ? $parcela->semana->nr_semana : '-';
                        $this->registrar_log($prescricao_id, 'financeiro', $parcela->id, 'Financeiro', 'Valor adicional de R$ ' . number_format($parte, 2, ',', '.') . ' rateado na parcela ' . $parcela->nr_parcela . ' (Semana ' . $nr_semana_p . ')');
                    }

                    // aumenta o valor do tratamento no mesmo montante para manter a coerência
                    $prescricao = Prescricao::find($prescricao_id);
                    if ($prescricao) {
                        $prescricao->valor_tratamento = round((float) $prescricao->valor_tratamento + $valor_adicional, 2);
                        $prescricao->save();
                    }

                    $this->recalcular_situacao_financeira($prescricao_id);
                }
            });

            if (!$prescricao_id) {
                throw new \Exception('Nenhuma semana válida encontrada.');
            }

            return redirect()->route('sistema.prescricoes.acessar', $prescricao_id)->with('mensagem', 'Medicamentos adicionados com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('mensagem_erro', 'Erro ao adicionar medicamentos: ' . $e->getMessage());
        }
    }

    public function delete_medicamento($id)
    {
        try {
            $med = PrescricaoSemanaMedicamento::with('semana')->find($id);
            if (!$med) {
                throw new \Exception('Medicamento não encontrado.');
            }

            $semana = $med->semana;
            $prescricao_id = $semana->prescricao_id;

            if ($med->situacao == 'Aplicada') {
                throw new \Exception('Não é possível remover uma medicação já aplicada.');
            }

            PrescricaoLote::where('prescricao_semana_medicamento_id', $med->id)->delete();
            $med->delete();

            $this->recalcular_tem_aplicacao($semana);

            $this->registrar_log($prescricao_id, 'semana', $semana->id, 'Remoção de Medicamento', 'Medicação removida da semana ' . $semana->nr_semana);

            return redirect()->route('sistema.prescricoes.acessar', $prescricao_id)->with('mensagem', 'Medicação removida com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', $e->getMessage());
        }
    }

    // ---------- impressões ----------

    public function imprimir_cadastro($prescricao_id)
    {
        $prescricao = Prescricao::with([
            'paciente',
            'clinica',
            'userCadastro',
            'semanas.medicamentos.medicamento',
            'semanas.medicamentos.lotes',
            'semanas.medicamentos.userAplicacao',
            'semanas.observacoes.user',
            'semanas.parcela',
            'parcelas.semana',
            'anexos',
            'logs.user',
        ])->find($prescricao_id);

        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        $cadastrante = $prescricao->userCadastro ? $prescricao->userCadastro->nome : null;

        // resumo de medicamentos (agrupando todas as semanas) — somente quantidade
        $array_resumo = [];
        foreach ($prescricao->semanas as $semana) {
            foreach ($semana->medicamentos as $med) {
                if (!$med->medicamento) {
                    continue;
                }
                $mid = $med->medicamento_id;
                if (!isset($array_resumo[$mid])) {
                    $array_resumo[$mid] = [
                        'medicamento' => $med->medicamento->nome,
                        'quantidade' => 0,
                    ];
                }
                $array_resumo[$mid]['quantidade'] += (float) $med->quantidade;
            }
        }
        $array_resumo = array_values($array_resumo);

        $vl_procedimentos = (float) $prescricao->parcelas()->sum('valor_parcela');
        $vl_pagamentos = (float) $prescricao->total_pago;
        $obs_pagamento = $prescricao->pagamentos()->whereNotNull('obs')->pluck('obs')->implode(' / ');

        return view('sistema/prescricoes/imprimir_cadastro', compact('prescricao', 'array_resumo', 'vl_procedimentos', 'vl_pagamentos', 'obs_pagamento', 'cadastrante'));
    }

    public function imprimir_detalhes($prescricao_id)
    {
        $prescricao = Prescricao::with(['paciente', 'clinica', 'semanas.medicamentos.medicamento', 'semanas.medicamentos.lotes', 'logs.user'])->find($prescricao_id);

        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        $logs = $prescricao->logs()->orderBy('created_at', 'desc')->get();

        return view('sistema/prescricoes/imprimir_detalhes', compact('prescricao', 'logs'));
    }

    public function imprimir_paciente($prescricao_id)
    {
        $prescricao = Prescricao::with([
            'paciente',
            'clinica',
            'semanas.medicamentos.medicamento',
            'semanas.medicamentos.lotes',
            'semanas.medicamentos.userAplicacao',
            'semanas.userAplicacao',
        ])->find($prescricao_id);

        if (!$prescricao) {
            return redirect()->route('sistema.prescricoes')->with('mensagem_erro', 'Prescrição não encontrada.');
        }

        // semanas já aplicadas (reutilizando o eager-load da consulta acima)
        $semanas = $prescricao->semanas->filter(function ($s) {
            return in_array($s->situacao, ['Aplicada', 'Aplicação Parcial']);
        })->values();

        $array_arquivos = [];

        foreach ($semanas as $semana) {
            $pdf = new GerarPdf('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->setPrintHeader(true);
            $pdf->SetMargins(10, 40, -1, true);
            $pdf->AddPage();

            // cabeçalho do paciente
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(95, 6, 'Paciente:', 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            $pdf->MultiCell(50, 6, 'CPF:', 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            $pdf->MultiCell(0, 6, 'Data Cadastro:', 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->MultiCell(95, 5, $prescricao->paciente->nm_paciente ?? '-', 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            $pdf->MultiCell(50, 5, $prescricao->paciente->cpf ?? '-', 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            $pdf->MultiCell(0, 5, $prescricao->data_prescricao ? dataDbForm($prescricao->data_prescricao) : '-', 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(95, 6, 'Médico:', 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            $pdf->MultiCell(0, 6, 'Clinica:', 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->MultiCell(95, 5, $prescricao->medico ?? '-', 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            $pdf->MultiCell(0, 5, $prescricao->clinica->nome ?? '-', 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

            $pdf->SetLineWidth(0.1);
            $pdf->Line(10, 65, 200, 65);
            $pdf->Ln();

            $ds_aplicacao = $semana->obs ?? '';
            if ($ds_aplicacao == '' && $semana->medicamentos->first()) {
                $ds_aplicacao = $semana->medicamentos->first()->obs ?? '';
            }

            $dt_hr_chegada = '';
            $dt_hr_atendimento = '';
            $dt_hr_finalizacao = '';
            if ($semana->dt_hr_chegada) {
                $dt_hr_chegada = explode(' ', $semana->dt_hr_chegada)[1] ?? '';
            }
            if ($semana->dt_hr_atendimento) {
                $dt_hr_atendimento = explode(' ', $semana->dt_hr_atendimento)[1] ?? '';
            }
            if ($semana->dt_hr_finalizacao) {
                $dt_hr_finalizacao = explode(' ', $semana->dt_hr_finalizacao)[1] ?? '';
            }

            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->MultiCell(160, 8, 'Semana: ' . $semana->nr_semana, 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
            $pdf->MultiCell(0, 8, $semana->data_aplicada ? dataDbForm($semana->data_aplicada) : '-', 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(70, 6, 'Chegada: ' . $dt_hr_chegada, 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            $pdf->MultiCell(70, 6, 'Atendimento: ' . $dt_hr_atendimento, 0, 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
            $pdf->MultiCell(0, 6, 'Finalização: ' . $dt_hr_finalizacao, 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
            $pdf->Ln(2);
            $pdf->MultiCell(0, 6, 'OBS:', 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 0, rtrim((string) $ds_aplicacao), 0, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

            $tabela = '
            <table style="border-collapse: collapse;width: 100%;border: 1px solid black;">
                <thead>
                    <tr>
                        <th style="border: 1px solid black;"><b>Medicamento</b></th>
                        <th style="border: 1px solid black;"><b>Unidade</b></th>
                        <th style="border: 1px solid black;"><b>Quantidade</b></th>
                        <th style="border: 1px solid black;"><b>Situação</b></th>
                        <th style="border: 1px solid black;"><b>Data Aplicação</b></th>
                        <th style="border: 1px solid black;"><b>Lote Aplicação</b></th>
                        <th style="border: 1px solid black;"><b>C.Barras</b></th>
                        <th style="border: 1px solid black;"><b>Validade</b></th>
                        <th style="border: 1px solid black;"><b>Enfermagem</b></th>
                    </tr>
                </thead>
                <tbody>
                ';

            $enfermeira = null;
            $enfermeira_nome = null;

            foreach ($semana->medicamentos as $med) {
                $dt_aplicacao = null;
                if ($med->aplicado_em) {
                    $dt_aplicacao = dataDbForm(explode(' ', $med->aplicado_em)[0]);
                }
                if (!$enfermeira && $med->userAplicacao) {
                    $enfermeira = $med->userAplicacao;
                    $enfermeira_nome = $enfermeira->nome;
                }
                $lote = $med->lotes->pluck('lote')->implode(', ');
                $codigo = $med->lotes->pluck('codigo_barras')->implode(', ');
                $tabela .= '
                    <tr>
                        <td style="border: 1px solid black;">' . ($med->medicamento->nome ?? '-') . '</td>
                        <td style="border: 1px solid black;">' . ($med->medicamento->unidade ?? '-') . '</td>
                        <td style="border: 1px solid black;">' . $med->quantidade . '</td>
                        <td style="border: 1px solid black;">' . $med->situacao . '</td>
                        <td style="border: 1px solid black;">' . $dt_aplicacao . '</td>
                        <td style="border: 1px solid black;">' . $lote . '</td>
                        <td style="border: 1px solid black;">' . $codigo . '</td>
                        <td style="border: 1px solid black;">-</td>
                        <td style="border: 1px solid black;">' . $enfermeira_nome . '</td>
                    </tr>
                ';
            }

            if (!$enfermeira && $semana->userAplicacao) {
                $enfermeira = $semana->userAplicacao;
                $enfermeira_nome = $enfermeira->nome;
            }

            $tabela .= '
                </tbody>
            </table>
            ';

            $pdf->writeHTML($tabela, true, false, false, false, '');
            $pdf->Ln(10);

            // assinatura digital (se a enfermeira tiver certificado)
            if ($enfermeira && $enfermeira->imagem_carimbo) {
                $pfxPath = public_path("img/usuarios/certificados_digitais/$enfermeira->imagem_carimbo");
                $pfxPass = $enfermeira->senha_certificado;

                $pfxContents = @file_get_contents($pfxPath);
                $certs = [];

                if ($pfxContents !== false && openssl_pkcs12_read($pfxContents, $certs, $pfxPass)) {
                    $certPem = $certs['cert'];
                    $pkeyPem = $certs['pkey'];
                    $pdf->setSignature($certPem, $pkeyPem, '', '', 2);
                } else {
                    \Log::warning("Falha ao ler o PFX com openssl_pkcs12_read() para: $enfermeira->nome");
                }

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $w = 50;
                $h = 25;

                $pdf->setSignatureAppearance($x, $y, $w, $h);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Text($x, $y, "Assinado digitalmente por: $enfermeira->nome");
                $y += 3;
                $pdf->Text($x, $y, "Coren: $enfermeira->coren");
                $y += 3;
                $pdf->Text($x, $y, "Data/Hora: " . date('d/m/Y H:i:s'));
            }

            $arquivo = "Relatorio_Prescricao_${prescricao_id}_Semana_" . $semana->nr_semana . '_' . date('YmdHis') . '.pdf';
            $diretorio = public_path("prescricoes/$prescricao_id/relatorios");

            if (!File::isDirectory($diretorio)) {
                File::makeDirectory($diretorio, 0755, true, true);
            }

            $destino = $diretorio . '/' . $arquivo;

            $pdf->Output($destino, 'F');

            $array_arquivos[] = [
                'prescricao_id' => $prescricao_id,
                'semana' => $semana->nr_semana,
                'arquivo' => $arquivo,
                'destino' => $destino,
            ];
        }

        $link_zip = null;

        if (count($array_arquivos) > 0) {
            $diretorio_zip = public_path("zips/relatorios");
            if (!File::isDirectory($diretorio_zip)) {
                File::makeDirectory($diretorio_zip, 0755, true, true);
            }

            $path_zip = $diretorio_zip . "/prescricao_$prescricao_id.zip";
            $link_zip = "/public/zips/relatorios/prescricao_$prescricao_id.zip";
            $zip = new \ZipArchive();

            if (!$zip->open($path_zip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                throw new \Exception('Falha ao criar arquivo zip.');
            }

            foreach ($array_arquivos as $arq) {
                $zip->addFile($arq['destino'], $arq['arquivo']);
            }
            $zip->close();
        }

        return view('sistema/prescricoes/imprimir_paciente', compact('array_arquivos', 'link_zip'));
    }

    public function update_flag(Request $request)
    {
        $semana = PrescricaoSemana::find($request->id);
        if ($semana) {
            $flag = $request->flag;
            $value = ($request->value == 1 ? 1 : 0);

            $update_data = [$flag => $value];
            $user_nome = "";

            if ($value == 1) {
                $user = session()->get('user');
                $adm = session()->get('administrador');
                $user_nome = $adm ? $adm->nome : ($user ? $user->nome : "Sistema");

                if ($flag == 'flag_coordenacao') {
                    $update_data['user_nome_coordenacao'] = $user_nome;
                } elseif ($flag == 'flag_qualidade') {
                    $update_data['user_nome_qualidade'] = $user_nome;
                }
            } else {
                if ($flag == 'flag_coordenacao') {
                    $update_data['user_nome_coordenacao'] = null;
                } elseif ($flag == 'flag_qualidade') {
                    $update_data['user_nome_qualidade'] = null;
                }
            }

            $semana->where('id', $request->id)->update($update_data);
            return response()->json(['success' => true, 'id' => $request->id, 'flag' => $flag, 'value' => $value, 'user_nome' => $user_nome]);
        }
        return response()->json(['success' => false, 'message' => 'Semana não encontrada']);
    }

    public function salvar_observacao(Request $request)
    {
        try {
            $user = auth()->user() ?? session()->get('user');
            $semana = PrescricaoSemana::find($request->prescricao_semana_id);
            if (!$semana) {
                throw new \Exception('Semana não encontrada.');
            }

            PrescricaoObservacao::create([
                'prescricao_semana_id' => $request->prescricao_semana_id,
                'user_id' => $user->id ?? null,
                'observacao' => $request->observacao,
            ]);

            $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Observação', 'Observação adicionada na semana ' . $semana->nr_semana);

            return redirect()->back()->with('mensagem', 'Observação salva com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->with('mensagem_erro', 'Erro ao salvar observação: ' . $e->getMessage());
        }
    }

    // ---------- helpers ----------

    private function inserir_medicamentos_semana($semana, $request, $situacao = 'Aberta')
    {
        for ($i = 1; $i <= $request->contador_medicamentos; $i++) {
            $var_med = 'medicamento_id_' . $i;
            $var_qtd = 'quantidade_' . $i;
            $var_combo = 'combo_id_' . $i;

            $medicamento_id = $request->$var_med ?? null;
            if (!$medicamento_id) {
                continue;
            }

            $medicamento = Medicamento::find($medicamento_id);
            $gera_aplicacao = $medicamento && $medicamento->aplicacao == 'Sim';

            $med = PrescricaoSemanaMedicamento::create([
                'prescricao_semana_id' => $semana->id,
                'medicamento_id' => $medicamento_id,
                'combo_id' => $request->$var_combo ?? null,
                'clinica_id_aplicacao' => null,
                'is_soro' => $medicamento && str_starts_with(strtolower($medicamento->nome), 'soro'),
                'gera_aplicacao' => $gera_aplicacao,
                'quantidade' => $request->$var_qtd ?? 1,
                'situacao' => $situacao,
                'data_prevista' => $semana->data_prevista,
            ]);

            $nome_med = $medicamento ? $medicamento->nome : ('Medicamento #' . $medicamento_id);
            $this->registrar_log($semana->prescricao_id, 'semana', $semana->id, 'Adição de Medicamento', 'Medicação "' . $nome_med . '" (qtd ' . ($request->$var_qtd ?? 1) . ') adicionada na semana ' . $semana->nr_semana . ($situacao == 'Pendente' ? ' — pendente' : ''));
        }
    }

    private function recalcular_tem_aplicacao($semana)
    {
        $tem = $semana->medicamentos()->where('gera_aplicacao', true)->exists();
        $semana->tem_aplicacao = $tem;
        $semana->save();
    }

    private function recalcular_semanas($prescricao)
    {
        $semanas = $prescricao->semanas()->orderBy('nr_semana')->get();
        $nr = 1;
        foreach ($semanas as $s) {
            $s->nr_semana = $nr;
            $s->save();
            $nr++;
        }
        $prescricao->qt_semanas = $semanas->count();
        $prescricao->qt_semanas_aplicacao = $prescricao->semanas()->where('tem_aplicacao', true)->count();
        $prescricao->save();
    }

    private function preencher_lacunas_semanais($prescricao, $semanasDados)
    {
        // última semana já existente na prescrição (data prevista)
        $ultima_existente = $prescricao->semanas()
            ->whereNotNull('data_prevista')
            ->max('data_prevista');

        $novas = [];
        foreach ($semanasDados as $d) {
            if (!empty($d['data_prevista'])) {
                $novas[] = $d['data_prevista'];
            }
        }

        if (!$ultima_existente || count($novas) === 0) {
            return $semanasDados;
        }

        sort($novas);
        $fim = strtotime(end($novas));
        $cur = strtotime($ultima_existente);
        $faltantes = [];

        while ($cur < $fim) {
            $prox = strtotime('+7 days', $cur);
            if ($prox >= $fim) {
                break;
            }
            $dt = date('Y-m-d', $prox);
            // não duplica semana que o usuário já informou
            if (!in_array($dt, $novas)) {
                $faltantes[] = $dt;
            }
            $cur = $prox;
        }

        foreach ($faltantes as $dt) {
            $semanasDados[] = ['data_prevista' => $dt, 'pausa' => true, 'obs' => null, 'meds' => []];
        }

        // reordena por data para a numeração das semanas ficar cronológica
        usort($semanasDados, function ($a, $b) {
            return strtotime($a['data_prevista']) <=> strtotime($b['data_prevista']);
        });

        return $semanasDados;
    }

    private function semana_ja_aplicada($semana)
    {
        if (in_array($semana->situacao, ['Aplicada', 'Aplicação Parcial', 'Em Atendimento'])) {
            return true;
        }
        return $semana->medicamentos()->where('situacao', 'Aplicada')->exists();
    }

    private function semana_esta_paga($semana, $parcela = null)
    {
        if (!$parcela) {
            $parcela = FinanceiroParcela::where('prescricao_semana_id', $semana->id)->first();
        }
        if ($parcela) {
            if ($parcela->situacao == 'Paga') {
                return true;
            }
            if (floatval($parcela->valor_parcela) > 0 && floatval($parcela->valor_pago) >= floatval($parcela->valor_parcela)) {
                return true;
            }
            return false;
        }
        // sem parcela: considera paga se o tratamento não possui valor ou o crédito em aberto cobre tudo
        $valor = (float) $semana->prescricao->valor_tratamento;
        $credito = (float) $semana->prescricao->credito_em_aberto;
        return $valor == 0 || $credito >= $valor - 0.005;
    }

    private function registrar_log($prescricao_id, $entidade, $entidade_id, $acao, $descricao, $dados_antigos = null, $dados_novos = null)
    {
        $user = auth()->user() ?? session()->get('user');
        PrescricaoLog::create([
            'prescricao_id' => $prescricao_id,
            'entidade' => $entidade,
            'entidade_id' => $entidade_id,
            'user_id' => $user->id ?? null,
            'acao' => $acao,
            'descricao' => $descricao,
            'dados_antigos' => $dados_antigos,
            'dados_novos' => $dados_novos,
        ]);
    }

    private function badgeSituacao($situacao)
    {
        switch ($situacao) {
            case 'Agendada':
                return '<span class="badge rounded-pill bg-label-warning">' . $situacao . '</span>';
            case 'Em Andamento':
                return '<span class="badge rounded-pill bg-label-info">' . $situacao . '</span>';
            case 'Concluída':
                return '<span class="badge rounded-pill bg-label-success">' . $situacao . '</span>';
            case 'Cancelada':
                return '<span class="badge rounded-pill bg-label-danger">' . $situacao . '</span>';
            default:
                return '<span class="badge rounded-pill bg-label-secondary">' . $situacao . '</span>';
        }
    }

    private function badgeSituacaoFinanceira($situacao)
    {
        switch ($situacao) {
            case 'Pago':
                return '<span class="badge rounded-pill bg-success">' . $situacao . '</span>';
            case 'Parcial':
                return '<span class="badge rounded-pill bg-warning">' . $situacao . '</span>';
            case 'Em Aberto':
                return '<span class="badge rounded-pill bg-danger">' . $situacao . '</span>';
            case 'Cancelado':
                return '<span class="badge rounded-pill bg-secondary">' . $situacao . '</span>';
            default:
                return '<span class="badge rounded-pill bg-secondary">' . $situacao . '</span>';
        }
    }
}
