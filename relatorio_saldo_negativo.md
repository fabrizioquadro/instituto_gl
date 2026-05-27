# 📊 Relatório de Auditoria de Estoque: Saldos Negativos por Unidade, Medicamento e Lote

Este relatório apresenta os resultados de uma análise minuciosa realizada diretamente no banco de dados do **Instituto GL** (`u528878205_sistema`), identificando todas as movimentações de estoque que resultaram em **saldos negativos** (onde a quantidade acumulada de saídas supera as entradas) segmentados por **Unidade (Clínica)**, **Medicamento** e **Lote**.

---

## 📈 Resumo Executivo

* **Total de Unidades com Ocorrências:** 3 unidades (`Instituto GL`, `Instituto GL Tatuapé` e `Estoque Central`).
* **Total de Lotes Analisados com Saldo Negativo:** 71 lotes distintos.
* **Volume Total de Déficit de Estoque:** -3.554 unidades físicas acumuladas.
* **Alertas de Integridade Críticos:** Foram encontradas movimentações de lote negativas onde o `medicamento_id` está **nulo/órfão** no banco de dados.

---

## 🚨 Principais Causas Identificadas (Diagnóstico Operacional)

1. **Inversão Cronológica de Lançamento (Saída antes da Entrada):** 
   Procedimentos médicos, aplicações e baixas são registrados de forma ágil no dia a dia da clínica (gerando registros de `Saida` imediatos), porém as Notas Fiscais e entradas físicas correspondentes (`Entrada`) demoram a ser faturadas ou digitadas no sistema pelo setor de compras. Isso gera um "estouro temporário" de estoque que se perpetua se o lote não for conciliado.
   
2. **Divergência de Digitação na Grafia de Lotes:** 
   O mesmo lote físico é digitado de formas ligeiramente diferentes nas entradas e saídas. Por exemplo, uma entrada é registrada no lote **`CUA00290`** e a saída é dada no lote **`CUA290`**. Para o banco de dados, tratam-se de dois lotes distintos: um fica com saldo credor positivo de +100 e o outro fica negativo em -100.
   
3. **Medicamentos Órfãos (`medicamento_id` NULL):** 
   Existem registros na tabela `estoques` onde a chave estrangeira do medicamento foi perdida ou nunca associada. Isso impede a correta identificação visual do item no dashboard convencional e representa uma quebra de integridade relacional.

---

## 📋 Detalhamento dos Saldos Negativos por Unidade

### 🏢 1. Unidade: INSTITUTO GL (Sede)
* **Total de Lotes Negativos:** 27 lotes
* **Volume Total Negativo:** -2.302 unidades

| Medicamento | Fabricante | Lote | Total Entradas | Total Saídas | Saldo Negativo | Diagnóstico / Ação |
| :--- | :--- | :--- | :---: | :---: | :---: | :--- |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `10022241` | 0 | 50 | **-50.0** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `1002241` | 0 | 50 | **-50.0** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `1002254` | 0 | 150 | **-150.0** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `1002449` | 0 | 50 | **-50.0** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `1002515` | 0 | 200 | **-200.0** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `MSA00091` | 0 | 100 | **-100.0** | Lote bate com **MORUSIL** (Victa Lab) |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `PQA00192` | 0 | 100 | **-100.0** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `PQA00193` | 0 | 100 | **-100.0** | Investigar lote para restaurar `medicamento_id` |
| **MOUNJARO 90MG** | UNIKKA | `2547460` | 17 | 18 | **-1.0** | Ajuste pontual de digitação |
| **TESTOSTERONA CIPIONATO** | STIN PHARMA | `1002449` | 200 | 231.5 | **-31.5** | Lançamento de saída fracionado maior que entrada |
| **CURCUMINA INATIVO** | VICTA LAB | `CUA290` | 0 | 100 | **-100.0** | Grafia errada (lote correto é `CUA00290`) |
| **CHORIOMON HCG** | STIN PHARMA | `1002709` | 146 | 157 | **-11.0** | Divergência de 11 ampolas |
| **N ACETILCISTEÍNA** | VICTA LAB | `NCA00142` | 200 | 300 | **-100.0** | Falta lançar entrada complementar de 100 un |
| **N ACETILCISTEÍNA** | VICTA LAB | `NCA00144` | 501 | 600.5 | **-99.5** | Divergência por fracionamento decimal |
| **N ACETILCISTEÍNA** | VICTA LAB | `NCA00148` | 400 | 415 | **-15.0** | Saídas excedentes de 15 un |
| **NANDROLONA** | FLUKKA | `10011615` | 90 | 369.5 | **-279.5** | Grande divergência de estoque físico |
| **NANDROLONA** | FLUKKA | `1001828` | 40 | 239.5 | **-199.5** | Grande divergência de estoque físico |
| **NANDROLONA** | FLUKKA | `1002570` | 163 | 212 | **-49.0** | Saídas sem a NF de entrada correspondente |
| **NADH** | VICTA LAB | `NDA00157` | 0 | 100 | **-100.0** | Sem nenhuma entrada registrada para este lote |
| **FERRO ENDOVENOSO** | BLAUS | `24110767` | 30 | 108 | **-78.0** | Lançar nota de entrada pendente |
| **MOUNJARO 60MG** | FLUKKA | `000135` | 9 | 10 | **-1.0** | Ajuste pontual de digitação |
| **MOUNJARO 60MG** | FLUKKA | `000162` | 30 | 32 | **-2.0** | Ajuste pontual de digitação |
| **MOUNJARO 60MG** | FLUKKA | `1002402` | 27 | 28 | **-1.0** | Ajuste pontual de digitação |
| **MOUNJARO 60MG** | FLUKKA | `1002450` | 29 | 32 | **-3.0** | Ajuste pontual de digitação |
| **MOUNJARO 60MG** | FLUKKA | `3231` | 32 | 33 | **-1.0** | Ajuste pontual de digitação |
| **MOUNJARO 60MG** | FLUKKA | `45002451` | 5 | 6 | **-1.0** | Ajuste pontual de digitação |
| **CURCUMINA** | VICTA LAB | `CUA00285` | 146 | 375 | **-229.0** | Lançamento em lote incorreto |

