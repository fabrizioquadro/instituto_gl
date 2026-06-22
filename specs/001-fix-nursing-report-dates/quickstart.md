# Guia de Validação Rápida (Quickstart)

Este guia descreve como verificar se as atualizações de filtragem de data e exibição no relatório de enfermagem funcionam corretamente.

## Pré-requisitos
- Ambiente Laravel ativo e rodando (ex: via XAMPP ou servidor local).
- Acesso ao painel administrativo (`/adm/relatorios/enfermagem`).

## Cenários de Validação

### Cenário 1: Consulta de Aplicação Parcial
1. Localize ou crie um procedimento semanal com pelo menos dois medicamentos.
2. Aplique o primeiro medicamento na Data A (ex: `2026-03-31`).
3. Aplique o segundo medicamento na Data B (ex: `2026-06-08`).
4. Acesse **Relatórios > Enfermagem**.
5. Gere o relatório filtrando o intervalo de datas de: `2026-03-31` a `2026-03-31`.
   - **Resultado Esperado**: Apenas o primeiro medicamento aplicado em `2026-03-31` deve ser listado no relatório.
6. Gere o relatório filtrando o intervalo de datas de: `2026-06-08` a `2026-06-08`.
   - **Resultado Esperado**: Apenas o segundo medicamento aplicado em `2026-06-08` deve ser listado no relatório.

### Cenário 2: Renderização de Coluna e Exportação
1. Gere o relatório para qualquer período de datas que contenha medicamentos aplicados.
2. **Resultado Esperado na Tela HTML**:
   - A tabela exibe uma coluna de cabeçalho **Aplicação** logo após a coluna **Finalização**.
   - Os valores das linhas exibem a data e a hora exatas em que o medicamento foi aplicado (ex: `31/03/2026 14:30:22`).
   - As colunas **Chegada** e **Atendimento** exibem os horários da respectiva sessão (ou o horário da própria aplicação caso sejam dados históricos).
3. Clique no botão **Exportar**.
4. **Resultado Esperado no Excel**:
   - O arquivo `.xlsx` baixado contém a coluna **Aplicação** no cabeçalho.
   - As linhas exibem a data e hora de aplicação corretas de cada medicamento.
   - As colunas **Chegada** e **Atendimento** no Excel também refletem os horários correspondentes da respectiva sessão ou o fallback.
