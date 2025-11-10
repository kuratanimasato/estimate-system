<?php
// --- 環境設定 ---
$is_local = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

// --- ローカル・本番共通ユーザー名 ---
$valid_user = 'sales_team';

// --- ローカルと本番でパスワードを分けたい場合 ---
if ($is_local) {
  // ローカル用パスワード
  $valid_password_hash = password_hash('local123', PASSWORD_DEFAULT);
} else {
  // 本番用ハッシュ（固定値）
  $valid_password_hash = '$5$rounds=6000$aa69a41f323f5d37$CQedza08uodk2T2cRnDdgCOjhduqa0qgRNiYcfZqRt2';
}

// --- 認証処理 ---
$auth_user = $_SERVER['PHP_AUTH_USER'] ?? null;
$auth_pass = $_SERVER['PHP_AUTH_PW'] ?? null;

$authenticated = false;
if ($auth_user && $auth_pass) {
  if (hash_equals($valid_user, $auth_user) && password_verify($auth_pass, $valid_password_hash)) {
    $authenticated = true;
  }
}

if (!$authenticated) {
  header('HTTP/1.1 401 Unauthorized');
  header('WWW-Authenticate: Basic realm="ログインID: sales_team / パスワード: sales_team123"');
  exit('Access denied');
}