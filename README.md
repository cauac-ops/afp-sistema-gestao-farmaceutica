# afp-sistema-gestao-farmaceutica
Sistema web de gestão farmacêutica com controle de vendas, estoque, clientes, agendamentos e relatórios, desenvolvido em PHP com foco em organização, segurança e boas práticas.

# 💊 AFP — Agenda Farmacêutica de Planejamento

## 📌 Sobre o projeto
O AFP é um sistema web completo de gestão farmacêutica, desenvolvido para auxiliar no controle de vendas, estoque, clientes, agendamentos clínicos, receitas médicas e relatórios gerenciais.

O sistema foi projetado com foco em organização, usabilidade e automação de processos comuns em farmácias e drogarias.

---

## 🚀 Funcionalidades

### 📊 Dashboard
- Indicadores de vendas e receita diária
- Agendamentos do dia
- Alertas de estoque baixo e receitas vencidas
- Gráfico de desempenho (últimos 7 dias)
- Acesso rápido às principais funcionalidades

### 💰 Vendas
- Registro de vendas com múltiplos produtos
- Aplicação de desconto por venda
- Formas de pagamento: Dinheiro, Débito, Crédito e PIX
- Atualização automática do estoque
- Histórico completo com filtros

### 📦 Produtos e Estoque
- Cadastro com código de barras, categoria e validade
- Controle de estoque mínimo com alertas
- Registro de movimentações (entrada/saída)
- Identificação de produtos com retenção de receita (RX)

### 👥 Clientes
- Cadastro completo (CPF, telefone, e-mail, endereço)
- Busca rápida por nome ou CPF

### 📅 Agendamentos
- Serviços: pressão, glicemia, injetáveis, curativos, etc.
- Controle de status: Agendado, Concluído, Cancelado, Faltou
- Filtros por data e status

### 📜 Receitas Médicas
- Vinculação com paciente e médico (CRM)
- Controle de validade automático
- Status: Válida, Vencida, Cancelada

### 📈 Relatórios *(restrito)*
- Vendas por período e forma de pagamento
- Produtos mais vendidos
- Ranking de clientes
- Relatórios de agendamentos

---

## 🛠️ Tecnologias utilizadas
- PHP 7.4+
- MySQL / MariaDB
- HTML, CSS e Bootstrap 5.3
- JavaScript
- Chart.js
- Bootstrap Icons

---

## 🔐 Controle de acesso
O sistema possui controle de permissões por cargo:

| Cargo         | Funcionários | Relatórios | Demais páginas |
|---------------|:------------:|:----------:|:--------------:|
| Administrador | ✅           | ✅         | ✅             |
| Gerente       | ✅           | ✅         | ✅             |
| Farmacêutico  | ❌           | ✅         | ✅             |
| Atendente     | ❌           | ❌         | ✅             |
| Auxiliar      | ❌           | ❌         | ✅             |

> As permissões são validadas em tempo real no servidor.

---

## 🔐 Acesso de demonstração

O sistema está disponível para testes:

🌐 http://cacau.byethost6.com/login_afp.php

Credenciais:
- Usuário: teste
- Senha: 1234

🔒 Esta conta possui acesso limitado para garantir a segurança do sistema.

---

## 📷 Demonstração do sistema

### 📊 Dashboard
<p align="center">
  <img src="assets/dashboard.png" width="800">
</p>

### 💰 Vendas
<p align="center">
  <img src="assets/vendas.png" width="800">
</p>

### 📦 Estoque
<p align="center">
  <img src="assets/estoque.png" width="800">
</p>

### 🛍️ Produtos
<p align="center">
  <img src="assets/produto.png" width="800">
</p>

### 📈 Relatórios
<p align="center">
  <img src="assets/relatorios.png" width="800">
</p>

---

## ⚙️ Instalação e execução

### 1. Banco de dados
- Crie um banco no MySQL
- Importe o script SQL do projeto

### 2. Configuração
Crie um arquivo `config.php` com suas credenciais:

```php
define('DB_HOST', 'seu_host');
define('DB_NAME', 'seu_banco');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
