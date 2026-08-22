# Plano 02 — Estrutura V2: Procedimentos + Financeiro (criar tabelas + migrar dados V1)

> Projeto: Instituto GL — v2 integrada v1
> Data: 21/08/2026
> Branch: `v2_integrada_v1`
> Base: documento `05-procedimentos-financeiro.md` (da V2 em `instituto_gl_2`) + análise da V1 atual

---

## 1. Objetivo

Criar no projeto atual (`instituto_gl`, branch `v2_integrada_v1`) a **estrutura de tabelas da V2** do módulo **Procedimentos + Financeiro** (`prescricaos`, `prescricao_semanas`, `prescricao_semana_medicamentos`, `financeiro_parcelas`, `prescricao_pagamentos`, `prescricao_pagamento_formas`, `pagamento_parcelas`, `anexos`, `prescricao_logs` + nova `prescricao_lotes`) — **tudo via migrations** e **sem alterar tabelas existentes** — para que, em seguida, possamos **migrar os dados atuais (V1) para as novas tabelas** sem perder nada.

> **Escopo desta etapa:** criar a estrutura + o mapeamento/migração de dados. Os módulos de tela/enfermagem/relatórios ficam para etapas seguintes (Fase 3).

---

## 2. Contexto e volumes atuais (V1) — levantado em 21/08/2026

| Tabela V1 | Papel | Qtd atual |
|---|---|---|
| `procedimentos` | 1 linha por SEMANA, agrupadas por `codigo` | **43.665** (13.256 grupos) |
| `aplicacaos` | 1 linha por MEDICAÇÃO da semana | **103.483** |
| `financeiros` | 1 por GRUPO | 11.971 |
| `financeiro_formas_pagamentos` | formas/pagamentos | 10.107 |
| `financeiro_procedimentos` | vínculo financeiro ↔ semana | — |
| `procedimento_anexos` | anexos (prescrição) | 6.217 |
| `procedimento_logs` | auditoria | 398.274 |
| `aplicacao_lotes` | lote/código usado na aplicação | 65.438 |
| `estoque_abertos` | estoque por lote aberto | 1.083 |
| `procedimento_observacaos` | observações avulsas | 591 |

> **13.256 grupos vs 11.971 financeiros:** ~1.285 grupos **não têm** registro em `financeiros` (a migração precisa tratar isso — valor padrão 0/soma das semanas).

---

## 3. Estratégia geral (fases)

```
Fase 1  Criar a estrutura nova (migrations de schema)      ← esta etapa
Fase 2  Migrar os dados V1 → V2 (migration de dados)       ← esta etapa
Fase 3  Módulos de tela/enfermagem/relatórios (futuro)
Fase 4  Cutover: validar em produção, desativar tabelas V1
```

**Princípios (herdados do plano 01 e da sua exigência):**
- **Tudo via migrations** do Laravel (schema + dados), com transação e `down()` — roda igual em produção (`php artisan migrate --force`).
- **DDL fora de transação** (lição do plano 01: `Schema::create` dentro de `DB::transaction` causa COMMIT implícito no MariaDB).
- **🚫 NUNCA alterar tabelas existentes:** sem `ALTER TABLE`/rename/drop de FK/adição de coluna nas tabelas atuais. Se o modelo V2 exigir um shape diferente de uma tabela V1, **cria-se uma tabela NOVA com nome diferente** e copia-se os dados; a tabela antiga fica **intocada** até o cutover (Fase 4).
- **🔗 Rastreabilidade V1 → V2 (sua exigência):** toda tabela nova terá, **quando aplicável**, um campo `id_versao1` (nullable, index) guardando o **id da tabela V1 de origem**; no grupo (mestre) o campo é `codigo_versao1` (o id do grupo na V1 é o `codigo`). Isso permite auditar/reverter/conciliar a migração linha a linha.
- **A migration de dados só LÊ das tabelas V1 e ESCREVE nas novas** — nunca modifica dados/estrutura das existentes.
- **Tabelas V1 ficam intactas** até a Fase 4 (validação completa). A migração é *aditiva*: cria as novas e copia; não apaga nada.
- **Idempotência:** `Schema::hasTable`/verificações para re-rodar sem erro.
- **Backup obrigatório** (`mysqldump`) antes de rodar.

---

## 4. FASE 1 — Criar a estrutura (migrations de schema)

