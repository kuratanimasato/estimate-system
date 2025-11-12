<?php
use Dotenv\Dotenv;
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
//セッション開始
if (session_status() === PHP_SESSION_NONE) {

  session_start();
}


date_default_timezone_set('Asia/Tokyo');

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();