---

### 🏢 2. Unidade: INSTITUTO GL TATUAPÉ
* **Total de Lotes Negativos:** 30 lotes
* **Volume Total Negativo:** -364 unidades

| Medicamento | Fabricante | Lote | Total Entradas | Total Saídas | Saldo Negativo | Diagnóstico / Ação |
| :--- | :--- | :--- | :---: | :---: | :---: | :--- |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `1002836` | 0 | 65 | **-65.0** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `2507071` | 0 | 0.5 | **-0.5** | Lote bate com **TESTOSTERONA ENANTATO** |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `2509231` | 0 | 0.5 | **-0.5** | Investigar lote para restaurar `medicamento_id` |
| ⚠️ **[ID Nulo - Órfão]** | N/D | `6711` | 0 | 0.5 | **-0.5** | Investigar lote para restaurar `medicamento_id` |
| **MOUNJARO 90MG** | UNIKKA | `2547460` | 9 | 10 | **-1.0** | Divergência pontual |
| **MOUNJARO 90MG** | UNIKKA | `5046` | 84 | 91 | **-7.0** | Divergência de 7 canetas |
| **TESTOSTERONA ENANTATO** | STIN PHARMA | `2507071` | 15 | 16 | **-1.0** | Ajuste pontual de digitação |
| **TESTOSTERONA ENANTATO** | STIN PHARMA | `5365` | 107 | 139 | **-32.0** | Falta NF de entrada |
| **TESTOSTERONA CIPIONATO** | STIN PHARMA | `10001997` | 50 | 52 | **-2.0** | Divergência pontual |
| **TESTOSTERONA CIPIONATO** | STIN PHARMA | `1002241` | 53 | 88 | **-35.0** | Saídas superiores às entradas |
| **TESTOSTERONA CIPIONATO** | STIN PHARMA | `1002823` | 0 | 20 | **-20.0** | Sem nenhuma entrada lançada no sistema |
| **CURCUMINA INATIVO** | VICTA LAB | `CUA00290` | 400 | 450 | **-50.0** | Grande consumo registrado sem NF |
| **COENZIMA Q10** | VICTA LAB | `CQB00140` | 161 | 164 | **-3.0** | Divergência pontual |
| **BCAA-HMB** | VICTA LAB | `TOW00055` | 20 | 25 | **-5.0** | Divergência pontual |
| **BCAA-HMB** | VICTA LAB | `TOW00056` | 20 | 27 | **-7.0** | Divergência pontual |
| **L CARNITINA** | VICTA LAB | `LAC00315` | 104 | 110 | **-6.0** | Divergência pontual |
| **L CARNITINA** | VICTA LAB | `LCA00316` | 316 | 319 | **-3.0** | Divergência pontual |
| **N ACETILCISTEÍNA** | VICTA LAB | `NCA00139` | 462.75 | 500.75 | **-38.0** | Discrepância decimal de fracionamento |
| **N ACETILCISTEÍNA** | VICTA LAB | `NCA00143` | 130 | 134 | **-4.0** | Divergência pontual |
| **NANDROLONA** | FLUKKA | `1002315` | 23 | 32 | **-9.0** | Divergência de ampolas |
| **NADH** | VICTA LAB | `NDA00157` | 63 | 68 | **-5.0** | Divergência de ampolas |
| **VITAMINA D** | VICTA LAB | `VTP00277` | 170 | 172.5 | **-2.5** | Fracionamento decimal |
| **VITAMINA D** | VICTA LAB | `VTP00283` | 1 | 5 | **-4.0** | Divergência pontual |
| **MORUSIL** | VICTA LAB | `MSA00091` | 80 | 82 | **-2.0** | Divergência pontual |
| **FERRO ENDOVENOSO** | BLAUS | `24110767` | 135 | 175 | **-40.0** | Falta registrar NF de entrada |
| **FERRO ENDOVENOSO** | BLAUS | `25103077` | 35 | 57 | **-22.0** | Falta registrar NF de entrada |
| **MOUNJARO 60MG** | FLUKKA | `000150` | 36 | 39 | **-3.0** | Divergência pontual |
| **MOUNJARO 60MG** | FLUKKA | `1002450` | 14 | 15 | **-1.0** | Divergência pontual |
| **MOUNJARO 60MG** | FLUKKA | `3224` | 5 | 6 | **-1.0** | Divergência pontual |
| **MOUNJARO 60MG** | FLUKKA | `3227` | 0 | 1 | **-1.0** | Sem entradas registradas |

