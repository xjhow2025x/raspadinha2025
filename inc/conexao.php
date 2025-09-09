<?php
// ===== Fuso horário global (Brasília) =====
date_default_timezone_set('America/Sao_Paulo');
ini_set('date.timezone', 'America/Sao_Paulo');
// =========================================

$host = 'localhost';
$db   = 'u701620395_ganhorspa';
$user = 'u701620395_ganhorspa';
$pass = '8/yRd2qxmaa';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Garantir que a sessão do MySQL use o mesmo fuso (sem depender de time zone tables)
    $pdo->exec("SET time_zone = '-03:00'");
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$site = $pdo->query("SELECT nome_site, logo, deposito_min, saque_min, cpa_padrao FROM config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$nomeSite   = $site['nome_site']   ?? ''; 
$logoSite   = $site['logo']        ?? '';
$depositoMin= $site['deposito_min']?? 10;
$saqueMin   = $site['saque_min']   ?? 50;
$cpaPadrao  = $site['cpa_padrao']  ?? 10;
