# 📡 API Documentation - Raspadinha Platform

## Visão Geral

A API da plataforma Raspadinha fornece endpoints para gerenciar usuários, jogos, transações e administração. Todos os endpoints retornam dados em formato JSON.

## 🔐 Autenticação

A maioria dos endpoints requer autenticação via sessão PHP. O usuário deve estar logado para acessar recursos protegidos.

```php
// Verificação de autenticação
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}
```

## 📋 Formato de Resposta Padrão

### Sucesso
```json
{
    "success": true,
    "data": {},
    "message": "Operação realizada com sucesso"
}
```

### Erro
```json
{
    "success": false,
    "error": "Descrição do erro",
    "code": 400
}
```

## 🎮 Endpoints de Jogos

### Comprar Raspadinha
**POST** `/raspadinhas/buy.php`

Compra uma raspadinha e gera a grade de prêmios.

**Parâmetros:**
```json
{
    "raspadinha_id": 1
}
```

**Resposta:**
```json
{
    "success": true,
    "order_id": 123,
    "grid": [1, 2, 3, 4, 5, 6, 7, 8, 9],
    "saldo_novo": 45.50
}
```

**Códigos de Status:**
- `200` - Compra realizada com sucesso
- `400` - Parâmetros inválidos
- `403` - Saldo insuficiente
- `404` - Raspadinha não encontrada

### Finalizar Jogo
**POST** `/raspadinhas/finish.php`

Finaliza um jogo e determina se o usuário ganhou.

**Parâmetros:**
```json
{
    "order_id": 123
}
```

**Resposta:**
```json
{
    "success": true,
    "ganhou": true,
    "premio": {
        "nome": "R$ 50,00",
        "valor": 50.00,
        "icone": "/assets/img/icons/money.png"
    },
    "saldo_novo": 95.50
}
```

### Visualizar Raspadinha
**GET** `/raspadinhas/show.php?id={id}`

Exibe detalhes de uma raspadinha específica.

**Parâmetros de URL:**
- `id` - ID da raspadinha

**Resposta:** Página HTML com detalhes da raspadinha

## 💰 Endpoints de Pagamento

### Processar Pagamento
**POST** `/api/payment.php`

Processa um depósito via gateway de pagamento.

**Parâmetros:**
```json
{
    "valor": 100.00,
    "gateway": "bullspay",
    "metodo": "pix"
}
```

**Resposta:**
```json
{
    "success": true,
    "transaction_id": "txn_123456",
    "payment_url": "https://gateway.com/pay/123456",
    "qr_code": "data:image/png;base64,..."
}
```

### Solicitar Saque
**POST** `/api/withdraw.php`

Solicita um saque para conta do usuário.

**Parâmetros:**
```json
{
    "valor": 50.00,
    "chave_pix": "usuario@email.com"
}
```

**Resposta:**
```json
{
    "success": true,
    "transaction_id": "withdraw_123",
    "status": "pendente",
    "taxa": 2.50,
    "valor_liquido": 47.50
}
```

### Verificar Status da Taxa
**POST** `/api/check_taxa_status.php`

Verifica o status de uma taxa de saque.

**Parâmetros:**
```json
{
    "transaction_id": "withdraw_123"
}
```

**Resposta:**
```json
{
    "success": true,
    "status": "aprovado",
    "valor_saque": 50.00,
    "valor_taxa": 2.50,
    "percentual_taxa": 5.0
}
```

## 👥 Endpoints de Usuário

### Login
**POST** `/login/index.php`

Autentica um usuário no sistema.

**Parâmetros:**
```json
{
    "email": "usuario@email.com",
    "senha": "senha123"
}
```

**Resposta:**
```json
{
    "success": true,
    "user": {
        "id": 1,
        "nome": "João Silva",
        "email": "usuario@email.com",
        "saldo": 100.50,
        "admin": false
    }
}
```

### Registro
**POST** `/cadastro/index.php`

Registra um novo usuário.

**Parâmetros:**
```json
{
    "nome": "João Silva",
    "email": "usuario@email.com",
    "senha": "senha123",
    "telefone": "(11) 99999-9999",
    "cpf": "123.456.789-00"
}
```

