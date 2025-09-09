# ⚙️ Guia de Configuração - Raspadinha Platform

## 🎯 Visão Geral

Este guia detalha todas as configurações necessárias para personalizar e otimizar a plataforma Raspadinha para seu ambiente específico.

## 🌍 Variáveis de Ambiente

### Arquivo .env

Todas as configurações sensíveis são gerenciadas através de variáveis de ambiente:

```env
# ======================
# CONFIGURAÇÕES BÁSICAS
# ======================
APP_ENV=production
APP_DEBUG=false
SITE_URL=https://seu-dominio.com
SITE_NAME=Raspadinha

# ======================
# SUPABASE
# ======================
SUPABASE_URL=https://seu-projeto-id.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# ======================
# BANCO DE DADOS
# ======================
DATABASE_URL=postgresql://postgres:senha@db.projeto-id.supabase.co:5432/postgres
DB_HOST=db.seu-projeto-id.supabase.co
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres
DB_PASSWORD=sua-senha-forte

# ======================
# GATEWAYS DE PAGAMENTO
# ======================
BULLSPAY_API_KEY=sua-chave-bullspay
BULLSPAY_SECRET=seu-secret-bullspay
AXISBANKING_API_KEY=sua-chave-axisbanking
AXISBANKING_SECRET=seu-secret-axisbanking

# ======================
# SEGURANÇA
# ======================
SESSION_SECRET=chave-secreta-para-sessoes-muito-forte
ENCRYPTION_KEY=chave-de-criptografia-32-caracteres

# ======================
# UPLOADS E STORAGE
# ======================
UPLOAD_PATH=/tmp/uploads
SUPABASE_STORAGE_BUCKET=raspadinha-uploads
MAX_UPLOAD_SIZE=5242880

# ======================
# EMAIL (OPCIONAL)
# ======================
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu-email@gmail.com
SMTP_PASS=sua-senha-app
SMTP_FROM_NAME=Raspadinha Platform
```

## 🎮 Configurações de Jogo

### Probabilidades e Odds

Edite `raspadinhas/buy.php` para ajustar:

```php
// Taxa de vitória para usuários normais (8% = 0.08)
$desiredWinRate = 0.08;

// Boost para influenciadores
if ($isInfluencer) {
    foreach ($premiosBrutos as &$p) {
        if ($p['valor'] > 50) {
            $p['probabilidade'] += 40; // +40% de chance
        }
    }
}
```

### Configurações de Prêmios

No painel admin ou diretamente no banco:

```sql
-- Exemplo: Configurar probabilidades
UPDATE raspadinha_premios 
SET probabilidade = 15.0 
WHERE valor >= 100;

UPDATE raspadinha_premios 
SET probabilidade = 5.0 
WHERE valor >= 1000;
```

## 💰 Configurações Financeiras

### Limites e Taxas

Configurar na tabela `config`:

```sql
UPDATE config SET 
    deposito_min = 10.00,
    saque_min = 50.00,
    cpa_padrao = 10.00;
```

### Taxas de Saque

Editar `api/payment_taxa_saque.php`:

```php
// Taxa percentual para saques
$taxaPercentual = 5.0; // 5%

// Taxa mínima
$taxaMinima = 2.50;

// Taxa máxima
$taxaMaxima = 50.00;
```

## 🎨 Personalização Visual

### Cores e Tema

Editar variáveis CSS em `assets/style/globalStyles.css`:

```css
:root {
  --primary-color: #00FF88;    /* Verde principal */
  --secondary-color: #00FF66;  /* Verde secundário */
  --tertiary-color: #df2dbb;   /* Rosa accent */
  --bg-color: #0D1F0D;         /* Fundo escuro */
  --text-color: #ffffff;       /* Texto principal */
}
```

### Logo e Branding

1. Substituir arquivos em `assets/img/`:
   - `logo.png` - Logo principal
   - `favicon.ico` - Ícone do site
   - `og-image.png` - Imagem para redes sociais

2. Atualizar configuração no banco:
```sql
UPDATE config SET 
    nome_site = 'Seu Nome',
    logo = '/assets/img/seu-logo.png';
```

## 🔐 Configurações de Segurança

### Row Level Security (RLS)

Configurar políticas no Supabase:

```sql
-- Política para usuários verem apenas seus dados
CREATE POLICY "users_own_data" ON orders
    FOR ALL USING (user_id = auth.uid());

-- Política para admins verem tudo
CREATE POLICY "admin_all_access" ON orders
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM usuarios 
            WHERE id = auth.uid() AND admin = true
        )
    );
```

### Configurações de Sessão

Em `config/database.php`:

```php
// Configurações de sessão segura
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => 3600, // 1 hora
    'path' => '/',
    'domain' => $_ENV['SITE_DOMAIN'] ?? '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

## 📧 Configurações de Email

### SMTP

Para notificações por email, configure:

```php
// config/email.php
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $_ENV['SMTP_HOST'];
$mail->SMTPAuth = true;
$mail->Username = $_ENV['SMTP_USER'];
$mail->Password = $_ENV['SMTP_PASS'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = $_ENV['SMTP_PORT'];
```

### Templates de Email

Criar em `templates/email/`:

```html
<!-- templates/email/welcome.html -->
<!DOCTYPE html>
<html>
<head>
    <title>Bem-vindo ao {{SITE_NAME}}</title>
</head>
<body>
    <h1>Olá {{USER_NAME}}!</h1>
    <p>Bem-vindo à plataforma de raspadinhas!</p>
</body>
</html>
```

## 🚀 Configurações de Performance

### Cache

Implementar cache com Redis (opcional):

```php
// config/cache.php
$redis = new Redis();
$redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);

