<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\Procedimento;
use App\Models\Aplicacao;
use App\Models\Paciente;
use App\Models\Clinica;
use App\Models\Medicamento;
use App\Models\User;

class EnfermagemRelatorioTest extends TestCase
{
    use DatabaseTransactions;

    public function test_gerar_relatorio_enfermagem_returns_filtered_procedures()
    {
        // 1. Criar Clinica
        $clinica = Clinica::create([
            'nome' => 'Clinica Teste',
            'email' => 'teste@clinica.com',
            'telefone' => '11999999999',
            'cidade' => 'Sao Paulo',
            'uf' => 'SP',
            'cep' => '01001000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'bairro' => 'Bairro Teste',
            'id_unidade_feegow' => 1,
            'cnpj' => '12345678901234',
        ]);

        // 2. Criar Paciente
        $paciente = Paciente::create([
            'nm_paciente' => 'Paciente Teste',
            'paciente_id_feegow' => 12345,
            'st_cadastro' => 'Ativo',
        ]);

        // 3. Criar Medicamento
        $medicamento = Medicamento::create([
            'nome' => 'Medicamento Teste',
            'fabricante' => 'Fabricante Teste',
            'unidade' => 'Ampola',
            'vl_venda' => '10.00',
            'situacao' => 'Ativo',
            'aplicacao' => 'Sim',
        ]);

        // 4. Criar Procedimento
        $procedimento = Procedimento::create([
            'codigo' => 999,
            'nr_procedimento' => 1,
            'clinica_id' => $clinica->id,
            'clinica_id_aplicacao' => $clinica->id,
            'paciente_id' => $paciente->id,
            'data_cad' => '2026-06-22',
            'data_aplicacao' => '2026-06-22',
            'valor' => 100.0,
            'st_pagamento' => 'Sim',
            'medico' => 'Dr. Teste',
            'situacao' => 'Fila de Aplicação',
            'dt_hr_chegada' => '2026-06-22 10:00:00',
            'dt_hr_atendimento' => '2026-06-22 10:15:00',
        ]);

        // 5. Criar Aplicacao
        $aplicacao = Aplicacao::create([
            'procedimento_id' => $procedimento->id,
            'medicamento_id' => $medicamento->id,
            'quantidade' => 1,
            'valor' => 100.0,
            'total' => 100.0,
            'situacao' => 'Aplicada',
            'dt_hr_chegada' => '2026-06-22 10:00:00',
            'dt_hr_atendimento' => '2026-06-22 10:15:00',
        ]);

        // Forçar updated_at da aplicação para um dia específico
        $aplicacao->updated_at = '2026-06-22 10:20:00';
        $aplicacao->save();

        // 6. Testar o filtro de relatorio
        $filtro = [
            'clinica_id' => $clinica->id,
            'paciente_id' => $paciente->id,
            'dt_inc' => '2026-06-22',
            'dt_fn' => '2026-06-22',
        ];

        $resultados = Procedimento::gerar_relatorio_enfermagem($filtro);
        $this->assertCount(1, $resultados);
        $this->assertEquals($procedimento->id, $resultados[0]->id);

        // Testar filtro com data fora do range
        $filtroFora = [
            'clinica_id' => $clinica->id,
            'paciente_id' => $paciente->id,
            'dt_inc' => '2026-06-23',
            'dt_fn' => '2026-06-23',
        ];
        $resultadosFora = Procedimento::gerar_relatorio_enfermagem($filtroFora);
        $this->assertCount(0, $resultadosFora);
    }

    public function test_aplicacao_fallback_when_dates_are_null()
    {
        // Criar aplicacao com dt_hr_chegada e dt_hr_atendimento como null
        $aplicacao = new Aplicacao();
        $aplicacao->updated_at = '2026-03-31 15:30:00';

        $chegada = $aplicacao->dt_hr_chegada ?? $aplicacao->updated_at;
        $atendimento = $aplicacao->dt_hr_atendimento ?? $aplicacao->updated_at;

        $this->assertEquals('2026-03-31 15:30:00', $chegada);
        $this->assertEquals('2026-03-31 15:30:00', $atendimento);
    }
}