### Logout
**POST** `/logout/logout.php`

Encerra a sessão do usuário.

**Resposta:**
```json
{
    "success": true,
    "message": "Logout realizado com sucesso"
}
```

## 📊 Endpoints de Dados

### Listar Raspadinhas
**GET** `/api/all_prizes.php`

Lista todas as raspadinhas disponíveis.

**Parâmetros de Query:**
- `categoria` - Filtrar por categoria
- `ordem` - Ordenação (destaque, maior_valor, menor_valor, mais_recente)

**Resposta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nome": "Raspadinha Dourada",
            "valor": 10.00,
            "maior_premio": 1000.00,
            "banner": "/assets/img/banners/dourada.jpg",
            "categoria": "Especiais"
        }
    ]
}
```

### Obter Configurações
**GET** `/api/get_config.php`

Retorna configurações do site.

**Resposta:**
```json
{
    "success": true,
    "config": {
        "nome_site": "Raspadinha",
        "deposito_min": 10.00,
        "saque_min": 50.00,
        "logo": "/assets/img/logo.png"
    }
}
```

### Consultar PIX
**POST** `/api/consult_pix.php`

Consulta informações de uma chave PIX.

**Parâmetros:**
```json
{
    "chave_pix": "usuario@email.com"
}
```

**Resposta:**
```json
{
    "success": true,
    "titular": "João Silva",
    "banco": "Banco do Brasil",
    "tipo_chave": "email"
}
```

## 📈 Endpoints de Analytics

### Atualizar Visualizações de Stories
**POST** `/api/update_story_views.php`

Incrementa contador de visualizações de um story.

**Parâmetros:**
```json
{
    "story_id": 1
}
```

**Resposta:**
```json
{
    "success": true,
    "views": 156
}
```

## 🔧 Endpoints Administrativos

### Gerenciar Raspadinhas
**POST** `/backoffice/cartelas.php`

CRUD completo para raspadinhas (apenas administradores).

**Parâmetros para Criar:**
```json
{
    "acao": "criar",
    "nome": "Nova Raspadinha",
    "descricao": "Descrição da raspadinha",
    "valor": 15.00,
    "categoria_id": 1,
    "destaque": true
}
```

### Gerenciar Usuários
**POST** `/backoffice/usuarios.php`

Gerenciamento de usuários (apenas administradores).

**Parâmetros:**
```json
{
    "acao": "editar",
    "user_id": 1,
    "saldo": 100.00,
    "admin": false,
    "influencer": true
}
```

## 🚨 Códigos de Erro

| Código | Descrição |
|--------|-----------|
| `400` | Requisição inválida |
| `401` | Não autorizado |
| `403` | Acesso negado |
| `404` | Recurso não encontrado |
| `405` | Método não permitido |
| `500` | Erro interno do servidor |

## 📝 Exemplos de Uso

### JavaScript/Fetch
```javascript
// Comprar raspadinha
const response = await fetch('/raspadinhas/buy.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        raspadinha_id: 1
    })
});

const data = await response.json();
if (data.success) {
    console.log('Compra realizada:', data.order_id);
}
```

### PHP/cURL
```php
// Processar pagamento
$data = [
    'valor' => 100.00,
    'gateway' => 'bullspay',
    'metodo' => 'pix'
];

$ch = curl_init('/api/payment.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$result = json_decode($response, true);
```

## 🔒 Segurança

### Headers Recomendados
```php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
```

### Validação de Entrada
```php
// Sanitizar dados de entrada
$valor = filter_var($_POST['valor'], FILTER_VALIDATE_FLOAT);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
```

### Rate Limiting
Implementar rate limiting para prevenir abuso:
- Máximo 100 requisições por minuto por IP
- Máximo 10 tentativas de login por hora
- Máximo 5 compras por minuto por usuário

## 📚 Recursos Adicionais

- [Documentação do Supabase](https://supabase.com/docs)
- [Documentação do Vercel](https://vercel.com/docs)
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