> Nomes seguem a convenção do projeto (plural com `s`): `prescricaos`, `prescricao_semanas`, etc. (igual a `administradors`, `aplicacaos`, `clinicas`).

### 4.1 Tabelas novas (baseadas no doc V2)

**`prescricaos` (mestre = cabeçalho do tratamento E financeiro)**
- `codigo_versao1` (string, index) — código antigo V1 (grupo)
- `paciente_id` (FK), `clinica_id` (FK), `user_id_cadastro` (FK, nullable)
- `medico`, `tipo_atendimento`, `agendamento`, `obs`
- `data_prescricao` (date)
- `qt_semanas` (int), `qt_semanas_aplicacao` (int), `qt_parcelas` (int)
- `semana_atual` (int, default 0)
- `valor_tratamento` (decimal 10,2), `credito_em_aberto` (decimal 10,2, default 0)
- `situacao` (Agendada | Em Andamento | Concluída | Cancelada)
- `situacao_financeira` (Em Aberto | Parcial | Pago | Cancelado)

**`prescricao_semanas` (1 por semana; pausa também existe)**
- `id_versao1` (int, index) — id do antigo `procedimentos`
- `prescricao_id` (FK, onDelete cascade)
- `nr_semana` (int), `data_prevista` (date), `tem_aplicacao` (bool)
- `situacao` (Agendada | Em Atendimento | Aplicada | Aplicação Parcial | Cancelada)
- `dt_hr_chegada/atendimento/finalizacao`, `user_id_aplicacao` (FK, nullable), `obs`

**`prescricao_semana_medicamentos` (cada medicação da semana = a "aplicação")**
- `id_versao1` (int, index) — id do antigo `aplicacaos`
- `prescricao_semana_id` (FK, cascade), `medicamento_id` (FK), `combo_id` (FK, nullable), `clinica_id_aplicacao` (FK, nullable)
- `is_soro` (bool), `gera_aplicacao` (bool — congela `medicamento.aplicacao == 'Sim'`)
- `quantidade`, `situacao` (Aberta | Aplicada | Cancelada)
- `data_prevista`, `dt_hr_chegada`, `dt_hr_atendimento`, `aplicado_em` (datetime), `user_id_aplicacao` (FK, nullable), `obs`
- **SEM `valor`/`total`** (decisão D14 — valor vem da prescrição/parcelas)

**`financeiro_parcelas` (previsão a receber — 1 por semana com aplicação)**
- `id_versao1` (int, index) — id do antigo `procedimentos` (a semana que a parcela representa)
- `prescricao_id` (FK, cascade), `prescricao_semana_id` (FK)
- `nr_parcela`, `valor_parcela`, `valor_pago`, `situacao` (Em Aberto | Parcial | Paga | Cancelada), `dt_vencimento`

**`prescricao_pagamentos` (evento de pagamento)**
- `id_versao1` (int, index) — id do antigo `financeiro_formas_pagamentos` (origem do evento)
- `prescricao_id` (FK, cascade), `dt_pagamento`, `vl_total`, `obs`, `user_id` (FK, nullable)

**`prescricao_pagamento_formas` (COMO pagou)**
- `id_versao1` (int, index) — id do antigo `financeiro_formas_pagamentos`
- `pagamento_id` (FK, cascade), `forma_pagamento` (string), `vl_pagamento`, `parcelas` (int, default 1), `id_transacao`, `obs`

**`pagamento_parcelas` (O QUE pagou — qual parcela o valor cobre)**
- `id_versao1` (int, index) — id do antigo `financeiro_procedimentos` (o vínculo financeiro↔semana que o valor cobria)
- `pagamento_id` (FK, cascade), `financeiro_parcela_id` (FK), `valor`

**`anexos` (unifica prescrição + financeiro)**
- `id_versao1` (int, index) — id do antigo `procedimento_anexos`
- `tipo` (prescricao | comprovante_pagamento | demonstrativo_pagamento)
- `prescricao_id` (FK, cascade, nullable), `pagamento_id` (FK, nullable)
- `user_id` (FK, nullable), `nm_anexo`, `arquivo`, `mime`, `extensao`
- `visualizado_em` (datetime), `visualizado_por` (FK) — rastreio (regra R3)
- *(manter campo `enviado_feegow` para preservar o dado da V1? — decidir na Fase 2)*

