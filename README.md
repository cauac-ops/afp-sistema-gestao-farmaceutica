# 💊 AFP — Sistema de Gestão Farmacêutica

<p align="center">
  <strong>💊 Pharmacy Management System</strong><br>
  <em>Complete web system for pharmacy operations</em>
</p>

🇧🇷 Português | 🇺🇸 [English](#-english)

> Sistema web completo para gestão de farmácias, com controle inteligente de estoque, vendas, clientes, agendamentos clínicos e relatórios gerenciais.

![Status](https://img.shields.io/badge/status-em%20desenvolvimento-yellow)
![Version](https://img.shields.io/badge/version-1.0.0-blue)

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)

![Last Commit](https://img.shields.io/github/last-commit/cauac-ops/afp-sistema-gestao-farmaceutica)
![Repo Size](https://img.shields.io/github/repo-size/cauac-ops/afp-sistema-gestao-farmaceutica)

![License](https://img.shields.io/badge/license-MIT-green)

---

## 🚀 Demonstração

🔗 **Sistema online:** [Abrir sistema](http://cacau.byethost6.com/)

👤 **Usuário:** `teste`  
🔒 **Senha:** `1234`

---

## 📸 Preview do Sistema
> Interface do sistema em diferentes módulos

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

## ⚙️ Funcionalidades

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
- Serviços clínicos: aferição de pressão, glicemia, aplicações injetáveis, curativos, entre outros
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

## 🛠️ Tecnologias

- **Backend:** PHP 7.4+
- **Banco de dados:** MySQL / MariaDB
- **Frontend:** HTML, CSS, Bootstrap 5.3
- **Scripts:** JavaScript
- **Gráficos:** Chart.js
- **Ícones:** Bootstrap Icons

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

## ⚙️ Instalação e execução

### 1. Banco de dados
- Crie um banco no MySQL
- Importe o script SQL do projeto

### 2. Configuração do ambiente
- Crie um arquivo chamado `.env` na raiz do projeto (caso não exista)
- Configure as variáveis de conexão com o banco de dados:

```env
DB_HOST=seu_host
DB_NAME=seu_banco
DB_USER=seu_usuario
DB_PASS=sua_senha
```
### 3. Execução
- Utilize um servidor local (XAMPP, Laragon ou WAMP)
- Acesse o projeto via navegador:
  http://localhost/afp-sistema-gestao-farmaceutica
  
---

## 🚀 Diferenciais do Projeto

- 🔐 **Controle de acesso por níveis de usuário**  
  Sistema com permissões bem definidas por cargo, garantindo segurança e organização das operações.

- 📦 **Atualização automática de estoque**  
  Cada venda realizada impacta diretamente o estoque, evitando inconsistências.

- ⚠️ **Alertas inteligentes**  
  Identificação automática de produtos com estoque baixo e receitas vencidas.

- 📊 **Dashboard com indicadores em tempo real**  
  Visualização rápida de dados importantes para tomada de decisão.

- 📅 **Integração entre módulos**  
  Clientes, receitas, vendas e agendamentos conectados em um único sistema.

- 📈 **Relatórios gerenciais completos**  
  Análise de desempenho, vendas e comportamento dos clientes.

- 🧩 **Arquitetura modular**  
  Código organizado facilitando manutenção e expansão do sistema.

- 💻 **Interface intuitiva**  
  Foco em usabilidade para facilitar o uso no dia a dia de farmácias.

---

## 📄 Licença

Este projeto está sob a licença MIT.

---

# 🇺🇸 English

## 💊 AFP — Pharmacy Management System

> Complete web system for pharmacy management, featuring smart control of inventory, sales, customers, clinical appointments, and management reports.

---

## 🚀 Demo

🔗 **Live system:** [Open system](http://cacau.byethost6.com/)

👤 **User:** `teste`  
🔒 **Password:** `1234`

---

## ⚙️ Features

### 📊 Dashboard
- Sales indicators and daily revenue overview
- Daily appointments
- Alerts for low stock and expired prescriptions
- Performance chart (last 7 days)
- Quick access to main features

### 💰 Sales Management
- Multi-product sales registration
- Discount application per sale
- Payment methods: Cash, Debit, Credit, PIX
- Automatic stock update
- Full history with filters

### 📦 Products and Inventory
- Product registration with barcode, category, and expiration date
- Minimum stock control with alerts
- Movement tracking (in/out)
- Identification of prescription-required products (RX)

### 👥 Customers
- Full customer registration (CPF, phone, email, address)
- Quick search by name or CPF

### 📅 Appointments
- Clinical services: blood pressure monitoring, glucose testing, injections, wound care, among others
- Status control: Scheduled, Completed, Cancelled, No-show
- Filters by date and status

### 📜 Prescriptions
- Linked to patient and doctor (CRM)
- Automatic expiration control
- Status: Valid, Expired, Cancelled

### 📈 Reports *(restricted)*
- Sales by period and payment method
- Best-selling products
- Customer ranking
- Appointment reports

---

## 🛠️ Technologies

- **Backend:** PHP 7.4+
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, Bootstrap 5.3
- **Scripts:** JavaScript
- **Charts:** Chart.js
- **Icons:** Bootstrap Icons

---

## 🔐 Access Control

The system includes role-based access control:

| Role           | Employees | Reports | Other pages |
|----------------|:---------:|:-------:|:-----------:|
| Administrator  | ✅        | ✅      | ✅          |
| Manager        | ✅        | ✅      | ✅          |
| Pharmacist     | ❌        | ✅      | ✅          |
| Attendant      | ❌        | ❌      | ✅          |
| Assistant      | ❌        | ❌      | ✅          |

> Permissions are validated in real time on the server.

---

## ⚙️ Installation and Setup

### 1. Database
- Create a database in MySQL
- Import the project's SQL script

### 2. Environment Configuration
- Create a file named `.env` in the project root (if it does not exist)
- Configure the database connection variables:

```env
DB_HOST=your_host
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password
```
### 3. Run
- Use a local server (XAMPP, Laragon, WAMP)
- Access via browser:
  http://localhost/afp-sistema-gestao-farmaceutica

---

## 🚀 Project Highlights

- 🔐 **Role-based access control**
- 📦 **Automatic stock updates**
- ⚠️ **Smart alerts system**
- 📊 **Real-time dashboard**
- 📅 **Integrated modules**
- 📈 **Advanced reports**
- 🧩 **Modular architecture**
- 💻 **User-friendly interface**

---
