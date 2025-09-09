# 🛠️ Guia de Desenvolvimento - Raspadinha Platform

## 🎯 Visão Geral do Desenvolvimento

Este guia fornece informações essenciais para desenvolvedores que trabalharão na plataforma Raspadinha, incluindo padrões de código, arquitetura e práticas recomendadas.

## 🏗️ Arquitetura do Sistema

### Estrutura de Diretórios

```
raspadinha/
├── api/                    # Endpoints da API REST
│   ├── payment.php         # Processamento de pagamentos
│   ├── withdraw.php        # Solicitações de saque
│   └── check_taxa_status.php # Verificação de taxas
├── assets/                 # Recursos estáticos
│   ├── img/               # Imagens (banners, ícones)
│   ├── js/                # JavaScript customizado
│   └── style/             # CSS customizado
├── backoffice/            # Painel administrativo
│   ├── cartelas.php       # Gestão de raspadinhas
│   ├── usuarios.php       # Gestão de usuários
│   └── components/        # Componentes do admin
├── components/            # Componentes reutilizáveis
│   ├── header.php         # Cabeçalho padrão
│   ├── footer.php         # Rodapé padrão
│   └── carrossel.php      # Carrossel de banners
├── config/                # Configurações
│   └── database.php       # Conexão com Supabase
├── database/              # Schema e migrações
│   └── supabase_schema.sql # Schema completo
├── docs/                  # Documentação
├── raspadinhas/           # Sistema de jogos
│   ├── show.php           # Interface do jogo
│   ├── buy.php            # Lógica de compra
│   └── finish.php         # Finalização do jogo
└── vendor/                # Dependências PHP
```

### Padrões de Arquitetura

#### MVC Simplificado
```php
// Model (acesso a dados)
class RaspadinhaModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM raspadinhas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Controller (lógica de negócio)
class RaspadinhaController {
    private $model;
    
    public function show($id) {
        $raspadinha = $this->model->getById($id);
        if (!$raspadinha) {
            throw new Exception('Raspadinha não encontrada');
        }
        return $raspadinha;
    }
}
```

## 💻 Padrões de Código

### PHP Standards

#### PSR-4 Autoloading
```php
// composer.json
{
    "autoload": {
        "psr-4": {
            "Raspadinha\\": "src/",
            "Raspadinha\\Models\\": "src/Models/",
            "Raspadinha\\Controllers\\": "src/Controllers/"
        }
    }
}
```

#### Convenções de Nomenclatura
```php
// Classes: PascalCase
class RaspadinhaManager {}

// Métodos e variáveis: camelCase
public function calculateProbability() {}
$userBalance = 100.50;

// Constantes: UPPER_SNAKE_CASE
const MAX_PRIZE_VALUE = 10000.00;

// Arquivos: snake_case.php
// raspadinha_manager.php
```

#### Tratamento de Erros
```php
try {
    $result = $this->processPayment($data);
    
    if (!$result['success']) {
        throw new PaymentException($result['error']);
    }
    
    return $result;
    
} catch (PaymentException $e) {
    error_log('Payment error: ' . $e->getMessage());
    return ['error' => 'Erro no pagamento'];
    
} catch (Exception $e) {
    error_log('Unexpected error: ' . $e->getMessage());
    return ['error' => 'Erro interno'];
}
```

### JavaScript Standards

#### ES6+ Features
```javascript
// Arrow functions
const calculateWinRate = (wins, total) => (wins / total) * 100;

// Destructuring
const { success, data, error } = await response.json();

// Template literals
const message = `Usuário ${userName} ganhou R$ ${prize}`;

// Async/await
async function buyRaspadinha(id) {
    try {
        const response = await fetch('/raspadinhas/buy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ raspadinha_id: id })
        });
        
        return await response.json();
    } catch (error) {
        console.error('Erro na compra:', error);
        throw error;
    }
}
```