**`prescricao_logs` (auditoria)**
- `id_versao1` (int, index) — id do antigo `procedimento_logs`
- `prescricao_id` (FK, cascade, index), `entidade` (prescricao|semana|medicamento|parcela|pagamento|reajuste|anexo), `entidade_id`
- `user_id` (FK, nullable), `acao`, `descricao`, `dados_antigos` (json), `dados_novos` (json)

### 4.2 Tabelas "espelho" NOVAS (em vez de alterar tabelas existentes)

> **Regra (sua exigência):** não alteramos tabelas existentes. Quando o modelo V2 exige um shape diferente de uma tabela V1, criamos uma **tabela nova com nome diferente** e copiamos os dados. A tabela V1 fica intocada.

**Nova `prescricao_lotes`** (substitui a função de `aplicacao_lotes` na V2):
- `id_versao1` (int, index) — id do antigo `aplicacao_lotes`
- `id`, `prescricao_semana_medicamento_id` (FK → `prescricao_semana_medicamentos`)
- `quantidade`, `lote`, `codigo_barras`, `estoque_aberto_id`
- Na Fase 2, os dados de `aplicacao_lotes` são **copiados** para cá, mapeando `aplicacao_id` → `prescricao_semana_medicamento_id` (via o mapa de aplicações).

**`estoque_abertos`** — **NÃO é alterada.** Fica como está (usada pela V1). O módulo V2 de enfermagem (Fase 3) decidirá se usa a existente ou cria uma nova (ex.: `prescricao_estoque_abertos`). Por ora, nada é feito nela.

### 4.3 Migrations propostas (ordem de timestamp)

| # | Migration (nome) | Conteúdo |
|---|---|---|
| 1 | `..._create_prescricaos_table` | mestre |
| 2 | `..._create_prescricao_semanas_table` | semanas |
| 3 | `..._create_prescricao_semana_medicamentos_table` | medicações |
| 4 | `..._create_financeiro_parcelas_table` | parcelas |
| 5 | `..._create_prescricao_pagamentos_table` | pagamentos |
| 6 | `..._create_prescricao_pagamento_formas_table` | formas |
| 7 | `..._create_pagamento_parcelas_table` | pagamento×parcela |
| 8 | `..._create_anexos_table` | anexos |
| 9 | `..._create_prescricao_logs_table` | logs |
| 10 | `..._create_prescricao_lotes_table` | nova (cópia da função de `aplicacao_lotes`, com FK certa) — **sem tocar em `aplicacao_lotes`** |

> Todas com `up()`/`down()` e **sem DDL dentro de transação** (as de criação são schema puro; ok). As de dados (Fase 2) seguirão o padrão do plano 01 (tabela de mapa p/ rollback).

---

## 5. FASE 2 — Migrar os dados V1 → V2 (migration de dados)

> Uma **única migration de dados** (ou várias por subfase, em transação), seguindo o padrão do plano 01: mapa de ids + tabela de auditoria `_migracao_*_map` + `down()` reverso.

### 5.1 Mapeamento tabela a tabela

**A) Grupos → `prescricaos`** (1 linha por `DISTINCT codigo` de `procedimentos`)
| V1 | V2 |
|---|---|
| `codigo` | `codigo_versao1` |
| `paciente_id` (da 1ª semana) | `paciente_id` |
| `clinica_id` (da 1ª semana) | `clinica_id` |
| `user_id_cadastro` (da 1ª semana) | `user_id_cadastro` |
| `medico`, `tipo_atendimento`, `agendamento` | idem |
| `data_cad` | `data_prescricao` |
| COUNT(semanas do grupo) | `qt_semanas` |
| COUNT(semanas com aplicação) | `qt_semanas_aplicacao` e `qt_parcelas` |
| (derivado) | `semana_atual` (0; recalcular na Fase 3 ou por regra) |
| `financeiros.vl_procedimentos` (+ `vl_consulta`? — decisão D15) | `valor_tratamento` |
| 0 | `credito_em_aberto` |
| (derivado das semanas) | `situacao` |
| (derivado de pagamentos) | `situacao_financeira` |
| `obs` | `obs` |

