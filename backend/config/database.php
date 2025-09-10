<?php
/**
 * Database Configuration for Supabase
 * This file replaces the old conexao.php with Supabase connection
 */

// Set timezone for Brazil
date_default_timezone_set('America/Sao_Paulo');
ini_set('date.timezone', 'America/Sao_Paulo');

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load .env files (legacy and new structure)
// 1) Legacy: backend/.env
loadEnv(__DIR__ . '/../.env');
// 2) New: root/config/.env
$rootConfigEnv = realpath(__DIR__ . '/../../config/.env');
if ($rootConfigEnv) {
    loadEnv($rootConfigEnv);
}

// Supabase configuration
$supabaseUrl = $_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL');
$supabaseKey = $_ENV['SUPABASE_ANON_KEY'] ?? getenv('SUPABASE_ANON_KEY');
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

// Parse database URL for PDO connection
if ($databaseUrl) {
    $dbParts = parse_url($databaseUrl);
    $host = $dbParts['host'];
    $port = $dbParts['port'] ?? 5432;
    $dbname = ltrim($dbParts['path'], '/');
    $user = $dbParts['user'];
    $password = $dbParts['pass'];
} else {
    // Fallback to individual environment variables
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 5432;
    $dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'postgres';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'postgres';
    $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? '';
}

// PDO connection string for PostgreSQL (Supabase uses PostgreSQL)
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    
    // Set timezone for PostgreSQL
    $pdo->exec("SET timezone = 'America/Sao_Paulo'");
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log('Database connection failed: ' . $e->getMessage());
    
    if ($_ENV['APP_ENV'] === 'development' || getenv('APP_ENV') === 'development') {
        throw new PDOException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode());
    } else {
        throw new PDOException('Database connection failed. Please try again later.', 500);
    }
}

// Get site configuration
try {
    $site = $pdo->query("SELECT nome_site, logo, deposito_min, saque_min, cpa_padrao FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    $nomeSite = $site['nome_site'] ?? 'Raspadinha';
    $logoSite = $site['logo'] ?? '';
    $depositoMin = $site['deposito_min'] ?? 10;
    $saqueMin = $site['saque_min'] ?? 50;
    $cpaPadrao = $site['cpa_padrao'] ?? 10;
} catch (PDOException $e) {
    // Fallback values if config table doesn't exist or is empty
    $nomeSite = 'Raspadinha';
    $logoSite = '';
    $depositoMin = 10;
    $saqueMin = 50;
    $cpaPadrao = 10;
}

/**
 * Supabase API Helper Class
 */
class SupabaseClient {
    private $url;
    private $key;
    
    public function __construct($url, $key) {
        $this->url = rtrim($url, '/');
        $this->key = $key;
    }
    
    /**
     * Make API request to Supabase
     */
    public function request($method, $endpoint, $data = null, $headers = []) {
        $url = $this->url . '/rest/v1/' . ltrim($endpoint, '/');
        
        $defaultHeaders = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        $headers = array_merge($defaultHeaders, $headers);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("Supabase API error: HTTP $httpCode - $response");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Insert data into table
     */
    public function insert($table, $data) {
        return $this->request('POST', $table, $data);
    }
    
    /**
     * Update data in table
     */
    public function update($table, $data, $where = '') {
        $endpoint = $table . ($where ? '?' . $where : '');
        return $this->request('PATCH', $endpoint, $data);
    }
    
    /**
     * Select data from table
     */
    public function select($table, $columns = '*', $where = '') {
        $endpoint = $table . '?select=' . $columns . ($where ? '&' . $where : '');
        return $this->request('GET', $endpoint);
    }
    
    /**
     * Delete data from table
     */
    public function delete($table, $where = '') {
        $endpoint = $table . ($where ? '?' . $where : '');
        return $this->request('DELETE', $endpoint);
    }
}

// Initialize Supabase client if credentials are available
$supabase = null;
if ($supabaseUrl && $supabaseKey) {
    $supabase = new SupabaseClient($supabaseUrl, $supabaseKey);
}
