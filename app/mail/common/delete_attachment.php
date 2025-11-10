<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

header('Content-Type: text/plain; charset=UTF-8');

$attachmentId = $_POST['attachment_id'] ?? null;
$documentId = $_POST['document_id'] ?? null;
$documentType = $_POST['document_type'] ?? null;

if (!$attachmentId || !$documentId || !$documentType) {
  http_response_code(400);
  echo "パラメータが不足しています";
  exit;
}

// 添付ファイルをDBから取得
$stmt = $pdo->prepare("
  SELECT file_path FROM document_attachments
  WHERE id = ? AND document_id = ? AND document_type = ?
");
$stmt->execute([$attachmentId, $documentId, $documentType]);
$file = $stmt->fetch();

if (!$file) {
  http_response_code(404);
  echo "添付ファイルが見つかりません";
  exit;
}

// ファイルを削除
$filePath = $file['file_path'];
if (is_file($filePath)) {
  unlink($filePath);
}

// DBレコードを削除
$deleteStmt = $pdo->prepare("DELETE FROM document_attachments WHERE id = ?");
$deleteStmt->execute([$attachmentId]);

echo "OK";