**B) Semanas → `prescricao_semanas`** (1 linha por `procedimentos`)
| V1 | V2 |
|---|---|
| `procedimentos.id` | `id_versao1` |
| mapa grupo → `prescricaos.id` | `prescricao_id` |
| `nr_procedimento` | `nr_semana` |
| `data_aplicacao` | `data_prevista` |
| (tem alguma aplicação?) | `tem_aplicacao` |
| `situacao` (mapear) | `situacao` |
| `dt_hr_chegada/atendimento/finalizacao` | idem |
| `user_id_aplicacao` | `user_id_aplicacao` |
| `obs` | `obs` |

> Mapa de situações (semana): `Agendado`→`Agendada`; `Fila de Aplicação`/`Atendimento`/`Pendente`→`Em Atendimento`; `Aplicado`→`Aplicada`; `Aplicação Parcial`→`Aplicação Parcial`; `Cancelado`→`Cancelada`; `Semana Sem Aplicação`→`Agendada` (com `tem_aplicacao=false`).

**C) Medicações → `prescricao_semana_medicamentos`** (1 linha por `aplicacaos`)
| V1 | V2 |
|---|---|
| `aplicacaos.id` | `id_versao1` |
| mapa semana → `prescricao_semanas.id` | `prescricao_semana_id` |
| `medicamento_id` | `medicamento_id` |
| `is_soro` | `is_soro` |
| (`medicamento.aplicacao == 'Sim'`) | `gera_aplicacao` |
| `quantidade` | `quantidade` |
| `situacao` (Aberta/Aplicada/Cancelada) | `situacao` |
| `dt_hr_chegada/atendimento` | idem; `aplicado_em` = `dt_hr_atendimento` (ou updated_at) |
| `user_id_aplicacao` | `user_id_aplicacao` |
| `obs` | `obs` |
| ❌ `valor`/`total` | **não migra** (D14) |

**D) Financeiro → novo modelo** (a parte mais delicada)
- `financeiros` (1/grupo) → alimenta `prescricaos.valor_tratamento` e `situacao_financeira`.
- `financeiro_formas_pagamentos` (cada forma) → vira eventos `prescricao_pagamentos` + `prescricao_pagamento_formas`, **preservando `id_versao1` = `financeiro_formas_pagamentos.id`**.
- **`financeiro_parcelas`:** gerar 1 parcela por semana com aplicação; `valor_parcela = valor_tratamento ÷ qt_parcelas` (diferença de centavos na última); `valor_pago`/`situacao` derivados de quanto a V1 marcou como pago (via `st_pagamento`/`vl_pago` por semana e `financeiro_formas_pagamentos.vl_pagamento`). **`id_versao1` = id da semana V1 (`procedimentos.id`).**
- **`prescricao_pagamentos.id_versao1`** = `financeiro_formas_pagamentos.id` (origem do evento); **`prescricao_pagamento_formas.id_versao1`** = `financeiro_formas_pagamentos.id`.
- **`pagamento_parcelas.id_versao1`** = `financeiro_procedimentos.id` (o vínculo financeiro↔semana que aquele valor cobria na V1).
- `financeiro_procedimentos` → não migra como tabela (o vínculo agora é direto por `prescricao_id`/`prescricao_semana_id`), mas seu **id fica preservado** em `pagamento_parcelas.id_versao1`.

> ⚠️ **Decisões de negócio necessárias (financeiro):** como agrupar as formas da V1 em "eventos" de pagamento; como derivar `valor_pago` por parcela; o que fazer com `vl_consulta` e `vl_desconto`/`vl_adicional` (a V2 não tem desconto/acréscimo — a V1 tinha). Ver seção 7 (D15–D18).

**E) Anexos → `anexos`** (1 por `procedimento_anexos`)
| V1 | V2 |
|---|---|
| `procedimento_anexos.id` | `id_versao1` |
| `procedimento_id` → mapa grupo | `prescricao_id` |
| `nm_anexo`, `anexo` | `nm_anexo`, `arquivo` |
| `enviado_feegow` | (manter? D19) |
| — | `tipo` = 'prescricao' |

**F) Logs → `prescricao_logs`** (1 por `procedimento_logs`)
| V1 | V2 |
|---|---|
| `procedimento_logs.id` | `id_versao1` |
| `procedimento_id` → mapa grupo | `prescricao_id` |
| `usuario_id`/`administrador_id` | `user_id` (priorizar `administrador_id` se houver; senão `usuario_id` — D20) |
| `acao`, `descricao`, `dados_antigos`, `dados_novos` | idem |
| — | `entidade` = 'prescricao' (ou derivar da acao) |

