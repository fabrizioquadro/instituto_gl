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
Route::get('/teste', [LoginController::class, 'teste']);
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('/esqueceu_senha', [LoginController::class, 'esqueceu_senha'])->name('esqueceu_senha');
Route::post('/recuperar_senha', [LoginController::class, 'recuperar_senha'])->name('recuperar_senha');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

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

        Route::get('/baixas', [BaixaSistemaController::class, 'index'])->name('sistema.baixas');
        Route::get('/baixas/adicionar', [BaixaSistemaController::class, 'adicionar'])->name('sistema.baixas.adicionar');
        Route::get('/baixas/editar/{id}', [BaixaSistemaController::class, 'editar'])->name('sistema.baixas.editar');
        Route::get('/baixas/excluir/{id}', [BaixaSistemaController::class, 'excluir'])->name('sistema.baixas.excluir');
        Route::get('/baixas/visualizar/{id}', [BaixaSistemaController::class, 'visualizar'])->name('sistema.baixas.visualizar');
        Route::get('/baixas/get_lotes_medicamento', [BaixaSistemaController::class, 'get_lotes_medicamento'])->name('sistema.baixas.get_lotes_medicamento');
        Route::post('/baixas/insert', [BaixaSistemaController::class, 'insert'])->name('sistema.baixas.insert');
        Route::post('/baixas/update', [BaixaSistemaController::class, 'update'])->name('sistema.baixas.update');
        Route::post('/baixas/delete', [BaixaSistemaController::class, 'delete'])->name('sistema.baixas.delete');

        Route::get('/transferencias', [TransferenciaSistemaController::class, 'index'])->name('sistema.transferencias');
        Route::get('/transferencias/adicionar', [TransferenciaSistemaController::class, 'adicionar'])->name('sistema.transferencias.adicionar');
        Route::get('/transferencias/excluir/{id}', [TransferenciaSistemaController::class, 'excluir'])->name('sistema.transferencias.excluir');
        Route::get('/transferencias/visualizar/{id}', [TransferenciaSistemaController::class, 'visualizar'])->name('sistema.transferencias.visualizar');
        Route::post('/transferencias/insert', [TransferenciaSistemaController::class, 'insert'])->name('sistema.transferencias.insert');
        Route::post('/transferencias/delete', [TransferenciaSistemaController::class, 'delete'])->name('sistema.transferencias.delete');

        Route::get('/estoques', [EstoqueSistemaController::class, 'index'])->name('sistema.estoques');

        Route::get('/procedimentos', [ProcedimentoSistemaController::class, 'index'])->name('sistema.procedimentos');
        Route::get('/procedimentos/adicionar/{retorno?}', [ProcedimentoSistemaController::class, 'adicionar'])->name('sistema.procedimentos.adicionar');
        Route::get('/procedimentos/editar/{id}', [ProcedimentoSistemaController::class, 'editar'])->name('sistema.procedimentos.editar');
        Route::get('/procedimentos/excluir/{id}', [ProcedimentoSistemaController::class, 'excluir'])->name('sistema.procedimentos.excluir');
        Route::get('/procedimentos/acessar/{id}/{retorno?}', [ProcedimentoSistemaController::class, 'acessar'])->name('sistema.procedimentos.acessar');
        Route::get('/procedimentos/acessar_grupo/{codigo}/{retorno?}', [ProcedimentoSistemaController::class, 'acessar_grupo'])->name('sistema.procedimentos.acessar_grupo');
        Route::get('/procedimentos/adicionar_grupo/{codigo}', [ProcedimentoSistemaController::class, 'adicionar_grupo'])->name('sistema.procedimentos.adicionar_grupo');
        Route::post('/procedimentos/insert', [ProcedimentoSistemaController::class, 'insert'])->name('sistema.procedimentos.insert');
        Route::post('/procedimentos/setar_pagamento', [ProcedimentoSistemaController::class, 'setar_pagamento'])->name('sistema.procedimentos.setar_pagamento');
        Route::post('/procedimentos/enviar_fila_aplicacao', [ProcedimentoSistemaController::class, 'enviar_fila_aplicacao'])->name('sistema.procedimentos.enviar_fila_aplicacao');
        Route::post('/procedimentos/enviar_fila_aplicacao_sem_pagamento', [ProcedimentoSistemaController::class, 'enviar_fila_aplicacao_sem_pagamento'])->name('sistema.procedimentos.enviar_fila_aplicacao_sem_pagamento');
        Route::post('/procedimentos/financeiros', [ProcedimentoSistemaController::class, 'financeiros'])->name('sistema.procedimentos.financeiros');
        Route::post('/procedimentos/delete', [ProcedimentoSistemaController::class, 'delete'])->name('sistema.procedimentos.delete');
        Route::post('/procedimentos/imprimir', [ProcedimentoSistemaController::class, 'imprimir'])->name('sistema.procedimentos.imprimir');
        Route::get('/procedimentos/get_aplicacao', [ProcedimentoSistemaController::class, 'get_aplicacao'])->name('sistema.procedimentos.get_aplicacao');
        Route::get('/procedimentos/update_aplicacao', [ProcedimentoSistemaController::class, 'update_aplicacao'])->name('sistema.procedimentos.update_aplicacao');
        Route::get('/procedimentos/delete_aplicacao', [ProcedimentoSistemaController::class, 'delete_aplicacao'])->name('sistema.procedimentos.delete_aplicacao');
        Route::get('/procedimentos/insert_aplicacao', [ProcedimentoSistemaController::class, 'insert_aplicacao'])->name('sistema.procedimentos.insert_aplicacao');
        Route::post('/procedimentos/adicionar_anexos', [ProcedimentoSistemaController::class, 'adicionar_anexos'])->name('sistema.procedimentos.adicionar_anexos');

        Route::get('/pacientes', [PacienteSistemaController::class, 'index'])->name('sistema.pacientes');
        Route::get('/pacientes/atualizar_integracao', [PacienteSistemaController::class, 'atualizar_integracao'])->name('sistema.pacientes.atualizar_integracao');
        Route::get('/pacientes/listar_pacientes_ajax', [PacienteSistemaController::class, 'listar_pacientes_ajax'])->name('sistema.pacientes.listar_pacientes_ajax');
        Route::get('/pacientes/procedimentos/{id}', [PacienteSistemaController::class, 'procedimentos'])->name('sistema.pacientes.procedimentos');

        Route::get('/financeiros', [FinanceiroSistemaController::class, 'index'])->name('sistema.financeiros');
        Route::get('/financeiros/adicionar', [FinanceiroSistemaController::class, 'adicionar'])->name('sistema.financeiros.adicionar');
        Route::get('/financeiros/acessar/{id}', [FinanceiroSistemaController::class, 'acessar'])->name('sistema.financeiros.acessar');
        Route::get('/financeiros/get_procedimentos_abertos', [FinanceiroSistemaController::class, 'get_procedimentos_abertos'])->name('sistema.financeiros.get_procedimentos_abertos');
        Route::post('/financeiros/insert', [FinanceiroSistemaController::class, 'insert'])->name('sistema.financeiros.insert');
    });
});
