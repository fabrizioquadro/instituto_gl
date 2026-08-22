# Plano 01 — Eliminar tabela `administradors` e migrar para `users` (tipo `Administrador`)

> Projeto: Instituto GL — v2 integrada v1
> Data: 20/08/2026
> Branch: `v2_integrada_v1`

---

## 1. Objetivo

Eliminar a tabela `administradors` e migrar todos os seus registros para a tabela `users`, criando o tipo `Administrador` no campo `tipo`. Isso unifica o sistema de autenticação (um só guard, uma só tabela) e elimina o "user fake" usado hoje no login do administrador.

---

## 2. Situação atual (levantamento feito)

### 2.1 Estrutura das tabelas

**`users` (65 registros)**
- Colunas: `id`, `clinica_id` (NOT NULL, FK→`clinicas`), `nome`, `email` (UNIQUE), `password`, `tipo`, `coren`, `imagem`, `imagem_carimbo`, `senha_certificado`, `dashboard_sec`, `dashboard_enf`, `controle_medicamentos`, `pacientes`, `procedimentos`, `financeiro`, `st_usuario`, `timestamps`
- `tipo` atuais: `Enfermagem` (34) e `Secretária` (31)

**`administradors` (22 registros)**
- Colunas: `id`, `nome`, `email`, `password`, `st_usuario`, `imagem`, `timestamps`
- Sem `clinica_id`, sem `tipo`, sem permissões

**User fake (id=0) em `users`**
- `nome` = "Administradores", `email` = `teste@teste.com.br`, `tipo` = `Secretária`, `clinica_id` = 8, password "não informado"
- Criado para satisfazer as FKs de `user_id_aplicacao` no login do admin
- **290** `procedimentos` e **94** `aplicacaos` apontam para `user_id_aplicacao = 0`

### 2.2 Fluxo de login atual (dois sistemas paralelos)

`LoginController::login`:
1. Se o e-mail existe em `administradors`:
   - valida `st_usuario = Ativo` e senha
   - grava na sessão: `administrador` (objeto) e `user` (objeto User **fake** com `id=0`, `tipo=Secretária`, `clinica_id` = primeira clínica)
   - redireciona para `adm.dashboard` (layout `admin`)
2. Senão, faz `Auth::attempt` contra `users` (com `st_usuario=Ativo`) → `sistema.dashboard` (layout `sistema`)

Middlewares:
- `verificaAdministrador` (`VerificaSessaoAdm`): exige `session('administrador')`
- `verificaAcessoSistema`: permite `session('administrador')` **ou** `auth()->user()`

### 2.3 Referências à tabela `administradors`

| Tabela / Coluna | Qtd registros | Modelo / Relação |
|---|---|---|
| `procedimentos.autorizador_sem_pagamento` (FK→`administradors.id`) | 1.667 | `Procedimento::autorizador()` → `Administrador` |
| `transferencias.administrador_id` | 58 | `Transferencia::administrador()` → `Administrador` |
| `procedimento_logs.administrador_id` (sem FK) | variável | `ProcedimentoLog::administrador()` → `Administrador`; gravado via `session('administrador')->id` |

### 2.4 Funcionalidade de aplicação de medicamentos (foco pedido)

Fluxo relevante:
- `ProcedimentoSistemaController::enviar_fila_aplicacao_sem_pagamento` — valida admin pelo **e-mail + senha** (`Administrador::where('email', ...)`), grava `procedimento.autorizador_sem_pagamento = $autorizador->id` (libera aplicação sem pagamento).
- `DashboardSistemaController::enfermagem_acessar_procedimento` — permite acesso se `st_pagamento = Sim` **ou** `autorizador_sem_pagamento` preenchido.
- `DashboardSistemaController::set_aplicacao` — efetiva a aplicação: grava `user_id_aplicacao = $user->id`, cria `AplicacaoLote`, dá baixa no estoque.
- Padrão usado em vários pontos: `$user = auth()->user(); if(!$user){ $user = session()->get('user'); }` — ou seja, o **admin hoje aplica como "Secretária" com id=0** (fake). As FKs `procedimentos.user_id_aplicacao` e `aplicacaos.user_id_aplicacao` → `users.id` só funcionam porque existe o registro id=0.

