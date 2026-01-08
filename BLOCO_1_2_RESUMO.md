# BLOCO 1 e BLOCO 2 - Resumo da Implementação

## ✅ BLOCO 1: Setup + Passport + Auth

### 1. Instalação do Laravel Passport
- ✅ Instalado via composer: `composer require laravel/passport`
- ✅ Publicadas migrations do Passport: `php artisan vendor:publish --tag=passport-migrations`
- ✅ Instalado Passport: `php artisan passport:install` (gerou chaves de criptografia)

### 2. Configuração do Passport
- ✅ Criado `AuthServiceProvider` com configuração do Passport
- ✅ Adicionado `HasApiTokens` trait no modelo `User`
- ✅ Configurado guard `api` no `config/auth.php` usando Passport
- ✅ Registrado `AuthServiceProvider` no `bootstrap/providers.php`

### 3. Estrutura de Pastas
- ✅ Criadas pastas:
  - `app/Services/` - Para lógica de negócio
  - `app/DTOs/` - Para Data Transfer Objects

## ✅ BLOCO 2: Migrations + Models

### 1. Migrations Criadas

#### `create_cards_table.php`
```bash
php artisan make:migration create_cards_table
```
**Campos:**
- `id` (bigint, PK)
- `user_id` (FK -> users)
- `name` (string)
- `brand` (string, nullable)
- `last_four` (string, nullable)
- `credit_limit` (decimal 15,2)
- `closing_day` (tinyint, 1-31)
- `due_day` (tinyint, 1-31)
- `status` (enum: 'active', 'inactive', default: 'active')
- `timestamps`

**Índices:** `user_id`, `status`

#### `create_categories_table.php`
```bash
php artisan make:migration create_categories_table
```
**Campos:**
- `id` (bigint, PK)
- `user_id` (FK -> users)
- `name` (string)
- `type` (enum: 'INCOME', 'EXPENSE')
- `color` (string, nullable, hex color)
- `timestamps`

**Índices:** `user_id`, `type`

#### `create_transactions_table.php`
```bash
php artisan make:migration create_transactions_table
```
**Campos:**
- `id` (bigint, PK)
- `user_id` (FK -> users)
- `category_id` (FK -> categories)
- `card_id` (FK -> cards, nullable)
- `type` (enum: 'INCOME', 'EXPENSE')
- `payment_method` (enum: 'CASH', 'PIX', 'DEBIT', 'CREDIT', nullable)
- `amount` (decimal 15,2)
- `description` (text, nullable)
- `transaction_date` (date)
- `installments_total` (tinyint, default: 1)
- `installment_number` (tinyint, default: 1)
- `group_uuid` (string, nullable) - Para agrupar parcelas
- `is_paid` (boolean, default: false)
- `timestamps`

**Índices:** `user_id`, `category_id`, `card_id`, `type`, `payment_method`, `transaction_date`, `group_uuid`, `is_paid`

#### `create_invoices_table.php`
```bash
php artisan make:migration create_invoices_table
```
**Campos:**
- `id` (bigint, PK)
- `user_id` (FK -> users)
- `card_id` (FK -> cards)
- `cycle_month` (tinyint, 1-12)
- `cycle_year` (smallint)
- `closing_date` (date)
- `due_date` (date)
- `total_amount` (decimal 15,2, default: 0)
- `paid_amount` (decimal 15,2, default: 0)
- `is_paid` (boolean, default: false)
- `paid_at` (timestamp, nullable)
- `timestamps`

**Índices:** `user_id`, `card_id`, `['cycle_month', 'cycle_year']`, `closing_date`, `is_paid`
**Unique:** `['card_id', 'cycle_month', 'cycle_year']`

### 2. Models Criados

#### `User.php`
- ✅ Adicionado trait `HasApiTokens` do Passport
- ✅ Relacionamentos:
  - `hasMany(Card::class)`
  - `hasMany(Category::class)`
  - `hasMany(Transaction::class)`
  - `hasMany(Invoice::class)`

#### `Card.php`
- ✅ Fillable: `user_id`, `name`, `brand`, `last_four`, `credit_limit`, `closing_day`, `due_day`, `status`
- ✅ Casts: `credit_limit` -> `decimal:2`
- ✅ Relacionamentos:
  - `belongsTo(User::class)`
  - `hasMany(Transaction::class)`
  - `hasMany(Invoice::class)`
- ✅ Scope: `active()`

#### `Category.php`
- ✅ Fillable: `user_id`, `name`, `type`, `color`
- ✅ Relacionamentos:
  - `belongsTo(User::class)`
  - `hasMany(Transaction::class)`
- ✅ Scopes: `income()`, `expense()`

#### `Transaction.php`
- ✅ Fillable: todos os campos
- ✅ Casts: `amount` -> `decimal:2`, `transaction_date` -> `date`, `is_paid` -> `boolean`
- ✅ Relacionamentos:
  - `belongsTo(User::class)`
  - `belongsTo(Category::class)`
  - `belongsTo(Card::class)`
- ✅ Scopes: `income()`, `expense()`, `paid()`, `unpaid()`
- ✅ Método: `installmentGroup()` - retorna todas as parcelas do mesmo grupo

#### `Invoice.php`
- ✅ Fillable: todos os campos
- ✅ Casts: todos os campos de data e decimal
- ✅ Relacionamentos:
  - `belongsTo(User::class)`
  - `belongsTo(Card::class)`
- ✅ Scopes: `paid()`, `unpaid()`
- ✅ Accessor: `remainingAmount` - calcula valor restante a pagar

## 📋 Próximos Passos (Blocos 3-6)

### BLOCO 3: Seeders
- Criar seeders com dados fake
- Usuário demo
- 2 cartões
- Categorias (entradas e saídas)
- Transações incluindo parceladas

### BLOCO 4: Services + DTOs
- TransactionService
- CardService
- InvoiceService
- CategoryService
- DTOs para cada entidade

### BLOCO 5: Controllers + Routes
- AuthController
- DashboardController
- CardsController
- CategoriesController
- TransactionsController
- InvoicesController
- Rotas web com middleware auth

### BLOCO 6: Views + JavaScript
- Views Blade para todas as funcionalidades
- JavaScript puro para interações
- Bootstrap CDN para layout

## 🚀 Comandos para Executar

Para rodar as migrations:
```bash
docker-compose exec -T app php artisan migrate
```

Para verificar se tudo está funcionando:
```bash
docker-compose exec -T app php artisan route:list
docker-compose exec -T app php artisan tinker
```

## 📝 Notas Importantes

1. **Passport**: Configurado para usar tokens de API. Para autenticação web simples, podemos usar sessões também.
2. **Relacionamentos**: Todos os relacionamentos estão configurados com `onDelete('cascade')` ou `onDelete('restrict')` conforme apropriado.
3. **Índices**: Criados índices nas colunas mais consultadas para melhor performance.
4. **UUID para Parcelas**: Usado `group_uuid` para agrupar parcelas da mesma compra.
5. **Invoice Table**: Decisão de criar tabela de invoices para melhor performance e histórico.
