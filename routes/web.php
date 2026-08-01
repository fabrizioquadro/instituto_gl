<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardAdmController;
use App\Http\Controllers\ClinicaAdmController;
use App\Http\Controllers\AdministradorAdmController;
use App\Http\Controllers\UsuarioAdmController;
use App\Http\Controllers\MedicamentoAdmController;
use App\Http\Controllers\FornecedorAdmController;
use App\Http\Controllers\DashboardSistemaController;
use App\Http\Controllers\EntradaSistemaController;
use App\Http\Controllers\BaixaSistemaController;
use App\Http\Controllers\TransferenciaSistemaController;
use App\Http\Controllers\EstoqueSistemaController;
use App\Http\Controllers\ProcedimentoSistemaController;
use App\Http\Controllers\PacienteSistemaController;
use App\Http\Controllers\DashboardAdmSisController;
use App\Http\Controllers\EstoqueAdmController;
use App\Http\Controllers\FinanceiroSistemaController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\MigracaoController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\ApiKaminoController;
use App\Http\Controllers\ApiFlegowController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [LoginController::class, 'index'])->name('index');
Route::get('/get_procedimentos', [ApiFlegowController::class, 'get_procedimentos']);
Route::get('/get_especialidades', [ApiFlegowController::class, 'get_especialidades']);
Route::get('/get_grupos_procedimento', [ApiFlegowController::class, 'get_grupos_procedimento']);
Route::get('/integra_api_kamino', [ApiKaminoController::class, 'gera_xlsx_kamino']);
Route::get('/teste', [LoginController::class, 'teste']);
Route::get('/teste_financeiro', [LoginController::class, 'teste_financeiro']);
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('/esqueceu_senha', [LoginController::class, 'esqueceu_senha'])->name('esqueceu_senha');
Route::post('/recuperar_senha', [LoginController::class, 'recuperar_senha'])->name('recuperar_senha');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/medicamentos/buscar', [MedicamentoAdmController::class, 'buscar'])->name('adm.medicamentos.buscar');
Route::get('/combos/buscar_medicamentos', [ComboController::class, 'buscar_medicamentos'])->name('adm.combos.buscar_medicamentos');

