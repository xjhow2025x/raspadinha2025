# 🎯 Raspadinha - Online Scratch Card Platform

A modern, scalable online scratch card gaming platform built with PHP, deployed on Vercel, and powered by Supabase.

## 🚀 Quick Deploy

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/yourusername/raspadinha&env=SUPABASE_URL,SUPABASE_ANON_KEY,DATABASE_URL&envDescription=Required%20environment%20variables&envLink=https://github.com/yourusername/raspadinha#environment-variables)

## 🏗️ Tech Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: Supabase (PostgreSQL)
- **Frontend**: Tailwind CSS + Vanilla JavaScript
- **Deployment**: Vercel
- **Payments**: Bulls Pay, Axis Banking

## 📋 Prerequisites

- PHP 7.4 or higher
- Composer
- Node.js (for Vercel CLI)
- Supabase account
- Vercel account

## 🔧 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/raspadinha.git
cd raspadinha
```

### 2. Install Dependencies

Use the provided setup scripts for your OS:

```bash
# Unix/macOS/Linux
chmod +x scripts/install.sh
./scripts/install.sh
```

```powershell
# Windows (PowerShell)
powershell -ExecutionPolicy Bypass -File .\scripts\install.ps1
```

### 3. Setup Supabase Database

1. Create a new project in [Supabase](https://supabase.com)
2. Go to the SQL Editor in your Supabase dashboard
3. Copy and paste the contents of `database/supabase_schema.sql`
4. Run the SQL to create all tables and initial data

### 4. Configure Environment Variables

```bash
cp config/.env.example config/.env
```

Edit `config/.env` with your actual values:

```env
# Supabase Configuration
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=your-anon-key-here
DATABASE_URL=postgresql://postgres:your-password@db.your-project-id.supabase.co:5432/postgres

# Payment Gateways
BULLSPAY_API_KEY=your-bullspay-api-key
AXISBANKING_API_KEY=your-axisbanking-api-key
```

### 5. Deploy to Vercel

```bash
# Install Vercel CLI
npm i -g vercel

# Login to Vercel
vercel login

# Deploy
vercel --prod
```

## 🌐 Environment Variables

Set these in your Vercel dashboard or `config/.env` file:

| Variable | Description | Required |
|----------|-------------|----------|
| `SUPABASE_URL` | Your Supabase project URL | ✅ |
| `SUPABASE_ANON_KEY` | Supabase anonymous key | ✅ |
| `DATABASE_URL` | PostgreSQL connection string | ✅ |
| `BULLSPAY_API_KEY` | Bulls Pay API key | ⚠️ |
| `AXISBANKING_API_KEY` | Axis Banking API key | ⚠️ |
| `APP_ENV` | Environment (development/production) | ✅ |

## 🎮 Features

### Core Functionality
- ✅ User registration and authentication
- ✅ Scratch card game mechanics (3x3 grid)
- ✅ Real-time balance management
- ✅ Probability-based prize distribution
- ✅ Payment processing integration
- ✅ Admin panel for game management

### Game Mechanics
- **Win Condition**: 3 matching symbols
- **Configurable Odds**: 8% win rate (adjustable)
- **Influencer Boost**: Enhanced odds for special users
- **Interactive Scratching**: Canvas-based UI with touch support

### Admin Features
- 🎯 Scratch card management (CRUD)
- 🏆 Prize configuration with probabilities
- 📊 Category management
- 👥 User management
- 💰 Transaction monitoring
- 📈 Analytics dashboard

## 🔒 Security Features

- Row Level Security (RLS) enabled on all tables
- Environment-based configuration
- SQL injection protection via PDO
- Input sanitization
- Session management
- HTTPS enforcement on production

## 📱 Mobile Responsive

- Touch-optimized scratch interface
- Responsive grid layouts
- Mobile-first design approach
- Progressive Web App ready

## 🛠️ Development

### Local Development

```bash
# Start local development server (from repo root)
npx vercel dev
```

## 📁 Project Structure

```
.
├─ backend/         # PHP application (entrypoint: backend/index.php)
│  ├─ api/          # PHP API endpoints
│  ├─ inc/          # Shared includes (uses backend/conexao.php)
│  ├─ includes/     # Legacy includes (UI snippets)
│  ├─ config/       # PHP database bootstrap (loads config/.env)
│  ├─ conexao.php   # Shim that includes config/database.php
│  └─ index.php     # Main router/controller
├─ config/          # Environment configuration files
│  ├─ .env.example
│  └─ .env          # (not committed) – used locally and on Vercel
├─ database/        # Supabase SQL schema and seed
│  ├─ supabase_schema.sql
│  └─ raspadinha corrigida.sql
├─ public/          # Static assets served directly
│  └─ assets/
├─ frontend/        # (optional) JS tooling, Vercel CLI scripts
├─ scripts/         # Setup scripts
│  ├─ install.sh
│  └─ install.ps1
├─ composer.json    # PHP dependencies (root so Vercel can detect)
├─ composer.lock
└─ vercel.json      # Vercel configuration (routes/backend root)
```

### Database Migrations

When you need to update the database schema:

1. Modify `database/supabase_schema.sql`
2. Run the new SQL in Supabase SQL Editor
3. Test thoroughly before deploying

### File Upload Configuration

For production, configure file uploads:

```php
// In production, files should be uploaded to external storage
// Update upload paths in backoffice files accordingly
```

## 📊 Database Schema

The platform uses the following main tables:

- `usuarios` - User accounts and profiles
- `raspadinhas` - Scratch card definitions
- `raspadinha_premios` - Prize configurations
- `orders` - Game purchase records
- `transacoes` - Financial transactions
- `categorias` - Game categories
- `config` - Site configuration

## 🚨 Important Notes

### Legal Compliance
This platform involves real money gambling. Ensure you:
- Obtain proper gaming licenses
- Comply with local gambling regulations
- Implement responsible gaming features
- Follow data protection laws

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Configure proper error logging
- [ ] Set up monitoring and alerts
- [ ] Implement backup strategies
- [ ] Configure CDN for static assets
- [ ] Set up SSL certificates
- [ ] Configure rate limiting

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the code comments

## 🔄 Migration from Legacy

If migrating from the original MySQL setup:

1. Export your existing data
2. Transform data format (MySQL → PostgreSQL)
3. Import into Supabase
4. Update file paths and configurations
5. Test all functionality

---

**⚠️ Disclaimer**: This software is for educational purposes. Ensure compliance with local laws and regulations before deploying for commercial use.
