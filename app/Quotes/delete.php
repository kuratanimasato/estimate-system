<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
// 共通ファイルの読み込み
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

// 1. GETパラメータからIDを取得
$id = $_GET['id'] ?? null;

// 2. IDのバリデーション
if (!$id || !is_numeric($id)) {
  // IDが無効な場合は、エラーメッセージと共に一覧ページにリダイレクト
  // (セッションを使ってメッセージを渡すのがより親切ですが、今回はシンプルにリダイレクトのみ)
  header('Location: index.php');
  exit;
}

try {
  // 安全のためトランザクションを開始
  $pdo->beginTransaction();

  $stmtDetail = $pdo->prepare("DELETE FROM quote_details WHERE quote_id = :id");
  $stmtDetail->execute([':id' => $id]);

  // 3. プリペアドステートメントでDELETE文を実行
  $stmt = $pdo->prepare("DELETE FROM quotes WHERE id = :id");
  $stmt->execute([':id' => $id]);

  // 処理が成功したらコミット
  $pdo->commit();

  // フラッシュメッセージをセッションに保存
  $_SESSION['flash_message'] = '納品書情報を削除しました。';

} catch (PDOException $e) {
  // エラーが発生したらロールバック
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  // エラーログを記録し、ユーザーには一般的なエラーメッセージを表示することも可能
  error_log("Delete failed: " . $e->getMessage());
  // ここではシンプルに一覧画面に戻す
}

// 4. 処理完了後、顧客一覧ページへリダイレクト
header('Location: index.php');
exit;