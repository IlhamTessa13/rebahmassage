<?php
// Load Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Load .env from the project root
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
