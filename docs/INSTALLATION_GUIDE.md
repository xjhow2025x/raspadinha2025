# 🚀 Guia de Instalação - Raspadinha Platform

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter:

- **PHP 7.4+** instalado
- **Composer** para gerenciamento de dependências PHP
- **Node.js 16+** e npm
- **Git** para controle de versão
- Conta no **Supabase** (gratuita)
- Conta no **Vercel** (gratuita)
- Conta no **GitHub** (gratuita)

## 🔧 Instalação Local

### 1. Clonar o Repositório

```bash
# Clonar o projeto
git clone https://github.com/yourusername/raspadinha.git
cd raspadinha

# Ou fazer fork e clonar seu fork
git clone https://github.com/SEU_USUARIO/raspadinha.git
cd raspadinha
```

### 2. Instalar Dependências

```bash
# Instalar dependências PHP
composer install

# Instalar dependências Node.js
npm install

# Instalar Vercel CLI globalmente
npm install -g vercel
```

### 3. Configurar Ambiente Local

```bash
# Copiar arquivo de exemplo
cp .env.example .env

# Editar com suas configurações
nano .env  # ou seu editor preferido
```

### 4. Configurar Supabase

#### 4.1 Criar Projeto
1. Acesse [supabase.com](https://supabase.com)
2. Clique em "New Project"
3. Escolha nome e senha forte
4. Aguarde criação (2-3 minutos)

#### 4.2 Configurar Banco
1. Vá para "SQL Editor"
2. Clique em "New Query"
3. Copie conteúdo de `database/supabase_schema.sql`
4. Execute o SQL
5. Verifique tabelas em "Table Editor"

#### 4.3 Obter Credenciais
1. Vá para "Settings" → "API"
2. Copie:
   - Project URL
   - anon public key
   - service_role key

3. Vá para "Settings" → "Database"
4. Copie Connection String

### 5. Configurar .env

Edite o arquivo `.env` com suas credenciais:

```env
# Ambiente
APP_ENV=development
APP_DEBUG=true

# Supabase
SUPABASE_URL=https://seu-projeto-id.supabase.co
SUPABASE_ANON_KEY=sua-chave-anon-aqui
SUPABASE_SERVICE_ROLE_KEY=sua-service-role-key

# Banco de Dados
DATABASE_URL=postgresql://postgres:sua-senha@db.seu-projeto-id.supabase.co:5432/postgres

# Site
SITE_URL=http://localhost:3000
SITE_NAME=Raspadinha Local
```

### 6. Testar Localmente

```bash
# Iniciar servidor de desenvolvimento
vercel dev

# Ou usar PHP built-in server
php -S localhost:8000
```

Acesse `http://localhost:3000` (Vercel) ou `http://localhost:8000` (PHP)

## 🌐 Deploy em Produção

### 1. Preparar Repositório GitHub

```bash
# Adicionar arquivos ao Git
git add .
git commit -m "Setup inicial do projeto"

# Criar repositório no GitHub (via interface web)
# Depois conectar:
git remote add origin https://github.com/SEU_USUARIO/raspadinha.git
git branch -M main
git push -u origin main
```

### 2. Deploy no Vercel

#### 2.1 Via Dashboard
1. Acesse [vercel.com](https://vercel.com)
2. Faça login com GitHub
3. Clique "New Project"
4. Selecione repositório `raspadinha`
5. Configure variáveis de ambiente
6. Clique "Deploy"

#### 2.2 Via CLI
```bash
# Login no Vercel
vercel login

# Deploy
vercel

# Deploy para produção
vercel --prod
```

### 3. Configurar Variáveis de Ambiente no Vercel

No dashboard do Vercel, vá para "Settings" → "Environment Variables":

```
APP_ENV = production
SUPABASE_URL = https://seu-projeto-id.supabase.co
SUPABASE_ANON_KEY = sua-chave-anon
DATABASE_URL = sua-connection-string
BULLSPAY_API_KEY = sua-chave-bullspay
AXISBANKING_API_KEY = sua-chave-axisbanking
```

## 🔧 Configurações Avançadas

### Configurar Domínio Personalizado

1. No Vercel, vá para "Settings" → "Domains"
2. Adicione seu domínio
3. Configure DNS conforme instruções:

```
# Adicionar registros DNS:
CNAME www seu-projeto.vercel.app
A @ 76.76.19.61
```

### Configurar Upload de Arquivos

Para produção, use Supabase Storage:

```php
// config/storage.php
$supabaseStorage = new SupabaseStorage([
    'url' => $_ENV['SUPABASE_URL'],
    'key' => $_ENV['SUPABASE_SERVICE_ROLE_KEY']
]);
```

### Configurar SSL/HTTPS

O Vercel fornece SSL automático. Para domínios personalizados:

1. SSL é configurado automaticamente
2. Redirecionamento HTTP → HTTPS é automático
3. Certificados são renovados automaticamente

## 🔍 Verificação da Instalação

### Checklist Pós-Instalação

- [ ] Página inicial carrega sem erros
- [ ] Banco de dados conecta corretamente
- [ ] Registro de usuário funciona
- [ ] Login/logout funcionam
- [ ] Visualização de raspadinhas
- [ ] Sistema de compra (teste)
- [ ] Painel admin (se aplicável)
- [ ] Uploads de imagem funcionam
- [ ] Notificações aparecem

### Comandos de Teste

```bash
# Testar conexão com banco
php -r "require 'config/database.php'; echo 'Conexão OK!';"

# Verificar dependências
composer check-platform-reqs

# Testar Vercel localmente
vercel dev --debug
```

## 🐛 Solução de Problemas

### Erro de Conexão com Banco

```bash
# Verificar string de conexão
echo $DATABASE_URL

# Testar conexão manual
psql $DATABASE_URL -c "SELECT version();"
```

### Erro 500 no Vercel

1. Verificar logs: `vercel logs`
2. Testar localmente: `vercel dev`
3. Verificar variáveis de ambiente
4. Verificar sintaxe PHP

### Problemas de Upload

```bash
# Verificar permissões
ls -la assets/img/
chmod 755 assets/img/banners/
chmod 755 assets/img/icons/
```

### Problemas de Dependências

```bash
# Limpar cache do Composer
composer clear-cache
composer install --no-cache

# Reinstalar dependências Node
rm -rf node_modules package-lock.json
npm install
```

## 📊 Monitoramento

### Logs de Aplicação

```bash
# Ver logs do Vercel
vercel logs

# Ver logs em tempo real
vercel logs --follow
```

### Métricas do Supabase

1. Dashboard → "Reports"
2. Monitorar:
   - Conexões de banco
   - Uso de API
   - Storage utilizado

### Alertas Recomendados

Configure alertas para:
- Erro 500 > 5% das requisições
- Tempo de resposta > 3 segundos
- Uso de banco > 80%
- Falhas de pagamento

## 🔄 Atualizações

### Atualizar Aplicação

```bash
# Fazer alterações no código
git add .
git commit -m "Descrição da atualização"
git push origin main

# Vercel fará deploy automático
```

### Atualizar Dependências

```bash
# Atualizar dependências PHP
composer update

# Atualizar dependências Node
npm update

# Verificar vulnerabilidades
npm audit
composer audit
```

### Migração de Banco

```sql
-- Executar no SQL Editor do Supabase
-- Exemplo: adicionar nova coluna
ALTER TABLE usuarios ADD COLUMN telefone_verificado BOOLEAN DEFAULT FALSE;
```

## 📚 Recursos Adicionais

- [Documentação Vercel](https://vercel.com/docs)
- [Documentação Supabase](https://supabase.com/docs)
- [PHP Manual](https://www.php.net/manual/)
- [Composer Documentation](https://getcomposer.org/doc/)

## 🆘 Suporte

Se encontrar problemas:

1. Consulte esta documentação
2. Verifique logs de erro
3. Teste em ambiente local
4. Abra issue no GitHub
5. Consulte documentação oficial das ferramentas

---

**🎉 Parabéns!** Sua instalação da Plataforma Raspadinha está completa!
