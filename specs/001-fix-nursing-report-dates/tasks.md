# Tarefas: Correção das Datas do Relatório de Enfermagem

**Entrada**: Documentos de design de `/specs/001-fix-nursing-report-dates/`

**Pré-requisitos**: plan.md, spec.md, research.md, data-model.md

**Organização**: As tarefas estão agrupadas por Cenário de Usuário (User Story) para permitir a implementação e os testes independentes de cada etapa.

## Formato: `[ID] [P?] [Story] Descrição`

- **[P]**: Pode ser executado em paralelo (arquivos diferentes, sem dependências diretas)
- **[Story]**: Cenário de usuário a que a tarefa pertence (ex: US1, US2)

---

## Fase 1: Configuração (Shared Infrastructure)

**Objetivo**: Preparação e alinhamento do ambiente para esta funcionalidade.

- [x] T001 Garantir a configuração da integração Antigravity e atualizar as dependências locais se necessário

---

## Fase 2: Fundacional (Bloqueios e Pré-requisitos)

**Objetivo**: Nenhuma tarefa fundacional é necessária para esta correção, pois a infraestrutura do Laravel e banco de dados já está operacional.

---

## Fase 3: Cenário de Usuário 1 - Filtrar Relatório de Enfermagem pela Data de Aplicação Individual (Prioridade: P1) 🎯 MVP

**Objetivo**: Filtrar os procedimentos e os itens do relatório pela data/hora real de aplicação do medicamento (`aplicacaos.updated_at`), em vez de usar a data geral de fechamento do procedimento semanal.

**Teste Independente**: Gerar relatórios para períodos de datas parciais distintos e garantir que apenas os medicamentos aplicados dentro do período de datas apareçam no relatório.

### Implementação para o Cenário de Usuário 1

- [x] T002 Modificar o método `gerar_relatorio_enfermagem` em [Procedimento.php](file:///c:/xampp/htdocs/instituto_gl/app/Models/Procedimento.php) para realizar um `JOIN` com a tabela `aplicacaos` e filtrar por `aplicacaos.updated_at` (com a situação 'Aplicada'), em vez de usar `procedimentos.data_aplicacao`.
- [x] T003 [US1] Atualizar o loop em [RelatorioController.php](file:///c:/xampp/htdocs/instituto_gl/app/Http/Controllers/RelatorioController.php) dentro do método `exportar_enfermagem` para filtrar e ignorar os registros de aplicações cuja data real de aplicação esteja fora do intervalo de datas informado (`dt_inc` e `dt_fn`).
- [x] T004 [US1] Atualizar o loop na view blade [enfermagem_gerar.blade.php](file:///c:/xampp/htdocs/instituto_gl/resources/views/adm/relatorios/enfermagem_gerar.blade.php) para ignorar os registros de aplicações cujas datas reais de aplicação estejam fora do intervalo de datas informado (`dt_inc` e `dt_fn`).

**Ponto de Controle**: Nesse ponto, o filtro do Relatório de Enfermagem deve retornar as informações filtradas corretamente pelas datas individuais de aplicação dos medicamentos.

---

## Fase 4: Cenário de Usuário 2 - Exibir a Data de Aplicação nas Colunas do Relatório (Prioridade: P1)

**Objetivo**: Adicionar a coluna de exibição "Aplicação" no relatório HTML da tela e no arquivo Excel exportado, preenchendo-a com a data e hora em que a aplicação ocorreu.

**Teste Independente**: Verificar visualmente a presença da coluna "Aplicação" com a data/hora correspondente na tela e na planilha Excel gerada.

### Implementação para o Cenário de Usuário 2

- [x] T005 [P] [US2] Adicionar a coluna de cabeçalho `<th>Aplicação</th>` e renderizar o valor da data e hora real de aplicação (extraídos de `$aplicacao->updated_at`) na view blade [enfermagem_gerar.blade.php](file:///c:/xampp/htdocs/instituto_gl/resources/views/adm/relatorios/enfermagem_gerar.blade.php).
- [x] T006 [P] [US2] Adicionar a coluna de cabeçalho "Aplicação" e renderizar a respectiva célula de dados (com a data e hora real de aplicação) no método `exportar_enfermagem` em [RelatorioController.php](file:///c:/xampp/htdocs/instituto_gl/app/Http/Controllers/RelatorioController.php).

**Ponto de Controle**: A data e hora exatas de aplicação de cada medicamento individual devem aparecer de forma legível na tela e no documento Excel.

---

## Fase 5: Cenário de Usuário 3 - Registrar Horários de Chegada e Atendimento por Aplicação Individual (Prioridade: P1)

**Objetivo**: Registrar os horários de chegada e atendimento específicos da aplicação no banco de dados, com fallback para o horário da própria aplicação (updated_at) quando nulos.

### Implementação para o Cenário de Usuário 3

- [x] T008 [US3] Criar migration para adicionar `dt_hr_chegada` e `dt_hr_atendimento` na tabela `aplicacaos`.
- [x] T009 [US3] Executar a migration no banco de dados (`php artisan migrate`).
- [x] T010 [US3] Atualizar o Model `Aplicacao` para incluir as novas colunas no array `$fillable`.
- [x] T011 [US3] Modificar `DashboardSistemaController::set_aplicacao` para gravar chegada e atendimento na aplicação.
- [x] T012 [US3] Atualizar a view blade `enfermagem_gerar.blade.php` para usar o fallback por aplicação (`updated_at`).
- [x] T013 [US3] Atualizar `RelatorioController.php` para usar o fallback por aplicação (`updated_at`).
- [x] T014 [US3] Criar e executar teste de feature automatizado para validar o filtro e o fallback (`tests/Feature/EnfermagemRelatorioTest.php`).

---

## Fase 6: Polimento e Validação Cruzada

**Objetivo**: Garantir que as alterações não causem regressão e validar o guia de validação rápida.

- [x] T015 Executar as validações do guia [quickstart.md](file:///c:/xampp/htdocs/instituto_gl/specs/001-fix-nursing-report-dates/quickstart.md) para todos os cenários de usuário.

---

## Dependências e Ordem de Execução

### Ordem de Execução

- **Configuração (Fase 1)**: Sem dependências - pode iniciar imediatamente.
- **Cenário de Usuário 1 (Fase 3)**: Depende da Fase 1 - deve ser concluído primeiro (MVP).
- **Cenário de Usuário 2 (Fase 4)**: Depende da conclusão funcional do Cenário de Usuário 1.
- **Polimento (Fase 5)**: Depende da conclusão de todas as fases anteriores.

### Oportunidades de Paralelismo
- T005 e T006 podem ser executados em paralelo por desenvolvedores diferentes, pois envolvem arquivos distintos (Blade view e Controller PHP, respectivamente).

---

## Estratégia de Implementação

### MVP Primeiro (Cenário de Usuário 1 Apenas)
1. Completar a Fase 1: Configuração.
2. Completar a Fase 3: Cenário de Usuário 1.
3. **PARAR E VALIDAR**: Testar a filtragem correta no painel de relatórios.

### Entrega Incremental
1. Entrega do MVP (Filtro corrigido) -> Apenas a filtragem dos dados fica correta.
2. Entrega do Cenário 2 (Coluna de data/hora) -> A informação visual é exibida para o usuário final.
