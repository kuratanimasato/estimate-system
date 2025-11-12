<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

function get_csrf_token()
{
  if (!isset($_SESSION['csrf_token'])) {
    // 32バイト（256ビット）のランダムなバイト列を生成し、16進数文字列に変換
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf_token'];
}

function validate_csrf_token($post_token = null)
{
  // POSTトークンが存在し、セッションにトークンが存在し、かつ両者が一致するか
  if ($post_token && isset($_SESSION['csrf_token']) && $post_token === $_SESSION['csrf_token']) {
    return true;
  }
  return false;
}