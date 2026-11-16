<?php

$env = parse_ini_file(__DIR__ . "/../.env");

if($env === false){
    exit("Arquivo .env não encontrado");
}

try {
    $pdo = new PDO(
        "pgsql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']};sslmode=require",
        $env['DB_USER'],
        $env['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true
        ]
    );
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    exit("Não foi possivel conectar ao banco de dados.");
}
unset($env);