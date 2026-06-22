# Relatório de Restauração Manual - Semana 7 (Procedimento 24066)

## 1. Contexto do Incidente
No dia **11/06/2026 às 18:59:15**, o procedimento de ID **24066** (Semana 7 da paciente Caroline Silva Sousa, ID **21330**) e suas respectivas aplicações foram excluídos acidentalmente no sistema pela usuária com ID **56**. 

Esta exclusão causou:
1. A perda do histórico clínico da aplicação da Semana 7 ocorrida em **11/05/2026**.
2. A renumeração automática das semanas seguintes do pacote no sistema (a Semana 8 tornou-se Semana 7, a nova Semana 9 tornou-se Semana 8, etc.).
3. Inconsistência de faturamento e de relatórios de enfermagem.

## 2. Análise Técnica e Resgate de Dados
Para restaurar a integridade total do sistema sem causar novos débitos de estoque físico ou financeiro, realizamos uma análise cruzando o banco de dados ativo, os logs de auditoria e o backup enviado do dia **08/05/2026** (`u528878205_sistema.20260508003624.sql`).

* **Originalmente no Backup (08/05/2026):** O procedimento `24066` existia em situação `"Agendado"` com 4 aplicações em aberto (`55112`, `55113`, `55114`, `70212`).
* **Nos Logs de Auditoria (11/05/2026):** A paciente compareceu no dia **11/05/2026**. A chegada foi registrada às **12:49:23** e o atendimento às **12:49:39**. As aplicações de Coenzima Q10, N Acetilcisteína e Curcumina foram marcadas como `"Aplicada"` às **12:59:49** pela profissional Luana Silva de Almeida (ID **50**). O medicamento Mounjaro 60mg permaneceu pendente (`"Aberta"`), alterando o status do procedimento para `"Aplicação Parcial"`.

## 3. Ações de Restauração Executadas (22/06/2026)
Executamos uma transação SQL contendo as seguintes ações:

### 3.1 Restauração do Procedimento Principal (`procedimentos`)
* **ID:** `24066`
* **Código:** `2133020260327205953`
* **Semana:** `7`
* **Situação:** `Aplicação Parcial`
* **Data da Aplicação:** `2026-05-11`
* **Chegada/Atendimento:** `12:49:23` / `12:49:39`

### 3.2 Restauração das Aplicações (`aplicacaos`)
1. **ID 55112:** COENZIMA Q10 | Qtd: `1.0` | Situação: `Aplicada` | Aplicado por: ID `50` | Data: `2026-05-11 12:59:49`
2. **ID 55113:** N ACETILCISTEÍNA | Qtd: `0.5` | Situação: `Aplicada` | Aplicado por: ID `50` | Data: `2026-05-11 12:59:49`
3. **ID 55114:** CURCUMINA | Qtd: `1.0` | Situação: `Aplicada` | Aplicado por: ID `50` | Data: `2026-05-11 12:59:49`
4. **ID 70212:** MOUNJARO 60MG | Qtd: `6.25` | Situação: `Aberta` (Pendente)

### 3.3 Associação de Lotes Históricos (`aplicacao_lotes`)
Como a aplicação ocorreu no dia 11/05 (posterior ao backup), associamos os lotes ativos na clínica naquele período para garantir a precisão histórica das ampolas de dose única aplicadas:
* **Coenzima Q10 (55112):** Lote `CQB00145` | C. Barras `1500032`
* **N Acetilcisteína (55113):** Lote `NCA00145` | C. Barras `2500119`
* **Curcumina (55114):** Lote `CUA00299` | C. Barras `5000017`

### 3.4 Correção da Numeração das Semanas (Sequenciamento)
Após a inserção, chamamos a lógica nativa do sistema (`recalcular_semanas_grupo`) com o código do pacote para reorganizar a numeração cronológica. As semanas seguintes foram reajustadas da seguinte forma:
* Procedimento **24067** (era Semana 7 temporária) $\rightarrow$ Retornou a ser **Semana 8** (Data: 18/05/2026)
* Procedimento **36694** (era Semana 8 temporária) $\rightarrow$ Atualizou para **Semana 9** (Data: 11/06/2026)
* Procedimento **37178** (era Semana 9 temporária) $\rightarrow$ Atualizou para **Semana 10** (Data: 15/06/2026)
* Procedimento **37179** (era Semana 10 temporária) $\rightarrow$ Atualizou para **Semana 11** (Data: 22/06/2026)

### 3.5 Log de Auditoria (`procedimento_logs`)
Inserido o registro de log ID correspondente na tabela para garantir a rastreabilidade futura da intervenção de suporte:
* **Ação:** Restauração
* **Descrição:** Procedimento e aplicações da Semana 7 restaurados a partir dos logs de auditoria e do backup.

---
*Este relatório foi gerado para histórico técnico de intervenção manual no banco de dados conforme solicitação do usuário.*