> **Insight:** com a migração, o admin passa a ser um `User` real com id real, eliminando a dependência do id=0 e permitindo rastrear corretamente as aplicações feitas pelo administrador.

---

## 3. Pontos de decisão de negócio (RESOLVIDOS em 20/08/2026)

1. **`clinica_id` do Administrador:** manter **NOT NULL com clínica padrão** — atribuir a clínica 8 (mesma usada pelo user fake hoje) a todos os admins migrados.
2. **4 conflitos de e-mail** (mesmas pessoas duplicadas): **manter o registro do `administradors` e excluir o `users`** correspondente, promovendo o admin a `tipo=Administrador`. As referências (FKs) que apontavam para o `users.id` antigo (10, 59, 57, 86) devem ser **reapontadas** para o novo `users.id` do admin (mesma pessoa).
   - Conflitos: Bruna (u10/a4), Luara Campelo Resende (u59/a12), Manoela Feitosa M Saraiva (u57/a13), Gabriela Jordana França Saiti (u86/a15).
3. **Permissões padrão do Administrador:** `Sim` para **todas** (`controle_medicamentos`, `pacientes`, `procedimentos`, `financeiro`, `dashboard_sec`, `dashboard_enf`).
4. **CRUD de administradores:** **manter CRUD separado** — `AdministradorAdmController` passa a operar sobre `User::where('tipo','Administrador')` (com telas `adm/administradores/*` mantidas).

---

## 4. Etapas de implementação

### Etapa A — Banco de dados (migração de dados **via migrations do Laravel**)

> **Regra obrigatória:** toda implementação de banco (estrutura E dados) deve ser feita **somente através de migrations do Laravel** (`php artisan make:migration` + `php artisan migrate`). **Nada de scripts avulsos** (`scratch_*.php`) ou comandos manuais no MySQL — pois o mesmo código será executado em produção (`php artisan migrate --force`) e não pode dar erro.

1. **Migration de dados** (`..._migrar_administradors_para_users.php`), rodando **dentro de uma transação** (`Schema::connection()->getConnection()->transaction(...)`):
   - Monta o mapa `administradors.id_antigo → novo users.id`
   - Para cada `administradors` (definindo `clinica_id=8`, `tipo='Administrador'`, permissões `Sim`):
     - **se já existe `users` com o mesmo e-mail** (4 conflitos): excluir o `users` existente **após** reapontar todas as referências desse `users.id` para o novo `users.id` do admin (procedimentos/user_id_cadastro, user_id_aplicacao, aplicacaos, baixas, transferencias, etc.)
     - senão, insere em `users` com os campos derivados
   - Atualiza `procedimentos.autorizador_sem_pagamento`, `transferencias.administrador_id`, `procedimento_logs.administrador_id` para os novos `users.id`
   - **Rollback (down):** reverter de forma segura (reinsere os admins em `administradors` usando o mapa inverso, restaura FKs e, se possível, restaura os users excluídos dos conflitos — ver seção 6 sobre backups)
2. **Migration de schema** (`..._drop_administradors_table.php`):
   - Remove FK `procedimentos.autorizador_sem_pagamento` → `administradors`
   - Altera a FK para apontar para `users` (e mesma coisa para `transferencias.administrador_id`)
   - `users.clinica_id` permanece NOT NULL (decisão 3.1 — clínica 8 para admins)
   - Drop da tabela `administradors`
   - Tratamento do user fake id=0: reapontar os 290+94 registros para o admin real correspondente (ou manter um user "Sistema" — definir) **antes** de qualquer alteração

### Estratégia de migrations para produção (rodar sem erro)

Para garantir que `php artisan migrate --force` rode limpo em produção (e também aqui no ambiente local de teste):

