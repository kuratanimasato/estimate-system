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
    $stmt = $pdo->prepare('DELETE FROM sales_reps WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['flash_message'] = '担当者を削除しました。';
  } catch (Exception $e) {
    error_log('Delete Error: ' . $e->getMessage());
    $_SESSION['flash_message'] = '削除中にエラーが発生しました。';
  }
}

header('Location: index.php');
exit;