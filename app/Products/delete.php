<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
  header('Location: index.php');
  exit;
}

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

header('Location: index.php');
exit;