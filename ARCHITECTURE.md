# Arquitetura do Sistema de Finanças Pessoais

## Visão Geral da Arquitetura

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php          # Login/Register
│   │   ├── DashboardController.php     # Dashboard principal
│   │   ├── CardsController.php         # CRUD de cartões
│   │   ├── CategoriesController.php    # CRUD de categorias
│   │   ├── TransactionsController.php  # CRUD de transações
│   │   └── InvoicesController.php      # Visualização de faturas
│   └── Middleware/
│       └── Authenticate.php            # Middleware de autenticação
├── Services/
│   ├── TransactionService.php          # Lógica de negócio de transações
│   ├── CardService.php                 # Lógica de negócio de cartões
│   ├── InvoiceService.php              # Cálculo e geração de faturas
│   └── CategoryService.php             # Lógica de negócio de categorias
├── DTOs/
│   ├── CreateTransactionDTO.php
│   ├── UpdateTransactionDTO.php
│   ├── CreateCardDTO.php
│   ├── UpdateCardDTO.php
│   ├── CreateCategoryDTO.php
│   └── UpdateCategoryDTO.php
└── Models/
    ├── User.php
    ├── Card.php
    ├── Category.php
    ├── Transaction.php
    └── Invoice.php

database/
├── migrations/
│   ├── create_cards_table.php
│   ├── create_categories_table.php
│   ├── create_transactions_table.php
│   └── create_invoices_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── UserSeeder.php
    ├── CardSeeder.php
    ├── CategorySeeder.php
    └── TransactionSeeder.php

resources/
└── views/
    ├── auth/
    │   ├── login.blade.php
    │   └── register.blade.php
    ├── dashboard/
    │   └── index.blade.php
    ├── cards/
    │   ├── index.blade.php
    │   ├── create.blade.php
    │   └── edit.blade.php
    ├── categories/
    │   ├── index.blade.php
    │   ├── create.blade.php
    │   └── edit.blade.php
    ├── transactions/
    │   ├── index.blade.php
    │   ├── create.blade.php
    │   └── edit.blade.php
    └── invoices/
        └── index.blade.php

routes/
└── web.php                              # Rotas web com middleware auth
```

## Modelagem do Banco de Dados

### Tabelas e Relacionamentos

#### 1. users (já existe)
- id (bigint, PK)
- name (string)
- email (string, unique)
- email_verified_at (timestamp, nullable)
- password (string)
- remember_token (string, nullable)
- created_at, updated_at

#### 2. cards
- id (bigint, PK)
- user_id (bigint, FK -> users.id)
- name (string) - ex: "Nubank", "Itau"
- brand (string, nullable) - ex: "VISA", "MASTERCARD"
- last_four (string, nullable) - últimos 4 dígitos
- credit_limit (decimal 15,2) - limite do cartão
- closing_day (tinyint) - dia do fechamento (1-31)
- due_day (tinyint) - dia do vencimento (1-31)
- status (enum: 'active', 'inactive') - default 'active'
- created_at, updated_at

**Índices:**
- user_id (index)
- status (index)

#### 3. categories
- id (bigint, PK)
- user_id (bigint, FK -> users.id)
- name (string)
- type (enum: 'INCOME', 'EXPENSE')
- color (string, nullable) - hex color
- created_at, updated_at

**Índices:**
- user_id (index)
- type (index)

#### 4. transactions
- id (bigint, PK)
- user_id (bigint, FK -> users.id)
- category_id (bigint, FK -> categories.id)
- card_id (bigint, nullable, FK -> cards.id) - apenas para CREDIT
- type (enum: 'INCOME', 'EXPENSE')
- payment_method (enum: 'CASH', 'PIX', 'DEBIT', 'CREDIT', nullable) - apenas para EXPENSE
- amount (decimal 15,2)
- description (text, nullable)
- transaction_date (date) - data da transação
- installments_total (tinyint, default 1) - total de parcelas
- installment_number (tinyint, default 1) - número da parcela atual
- group_uuid (string, nullable) - UUID para agrupar parcelas
- is_paid (boolean, default false) - se a parcela foi paga
- created_at, updated_at

**Índices:**
- user_id (index)
- category_id (index)
- card_id (index)
- type (index)
- payment_method (index)
- transaction_date (index)
- group_uuid (index)
- is_paid (index)

#### 5. invoices
- id (bigint, PK)
- user_id (bigint, FK -> users.id)
- card_id (bigint, FK -> cards.id)
- cycle_month (tinyint) - mês do ciclo (1-12)
- cycle_year (smallint) - ano do ciclo
- closing_date (date) - data de fechamento calculada
- due_date (date) - data de vencimento calculada
- total_amount (decimal 15,2) - total da fatura
- paid_amount (decimal 15,2, default 0) - valor pago
- is_paid (boolean, default false) - se a fatura foi paga
- paid_at (timestamp, nullable) - quando foi paga
- created_at, updated_at

**Índices:**
- user_id (index)
- card_id (index)
- cycle_month, cycle_year (composite index)
- closing_date (index)
- is_paid (index)
- UNIQUE: (card_id, cycle_month, cycle_year)

### Relacionamentos

```
User (1) -> (N) Card
User (1) -> (N) Category
User (1) -> (N) Transaction
User (1) -> (N) Invoice

Card (1) -> (N) Transaction (quando payment_method = CREDIT)
Card (1) -> (N) Invoice

Category (1) -> (N) Transaction
```

### Decisões de Design

1. **Invoice Table**: Criamos uma tabela `invoices` para armazenar faturas calculadas. Isso permite:
   - Performance melhor ao consultar faturas
   - Histórico de faturas pagas
   - Marcar fatura como paga sem alterar transações
   - Consistência de dados

2. **group_uuid**: Usamos UUID para agrupar parcelas da mesma compra, permitindo identificar todas as parcelas relacionadas.

3. **Ciclo de Fatura**: O ciclo é calculado baseado no `closing_day` do cartão. Por exemplo, se closing_day = 10:
   - Transações de 11/jan a 10/fev → ciclo de fevereiro
   - Transações de 11/fev a 10/mar → ciclo de março

4. **is_paid na Transaction**: Permite marcar parcelas individuais como pagas, útil para controle fino.

5. **is_paid na Invoice**: Permite marcar a fatura inteira como paga, simplificando o controle.

