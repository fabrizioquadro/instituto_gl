# Especificação da Funcionalidade: Correção das Datas do Relatório de Enfermagem e Divergência de Estoque

**Branch da Funcionalidade**: `001-fix-nursing-report-dates`

**Data de Criação**: 19/06/2026

**Status**: Rascunho (Atualizado com tempos de Chegada/Atendimento)

**Entrada**: Descrição do usuário sobre o problema:
"essa paciente no dia 31/03, ela aplicou coenzima, NAC, curcumina e vitamina D. E vitamina C e zinco ficou parcial para ser aplicado no dia 08/06, como mostra aí no relatório. Só que quando eu puxo aqui o relatório de enfermagem, tá aparecendo no dia 08/06 que essas outras medicações do dia de março, como se tivesse dado saída no dia 08/06 no relatório. E o estoque da enfermagem não está batendo, e essas medicações que estão aqui eh estão faltando no estão com saldo Enfim, tá dando divergência físico com o sistema. Você poderia verificar para mi se o sistema está dando saída novamente no estoque?"
E complemento do usuário:
"o problema é que a chegada e o atendimento ficam com os horarios muito errados, temos que arumar isso, tem como??? o que me indicas para podermmos arumar, colocar horaio de chegada e atendimento com a datetime na tabela de aplicação????? e os dados que já temos como tratariamos, os que não tem usariamos somente o que já temos o que me indicas???"

## Cenários de Usuário e Testes *(obrigatório)*

### Caso de Uso 1 - Filtrar Relatório de Enfermagem pela Data de Aplicação Individual (Prioridade: P1)

Como administrador ou auditor da clínica, eu quero que o Relatório de Enfermagem seja filtrado pela data real em que o medicamento foi aplicado, de modo que o relatório reflita as datas reais de saída do medicamento e atualização do estoque físico.

**Por que esta prioridade**: Sem isso, as aplicações parciais de procedimentos são completamente transferidas para a data de finalização do procedimento (última aplicação), fazendo com que relatórios históricos (como de março) não tenham os dados e os relatórios atuais (como de junho) acumulem as saídas incorretamente.

**Teste Independente**: Pode ser testado criando um procedimento com aplicações parciais em datas diferentes, gerando o relatório para cada período de data e verificando se os medicamentos aparecem apenas sob suas respectivas datas reais de aplicação.

**Cenários de Aceitação**:

1. **Dado** um procedimento com Coenzima aplicada em 31/03/2026 e Vitamina C aplicada em 08/06/2026, **Quando** o relatório de enfermagem for consultado para março de 2026, **Então** apenas a Coenzima deve ser listada.
2. **Dado** o mesmo procedimento, **Quando** o relatório de enfermagem for consultado para junho de 2026, **Então** apenas a Vitamina C deve ser listada.

---

### Caso de Uso 2 - Exibir a Data de Aplicação nas Colunas do Relatório (Prioridade: P1)

Como enfermeiro ou auditor que visualiza ou exporta o relatório, quero ver a data e a hora específicas em que cada medicamento foi aplicado em uma coluna dedicada, em vez de ver apenas a data geral de finalização do procedimento.

**Por que esta prioridade**: Fornecer uma data de aplicação clara no relatório torna as auditorias de estoque diretas e resolve a confusão de medicamentos que parecem ter saído na data errada.

**Teste Independente**: Verificar se a tabela HTML na tela e o arquivo Excel exportado contêm uma coluna que exibe a data/hora exata da aplicação para cada linha.

**Cenários de Aceitação**:

1. **Dado** que um relatório de enfermagem é gerado, **Quando** visualizado na tela ou exportado to Excel, **Então** uma coluna "Data Aplicação" exibe a data/hora exata em que a aplicação do medicamento foi registrada.

---

### Caso de Uso 3 - Registrar Horários de Chegada e Atendimento por Aplicação Individual (Prioridade: P1)

