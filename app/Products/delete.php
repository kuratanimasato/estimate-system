<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/csrf.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php');
  exit;
}
$csrf_token = get_csrf_token();
// --- CSRFトークンチェック ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token($_POST["csrf_token"] ?? null)) {
    echo "不正なリクエストです";
    exit();
  }
}
$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
  try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $_SESSION['flash_message'] = '商品を削除しました。';
    $pdo->commit();

  } catch (PDOException $e) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }
    error_log("削除エラー: " . $e->getMessage());
  }
}

header('Location: index.php');
exit;