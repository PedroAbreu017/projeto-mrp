<?php
// Bootstrap para testes PHPUnit

require_once __DIR__ . '/../vendor/autoload.php';

// Configurar environment para testes
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_NAME'] = 'sistema_mrp_test'; // Database separado para testes
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '123456';

// Função helper para testes
function createTestDatabase() {
    // TODO: Implementar criação de DB de teste
}