# 🎯 Raspadinha - Plataforma de Raspadinhas Online

Uma plataforma moderna e escalável de jogos de raspadinha online construída com PHP, implantada no Vercel e alimentada pelo Supabase.

![Raspadinha Platform](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Supabase](https://img.shields.io/badge/Supabase-3ECF8E?style=for-the-badge&logo=supabase&logoColor=white)
![Vercel](https://img.shields.io/badge/Vercel-000000?style=for-the-badge&logo=vercel&logoColor=white)

## 🚀 Deploy Rápido

[![Deploy com Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/yourusername/raspadinha)

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [Stack Tecnológica](#️-stack-tecnológica)
- [Funcionalidades](#-funcionalidades)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Deploy](#-deploy)
- [Desenvolvimento](#️-desenvolvimento)
- [API](#-api)
- [Segurança](#-segurança)
- [Licença](#-licença)

## 🎯 Visão Geral

A Plataforma Raspadinha é um sistema completo de jogos de azar online que simula raspadinhas físicas em um ambiente digital. O sistema inclui:

- **Interface Intuitiva**: Experiência de raspagem realista com Canvas HTML5
- **Sistema de Probabilidades**: Algoritmos sofisticados para distribuição de prêmios
- **Painel Administrativo**: Gestão completa de jogos, prêmios e usuários
- **Pagamentos Integrados**: Suporte a múltiplos gateways de pagamento
- **Responsivo**: Otimizado para desktop e mobile

## 🏗️ Stack Tecnológica

### Backend
- **PHP 7.4+** com PDO para operações de banco
- **PostgreSQL** via Supabase para persistência
- **Composer** para gerenciamento de dependências

### Frontend
- **Tailwind CSS** para estilização responsiva
- **JavaScript Vanilla** para interatividade
- **Canvas API** para mecânica de raspagem
- **Font Awesome** para ícones

### Infraestrutura
- **Vercel** para deploy e hosting
- **Supabase** para banco de dados e autenticação
- **GitHub** para controle de versão

### Integrações
- **Bulls Pay** para processamento de pagamentos
- **Axis Banking** para transações bancárias
- **Notiflix** para notificações

## 🎮 Funcionalidades

### 🎯 Sistema de Jogos
- **Grade 3x3**: Sistema de 9 posições com prêmios aleatórios
- **Condição de Vitória**: 3 símbolos iguais para ganhar
- **Raspagem Interativa**: Interface touch com feedback visual
- **Probabilidades Configuráveis**: Taxa de vitória ajustável (padrão 8%)
- **Sistema de Influenciadores**: Odds aumentadas para usuários especiais

### 👥 Gestão de Usuários
- **Registro e Login**: Sistema completo de autenticação
- **Perfis de Usuário**: Informações pessoais e financeiras
- **Sistema de Saldo**: Gerenciamento em tempo real
- **Histórico de Transações**: Rastreamento completo de atividades
- **Níveis de Acesso**: Usuários normais, influenciadores e administradores

### 💰 Sistema Financeiro
- **Depósitos**: Integração com gateways de pagamento
- **Saques**: Processamento automático com taxas configuráveis
- **Transações**: Log completo de todas as operações
- **Comissões**: Sistema de afiliados com percentuais configuráveis

### 🛠️ Painel Administrativo
- **Gestão de Raspadinhas**: CRUD completo com upload de imagens
- **Configuração de Prêmios**: Valores e probabilidades personalizáveis
- **Gestão de Categorias**: Organização por tipos de jogos
- **Monitoramento**: Dashboard com métricas e analytics
- **Gestão de Usuários**: Controle de permissões e status

## 🔧 Instalação

### Pré-requisitos
- PHP 7.4 ou superior
- Composer
- Node.js (para Vercel CLI)
- Conta no Supabase
- Conta no Vercel

### 1. Clonar Repositório
```bash
git clone https://github.com/yourusername/raspadinha.git
cd raspadinha
```

### 2. Instalar Dependências
```bash
composer install
npm install
```

### 3. Configurar Banco de Dados
1. Criar projeto no [Supabase](https://supabase.com)
2. Ir para SQL Editor no dashboard
3. Executar o conteúdo de `database/supabase_schema.sql`
4. Verificar criação das tabelas no Table Editor

### 4. Configurar Variáveis de Ambiente
```bash
cp .env.example .env
```

Editar `.env` com suas credenciais:
```env
SUPABASE_URL=https://seu-projeto-id.supabase.co
SUPABASE_ANON_KEY=sua-chave-anon
DATABASE_URL=postgresql://postgres:senha@db.projeto-id.supabase.co:5432/postgres
```

## ⚙️ Configuração

### Variáveis de Ambiente

| Variável | Descrição | Obrigatório |
|----------|-----------|-------------|
| `SUPABASE_URL` | URL do projeto Supabase | ✅ |
| `SUPABASE_ANON_KEY` | Chave anônima do Supabase | ✅ |
| `DATABASE_URL` | String de conexão PostgreSQL | ✅ |
| `BULLSPAY_API_KEY` | Chave API Bulls Pay | ⚠️ |
| `AXISBANKING_API_KEY` | Chave API Axis Banking | ⚠️ |
| `APP_ENV` | Ambiente (development/production) | ✅ |

### Configuração de Upload
Para produção, configure storage externo:
```php
// Recomendado: Supabase Storage ou AWS S3
$uploadPath = $_ENV['SUPABASE_STORAGE_URL'] ?? '/tmp/uploads';
```

## 🚀 Deploy

### Deploy no Vercel
```bash
# Instalar Vercel CLI
npm i -g vercel

# Login
vercel login

# Deploy
vercel --prod
```

### Configurar no Dashboard Vercel
1. Conectar repositório GitHub
2. Adicionar variáveis de ambiente
3. Configurar domínio personalizado (opcional)

## 🛠️ Desenvolvimento

### Servidor Local
```bash
vercel dev
```

### Estrutura de Arquivos
```
raspadinha/
├── api/                    # Endpoints da API
├── assets/                 # Recursos estáticos
├── backoffice/            # Painel administrativo
├── components/            # Componentes reutilizáveis
├── config/                # Configurações
├── database/              # Schema e migrações
├── docs/                  # Documentação
├── raspadinhas/           # Sistema de jogos
└── vendor/                # Dependências PHP
```

### Padrões de Código
- **PSR-4** para autoloading
- **PDO** para operações de banco
- **Prepared Statements** para segurança
- **Environment Variables** para configurações

## 📡 API

### Endpoints Principais

#### Autenticação
- `POST /api/login.php` - Login de usuário
- `POST /api/register.php` - Registro de usuário
- `POST /api/logout.php` - Logout

#### Jogos
- `POST /raspadinhas/buy.php` - Comprar raspadinha
- `POST /raspadinhas/finish.php` - Finalizar jogo
- `GET /raspadinhas/show.php?id={id}` - Visualizar raspadinha

#### Pagamentos
- `POST /api/payment.php` - Processar pagamento
- `POST /api/withdraw.php` - Solicitar saque
- `GET /api/transactions.php` - Histórico de transações

### Formato de Resposta
```json
{
  "success": true,
  "data": {},
  "message": "Operação realizada com sucesso"
}
```

## 🔒 Segurança

### Medidas Implementadas
- **Row Level Security (RLS)** no Supabase
- **Prepared Statements** para prevenir SQL Injection
- **Sanitização de Entrada** com `htmlspecialchars()`
- **Validação de Sessões** em todas as operações
- **HTTPS** obrigatório em produção
- **Rate Limiting** via Vercel

### Configurações de Segurança
```php
// Configuração PDO segura
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
```

## 📊 Schema do Banco

### Tabelas Principais
- **usuarios** - Contas e perfis de usuário
- **raspadinhas** - Definições dos jogos
- **raspadinha_premios** - Configurações de prêmios
- **orders** - Registros de compras
- **transacoes** - Transações financeiras
- **categorias** - Categorias de jogos
- **config** - Configurações do site

### Relacionamentos
```sql
usuarios (1:N) orders
raspadinhas (1:N) orders
raspadinhas (1:N) raspadinha_premios
categorias (1:N) raspadinhas
```

## 📱 Responsividade

### Breakpoints
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

### Otimizações Mobile
- Interface touch otimizada
- Grids responsivos
- Imagens adaptáveis
- Performance otimizada

## 🧪 Testes

### Testes Manuais
- [ ] Registro e login funcionam
- [ ] Compra de raspadinha
- [ ] Mecânica de raspagem
- [ ] Sistema de pagamentos
- [ ] Painel administrativo

### Testes Automatizados
```bash
# Executar testes (quando implementados)
composer test
```

## 📈 Monitoramento

### Métricas Importantes
- Taxa de conversão por raspadinha
- Tempo médio de sessão
- Valor médio por transação
- Taxa de retenção de usuários

### Logs
- **Vercel**: Dashboard > Functions > Logs
- **Supabase**: Dashboard > Logs
- **Aplicação**: Logs customizados via Monolog

## 🔄 Migração

### De MySQL para Supabase
1. Exportar dados existentes
2. Transformar formato (MySQL → PostgreSQL)
3. Importar para Supabase
4. Atualizar configurações
5. Testar funcionalidades

## 🤝 Contribuição

1. Fork o repositório
2. Criar branch de feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit das mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para branch (`git push origin feature/nova-funcionalidade`)
5. Abrir Pull Request

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## ⚠️ Aviso Legal

Esta plataforma envolve jogos de azar com dinheiro real. Certifique-se de:
- Obter licenças adequadas
- Cumprir regulamentações locais
- Implementar jogo responsável
- Seguir leis de proteção de dados

## 🆘 Suporte

Para suporte e dúvidas:
- Criar issue no GitHub
- Consultar documentação
- Revisar comentários do código

---

**Desenvolvido com ❤️ por Daanrox**
