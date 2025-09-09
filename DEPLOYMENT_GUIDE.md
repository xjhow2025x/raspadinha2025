# 🚀 Guia de Deploy - Raspadinha para GitHub + Vercel + Supabase

## 📋 Passo a Passo Completo

### 1. Preparação do Supabase

#### 1.1 Criar Projeto no Supabase
1. Acesse [supabase.com](https://supabase.com)
2. Clique em "Start your project"
3. Crie uma nova organização ou use existente
4. Clique em "New Project"
5. Escolha um nome e senha forte
6. Aguarde a criação (2-3 minutos)

#### 1.2 Configurar Banco de Dados
1. No dashboard do Supabase, vá para "SQL Editor"
2. Clique em "New Query"
3. Copie todo o conteúdo do arquivo `database/supabase_schema.sql`
4. Cole no editor e clique em "Run"
5. Verifique se todas as tabelas foram criadas em "Table Editor"

#### 1.3 Obter Credenciais
1. Vá para "Settings" > "API"
2. Copie:
   - Project URL
   - anon public key
   - service_role key (para admin)
3. Vá para "Settings" > "Database"
4. Copie a Connection String (URI)

### 2. Preparação do GitHub

#### 2.1 Criar Repositório
```bash
# No terminal, dentro da pasta do projeto
git init
git add .
git commit -m "Initial commit: Raspadinha platform"

# Criar repositório no GitHub (via web)
# Depois conectar:
git remote add origin https://github.com/SEU_USUARIO/raspadinha.git
git branch -M main
git push -u origin main
```

#### 2.2 Configurar .env
1. Copie `.env.example` para `.env`
2. Preencha com suas credenciais do Supabase:

```env
SUPABASE_URL=https://seu-projeto-id.supabase.co
SUPABASE_ANON_KEY=sua-chave-anon-aqui
DATABASE_URL=postgresql://postgres:sua-senha@db.seu-projeto-id.supabase.co:5432/postgres
```

### 3. Deploy no Vercel

#### 3.1 Conectar GitHub ao Vercel
1. Acesse [vercel.com](https://vercel.com)
2. Faça login com GitHub
3. Clique em "New Project"
4. Selecione seu repositório `raspadinha`
5. Clique em "Import"

#### 3.2 Configurar Variáveis de Ambiente
No dashboard do Vercel, vá para "Settings" > "Environment Variables" e adicione:

```
SUPABASE_URL = https://seu-projeto-id.supabase.co
SUPABASE_ANON_KEY = sua-chave-anon
DATABASE_URL = sua-connection-string-completa
APP_ENV = production
```

#### 3.3 Deploy
1. Clique em "Deploy"
2. Aguarde o build (2-5 minutos)
3. Acesse a URL gerada pelo Vercel

### 4. Configurações Pós-Deploy

#### 4.1 Testar Funcionalidades
- [ ] Página inicial carrega
- [ ] Cadastro de usuário funciona
- [ ] Login funciona
- [ ] Visualização de raspadinhas
- [ ] Admin panel (se aplicável)

#### 4.2 Configurar Domínio (Opcional)
1. No Vercel, vá para "Settings" > "Domains"
2. Adicione seu domínio personalizado
3. Configure DNS conforme instruções

#### 4.3 Configurar Uploads
Para produção, considere usar:
- Supabase Storage para imagens
- Cloudinary para otimização
- AWS S3 para arquivos grandes

### 5. Monitoramento e Manutenção

#### 5.1 Logs
- Vercel: Dashboard > Functions > View Function Logs
- Supabase: Dashboard > Logs

#### 5.2 Backups
```sql
-- No Supabase SQL Editor, para backup:
-- Vá para Settings > Database > Connection Pooling
-- Use pg_dump para backups regulares
```

#### 5.3 Atualizações
```bash
# Para atualizar o projeto:
git add .
git commit -m "Descrição da atualização"
git push origin main
# Vercel fará deploy automático
```

## 🔧 Troubleshooting

### Problemas Comuns

#### Erro de Conexão com Banco
```
Solução: Verifique se DATABASE_URL está correto
Formato: postgresql://postgres:senha@host:5432/postgres
```

#### Erro 500 no Vercel
```
1. Verifique logs no dashboard Vercel
2. Confirme variáveis de ambiente
3. Teste localmente com `vercel dev`
```

#### Uploads não funcionam
```
1. Crie diretórios com .gitkeep
2. Configure permissões no Supabase
3. Considere usar Supabase Storage
```

### Comandos Úteis

```bash
# Desenvolvimento local
vercel dev

# Deploy manual
vercel --prod

# Ver logs
vercel logs

# Listar projetos
vercel ls
```

## 📊 Checklist Final

### Antes do Launch
- [ ] Banco configurado e populado
- [ ] Todas as variáveis de ambiente definidas
- [ ] Testes de funcionalidade passando
- [ ] SSL/HTTPS funcionando
- [ ] Domínio configurado (se aplicável)
- [ ] Backups configurados
- [ ] Monitoramento ativo

### Segurança
- [ ] Senhas fortes no Supabase
- [ ] RLS (Row Level Security) ativado
- [ ] Variáveis sensíveis não expostas
- [ ] CORS configurado adequadamente
- [ ] Rate limiting considerado

### Performance
- [ ] Índices de banco otimizados
- [ ] Imagens otimizadas
- [ ] CDN configurado (se necessário)
- [ ] Cache configurado

## 🆘 Suporte

Se encontrar problemas:

1. **Verifique os logs** primeiro
2. **Consulte documentação**:
   - [Vercel Docs](https://vercel.com/docs)
   - [Supabase Docs](https://supabase.com/docs)
3. **Teste localmente** com `vercel dev`
4. **Abra issue** no GitHub se necessário

---

**🎉 Parabéns!** Seu projeto Raspadinha está agora rodando na nuvem com a stack moderna GitHub + Vercel + Supabase!