Como auditor da clínica, quero que as datas/horas de chegada e de atendimento no relatório correspondam aos horários reais do dia em que a medicação foi aplicada, e não aos horários do último dia de finalização do procedimento.

**Por que esta prioridade**: Em procedimentos com múltiplas sessões, se os horários de chegada e atendimento forem atualizados apenas no cabeçalho do procedimento, as aplicações anteriores perdem o histórico de horários originais e assumem indevidamente os tempos da última sessão.

**Teste Independente**: Confirmar que, ao salvar uma aplicação de medicamento, os horários atuais de chegada e atendimento do procedimento sejam gravados junto à aplicação, e exibidos individualmente na tabela do relatório.

**Cenários de Aceitação**:

1. **Dado** que a chegada do dia 31/03 foi às 10:00 e o atendimento às 10:30, **Quando** a Coenzima for aplicada e salva, **Então** a Coenzima registra estes horários de chegada e atendimento.
2. **Dado** que o paciente retorna em 08/06 com chegada às 14:00 e atendimento às 14:15, **Quando** a Vitamina C for aplicada e salva, **Então** a Vitamina C registra estes novos horários, mantendo os horários antigos da Coenzima inalterados.

---

### Casos de Borda (Edge Cases)

- **Medicamento em situação 'Aberta' ou 'Pendente'**: Medicamentos que ainda não foram aplicados DEVEM ser excluídos do relatório de enfermagem.
- **Dados Históricos Antigos**: Para aplicações antigas que não possuem `dt_hr_chegada` ou `dt_hr_atendimento` gravados no registro de aplicação, o sistema deve utilizar como fallback a data e hora da própria aplicação (`updated_at`).

## Requisitos *(obrigatório)*

### Requisitos Funcionais

- **RF-001**: O sistema DEVE consultar o relatório de enfermagem utilizando a data/hora real de gravação da aplicação (`aplicacaos.updated_at`), em vez de usar o campo `data_aplicacao` do procedimento.
- **RF-002**: A tabela HTML do relatório de enfermagem DEVE exibir a data e hora individuais de aplicação para cada linha de medicamento.
- **RF-003**: A exportação em Excel do relatório de enfermagem DEVE conter a coluna de data e hora individuais de aplicação para cada linha de medicamento.
- **RF-004**: Apenas aplicações com a situação "Aplicada" DEVEM ser incluídas no relatório.
- **RF-005**: O banco de dados DEVE conter campos de `dt_hr_chegada` e `dt_hr_atendimento` na tabela de `aplicacaos`.
- **RF-006**: Ao salvar a aplicação (método `set_aplicacao`), o sistema DEVE gravar a `dt_hr_chegada` e a `dt_hr_atendimento` vigentes do procedimento na respectiva aplicação.
- **RF-007**: Os relatórios e exportações DEVEM exibir os horários da aplicação, recorrendo ao horário da própria aplicação (`updated_at`) como fallback caso estejam nulos (dados históricos).

### Entidades Principais

- **Procedimento**: Representa a sessão semanal de procedimentos. Contém horários de sessão geral.
- **Aplicacao**: Representa o item de medicamento individual, agora contendo atributos de `dt_hr_chegada` e `dt_hr_atendimento` próprios, além de quantidade, situação, lote e `updated_at`.

## Critérios de Sucesso *(obrigatório)*

### Resultados Mensuráveis

- **CS-001**: Relatórios de enfermagem históricos (ex: 31/03) exibem exatamente os medicamentos aplicados naquela data específica com seus respectivos horários corretos de chegada e atendimento.
- **CS-002**: Relatórios de enfermagem atuais (ex: 08/06) exibem os novos medicamentos aplicados com seus horários vigentes corretos de chegada e atendimento.
- **CS-003**: Dados históricos mantêm a exibição de horários corretos através da estratégia de fallback.

## Premissas

- As tabelas e colunas necessárias serão adicionadas através de uma migration segura.
