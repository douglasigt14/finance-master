# BLOCO 4 e BLOCO 5 - Resumo da Implementação

## ✅ BLOCO 4: Services + DTOs

### DTOs Criados

#### 1. CreateCardDTO
**Arquivo:** `app/DTOs/CreateCardDTO.php`
- Valida e normaliza dados para criação de cartões
- Métodos: `fromArray()`, `toArray()`

#### 2. UpdateCardDTO
**Arquivo:** `app/DTOs/UpdateCardDTO.php`
- Valida e normaliza dados para atualização de cartões
- Todos os campos são opcionais

#### 3. CreateCategoryDTO
**Arquivo:** `app/DTOs/CreateCategoryDTO.php`
- Valida e normaliza dados para criação de categorias

#### 4. UpdateCategoryDTO
**Arquivo:** `app/DTOs/UpdateCategoryDTO.php`
- Valida e normaliza dados para atualização de categorias

#### 5. CreateTransactionDTO
**Arquivo:** `app/DTOs/CreateTransactionDTO.php`
- Valida e normaliza dados para criação de transações
- Suporta parcelas via `installments_total`

#### 6. UpdateTransactionDTO
**Arquivo:** `app/DTOs/UpdateTransactionDTO.php`
- Valida e normaliza dados para atualização de transações

### Services Criados

#### 1. CategoryService
**Arquivo:** `app/Services/CategoryService.php`
- `getAllByUser()` - Lista todas as categorias do usuário
- `getById()` - Busca categoria por ID
- `create()` - Cria nova categoria
- `update()` - Atualiza categoria
- `delete()` - Deleta categoria
- `getByType()` - Filtra por tipo (INCOME/EXPENSE)

#### 2. CardService
**Arquivo:** `app/Services/CardService.php`
- `getAllByUser()` - Lista todos os cartões do usuário
- `getActiveByUser()` - Lista apenas cartões ativos
- `getById()` - Busca cartão por ID
- `create()` - Cria novo cartão
- `update()` - Atualiza cartão
- `delete()` - Deleta cartão

#### 3. TransactionService
**Arquivo:** `app/Services/TransactionService.php`
- `getAllByUser()` - Lista transações com filtros opcionais
- `getById()` - Busca transação por ID
- `create()` - Cria transação (com suporte a parcelas)
- `createInstallments()` - Cria múltiplas parcelas automaticamente
- `update()` - Atualiza transação
- `delete()` - Deleta transação (ou grupo de parcelas)
- `markAsPaid()` - Marca como paga
- `markAsUnpaid()` - Marca como não paga
- `getInstallmentGroup()` - Retorna todas as parcelas do mesmo grupo

**Lógica de Parcelas:**
- Quando `payment_method = CREDIT` e `installments_total > 1`
- Gera automaticamente N transações (uma por parcela)
- Cada parcela tem data incrementada mensalmente
- Todas as parcelas compartilham o mesmo `group_uuid`
- Valor é dividido igualmente entre as parcelas

#### 4. InvoiceService
**Arquivo:** `app/Services/InvoiceService.php`
- `getOrCreateInvoice()` - Busca ou cria fatura para um ciclo
- `createInvoice()` - Cria nova fatura
- `calculateCycleDates()` - Calcula datas do ciclo baseado no `closing_day`
- `calculateInvoiceTotal()` - Calcula total da fatura baseado nas transações
- `recalculateInvoice()` - Recalcula fatura existente
- `getInvoicesByCard()` - Lista todas as faturas de um cartão
- `getCurrentInvoice()` - Retorna fatura do ciclo atual
- `markAsPaid()` - Marca fatura como paga
- `markAsUnpaid()` - Marca fatura como não paga
- `getAvailableCredit()` - Calcula crédito disponível (limite - gastos)

**Lógica de Ciclo de Fatura:**
- Baseado no `closing_day` do cartão
- Exemplo: se `closing_day = 10`:
  - Ciclo de fevereiro: 11/jan a 10/fev
  - Ciclo de março: 11/fev a 10/mar