#### Canvas API para Raspagem
```javascript
class ScratchCard {
    constructor(canvasId, prizes) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.prizes = prizes;
        this.isScratching = false;
        this.scratchedPercentage = 0;
        
        this.setupCanvas();
        this.bindEvents();
    }
    
    setupCanvas() {
        const rect = this.canvas.getBoundingClientRect();
        this.canvas.width = rect.width;
        this.canvas.height = rect.height;
        
        // Desenhar camada de raspagem
        this.ctx.fillStyle = '#c0c0c0';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    }
    
    scratch(x, y) {
        this.ctx.globalCompositeOperation = 'destination-out';
        this.ctx.beginPath();
        this.ctx.arc(x, y, 30, 0, Math.PI * 2);
        this.ctx.fill();
        
        this.updateScratchedPercentage();
    }
    
    updateScratchedPercentage() {
        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        const pixels = imageData.data;
        let transparentPixels = 0;
        
        for (let i = 3; i < pixels.length; i += 4) {
            if (pixels[i] === 0) transparentPixels++;
        }
        
        this.scratchedPercentage = (transparentPixels / (this.canvas.width * this.canvas.height)) * 100;
        
        if (this.scratchedPercentage > 50) {
            this.revealAll();
        }
    }
}
```

## 🎮 Sistema de Jogos

### Algoritmo de Probabilidades

#### Configuração Base
```php
class ProbabilityEngine {
    private $desiredWinRate = 0.08; // 8% taxa de vitória
    
    public function calculatePrizes($prizes, $isInfluencer = false) {
        if ($isInfluencer) {
            return $this->applyInfluencerBoost($prizes);
        }
        
        return $this->normalizeToWinRate($prizes);
    }
    
    private function applyInfluencerBoost($prizes) {
        foreach ($prizes as &$prize) {
            if ($prize['valor'] > 50) {
                $prize['probabilidade'] += 40; // +40% para prêmios altos
            }
        }
        return $prizes;
    }
    
    private function normalizeToWinRate($prizes) {
        $winPrizes = array_filter($prizes, fn($p) => $p['valor'] > 0);
        $losePrizes = array_filter($prizes, fn($p) => $p['valor'] == 0);
        
        $totalWin = array_sum(array_column($winPrizes, 'probabilidade'));
        $totalLose = array_sum(array_column($losePrizes, 'probabilidade'));
        $totalOriginal = $totalWin + $totalLose;
        
        if ($totalOriginal > 0 && $totalWin > 0 && $totalLose > 0) {
            $scaleWin = ($totalOriginal * $this->desiredWinRate) / $totalWin;
            $scaleLose = ($totalOriginal * (1 - $this->desiredWinRate)) / $totalLose;
            
            foreach ($prizes as &$prize) {
                $prize['probabilidade'] *= ($prize['valor'] > 0) ? $scaleWin : $scaleLose;
            }
        }
        
        return $prizes;
    }
}
```

#### Geração de Grade 3x3
```php
class GridGenerator {
    public function generateGrid($prizes) {
        $maxAttempts = 30;
        $attempts = 0;
        
        while ($attempts++ < $maxAttempts) {
            $grid = $this->createRandomGrid($prizes);
            
            if ($this->validateGrid($grid, $prizes)) {
                return $grid;
            }
        }
        
        // Fallback: grade simples
        return $this->createSimpleGrid($prizes);
    }
    
    private function createRandomGrid($prizes) {
        $grid = [];
        
        // Garantir que todos os prêmios sejam usados se <= 9
        if (count($prizes) <= 9) {
            foreach ($prizes as $prize) {
                $grid[] = $prize['id'];
            }
            
            // Preencher restante se necessário
            while (count($grid) < 9) {
                $grid[] = $this->selectRandomPrize($prizes)['id'];
            }
        } else {
            // Selecionar 9 prêmios aleatórios
            for ($i = 0; $i < 9; $i++) {
                $grid[] = $this->selectRandomPrize($prizes)['id'];
            }
        }
        
        shuffle($grid);
        return $grid;
    }
    
    private function validateGrid($grid, $prizes) {
        if (!$this->hasThreeMatching($grid)) {
            return true; // Sem 3 iguais = válido
        }
        
        // Se tem 3 iguais, verificar se é prêmio vencedor
        $counts = array_count_values($grid);
        foreach ($counts as $prizeId => $count) {
            if ($count >= 3) {
                $prize = $this->findPrizeById($prizes, $prizeId);
                return $prize && $prize['valor'] > 0;
            }
        }
        
        return false;
    }
}
```

