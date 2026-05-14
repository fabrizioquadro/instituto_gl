# Relatório de Implementação e Auditoria - Instituto GL

## 1. Alterações Realizadas
- **Correção de Lógica de Estoque (`EstoqueAdmController`)**: O sistema agora exibe lotes se houver saldo positivo em **qualquer clínica individual**, eliminando a dependência do saldo global (que podia ser negativo e "esconder" estoques reais).
- **Precisão Decimal em Procedimentos**: Alterado de `parseInt` para `parseFloat` nos formulários de Adicionar e Editar Procedimentos. Isso permite que medicamentos como "Pellet" (que usam valores decimais) tenham o faturamento calculado corretamente.

## 2. Auditoria do Dashboard (14/05/2026)
Identificamos uma divergência entre os valores apresentados na interface e os valores brutos do banco de dados:

- **Valor no Dashboard (Print)**: R$ 146.455,00
- **Valor no Banco de Dados (Consulta Direta)**: R$ 128.820,00
- **Diferença**: R$ 17.635,00

### Observações do Gráfico "Faturamento Clínica":
- O gráfico apresenta apenas 2 clínicas (Instituto GL e Tatuapé).
- A consulta ao banco identificou uma 3ª clínica (Núcleo I DR GUSTAVO) com faturamento de R$ 485,00 hoje, que pode não estar aparecendo no gráfico por ser um valor proporcionalmente muito pequeno (0.3%).

### Hipóteses para a Diferença de R$ 17k:
1. **Cache**: O Dashboard pode estar exibindo dados cacheados de um momento anterior do dia.
2. **Lógica de Soma**: Pode haver alguma regra de negócio no Controller do Dashboard que inclua ou exclua tipos específicos de procedimentos que não foram filtrados na consulta manual.
3. **Fuso Horário**: Pequenas variações na virada do dia dependendo da configuração do servidor.

---
*Este relatório foi gerado para acompanhamento posterior conforme solicitação do usuário.*