Route::middleware(['verificaAdministrador'])->group(function () {
    Route::prefix('adm')->group(function(){
        Route::get('/dashboard', [DashboardAdmController::class, 'index'])->name('adm.dashboard');
        Route::get('/dashboard/alterar_clinica_user', [DashboardAdmController::class, 'alterar_clinica_user'])->name('adm.dashboard.alterar_clinica_user');
        Route::get('/dashboard/alterar_tipo_user', [DashboardAdmController::class, 'alterar_tipo_user'])->name('adm.dashboard.alterar_tipo');
        Route::get('/dashboard/alterar_enfermeira', [DashboardAdmController::class, 'alterar_enfermeira'])->name('adm.dashboard.alterar_enfermeira');
        Route::get('/perfil', [DashboardAdmController::class, 'perfil'])->name('adm.perfil');
        Route::get('/alterar_senha', [DashboardAdmController::class, 'alterar_senha'])->name('adm.alterar_senha');
        Route::post('/perfil/atualizar_foto', [DashboardAdmController::class, 'atualizar_foto'])->name('adm.perfil.atualizar_foto');
        Route::post('/perfil/update', [DashboardAdmController::class, 'update'])->name('adm.perfil.update');
        Route::get('/perfil/resetar_foto', [DashboardAdmController::class, 'resetar_foto'])->name('adm.perfil.resetar_foto');
        Route::post('/alterar_senha/update', [DashboardAdmController::class, 'alterar_senha_update'])->name('adm.alterar_senha.update');

        Route::get('/administradores', [AdministradorAdmController::class, 'index'])->name('adm.administradores');
        Route::get('/administradores/adicionar', [AdministradorAdmController::class, 'adicionar'])->name('adm.administradores.adicionar');
        Route::get('/administradores/editar/{id}', [AdministradorAdmController::class, 'editar'])->name('adm.administradores.editar');
        Route::get('/administradores/excluir/{id}', [AdministradorAdmController::class, 'excluir'])->name('adm.administradores.excluir');
        Route::get('/administradores/alterar_senha/{id}', [AdministradorAdmController::class, 'alterar_senha'])->name('adm.administradores.alterar_senha');
        Route::post('/administradores/insert', [AdministradorAdmController::class, 'insert'])->name('adm.administradores.insert');
        Route::post('/administradores/update', [AdministradorAdmController::class, 'update'])->name('adm.administradores.update');
        Route::post('/administradores/delete', [AdministradorAdmController::class, 'delete'])->name('adm.administradores.delete');
        Route::post('/administradores/alterar_senha_update', [AdministradorAdmController::class, 'alterar_senha_update'])->name('adm.administradores.alterar_senha_update');

        Route::get('/clinicas', [ClinicaAdmController::class, 'index'])->name('adm.clinicas');
        Route::get('/clinicas/adicionar', [ClinicaAdmController::class, 'adicionar'])->name('adm.clinicas.adicionar');
        Route::get('/clinicas/editar/{id}', [ClinicaAdmController::class, 'editar'])->name('adm.clinicas.editar');
        Route::get('/clinicas/excluir/{id}', [ClinicaAdmController::class, 'excluir'])->name('adm.clinicas.excluir');
        Route::post('/clinicas/insert', [ClinicaAdmController::class, 'insert'])->name('adm.clinicas.insert');
        Route::post('/clinicas/update', [ClinicaAdmController::class, 'update'])->name('adm.clinicas.update');
        Route::post('/clinicas/delete', [ClinicaAdmController::class, 'delete'])->name('adm.clinicas.delete');

        Route::get('/usuarios', [UsuarioAdmController::class, 'index'])->name('adm.usuarios');
        Route::get('/usuarios/adicionar', [UsuarioAdmController::class, 'adicionar'])->name('adm.usuarios.adicionar');
        Route::get('/usuarios/editar/{id}', [UsuarioAdmController::class, 'editar'])->name('adm.usuarios.editar');
        Route::get('/usuarios/excluir/{id}', [UsuarioAdmController::class, 'excluir'])->name('adm.usuarios.excluir');
        Route::get('/usuarios/alterar_senha/{id}', [UsuarioAdmController::class, 'alterar_senha'])->name('adm.usuarios.alterar_senha');
        Route::post('/usuarios/insert', [UsuarioAdmController::class, 'insert'])->name('adm.usuarios.insert');
        Route::post('/usuarios/update', [UsuarioAdmController::class, 'update'])->name('adm.usuarios.update');
        Route::post('/usuarios/delete', [UsuarioAdmController::class, 'delete'])->name('adm.usuarios.delete');
        Route::post('/usuarios/alterar_senha_update', [UsuarioAdmController::class, 'alterar_senha_update'])->name('adm.usuarios.alterar_senha_update');

        Route::get('/medicamentos', [MedicamentoAdmController::class, 'index'])->name('adm.medicamentos');
        Route::get('/medicamentos/adicionar', [MedicamentoAdmController::class, 'adicionar'])->name('adm.medicamentos.adicionar');
        Route::get('/medicamentos/editar/{id}', [MedicamentoAdmController::class, 'editar'])->name('adm.medicamentos.editar');
        Route::get('/medicamentos/excluir/{id}', [MedicamentoAdmController::class, 'excluir'])->name('adm.medicamentos.excluir');
        Route::post('/medicamentos/insert', [MedicamentoAdmController::class, 'insert'])->name('adm.medicamentos.insert');
        Route::post('/medicamentos/update', [MedicamentoAdmController::class, 'update'])->name('adm.medicamentos.update');
        Route::post('/medicamentos/delete', [MedicamentoAdmController::class, 'delete'])->name('adm.medicamentos.delete');

        Route::get('/fornecedores', [FornecedorAdmController::class, 'index'])->name('adm.fornecedores');
        Route::get('/fornecedores/adicionar', [FornecedorAdmController::class, 'adicionar'])->name('adm.fornecedores.adicionar');
        Route::get('/fornecedores/editar/{id}', [FornecedorAdmController::class, 'editar'])->name('adm.fornecedores.editar');
        Route::get('/fornecedores/excluir/{id}', [FornecedorAdmController::class, 'excluir'])->name('adm.fornecedores.excluir');
        Route::post('/fornecedores/insert', [FornecedorAdmController::class, 'insert'])->name('adm.fornecedores.insert');
        Route::post('/fornecedores/update', [FornecedorAdmController::class, 'update'])->name('adm.fornecedores.update');
        Route::post('/fornecedores/delete', [FornecedorAdmController::class, 'delete'])->name('adm.fornecedores.delete');

        Route::get('/estoques', [EstoqueAdmController::class, 'index'])->name('adm.estoques');
        Route::get('/estoques/get_lotes_medicamento', [EstoqueAdmController::class, 'get_lotes_medicamento'])->name('adm.estoques.get_lotes_medicamento');
        Route::post('/estoques/exportar', [EstoqueAdmController::class, 'exportar'])->name('adm.estoques.exportar');

        Route::get('/relatorios/financeiro', [RelatorioController::class, 'financeiro'])->name('adm.relatorios.financeiro');
        Route::get('/relatorios/financeiro-simplificado', [RelatorioController::class, 'financeiro_simplificado'])->name('adm.relatorios.financeiro_simplificado');
        Route::get('/relatorios/vendas', [RelatorioController::class, 'vendas'])->name('adm.relatorios.vendas');
        Route::get('/relatorios/enfermagem', [RelatorioController::class, 'enfermagem'])->name('adm.relatorios.enfermagem');
        Route::get('/relatorios/transferencias', [RelatorioController::class, 'transferencias'])->name('adm.relatorios.transferencias');
        Route::get('/relatorios/baixas', [RelatorioController::class, 'baixas'])->name('adm.relatorios.baixas');
        Route::get('/relatorios/recepcao', [RelatorioController::class, 'recepcao'])->name('adm.relatorios.recepcao');
        Route::get('/relatorios/caixa', [RelatorioController::class, 'caixa'])->name('adm.relatorios.caixa');
        Route::post('/relatorios/caixa/gerar', [RelatorioController::class, 'caixa_gerar'])->name('adm.relatorios.caixa.gerar');
        Route::post('/relatorios/baixas/gerar', [RelatorioController::class, 'baixas_gerar'])->name('adm.relatorios.baixas.gerar');
        Route::post('/relatorios/recepcao/gerar', [RelatorioController::class, 'recepcao_gerar'])->name('adm.relatorios.recepcao.gerar');
        Route::post('/relatorios/financeiro/gerar', [RelatorioController::class, 'financeiro_gerar'])->name('adm.relatorios.financeiro.gerar');
        Route::post('/relatorios/financeiro-simplificado/gerar', [RelatorioController::class, 'financeiro_simplificado_gerar'])->name('adm.relatorios.financeiro_simplificado.gerar');
        Route::post('/relatorios/vendas/gerar', [RelatorioController::class, 'vendas_gerar'])->name('adm.relatorios.vendas.gerar');
        Route::post('/relatorios/enfermagem/gerar', [RelatorioController::class, 'enfermagem_gerar'])->name('adm.relatorios.enfermagem.gerar');
        Route::post('/relatorios/transferencias/gerar', [RelatorioController::class, 'transferencias_gerar'])->name('adm.relatorios.transferencias.gerar');
        Route::post('/relatorios/exportar', [RelatorioController::class, 'exportar'])->name('adm.relatorios.exportar');
        Route::post('/relatorios/exportar/enfermagem', [RelatorioController::class, 'exportar_enfermagem'])->name('adm.relatorios.exportar_enfermagem');
        Route::post('/relatorios/exportar/financeiro', [RelatorioController::class, 'exportar_financeiro'])->name('adm.relatorios.exportar_financeiro');
        Route::post('/relatorios/exportar/financeiro-simplificado', [RelatorioController::class, 'exportar_financeiro_simplificado'])->name('adm.relatorios.exportar_financeiro_simplificado');
        Route::post('/relatorios/exportar/vendas', [RelatorioController::class, 'exportar_vendas'])->name('adm.relatorios.exportar_vendas');
        Route::post('/relatorios/exportar/baixas', [RelatorioController::class, 'exportar_baixas'])->name('adm.relatorios.exportar_baixas');
        Route::post('/relatorios/exportar/transferencias', [RelatorioController::class, 'exportar_transferencias'])->name('adm.relatorios.exportar_transferencias');
        Route::get('/relatorios/estoque', [RelatorioController::class, 'estoque'])->name('adm.relatorios.estoque');
        Route::post('/relatorios/estoque/gerar', [RelatorioController::class, 'estoque_gerar'])->name('adm.relatorios.estoque.gerar');
        Route::post('/relatorios/exportar/estoque', [RelatorioController::class, 'exportar_estoque'])->name('adm.relatorios.exportar_estoque');

        Route::get('/combos', [ComboController::class, 'index'])->name('adm.combos');
        Route::get('/combos/adicionar', [ComboController::class, 'adicionar'])->name('adm.combos.adicionar');
        Route::get('/combos/editar/{id}', [ComboController::class, 'editar'])->name('adm.combos.editar');
        Route::get('/combos/excluir/{id}', [ComboController::class, 'excluir'])->name('adm.combos.excluir');
        Route::get('/combos/delete_medicamento', [ComboController::class, 'delete_medicamento'])->name('adm.combos.delete_medicamento');
        Route::post('/combos/insert', [ComboController::class, 'insert'])->name('adm.combos.insert');
        Route::post('/combos/update', [ComboController::class, 'update'])->name('adm.combos.update');
        Route::post('/combos/delete', [ComboController::class, 'delete'])->name('adm.combos.delete');

        Route::get('/grupos', [GrupoController::class, 'index'])->name('adm.grupos');
        Route::get('/grupos/adicionar', [GrupoController::class, 'adicionar'])->name('adm.grupos.adicionar');
        Route::get('/grupos/editar/{id}', [GrupoController::class, 'editar'])->name('adm.grupos.editar');
        Route::get('/grupos/excluir/{id}', [GrupoController::class, 'excluir'])->name('adm.grupos.excluir');
        Route::post('/grupos/insert', [GrupoController::class, 'insert'])->name('adm.grupos.insert');
        Route::post('/grupos/update', [GrupoController::class, 'update'])->name('adm.grupos.update');
        Route::post('/grupos/delete', [GrupoController::class, 'delete'])->name('adm.grupos.delete');

        // rotas do sistema para o administrador
        Route::prefix('sistema')->group(function(){
            Route::any('/dashboard', [DashboardAdmSisController::class, 'index'])->name('adm.sistema.dashboard');
        });
    });
});