**G) `aplicacao_lotes` → `prescricao_lotes` (NOVA)** — copia os dados mapeando `aplicacao_id` → `prescricao_semana_medicamento_id` (via mapa aplicação→medicação) e **`id_versao1` = `aplicacao_lotes.id`**. A `aplicacao_lotes` original **não é alterada**.
**`estoque_abertos`** — **não é tocada** (fica como está; o módulo de enfermagem decide depois se cria tabela própria).

### 5.2 Regras de cálculo e consistência
- Construir **mapas em memória/auditoria** (`_migracao_presc_map`): codigo→prescricao_id, semana_antiga→semana_nova, aplicacao_antiga→medicamento_novo.
- `valor_tratamento` sem `financeiros`: usar soma das semanas (ou 0) — D15.
- `qt_semanas_aplicacao`/`qt_parcelas` = semanas com ≥1 medicação `gera_aplicacao=true`.
- `situacao` do mestre derivada das semanas; `situacao_financeira` derivada de `valor_pago` vs `valor_tratamento`.
- **Rollback (`down()`):** apagar o que foi criado (via mapas), sem tocar na V1.

### 5.3 Validação (após rodar)
- Contagens batem: 13.256 `prescricaos`; 43.665 `prescricao_semanas`; 103.483 `prescricao_semana_medicamentos`; 6.217 `anexos`; ~398.274 `prescricao_logs`; 65.438 `prescricao_lotes` (novos).
- Spot-checks: prescrição X tem as N semanas certas; medicação aplicada tem `aplicado_em`; financeiro soma confere; relatório de amostra.
- Rodar em **ambiente de teste com dump real** (como no plano 01) antes de produção.

---

## 6. FASE 3 — Módulos (futuro, fora desta etapa)
- Telas: listagem, cadastro em cards por semana, visualizar (abas), editar/correções (R5).
- Enfermagem: fila de aplicação, lote/estoque, `aplicado_em` por medicação, trava de finalização sem abrir anexo (R3), `semana_atual`.
- Relatórios/impressões (imprimir cadastro, financeiro, enfermagem com coluna de semana).
- Cutover (Fase 4): após validação total, desativar/remover tabelas V1.

---

## 7. Decisões em aberto (confirmar antes de implementar)

- **D15 — `valor_tratamento`:** usar só `financeiros.vl_procedimentos`, ou `vl_procedimentos + vl_consulta`? E grupos sem `financeiros`?
- **D16 — Eventos de pagamento:** cada `financeiro_formas_pagamentos` vira um `prescricao_pagamento` próprio, ou agrupar por data/`financeiros`?
- **D17 — Desconto/acréscimo da V1:** descartar, ou registrar como log/obs (a V2 não tem)?
- **D18 — `valor_pago` por parcela:** como derivar da V1 (que distribuía por `st_pagamento`/`vl_pago` por semana)?
- **D19 — `enviado_feegow`:** manter campo extra em `anexos`?
- **D20 — Autor dos logs:** priorizar `administrador_id` ou `usuario_id`?
- **D21 — `estoque_abertos`:** mantida intocada (regra de não alterar tabelas existentes); o módulo V2 de enfermagem decidirá depois se cria tabela própria.
- **D22 — `procedimento_observacaos`:** a V2 não tem tabela de observações avulsas — migrar para uma nova tabela `prescricao_observacaos` ou para `prescricao_semanas.obs`?

---

## 8. Riscos

- **Volumes grandes** (103k aplicações, 398k logs) — a migration de dados pode demorar; rodar em transação por subfase e testar performance.
- **Financeiro é a parte frágil** (distribuição de pagamento, parcelas) — validar com casos reais antes.
- **FKs e ids** — usar mapas para não perder vínculos (mesma lição do plano 01).
- **Não pode dar erro em produção** — backup, `--pretend`, e teste com dump real antes.
- **Duas fontes de verdade durante a transição** (V1 + V2) — não misturar na tela até o cutover.

---

## 9. Pendências / próximos passos

- [ ] Aprovar decisões D15–D22
- [ ] Aprovar este plano
- [ ] **Fase 1:** criar as 10 migrations de schema + rodar (com backup)
- [ ] **Fase 2:** criar a migration de dados + rodar + validar contagens
- [ ] Teste no navegador / amostras
- [ ] Fase 3 (módulos) em planos separados