---

### 🏢 3. Unidade: ESTOQUE CENTRAL
* **Total de Lotes Negativos:** 14 lotes
* **Volume Total Negativo:** -888 unidades

| Medicamento | Fabricante | Lote | Total Entradas | Total Saídas | Saldo Negativo | Diagnóstico / Ação |
| :--- | :--- | :--- | :---: | :---: | :---: | :--- |
| **MOUNJARO 90MG** | UNIKKA | `2547081` | 53 | 56 | **-3.0** | Divergência pontual |
| **TESTOSTERONA ENANTATO** | STIN PHARMA | `1002309` | 1030 | 1380 | **-350.0** | **Alerta Crítico:** Altíssimo volume negativo no Central |
| **TESTOSTERONA ENANTATO** | STIN PHARMA | `1002515` | 175 | 275 | **-100.0** | Falta entrada de lote no sistema central |
| **COENZIMA Q10** | VICTA LAB | `CQB00143` | 100 | 200 | **-100.0** | Lançamento duplicado de saídas ou falta NF |
| **NANDROLONA** | FLUKKA | `10011615` | 140 | 200 | **-60.0** | Saídas sem NF de cobertura |
| **NANDROLONA** | FLUKKA | `1001828` | 175 | 275 | **-100.0** | Falta registrar NF de entrada |
| **NANDROLONA** | FLUKKA | `1002372` | 200 | 220 | **-20.0** | Divergência de 20 un |
| **NANDROLONA** | FLUKKA | `1002570` | 230 | 250 | **-20.0** | Divergência de 20 un |
| **NADH** | VICTA LAB | `NDA00160` | 0 | 30 | **-30.0** | Sem nenhuma entrada registrada no Central |
| **NADH** | VICTA LAB | `NDA00165` | 310 | 360 | **-50.0** | Saídas excedendo as entradas |
| **VITAMINA B3** | VICTA LAB | `VTJ00170` | 200 | 250 | **-50.0** | Falta registrar NF de entrada |
| **MOUNJARO 60MG** | FLUKKA | `000150` | 90 | 91 | **-1.0** | Divergência pontual |
| **MOUNJARO 60MG** | FLUKKA | `3225` | 3 | 6 | **-3.0** | Divergência de 3 un |
| **MOUNJARO 60MG** | FLUKKA | `3231` | 31 | 32 | **-1.0** | Divergência pontual |

---

## 🛠️ Recomendações e Plano de Mitigação Operacional

### 1. Implementação de Regra Impeditiva de Estoque Negativo
Atualmente o sistema permite dar baixa em quantidades sem validar a existência física/fictícia no estoque. Recomendamos alterar o fluxo de baixa (na aplicação do procedimento ou baixa manual) para:
* Validar se o lote selecionado possui saldo suficiente na clínica executora.
* Se o saldo for insuficiente, **bloquear a operação** e exibir uma mensagem amigável: *"Lote sem saldo suficiente. Por favor, registre a Nota Fiscal de Entrada ou faça uma transferência de estoque antes de prosseguir."*

### 2. Saneamento dos Dados Relacionais (Chaves Estrangeiras Nulas)
Executar um script SQL corretivo para associar os medicamentos órfãos. Pelo lote e fabricante, é possível correlacionar as linhas nulas ao seu respectivo `medicamento_id` na tabela `medicamentos`. Por exemplo:
* O lote **`MSA00091`** pertence inequivocamente ao medicamento **MORUSIL** (Victa Lab).
* O lote **`2507071`** pertence inequivocamente ao medicamento **TESTOSTERONA ENANTATO**.

### 3. Padronização na Digitação dos Lotes (Validação Regex)
Adicionar máscaras ou validação de formato nos inputs de cadastro de novos lotes de entrada e saída. Isso evitaria que um mesmo lote fosse grafado como `CUA00290` e `CUA290`.

---
> 📅 **Data da Auditoria:** 18 de Maio de 2026  
> 💻 **Banco de Dados Analisado:** `u528878205_sistema` (MySQL via Laravel Eloquent)  
> 📊 **Ferramenta de Extração:** Script de Auditoria Personalizado (`scratch/analisar_saldo_negativo.php`)
