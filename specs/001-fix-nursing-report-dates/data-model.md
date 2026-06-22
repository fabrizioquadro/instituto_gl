# Modelo de Dados: Relatório de Enfermagem e Datas de Aplicação

## Entidades

### Procedimento
- **Tabela**: `procedimentos`
- **Campos utilizados**:
  - `id` (int, Chave Primária)
  - `codigo` (string, código semanal do procedimento)
  - `nr_procedimento` (int, número da semana/sequência)
  - `data_aplicacao` (date, data da última finalização/aplicação)
  - `situacao` (string, ex: "Aplicado", "Aplicação Parcial")
- **Relacionamentos**:
  - Possui muitas `Aplicacao` (via método `aplicacaos()`)

### Aplicacao
- **Tabela**: `aplicacaos`
- **Campos utilizados**:
  - `id` (int, Chave Primária)
  - `procedimento_id` (int, Chave Estrangeira referenciando `procedimentos`)
  - `medicamento_id` (int, Chave Estrangeira referenciando `medicamentos`)
  - `situacao` (string, ex: "Aberta", "Aplicada", "Pendente")
  - `dt_hr_chegada` (dateTime, nullable, armazena o horário de chegada no momento da aplicação)
  - `dt_hr_atendimento` (dateTime, nullable, armazena o horário de atendimento no momento da aplicação)
  - `updated_at` (timestamp, registra quando a aplicação do medicamento foi finalizada/atualizada para "Aplicada")
- **Relacionamentos**:
  - Pertence a `Procedimento`
  - Pertence a `Medicamento`