1. **Sempre via migrations** — nenhuma alteração de banco feita manualmente ou por script avulso; tudo versionado em `database/migrations/`.
2. **Ordem de execução correta:** a migration de **dados** deve rodar **antes** da migration que **dropar a tabela** (o Laravel ordena por timestamp no nome do arquivo — nomear com timestamps crescentes na ordem certa).
3. **Idempotência / re-executável:** cada migration usa o `migrations` registry do Laravel; dentro delas, usar verificações de existência (`Schema::hasTable`, `Schema::hasColumn`) para não estourar erro se algum passo já tiver sido aplicado.
4. **Transação única:** cada migration envolve toda a lógica em `DB::transaction(...)` — se qualquer passo falhar, faz rollback total e a migration não fica registrada como rodada (sem estado "pela metade").
5. **Sem depender de dados locais:** as migrations de dados leem das próprias tabelas (`administradors`), nunca de constantes/ids fixos vindos do ambiente de teste; o código precisa funcionar com qualquer conteúdo real.
6. **Validar em produção "seca":** antes de rodar em produção, replicar a estrutura real em um ambiente de teste com o dump de produção (como já foi feito aqui) e rodar as migrations até passar.
7. **Backup obrigatório:** `mysqldump` do banco antes do primeiro `migrate` em produção (e aqui também), para permitir rollback manual se necessário.
8. **`down()` funcional:** toda migration de dados deve ter `down()` que reverta a operação (mapa inverso), para permitir `migrate:rollback` seguro.

### Etapa B — Autenticação (unificar login) — ✅ IMPLEMENTADO

> **Decisão de implementação:** foi mantido o modelo de sessão para o Administrador
> (o admin autentica lendo da tabela `users` com `tipo=Administrador`, sem usar o
> guard `Auth`). Isso preserva 100% a arquitetura existente (o admin usa `layout.admin`
> e `session('user')` para o contexto de clínica/enfermeira; as views do sistema usam
> `$template = "layout.".session('layout')`). Usar `Auth` para o admin quebraria a
> impersonação (troca de clínica/enfermeira) nos ~50 pontos do padrão
> `$user = auth()->user(); if(!$user){ session('user') }`.

1. `LoginController::login`: 
   - 1º) se `User::where('email')->where('tipo','Administrador')` → valida `st_usuario`/senha → `session('administrador')`, `session('user')` (= o User real), `layout='admin'` → `adm.dashboard`
   - 2º) senão → `Auth::attempt` (Enfermagem/Secretária) → `layout='sistema'` → `sistema.dashboard`
2. `recuperar_senha`: unificado (só `User`)
3. Middlewares: `VerificaSessaoAdm` e `verificaAcessoSistema` inalterados (continuam válidos)
4. User fake id=0 **mantido** como marcador histórico (290 procedimentos / 94 aplicações não foram alterados)

### Etapa C — Models e relações — ✅ IMPLEMENTADO

| Modelo | Hoje | Depois |
|---|---|---|
| `Procedimento::autorizador()` | `belongsTo(Administrador, 'autorizador_sem_pagamento')` | `belongsTo(User, 'autorizador_sem_pagamento')` |
| `Transferencia::administrador()` | `belongsTo(Administrador)` | `belongsTo(User)` |
| `ProcedimentoLog::administrador()` | `belongsTo(Administrador)` | `belongsTo(User)` |

### Etapa D — Controllers e regras de negócio

1. `ProcedimentoSistemaController::enviar_fila_aplicacao_sem_pagamento`:
   - `Administrador::where('email',...)` → `User::where('email',...)->where('tipo','Administrador')`
2. `DashboardSistemaController` (aplicação de medicamentos):
   - Manter o padrão `$user = auth()->user(); if(!$user){ $user = session('user'); }` — com o admin agora sendo `User` real, `session('user')` terá id real → `user_id_aplicacao` correto
   - Rever pontos que fazem `$user->id` / `$user->clinica_id` para garantir que o admin tenha `clinica_id` coerente (ou tratar nullable)
3. `ProcedimentoLog::logar()`: `session('administrador')->id` passa a ser `users.id` (ok)
4. `DashboardAdmController` (perfil): `session('administrador')` passa a ser `User` — validar campos (`imagem`, senha, etc.)
5. `FinanceiroSistemaController` / `TransferenciaSistemaController`: usos de `session('administrador')` — manter, pois o objeto continua existindo

### Etapa E — CRUD / telas de administração

- `AdministradorAdmController` + views `adm/administradores/*`:
  - Decisão 3.4: ou passa a operar sobre `User::where('tipo','Administrador')`, ou é descontinuado em favor de `UsuarioAdmController`
  - Upload de imagem: hoje `img/administradores` — decidir manter ou unificar em `img/usuarios`

---

## 5. Testes obrigatórios