Route::middleware(['verificaAcessoSistema'])->group(function () {
    Route::prefix('sistema')->group(function(){
        Route::any('/dashboard', [DashboardSistemaController::class, 'index'])->name('sistema.dashboard');
        Route::get('/dashboard/enfermagem_acessar_procedimento/{id}', [DashboardSistemaController::class, 'enfermagem_acessar_procedimento'])->name('sistema.dashboard.enfermagem_acessar_procedimento');
        Route::get('/dashboard/enfermagem_visualizar_procedimento/{id}', [DashboardSistemaController::class, 'enfermagem_visualizar_procedimento'])->name('sistema.dashboard.enfermagem_visualizar_procedimento');
        Route::get('/dashboard/busca_lote_por_codigo', [DashboardSistemaController::class, 'busca_lote_por_codigo'])->name('sistema.dashboard.busca_lote_por_codigo');
        Route::get('/dashboard/busca_lote_por_codigo_frasco', [DashboardSistemaController::class, 'busca_lote_por_codigo_frasco'])->name('sistema.dashboard.busca_lote_por_codigo_frasco');
        Route::post('/dashboard/abrir_frasco', [DashboardSistemaController::class, 'abrir_frasco'])->name('sistema.dashboard.abrir_frasco');
        Route::post('/dashboard/set_aplicacao', [DashboardSistemaController::class, 'set_aplicacao'])->name('sistema.dashboard.set_aplicacao');
        Route::get('/perfil', [DashboardSistemaController::class, 'perfil'])->name('sistema.perfil');
        Route::get('/alterar_senha', [DashboardSistemaController::class, 'alterar_senha'])->name('sistema.alterar_senha');
        Route::post('/perfil/atualizar_foto', [DashboardSistemaController::class, 'atualizar_foto'])->name('sistema.perfil.atualizar_foto');
        Route::post('/perfil/update', [DashboardSistemaController::class, 'update'])->name('sistema.perfil.update');
        Route::get('/perfil/resetar_foto', [DashboardSistemaController::class, 'resetar_foto'])->name('sistema.perfil.resetar_foto');
        Route::post('/alterar_senha/update', [DashboardSistemaController::class, 'alterar_senha_update'])->name('sistema.alterar_senha.update');
        Route::get('/dashboard/add_biopedancia_coleta/{paciente_id?}', [DashboardSistemaController::class, 'add_biopedancia_coleta'])->name('sistema.dashboard.add_biopedancia_coleta');
        Route::post('/dashboard/insert_biopedancia_coleta', [DashboardSistemaController::class, 'insert_biopedancia_coleta'])->name('sistema.dashboard.insert_biopedancia_coleta');
        Route::get('/dashboard/get_lotes_medicamento_mg', [DashboardSistemaController::class, 'get_lotes_medicamento_mg'])->name('sistema.dashboard.get_lotes_medicamento_mg');
        Route::get('/dashboard/filtrar_atrasados', [DashboardSistemaController::class, 'filtrar_atrasados'])->name('sistema.dashboard.filtrar_atrasados');
        Route::get('/dashboard/keep-alive', [DashboardSistemaController::class, 'keep_alive'])->name('sistema.dashboard.keep_alive');

        Route::any('/fila_atendimento', [DashboardSistemaController::class, 'fila_atendimento'])->name('sistema.fila_atendimento');

        Route::get('/entradas', [EntradaSistemaController::class, 'index'])->name('sistema.entradas');
        Route::get('/entradas/adicionar', [EntradaSistemaController::class, 'adicionar'])->name('sistema.entradas.adicionar');
        Route::get('/entradas/editar/{id}', [EntradaSistemaController::class, 'editar'])->name('sistema.entradas.editar');
        Route::get('/entradas/excluir/{id}', [EntradaSistemaController::class, 'excluir'])->name('sistema.entradas.excluir');
        Route::get('/entradas/visualizar/{id}', [EntradaSistemaController::class, 'visualizar'])->name('sistema.entradas.visualizar');
        Route::get('/entradas/gerar_codigo_barras', [EntradaSistemaController::class, 'gerar_codigo_barras'])->name('sistema.entradas.gerar_codigo_barras');
        Route::get('/entradas/etiquetas_imprimir/{id}', [EntradaSistemaController::class, 'etiquetas_imprimir']);
        Route::post('/entradas/insert', [EntradaSistemaController::class, 'insert'])->name('sistema.entradas.insert');
        Route::post('/entradas/update', [EntradaSistemaController::class, 'update'])->name('sistema.entradas.update');
        Route::post('/entradas/delete', [EntradaSistemaController::class, 'delete'])->name('sistema.entradas.delete');
        Route::get('/entradas/gerar_pdf/{id}', [EntradaSistemaController::class, 'gerar_pdf'])->name('sistema.entradas.gerar_pdf');


        Route::get('/baixas', [BaixaSistemaController::class, 'index'])->name('sistema.baixas');
        Route::get('/baixas/adicionar', [BaixaSistemaController::class, 'adicionar'])->name('sistema.baixas.adicionar');
        Route::get('/baixas/editar/{id}', [BaixaSistemaController::class, 'editar'])->name('sistema.baixas.editar');
        Route::get('/baixas/excluir/{id}', [BaixaSistemaController::class, 'excluir'])->name('sistema.baixas.excluir');
        Route::get('/baixas/visualizar/{id}', [BaixaSistemaController::class, 'visualizar'])->name('sistema.baixas.visualizar');
        Route::get('/baixas/get_lotes_medicamento', [BaixaSistemaController::class, 'get_lotes_medicamento'])->name('sistema.baixas.get_lotes_medicamento');
        Route::post('/baixas/insert', [BaixaSistemaController::class, 'insert'])->name('sistema.baixas.insert');
        Route::post('/baixas/update', [BaixaSistemaController::class, 'update'])->name('sistema.baixas.update');
        Route::post('/baixas/delete', [BaixaSistemaController::class, 'delete'])->name('sistema.baixas.delete');
        Route::get('/baixas/gerar_pdf/{id}', [BaixaSistemaController::class, 'gerar_pdf'])->name('sistema.baixas.gerar_pdf');

        Route::get('/baixas_abertos', [BaixaSistemaController::class, 'index_abertos'])->name('sistema.baixas_abertos');
        Route::get('/baixas_abertos/adicionar', [BaixaSistemaController::class, 'adicionar_abertos'])->name('sistema.baixas.adicionar_abertos');
        Route::get('/baixas_abertos/excluir/{id}', [BaixaSistemaController::class, 'excluir_abertos'])->name('sistema.baixas.excluir_abertos');
        Route::post('/baixas_abertos/insert', [BaixaSistemaController::class, 'insert_abertos'])->name('sistema.baixas.insert_abertos');
        Route::post('/baixas_abertos/delete', [BaixaSistemaController::class, 'delete_abertos'])->name('sistema.baixas_abertos.delete');

        Route::get('/transferencias', [TransferenciaSistemaController::class, 'index'])->name('sistema.transferencias');
        Route::get('/transferencias/adicionar', [TransferenciaSistemaController::class, 'adicionar'])->name('sistema.transferencias.adicionar');
        Route::get('/transferencias/excluir/{id}', [TransferenciaSistemaController::class, 'excluir'])->name('sistema.transferencias.excluir');
        Route::get('/transferencias/visualizar/{id}', [TransferenciaSistemaController::class, 'visualizar'])->name('sistema.transferencias.visualizar');
        Route::get('/transferencias/imprimir_etiquetas/{id}', [TransferenciaSistemaController::class, 'imprimir_etiquetas'])->name('sistema.transferencias.imprimir_etiquetas');
        Route::get('/transferencias/gerar_pdf/{id}', [TransferenciaSistemaController::class, 'gerar_pdf'])->name('sistema.transferencias.gerar_pdf');
        Route::post('/transferencias/insert', [TransferenciaSistemaController::class, 'insert'])->name('sistema.transferencias.insert');
        Route::post('/transferencias/delete', [TransferenciaSistemaController::class, 'delete'])->name('sistema.transferencias.delete');
        Route::get('/transferencias/get_codigos_barras', [TransferenciaSistemaController::class, 'get_codigos_barras'])->name('sistema.transferencias.get_codigos_barras');
        Route::get('/transferencias/get_lotes_por_codigo_barras', [TransferenciaSistemaController::class, 'get_lotes_por_codigo_barras'])->name('sistema.transferencias.get_lotes_por_codigo_barras');

        Route::get('/estoques', [EstoqueSistemaController::class, 'index'])->name('sistema.estoques');

        Route::get('/procedimentos', [ProcedimentoSistemaController::class, 'index'])->name('sistema.procedimentos');
        Route::get('/procedimentos/index_pesq', [ProcedimentoSistemaController::class, 'index_pesq'])->name('sistema.procedimentos.index_pesq');
        Route::get('/procedimentos/adicionar/{retorno?}', [ProcedimentoSistemaController::class, 'adicionar'])->name('sistema.procedimentos.adicionar');
        Route::get('/procedimentos/editar/{id}', [ProcedimentoSistemaController::class, 'editar'])->name('sistema.procedimentos.editar');
        Route::get('/procedimentos/excluir/{id}', [ProcedimentoSistemaController::class, 'excluir'])->name('sistema.procedimentos.excluir');
        Route::get('/procedimentos/excluir_grupo/{codigo}', [ProcedimentoSistemaController::class, 'excluir_grupo'])->name('sistema.procedimentos.excluir_grupo');
        Route::get('/procedimentos/acessar/{id}/{retorno?}', [ProcedimentoSistemaController::class, 'acessar'])->name('sistema.procedimentos.acessar');
        Route::get('/procedimentos/acessar_grupo/{codigo}/{retorno?}', [ProcedimentoSistemaController::class, 'acessar_grupo'])->name('sistema.procedimentos.acessar_grupo');
        Route::get('/procedimentos/adicionar_grupo/{codigo}', [ProcedimentoSistemaController::class, 'adicionar_grupo'])->name('sistema.procedimentos.adicionar_grupo');
        Route::get('/procedimentos/adicionar_medicamentos/{codigo}', [ProcedimentoSistemaController::class, 'adicionar_medicamentos'])->name('sistema.procedimentos.adicionar_medicamentos');
        Route::post('/procedimentos/adicionar_medicamentos_insert', [ProcedimentoSistemaController::class, 'adicionar_medicamentos_insert'])->name('sistema.procedimentos.adicionar_medicamentos_insert');
        Route::post('/procedimentos/insert', [ProcedimentoSistemaController::class, 'insert'])->name('sistema.procedimentos.insert');
        Route::post('/procedimentos/salvar_observacao', [ProcedimentoSistemaController::class, 'salvar_observacao'])->name('sistema.procedimentos.salvar_observacao');
        Route::post('/procedimentos/setar_pagamento', [ProcedimentoSistemaController::class, 'setar_pagamento'])->name('sistema.procedimentos.setar_pagamento');
        Route::get('/procedimentos/setar_pagamento_pendente/{id}', [ProcedimentoSistemaController::class, 'setar_pagamento_pendente'])->name('sistema.procedimentos.setar_pagamento_pendente');
        Route::post('/procedimentos/enviar_fila_aplicacao', [ProcedimentoSistemaController::class, 'enviar_fila_aplicacao'])->name('sistema.procedimentos.enviar_fila_aplicacao');
        Route::post('/procedimentos/enviar_fila_aplicacao_sem_pagamento', [ProcedimentoSistemaController::class, 'enviar_fila_aplicacao_sem_pagamento'])->name('sistema.procedimentos.enviar_fila_aplicacao_sem_pagamento');
        Route::post('/procedimentos/financeiros', [ProcedimentoSistemaController::class, 'financeiros'])->name('sistema.procedimentos.financeiros');
        Route::post('/procedimentos/delete', [ProcedimentoSistemaController::class, 'delete'])->name('sistema.procedimentos.delete');
        Route::post('/procedimentos/delete_grupo', [ProcedimentoSistemaController::class, 'delete_grupo'])->name('sistema.procedimentos.delete_grupo');
        Route::post('/procedimentos/imprimir', [ProcedimentoSistemaController::class, 'imprimir'])->name('sistema.procedimentos.imprimir');
        Route::get('/procedimentos/imprimir_paciente/{codigo}', [ProcedimentoSistemaController::class, 'imprimir_paciente'])->name('sistema.procedimentos.imprimir_paciente');
        Route::post('/procedimentos/update_google_flag', [ProcedimentoSistemaController::class, 'update_google_flag'])->name('sistema.procedimentos.update_google_flag');
        Route::get('/procedimentos/imprimir_cadastro/{codigo}', [ProcedimentoSistemaController::class, 'imprimir_cadastro'])->name('sistema.procedimentos.imprimir_cadastro');
        Route::get('/procedimentos/imprimir_detalhes/{id}', [ProcedimentoSistemaController::class, 'imprimir_detalhes'])->name('sistema.procedimentos.imprimir_detalhes');
        Route::get('/procedimentos/get_aplicacao', [ProcedimentoSistemaController::class, 'get_aplicacao'])->name('sistema.procedimentos.get_aplicacao');
        Route::get('/procedimentos/update_aplicacao', [ProcedimentoSistemaController::class, 'update_aplicacao'])->name('sistema.procedimentos.update_aplicacao');
        Route::post('/procedimentos/atualizar_aplicacoes_lote', [ProcedimentoSistemaController::class, 'atualizarAplicacoesLote'])->name('sistema.procedimentos.atualizar_aplicacoes_lote');
        Route::post('/procedimentos/update_flag', [ProcedimentoSistemaController::class, 'update_flag'])->name('sistema.procedimentos.update_flag');
        Route::post('/procedimentos/update_data', [ProcedimentoSistemaController::class, 'update_data'])->name('sistema.procedimentos.update_data');
        Route::get('/procedimentos/delete_aplicacao', [ProcedimentoSistemaController::class, 'delete_aplicacao'])->name('sistema.procedimentos.delete_aplicacao');
        Route::get('/procedimentos/insert_aplicacao', [ProcedimentoSistemaController::class, 'insert_aplicacao'])->name('sistema.procedimentos.insert_aplicacao');
        Route::post('/procedimentos/insert_combo', [ProcedimentoSistemaController::class, 'insert_combo'])->name('sistema.procedimentos.insert_combo');
        Route::post('/procedimentos/adicionar_anexos', [ProcedimentoSistemaController::class, 'adicionar_anexos'])->name('sistema.procedimentos.adicionar_anexos');
        Route::get('/procedimentos/delete_anexo/{id}', [ProcedimentoSistemaController::class, 'delete_anexo'])->name('sistema.procedimentos.delete_anexo');
        Route::get('/procedimentos/cancelar/{codigo}', [ProcedimentoSistemaController::class, 'cancelar'])->name('sistema.procedimentos.cancelar');
        Route::post('/procedimentos/cancelar_set/', [ProcedimentoSistemaController::class, 'cancelar_set'])->name('sistema.procedimentos.cancelar_set');
        Route::get('/procedimentos/editar_medico/{codigo}', [ProcedimentoSistemaController::class, 'editar_medico'])->name('sistema.procedimentos.editar_medico');
        Route::post('/procedimentos/editar_medico_set', [ProcedimentoSistemaController::class, 'editar_medico_set'])->name('sistema.procedimentos.editar_medico_set');

        Route::get('/pacientes', [PacienteSistemaController::class, 'index'])->name('sistema.pacientes');
        Route::get('/pacientes/index_pesq', [PacienteSistemaController::class, 'index_pesq'])->name('sistema.pacientes.index_pesq');
        Route::get('/pacientes/atualizar_integracao', [PacienteSistemaController::class, 'atualizar_integracao'])->name('sistema.pacientes.atualizar_integracao');
        Route::get('/pacientes/listar_pacientes_ajax', [PacienteSistemaController::class, 'listar_pacientes_ajax'])->name('sistema.pacientes.listar_pacientes_ajax');
        Route::get('/pacientes/get_paciente_ajax', [PacienteSistemaController::class, 'get_paciente_ajax'])->name('sistema.pacientes.get_paciente_ajax');
        Route::post('/pacientes/salvar_obs_ajax', [PacienteSistemaController::class, 'salvar_obs_ajax'])->name('sistema.pacientes.salvar_obs_ajax');
        Route::get('/pacientes/procedimentos/{id}', [PacienteSistemaController::class, 'procedimentos'])->name('sistema.pacientes.procedimentos');

        Route::get('/financeiros', [FinanceiroSistemaController::class, 'index'])->name('sistema.financeiros');
        Route::get('/financeiros/caixa_diario', [RelatorioController::class, 'caixa_diario_sistema'])->name('sistema.financeiros.caixa_diario');
        Route::get('/financeiros/adicionar', [FinanceiroSistemaController::class, 'adicionar'])->name('sistema.financeiros.adicionar');
        Route::get('/financeiros/acessar/{id}', [FinanceiroSistemaController::class, 'acessar'])->name('sistema.financeiros.acessar');
        Route::get('/financeiros/get_procedimentos_abertos', [FinanceiroSistemaController::class, 'get_procedimentos_abertos'])->name('sistema.financeiros.get_procedimentos_abertos');
        Route::get('/financeiros/adicionar_pagamento/{id}', [FinanceiroSistemaController::class, 'adicionar_pagamento'])->name('sistema.financeiros.adicionar_pagamento');
        Route::get('/financeiros/delete_pagamento/{id?}', [FinanceiroSistemaController::class, 'delete_pagamento'])->name('sistema.financeiros.delete_pagamento');
        Route::get('/financeiros/delete_anexo_pagamento/{id}', [FinanceiroSistemaController::class, 'delete_anexo_pagamento'])->name('sistema.financeiros.delete_anexo_pagamento');
        Route::post('/financeiros/insert_pagamento', [FinanceiroSistemaController::class, 'insert_pagamento'])->name('sistema.financeiros.insert_pagamento');
        Route::get('/financeiros/editar_pagamento/{id}', [FinanceiroSistemaController::class, 'editar_pagamento'])->name('sistema.financeiros.editar_pagamento');
        Route::post('/financeiros/update_pagamento', [FinanceiroSistemaController::class, 'update_pagamento'])->name('sistema.financeiros.update_pagamento');
        Route::get('/financeiros/editar_valores/{id}', [FinanceiroSistemaController::class, 'editar_valores'])->name('sistema.financeiros.editar_valores');
        Route::post('/financeiros/update_valores', [FinanceiroSistemaController::class, 'update_valores'])->name('sistema.financeiros.update_valores');
        Route::post('/financeiros/insert', [FinanceiroSistemaController::class, 'insert'])->name('sistema.financeiros.insert');
    });
});
