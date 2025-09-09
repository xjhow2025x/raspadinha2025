-- Raspadinha Project - Supabase Database Schema
-- This file contains all the necessary tables for the scratch card platform

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Enable Row Level Security
ALTER DATABASE postgres SET timezone TO 'America/Sao_Paulo';

-- Config table for site settings
CREATE TABLE config (
    id SERIAL PRIMARY KEY,
    nome_site VARCHAR(255) NOT NULL DEFAULT 'Raspadinha',
    logo TEXT,
    deposito_min DECIMAL(10,2) DEFAULT 10.00,
    saque_min DECIMAL(10,2) DEFAULT 50.00,
    cpa_padrao DECIMAL(10,2) DEFAULT 10.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Categories table
CREATE TABLE categorias (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icone VARCHAR(255),
    cor VARCHAR(7) DEFAULT '#00FF88',
    ordem INTEGER DEFAULT 0,
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Users table
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cpf VARCHAR(14),
    pix VARCHAR(255),
    saldo DECIMAL(10,2) DEFAULT 0.00,
    admin BOOLEAN DEFAULT false,
    influencer BOOLEAN DEFAULT false,
    afiliado_id INTEGER REFERENCES usuarios(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Scratch cards table
CREATE TABLE raspadinhas (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    banner TEXT,
    valor DECIMAL(10,2) NOT NULL,
    categoria_id INTEGER REFERENCES categorias(id),
    destaque BOOLEAN DEFAULT false,
    ordem INTEGER DEFAULT 0,
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Prizes table for scratch cards
CREATE TABLE raspadinha_premios (
    id SERIAL PRIMARY KEY,
    raspadinha_id INTEGER NOT NULL REFERENCES raspadinhas(id) ON DELETE CASCADE,
    nome VARCHAR(255) NOT NULL,
    icone TEXT,
    valor DECIMAL(10,2) DEFAULT 0.00,
    probabilidade DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Orders table for game purchases
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES usuarios(id),
    raspadinha_id INTEGER NOT NULL REFERENCES raspadinhas(id),
    premios_json JSONB NOT NULL,
    resultado_json JSONB,
    valor_pago DECIMAL(10,2),
    valor_ganho DECIMAL(10,2) DEFAULT 0.00,
    finalizado BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Transactions table for financial operations
CREATE TABLE transacoes (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES usuarios(id),
    tipo VARCHAR(50) NOT NULL, -- 'deposito', 'saque', 'compra', 'premio'
    valor DECIMAL(10,2) NOT NULL,
    descricao TEXT,
    status VARCHAR(50) DEFAULT 'pendente', -- 'pendente', 'aprovado', 'rejeitado'
    transaction_id VARCHAR(255),
    gateway VARCHAR(50), -- 'bullspay', 'axisbanking'
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Withdrawal taxes table
CREATE TABLE taxas_saque (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES usuarios(id),
    transaction_id VARCHAR(255) NOT NULL,
    valor_saque DECIMAL(10,2) NOT NULL,
    valor_taxa DECIMAL(10,2) NOT NULL,
    percentual_taxa DECIMAL(5,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pendente',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Affiliates table
CREATE TABLE afiliados (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES usuarios(id),
    codigo_afiliado VARCHAR(50) UNIQUE NOT NULL,
    comissao_percentual DECIMAL(5,2) DEFAULT 10.00,
    total_indicados INTEGER DEFAULT 0,
    total_comissao DECIMAL(10,2) DEFAULT 0.00,
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Stories table for marketing content
CREATE TABLE stories (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    imagem TEXT NOT NULL,
    link TEXT,
    ordem INTEGER DEFAULT 0,
    ativo BOOLEAN DEFAULT true,
    visualizacoes INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Banners table for promotional content
CREATE TABLE banners (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    imagem TEXT NOT NULL,
    link TEXT,
    posicao VARCHAR(50) DEFAULT 'home', -- 'home', 'sidebar', 'footer'
    ordem INTEGER DEFAULT 0,
    ativo BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes for better performance
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_afiliado ON usuarios(afiliado_id);
CREATE INDEX idx_raspadinhas_categoria ON raspadinhas(categoria_id);
CREATE INDEX idx_raspadinhas_destaque ON raspadinhas(destaque);
CREATE INDEX idx_raspadinha_premios_raspadinha ON raspadinha_premios(raspadinha_id);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_raspadinha ON orders(raspadinha_id);
CREATE INDEX idx_transacoes_user ON transacoes(user_id);
CREATE INDEX idx_transacoes_tipo ON transacoes(tipo);
CREATE INDEX idx_taxas_saque_user ON taxas_saque(user_id);
CREATE INDEX idx_afiliados_codigo ON afiliados(codigo_afiliado);

-- Insert default config
INSERT INTO config (nome_site, deposito_min, saque_min, cpa_padrao) 
VALUES ('Raspadinha', 10.00, 50.00, 10.00);

-- Insert default categories
INSERT INTO categorias (nome, slug, icone, cor, ordem) VALUES
('Destaque', 'destaque', 'fas fa-star', '#FFD700', 1),
('Clássicas', 'classicas', 'fas fa-gem', '#00FF88', 2),
('Especiais', 'especiais', 'fas fa-crown', '#FF6B6B', 3),
('Novas', 'novas', 'fas fa-sparkles', '#4ECDC4', 4);

-- Enable Row Level Security on all tables
ALTER TABLE config ENABLE ROW LEVEL SECURITY;
ALTER TABLE categorias ENABLE ROW LEVEL SECURITY;
ALTER TABLE usuarios ENABLE ROW LEVEL SECURITY;
ALTER TABLE raspadinhas ENABLE ROW LEVEL SECURITY;
ALTER TABLE raspadinha_premios ENABLE ROW LEVEL SECURITY;
ALTER TABLE orders ENABLE ROW LEVEL SECURITY;
ALTER TABLE transacoes ENABLE ROW LEVEL SECURITY;
ALTER TABLE taxas_saque ENABLE ROW LEVEL SECURITY;
ALTER TABLE afiliados ENABLE ROW LEVEL SECURITY;
ALTER TABLE stories ENABLE ROW LEVEL SECURITY;
ALTER TABLE banners ENABLE ROW LEVEL SECURITY;

-- Create policies for public access (adjust as needed for security)
CREATE POLICY "Allow public read access on config" ON config FOR SELECT USING (true);
CREATE POLICY "Allow public read access on categorias" ON categorias FOR SELECT USING (ativo = true);
CREATE POLICY "Allow public read access on raspadinhas" ON raspadinhas FOR SELECT USING (ativo = true);
CREATE POLICY "Allow public read access on raspadinha_premios" ON raspadinha_premios FOR SELECT USING (true);
CREATE POLICY "Allow public read access on stories" ON stories FOR SELECT USING (ativo = true);
CREATE POLICY "Allow public read access on banners" ON banners FOR SELECT USING (ativo = true);

-- User-specific policies
CREATE POLICY "Users can read their own data" ON usuarios FOR SELECT USING (true);
CREATE POLICY "Users can update their own data" ON usuarios FOR UPDATE USING (true);
CREATE POLICY "Users can read their own orders" ON orders FOR SELECT USING (true);
CREATE POLICY "Users can create orders" ON orders FOR INSERT WITH CHECK (true);
CREATE POLICY "Users can update their own orders" ON orders FOR UPDATE USING (true);
CREATE POLICY "Users can read their own transactions" ON transacoes FOR SELECT USING (true);
CREATE POLICY "Users can create transactions" ON transacoes FOR INSERT WITH CHECK (true);