## 💰 Sistema de Pagamentos

### Gateway Integration Pattern
```php
abstract class PaymentGateway {
    protected $apiKey;
    protected $secret;
    
    abstract public function createPayment($amount, $method);
    abstract public function verifyCallback($data);
    abstract public function processWithdrawal($amount, $pixKey);
    
    protected function makeRequest($endpoint, $data) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new PaymentException("HTTP Error: $httpCode");
        }
        
        return json_decode($response, true);
    }
}

class BullsPayGateway extends PaymentGateway {
    protected $baseUrl = 'https://api.bullspay.com/v1/';
    
    public function createPayment($amount, $method) {
        return $this->makeRequest('payments', [
            'amount' => $amount,
            'method' => $method,
            'currency' => 'BRL'
        ]);
    }
    
    public function verifyCallback($data) {
        $signature = hash_hmac('sha256', json_encode($data), $this->secret);
        return hash_equals($signature, $data['signature'] ?? '');
    }
}
```

## 🔐 Segurança

### Input Validation
```php
class Validator {
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validateCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        return strlen($cpf) === 11 && self::isValidCPF($cpf);
    }
    
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateAmount($amount) {
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
        return $amount !== false && $amount > 0;
    }
}
```

### SQL Injection Prevention
```php
// ✅ Correto - Prepared Statements
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND senha = ?");
$stmt->execute([$email, $hashedPassword]);

// ❌ Incorreto - Concatenação direta
$query = "SELECT * FROM usuarios WHERE email = '$email'"; // NUNCA FAZER ISSO
```

### XSS Prevention
```php
// ✅ Sempre escapar output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ✅ Para JSON
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
```

## 🧪 Testes

### Unit Tests com PHPUnit
```php
class RaspadinhaTest extends PHPUnit\Framework\TestCase {
    private $probabilityEngine;
    
    protected function setUp(): void {
        $this->probabilityEngine = new ProbabilityEngine();
    }
    
    public function testInfluencerBoost() {
        $prizes = [
            ['id' => 1, 'valor' => 100, 'probabilidade' => 10],
            ['id' => 2, 'valor' => 0, 'probabilidade' => 90]
        ];
        
        $result = $this->probabilityEngine->calculatePrizes($prizes, true);
        
        $this->assertEquals(50, $result[0]['probabilidade']); // 10 + 40
        $this->assertEquals(90, $result[1]['probabilidade']); // Inalterado
    }
    
    public function testWinRateNormalization() {
        $prizes = [
            ['id' => 1, 'valor' => 100, 'probabilidade' => 50],
            ['id' => 2, 'valor' => 0, 'probabilidade' => 50]
        ];
        
        $result = $this->probabilityEngine->calculatePrizes($prizes, false);
        
        // Deve resultar em 8% de chance de vitória
        $winRate = $result[0]['probabilidade'] / 100;
        $this->assertEqualsWithDelta(0.08, $winRate, 0.01);
    }
}
```