// Cache de configurações
$config = $redis->get('site_config');
if (!$config) {
    $config = $pdo->query("SELECT * FROM config")->fetch();
    $redis->setex('site_config', 3600, serialize($config));
}
```

### Otimização de Imagens

Configurar processamento automático:

```php
// config/image.php
function optimizeImage($source, $destination, $quality = 85) {
    $info = getimagesize($source);
    
    switch ($info['mime']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagepng($image, $destination, 9);
            break;
    }
    
    imagedestroy($image);
}
```

## 📊 Configurações de Analytics

### Google Analytics

Adicionar em `inc/header.php`:

```html
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= $_ENV['GA_MEASUREMENT_ID'] ?>');
</script>
```

### Métricas Customizadas

```javascript
// Rastrear compras de raspadinhas
function trackPurchase(raspadinhaId, valor) {
    gtag('event', 'purchase', {
        'transaction_id': Date.now(),
        'value': valor,
        'currency': 'BRL',
        'items': [{
            'item_id': raspadinhaId,
            'item_name': 'Raspadinha',
            'category': 'Games',
            'price': valor
        }]
    });
}
```

## 🔧 Configurações do Vercel

### vercel.json Avançado

```json
{
  "version": 2,
  "functions": {
    "api/**/*.php": {
      "runtime": "vercel-php@0.6.0",
      "maxDuration": 30
    }
  },
  "routes": [
    {
      "src": "/assets/(.*)",
      "headers": {
        "Cache-Control": "public, max-age=31536000, immutable"
      },
      "dest": "/assets/$1"
    },
    {
      "src": "/(.*\\.php)$",
      "dest": "/$1"
    }
  ],
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        {
          "key": "X-Content-Type-Options",
          "value": "nosniff"
        },
        {
          "key": "X-Frame-Options",
          "value": "DENY"
        },
        {
          "key": "X-XSS-Protection",
          "value": "1; mode=block"
        }
      ]
    }
  ]
}
```

## 🌐 Configurações de CDN

### Cloudflare (Recomendado)

1. Adicionar site ao Cloudflare
2. Configurar DNS
3. Ativar otimizações:
   - Auto Minify (CSS, JS, HTML)
   - Brotli Compression
   - Image Optimization

### Configurações de Cache

```javascript
// Service Worker para cache offline
self.addEventListener('fetch', event => {
  if (event.request.destination === 'image') {
    event.respondWith(
      caches.match(event.request).then(response => {
        return response || fetch(event.request);
      })
    );
  }
});
```

## 📱 Configurações PWA

### Manifest

Criar `manifest.json`:

```json
{
  "name": "Raspadinha Platform",
  "short_name": "Raspadinha",
  "description": "Plataforma de raspadinhas online",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#0D1F0D",
  "theme_color": "#00FF88",
  "icons": [
    {
      "src": "/assets/img/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

## 🔍 Configurações de SEO

### Meta Tags

Em `inc/meta.php`:

```html
<meta name="description" content="<?= $pageDescription ?? 'Plataforma de raspadinhas online' ?>">
<meta name="keywords" content="raspadinha, jogos, prêmios, sorte">
<meta property="og:title" content="<?= $pageTitle ?? $nomeSite ?>">
<meta property="og:description" content="<?= $pageDescription ?? 'Jogue e ganhe prêmios incríveis' ?>">
<meta property="og:image" content="<?= $siteUrl ?>/assets/img/og-image.png">
<meta name="twitter:card" content="summary_large_image">
```

### Sitemap

Gerar automaticamente:

```php
// generate-sitemap.php
$urls = [
    '/',
    '/login',
    '/cadastro',
    '/raspadinhas'
];

$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $url) {
    $sitemap .= '<url><loc>' . $siteUrl . $url . '</loc></url>' . "\n";
}

$sitemap .= '</urlset>';
file_put_contents('sitemap.xml', $sitemap);
```

## 🔄 Backup e Restauração

### Backup Automático

Script para backup do Supabase:

```bash
#!/bin/bash
# backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
pg_dump $DATABASE_URL > backup_$DATE.sql
```

### Configurar Cron

```bash
# Backup diário às 2h da manhã
0 2 * * * /path/to/backup.sh
```

## 📋 Checklist de Configuração

### Desenvolvimento
- [ ] .env configurado
- [ ] Banco de dados criado
- [ ] Dependências instaladas
- [ ] Servidor local funcionando

### Produção
- [ ] Variáveis de ambiente no Vercel
- [ ] Domínio configurado
- [ ] SSL ativo
- [ ] Backups configurados
- [ ] Monitoramento ativo
- [ ] Analytics configurado

### Segurança
- [ ] RLS ativado no Supabase
- [ ] Headers de segurança configurados
- [ ] Rate limiting implementado
- [ ] Logs de auditoria ativos

---

**💡 Dica**: Mantenha sempre backups das configurações e teste todas as mudanças em ambiente de desenvolvimento primeiro.
