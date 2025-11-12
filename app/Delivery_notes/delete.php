<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
// 共通ファイルの読み込み
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/csrf.php';

$csrf_token = get_csrf_token();
// フォーム送信後の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token($_POST["csrf_token"] ?? null)) {
    echo "不正なリクエストです";
    exit();
  }
}
$id = (int) ($_POST['id'] ?? 0);


if ($id > 0) {
  try {
    // 安全のためトランザクションを開始
    $pdo->beginTransaction();

    $stmtDetail = $pdo->prepare("DELETE FROM delivery_note_details WHERE delivery_note_id = :id");
    $stmtDetail->execute([':id' => $id]);

    // 3. プリペアドステートメントでDELETE文を実行
    $stmt = $pdo->prepare("DELETE FROM delivery_notes WHERE id = :id");
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

}
// 4. 処理完了後、顧客一覧ページへリダイレクト
header('Location: index.php');
exit;