1. **Login admin** (e-mail/senha da antiga `administradors`) → cai em `adm.dashboard` com layout admin
2. **Login usuário** (Enfermagem/Secretária) → `sistema.dashboard` (sem regressão)
3. **Login inativo** → mensagem de usuário inativo (admin e usuário)
4. **Recuperar senha** para admin e para usuário
5. **Aplicação de medicamentos:**
   - Enfermeiro aplica normalmente
   - **Admin aplica** → `user_id_aplicacao` deve gravar o id real do admin (não 0)
6. **Autorização sem pagamento:** e-mail/senha de admin autoriza procedimento → `autorizador_sem_pagamento` aponta para `users.id` válido; aplicação liberada
7. **Relatórios/transferências:** `transferencias.administrador_id` e `procedimento_logs.administrador_id` resolvem o nome do admin
8. Verificar que 290 procedimentos / 94 aplicações antigas continuam exibindo (user fake tratado)

---

## 6. Riscos e cuidados

- **FKs existentes** impedem drop da `administradors` antes de reapontar as FKs de `procedimentos`, `transferencias`
- **Integridade do id=0:** 290 procedimentos + 94 aplicações dependem do registro fake — tratar antes de remover
- **E-mail duplicado** (UNIQUE em `users.email`): resolver os 4 conflitos antes do insert
- **`clinica_id` NOT NULL:** admins não têm clínica — decisão 3.1 (clínica 8)
- **Regressão de login:** mudar para `Auth::attempt` altera o fluxo de sessão atual dos admins — testar logout/re-login
- **Migration em produção:** toda a mudança de banco é via migration com transação e `down()`; **backup** (`mysqldump`) antes de rodar em qualquer ambiente
- **Rollback:** testar `php artisan migrate:rollback` localmente após a migração para garantir que reverte sem erro

---

## 7. Pendências / próximos passos

- [x] Definir os pontos de decisão da seção 3 (resolvidos em 20/08/2026)
- [x] Definir que **toda mudança de banco é via migration** (ver "Estratégia de migrations para produção")
- [x] **Etapa A implementada e rodada** (4 migrations: drop FK → dados → normalizar datas zero → drop tabela) — verificado no banco
- [x] **Etapa B implementada** (LoginController unificado via users/tipo)
- [x] **Etapa C implementada** (Procedimento/Transferencia/ProcedimentoLog → User) — `Administrador.php` removido
- [x] **Etapa D implementada** (`enviar_fila_aplicacao_sem_pagamento` → User tipo Administrador)
- [x] **Etapa E implementada** (AdministradorAdmController sobre User tipo Administrador; dashboard com check por e-mail)
- [ ] **Teste manual no navegador** (login admin, login usuário, aplicação de medicamentos, autorização sem pagamento)
- [ ] Rodar `php artisan migrate --force` no ambiente de produção (após testar aqui)

## 8. Notas da implementação (20/08/2026)

- **Ordem das migrations importa:** ① drop da FK `autorizador_sem_pagamento→administradors` (175000), ② dados (180000), ③ normalizar datas zero (180500), ④ re-add FK→users + drop da tabela (181000). Essa ordem resolve o "chicken-egg" da FK durante o reapontamento.
- **DDL fora de transação:** `Schema::create`/`drop` de tabela dentro de `DB::transaction` causa COMMIT implícito no MariaDB (quebra o rollback). As migrations de dados criam a tabela de mapa `_migracao_adm_map` FORA da transação.
- **Datas zero preexistentes:** o dump real tem '0000-00-00' em `procedimentos.data_aplicacao` (36), `dt_hr_finalizacao` (94), `aplicacaos.dt_hr_chegada/atendimento` (131), `pacientes.dt_nascimento` (4). Com `NO_ZERO_DATE` no sql_mode, qualquer rebuild de tabela falha — normalizado para NULL (migration 180500).
- **Check de admin por e-mail:** o dashboard admin usava ids hardcoded da antiga `administradors` (1, 3, 6); como os ids mudam por ambiente, trocado por e-mails estáveis.
- **Tabela de auditoria:** `_migracao_adm_map` (22 linhas) fica no banco para permitir o `down()` das migrations de dados. Pode ser removida após a validação em produção se desejado.
- **Backup:** `backup_antes_migracao_adm_20260820_173719.sql` (estado antes das migrations).