- Calcula automaticamente `closing_date` e `due_date`

## ✅ BLOCO 5: Controllers + Routes

### Form Requests Criados

#### 1. StoreCardRequest
**Arquivo:** `app/Http/Requests/StoreCardRequest.php`
- Valida: name, brand, last_four, credit_limit, closing_day, due_day, status

#### 2. UpdateCardRequest
**Arquivo:** `app/Http/Requests/UpdateCardRequest.php`
- Todos os campos são opcionais (sometimes)

#### 3. StoreCategoryRequest
**Arquivo:** `app/Http/Requests/StoreCategoryRequest.php`
- Valida: name, type (INCOME/EXPENSE), color (hex)

#### 4. UpdateCategoryRequest
**Arquivo:** `app/Http/Requests/UpdateCategoryRequest.php`
- Todos os campos são opcionais

#### 5. StoreTransactionRequest
**Arquivo:** `app/Http/Requests/StoreTransactionRequest.php`
- Valida: category_id, type, amount, transaction_date
- `card_id` obrigatório se `payment_method = CREDIT`
- `payment_method` obrigatório se `type = EXPENSE`
- `installments_total` obrigatório se `payment_method = CREDIT`

#### 6. UpdateTransactionRequest
**Arquivo:** `app/Http/Requests/UpdateTransactionRequest.php`
- Todos os campos são opcionais

### Controllers Criados

#### 1. AuthController
**Arquivo:** `app/Http/Controllers/AuthController.php`
- `showLoginForm()` - Exibe formulário de login
- `login()` - Processa login
- `showRegisterForm()` - Exibe formulário de registro
- `register()` - Processa registro
- `logout()` - Faz logout

#### 2. DashboardController
**Arquivo:** `app/Http/Controllers/DashboardController.php`
- `index()` - Exibe dashboard com:
  - Total de entradas do mês
  - Total de saídas do mês
  - Saldo (entradas - saídas)
  - Gastos por categoria
  - Resumo de cartões (limite, usado, disponível)
  - Transações recentes

#### 3. CardsController
**Arquivo:** `app/Http/Controllers/CardsController.php`
- Resource controller completo:
  - `index()` - Lista cartões
  - `create()` - Formulário de criação
  - `store()` - Salva novo cartão
  - `show()` - Detalhes do cartão
  - `edit()` - Formulário de edição
  - `update()` - Atualiza cartão
  - `destroy()` - Deleta cartão

#### 4. CategoriesController
**Arquivo:** `app/Http/Controllers/CategoriesController.php`
- Resource controller completo:
  - `index()` - Lista categorias
  - `create()` - Formulário de criação
  - `store()` - Salva nova categoria
  - `show()` - Detalhes da categoria
  - `edit()` - Formulário de edição
  - `update()` - Atualiza categoria
  - `destroy()` - Deleta categoria

#### 5. TransactionsController
**Arquivo:** `app/Http/Controllers/TransactionsController.php`
- Resource controller completo:
  - `index()` - Lista transações (com filtros)
  - `create()` - Formulário de criação
  - `store()` - Salva nova transação (gera parcelas se necessário)
  - `show()` - Detalhes da transação (mostra grupo de parcelas se aplicável)
  - `edit()` - Formulário de edição
  - `update()` - Atualiza transação
  - `destroy()` - Deleta transação (ou grupo de parcelas)
- Métodos extras:
  - `markAsPaid()` - Marca como paga
  - `markAsUnpaid()` - Marca como não paga

#### 6. InvoicesController
**Arquivo:** `app/Http/Controllers/InvoicesController.php`
- `index()` - Lista faturas de um cartão (ou primeiro cartão)
- `show()` - Detalhes da fatura com transações do ciclo
- `markAsPaid()` - Marca fatura como paga
- `markAsUnpaid()` - Marca fatura como não paga
- `recalculate()` - Recalcula total da fatura

### Rotas Configuradas

**Arquivo:** `routes/web.php`

#### Rotas Públicas (guest):
- `GET /login` - Formulário de login
- `POST /login` - Processa login
- `GET /register` - Formulário de registro
- `POST /register` - Processa registro

