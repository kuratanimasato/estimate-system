<?php

// -----------------------------------------------------
// 1. 接続情報の設定
// -----------------------------------------------------
$db_host = 'localhost';   // データベースのホスト名（多くの場合 'localhost' または '127.0.0.1'）
$db_name = 'quote_system'; // 作成したDB名
$db_user = 'masatokuratani';        // DBにアクセスするユーザー名
$db_pass = 'masatomimi55'; // DBにアクセスするパスワード
// -----------------------------------------------------
// 2. 接続を試行し、結果を表示
// -----------------------------------------------------
try {
  // PDOオブジェクトを作成し、接続を試みます
  $pdo = new PDO(
    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
    $db_user,
    $db_pass,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // エラー発生時に例外をスローする設定
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // 結果を連想配列で取得する設定
    ]
  );


} catch (PDOException $e) {
  // 接続失敗の場合
  echo "<h1>❌ データベース接続に失敗しました。</h1>";
  echo "<p>エラー内容をご確認ください:</p>";
  echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
  //原因究明のためエラーメッセージをそのまま表示
}

?>