### Frontend Testing
```javascript
// Jest test example
describe('ScratchCard', () => {
    let scratchCard;
    
    beforeEach(() => {
        document.body.innerHTML = '<canvas id="test-canvas"></canvas>';
        scratchCard = new ScratchCard('test-canvas', []);
    });
    
    test('should initialize canvas correctly', () => {
        expect(scratchCard.canvas).toBeTruthy();
        expect(scratchCard.ctx).toBeTruthy();
    });
    
    test('should calculate scratched percentage', () => {
        scratchCard.scratch(50, 50);
        expect(scratchCard.scratchedPercentage).toBeGreaterThan(0);
    });
});
```

## 📊 Performance

### Database Optimization
```sql
-- Índices importantes
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_orders_user_created ON orders(user_id, created_at);
CREATE INDEX idx_raspadinhas_categoria_ativo ON raspadinhas(categoria_id, ativo);

-- Query otimizada para listagem
SELECT r.*, c.nome as categoria_nome,
       (SELECT MAX(valor) FROM raspadinha_premios WHERE raspadinha_id = r.id) as maior_premio
FROM raspadinhas r
LEFT JOIN categorias c ON r.categoria_id = c.id
WHERE r.ativo = true
ORDER BY r.destaque DESC, r.ordem ASC
LIMIT 20;
```

### Caching Strategy
```php
class CacheManager {
    private $redis;
    
    public function get($key) {
        return $this->redis ? $this->redis->get($key) : null;
    }
    
    public function set($key, $value, $ttl = 3600) {
        if ($this->redis) {
            $this->redis->setex($key, $ttl, serialize($value));
        }
    }
    
    public function getCachedRaspadinhas($categoria = null) {
        $key = "raspadinhas:" . ($categoria ?? 'all');
        $cached = $this->get($key);
        
        if ($cached) {
            return unserialize($cached);
        }
        
        // Buscar do banco e cachear
        $data = $this->fetchFromDatabase($categoria);
        $this->set($key, $data, 1800); // 30 minutos
        
        return $data;
    }
}
```

## 🚀 Deploy e CI/CD

### GitHub Actions
```yaml
# .github/workflows/deploy.yml
name: Deploy to Vercel

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          
      - name: Install dependencies
        run: composer install --no-dev --optimize-autoloader
        
      - name: Run tests
        run: composer test
        
      - name: Deploy to Vercel
        uses: amondnet/vercel-action@v20
        with:
          vercel-token: ${{ secrets.VERCEL_TOKEN }}
          vercel-org-id: ${{ secrets.ORG_ID }}
          vercel-project-id: ${{ secrets.PROJECT_ID }}
```

## 📚 Recursos de Desenvolvimento

### Debugging
```php
// Debug helper
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

// Logging
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('raspadinha');
$logger->pushHandler(new StreamHandler('logs/app.log', Logger::DEBUG));
$logger->info('User purchased raspadinha', ['user_id' => $userId, 'raspadinha_id' => $id]);
```

### Code Quality Tools
```bash
# PHP CodeSniffer
composer require --dev squizlabs/php_codesniffer
./vendor/bin/phpcs --standard=PSR12 src/

# PHPStan
composer require --dev phpstan/phpstan
./vendor/bin/phpstan analyse src/

# PHP-CS-Fixer
composer require --dev friendsofphp/php-cs-fixer
./vendor/bin/php-cs-fixer fix src/
```

## 🔄 Contribuição

### Git Workflow
```bash
# Criar branch para feature
git checkout -b feature/nova-funcionalidade

# Fazer commits pequenos e descritivos
git commit -m "feat: adiciona validação de CPF"
git commit -m "fix: corrige cálculo de probabilidade"

# Push e criar PR
git push origin feature/nova-funcionalidade
```

### Code Review Checklist
- [ ] Código segue padrões PSR-12
- [ ] Testes unitários incluídos
- [ ] Documentação atualizada
- [ ] Sem vulnerabilidades de segurança
- [ ] Performance adequada
- [ ] Compatibilidade com PHP 7.4+

---

**🎯 Este guia deve ser atualizado conforme o projeto evolui. Mantenha sempre as melhores práticas e padrões atualizados.**