#### Rotas Protegidas (auth):
- `GET /` - Redireciona para dashboard
- `POST /logout` - Logout
- `GET /dashboard` - Dashboard principal

**Resource Routes:**
- `GET /cards` - Lista cartões
- `GET /cards/create` - Formulário criar cartão
- `POST /cards` - Salva cartão
- `GET /cards/{id}` - Detalhes do cartão
- `GET /cards/{id}/edit` - Formulário editar cartão
- `PUT/PATCH /cards/{id}` - Atualiza cartão
- `DELETE /cards/{id}` - Deleta cartão

- `GET /categories` - Lista categorias
- `GET /categories/create` - Formulário criar categoria
- `POST /categories` - Salva categoria
- `GET /categories/{id}` - Detalhes da categoria
- `GET /categories/{id}/edit` - Formulário editar categoria
- `PUT/PATCH /categories/{id}` - Atualiza categoria
- `DELETE /categories/{id}` - Deleta categoria

- `GET /transactions` - Lista transações
- `GET /transactions/create` - Formulário criar transação
- `POST /transactions` - Salva transação
- `GET /transactions/{id}` - Detalhes da transação
- `GET /transactions/{id}/edit` - Formulário editar transação
- `PUT/PATCH /transactions/{id}` - Atualiza transação
- `DELETE /transactions/{id}` - Deleta transação
- `POST /transactions/{id}/mark-paid` - Marca como paga
- `POST /transactions/{id}/mark-unpaid` - Marca como não paga

- `GET /invoices` - Lista faturas
- `GET /invoices/card/{cardId}` - Faturas de um cartão
- `GET /invoices/card/{cardId}/{month}/{year}` - Detalhes da fatura
- `POST /invoices/card/{cardId}/{month}/{year}/mark-paid` - Marca como paga
- `POST /invoices/card/{cardId}/{month}/{year}/mark-unpaid` - Marca como não paga
- `POST /invoices/card/{cardId}/{month}/{year}/recalculate` - Recalcula fatura

## 🏗️ Arquitetura

### Separação de Responsabilidades

1. **Controllers** - Magros, apenas recebem requests e delegam para Services
2. **Services** - Contêm toda a lógica de negócio
3. **DTOs** - Normalizam e validam dados de entrada
4. **Form Requests** - Validação de formulários
5. **Models** - Apenas relacionamentos e configurações Eloquent

### Fluxo de Dados

```
Request → FormRequest (validação) → Controller → DTO (normalização) → Service (lógica) → Model → Database
```

### Exemplo: Criar Transação Parcelada

1. Usuário submete formulário
2. `StoreTransactionRequest` valida dados
3. `TransactionsController::store()` recebe request
4. Cria `CreateTransactionDTO` com dados normalizados
5. `TransactionService::create()` processa:
   - Se `payment_method = CREDIT` e `installments_total > 1`
   - Gera N transações (parcelas) em transação de banco
   - Cada parcela com data incrementada mensalmente
   - Todas com mesmo `group_uuid`
6. Retorna sucesso ao controller
7. Controller redireciona com mensagem

## 📝 Observações Importantes

1. **Transações de Banco**: Uso de `DB::transaction()` ao criar parcelas para garantir atomicidade
2. **Validação em Camadas**: Form Requests + DTOs garantem dados válidos
3. **Autorização**: Todos os controllers verificam se o recurso pertence ao usuário logado
4. **Cálculo de Faturas**: Baseado em `closing_day` do cartão, calcula ciclo automaticamente
5. **Parcelas**: Agrupadas por `group_uuid`, permitindo visualizar e gerenciar todas juntas

## ✅ Próximos Passos

Com os Services, DTOs, Controllers e Rotas prontos, falta apenas:
- **BLOCO 6**: Views Blade + JavaScript
  - Formulários de login/register
  - Dashboard
  - CRUD de cartões, categorias, transações
  - Visualização de faturas
  - JavaScript para interações (ex: mostrar campos de cartão ao selecionar CREDIT)
