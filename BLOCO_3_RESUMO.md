# BLOCO 3 - Seeders - Resumo da Implementação

## ✅ Seeders Criados

### 1. UserSeeder
**Arquivo:** `database/seeders/UserSeeder.php`

Cria um usuário demo para testes:
- **Email:** `demo@finance.com`
- **Senha:** `password123`
- **Nome:** `Demo User`
- Email verificado automaticamente

### 2. CardSeeder
**Arquivo:** `database/seeders/CardSeeder.php`

Cria 2 cartões de crédito para o usuário demo:

#### Cartão 1: Nubank
- **Nome:** Nubank
- **Bandeira:** MASTERCARD
- **Últimos 4 dígitos:** 1234
- **Limite:** R$ 5.000,00
- **Dia de fechamento:** 10
- **Dia de vencimento:** 17
- **Status:** Ativo

#### Cartão 2: Itaú
- **Nome:** Itaú
- **Bandeira:** VISA
- **Últimos 4 dígitos:** 5678
- **Limite:** R$ 8.000,00
- **Dia de fechamento:** 5
- **Dia de vencimento:** 12
- **Status:** Ativo

### 3. CategorySeeder
**Arquivo:** `database/seeders/CategorySeeder.php`

Cria categorias de entrada e saída:

#### Categorias de Entrada (INCOME):
1. **Salary** - #10b981 (verde)
2. **Freelance** - #3b82f6 (azul)
3. **Investment** - #8b5cf6 (roxo)
4. **Bonus** - #06b6d4 (ciano)

#### Categorias de Saída (EXPENSE):
1. **Food** - #ef4444 (vermelho)
2. **Transport** - #f59e0b (laranja)
3. **Shopping** - #ec4899 (rosa)
4. **Bills** - #6366f1 (índigo)
5. **Entertainment** - #14b8a6 (turquesa)
6. **Health** - #f97316 (laranja escuro)
7. **Education** - #06b6d4 (ciano)

### 4. TransactionSeeder
**Arquivo:** `database/seeders/TransactionSeeder.php`

Cria transações variadas incluindo parceladas:

#### Entradas (INCOME):
1. **Salary** - R$ 5.000,00 (início do mês) - Paga
2. **Freelance** - R$ 1.200,00 (5 dias atrás) - Paga

#### Saídas - Pagamento à Vista:
1. **Food (PIX)** - R$ 85,50 (2 dias atrás) - Paga
2. **Transport (CASH)** - R$ 45,00 (1 dia atrás) - Paga
3. **Bills (DEBIT)** - R$ 350,00 (3 dias atrás) - Paga

#### Saídas - Cartão de Crédito:

**Nubank:**
1. **Shopping** - R$ 600,00 em 3x de R$ 200,00
   - Parcela 1/3: Paga (10 dias atrás)
   - Parcela 2/3: Não paga (próximo mês)
   - Parcela 3/3: Não paga (2 meses à frente)

2. **Entertainment** - R$ 150,00 à vista (7 dias atrás) - Não paga

**Itaú:**
1. **Health** - R$ 1.200,00 em 6x de R$ 200,00
   - Parcelas 1-2/6: Pagas (15 dias atrás)
   - Parcelas 3-6/6: Não pagas (próximos meses)

2. **Education** - R$ 2.400,00 em 12x de R$ 200,00
   - Todas as 12 parcelas: Não pagas (20 dias atrás + próximos 11 meses)

### 5. DatabaseSeeder
**Arquivo:** `database/seeders/DatabaseSeeder.php`

Atualizado para chamar todos os seeders na ordem correta:
1. UserSeeder
2. CardSeeder
3. CategorySeeder
4. TransactionSeeder

## 🚀 Como Executar

### Executar todas as migrations e seeders:
```bash
docker-compose exec -T app php artisan migrate:fresh --seed
```

### Executar apenas os seeders (após migrations):
```bash
docker-compose exec -T app php artisan db:seed
```

### Executar um seeder específico:
```bash
docker-compose exec -T app php artisan db:seed --class=UserSeeder
docker-compose exec -T app php artisan db:seed --class=CardSeeder
docker-compose exec -T app php artisan db:seed --class=CategorySeeder
docker-compose exec -T app php artisan db:seed --class=TransactionSeeder
```

## 📊 Dados Criados

### Resumo:
- **1 usuário** (demo@finance.com)
- **2 cartões** (Nubank e Itaú)
- **11 categorias** (4 entradas + 7 saídas)
- **22 transações**:
  - 2 entradas
  - 3 saídas à vista (PIX, CASH, DEBIT)
  - 17 saídas no cartão (incluindo parceladas)

### Parcelas Criadas:
- **3 parcelas** de uma compra (Shopping - Nubank)
- **6 parcelas** de uma compra (Health - Itaú)
- **12 parcelas** de uma compra (Education - Itaú)
- **1 transação** à vista no cartão (Entertainment - Nubank)

## 🔑 Credenciais de Acesso

**Email:** `demo@finance.com`  
**Senha:** `password123`

## 📝 Observações

1. **Ordem de Execução:** Os seeders verificam se as dependências existem antes de criar dados (ex: CardSeeder verifica se User existe).

2. **UUID para Parcelas:** As transações parceladas usam `group_uuid` para agrupar todas as parcelas da mesma compra.

3. **Datas:** As transações são criadas com datas relativas ao momento atual (usando `now()->subDays()` e `now()->addMonths()`).

4. **Status de Pagamento:** Algumas parcelas estão marcadas como pagas, outras não, para simular um cenário realista.

5. **Métodos de Pagamento:** Inclui exemplos de todos os métodos: CASH, PIX, DEBIT, CREDIT.

## ✅ Próximos Passos

Com os seeders prontos, você pode:
1. Executar as migrations e seeders para popular o banco
2. Testar os relacionamentos entre os models
3. Verificar se os dados estão sendo criados corretamente
4. Prosseguir para o BLOCO 4 (Services + DTOs)
