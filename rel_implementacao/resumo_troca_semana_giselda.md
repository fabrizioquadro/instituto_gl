# Relatório de Ajuste Manual - Giselda Meira Falbo (Semana 2 <-> Semana 6)

## 1. Contexto do Ajuste
No pacote da paciente **Giselda Meira Falbo** (Código do Pacote: `1956420260602162045`), as aplicações das semanas 2 e 6 estavam invertidas em relação ao cronograma de doses real:
* A aplicação realizada na segunda visita (em **10/06/2026**, contendo Mounjaro 2.5mg e Sangria pendente) foi registrada no sistema sob a **Semana 6**.
* A **Semana 2** (contendo Mounjaro 3.75mg e Sangria pendente) ficou em aberto com a data de agendamento retroativa (**03/06/2026**).

Para corrigir o histórico cronológico de forma limpa, realizamos a inversão das posições e datas de aplicação entre os dois procedimentos.

## 2. Ações Executadas (22/06/2026)
Executamos uma transação SQL contendo as seguintes ações:

### 2.1 Troca de Semana (`nr_procedimento`) e Data (`data_aplicacao`)
* **Procedimento ID 35322:**
  * Alterado de **Semana 2** para **Semana 6**
  * Data de aplicação alterada de **03/06/2026** para **10/06/2026**
* **Procedimento ID 35326:**
  * Alterado de **Semana 6** para **Semana 2**
  * Data de aplicação alterada de **10/06/2026** para **03/06/2026**

### 2.2 Log de Auditoria (`procedimento_logs`)
Inserimos registros de log detalhados para ambos os procedimentos documentando a ação:
* **Log do Proc 35322:** Atualização de semana/data para fins de ajuste cronológico de Semana 2 para Semana 6.
* **Log do Proc 35326:** Atualização de semana/data para fins de ajuste cronológico de Semana 6 para Semana 2.

---
*Este relatório foi gerado para histórico técnico de intervenção manual no banco de dados conforme as boas práticas do projeto.*
