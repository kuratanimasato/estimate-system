<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/app/mail/common/functions.php';

// === 受け取り ===
$documentType = $_POST['document_type'] ?? '';
$documentId = $_POST['id'] ?? '';
$to = trim($_POST['to'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body = trim($_POST['body'] ?? '');
$uploadedFile = $_FILES['attachment'] ?? null;

// === バリデーション ===
if (!$documentType || !$documentId || !ctype_digit($documentId)) {
  die('不正なアクセスです。');
}

// === DBテーブル判定 ===
$tableMap = [
  'quote' => 'quotes',
  'invoice' => 'invoices',
  'delivery' => 'delivery_notes',
  'receipt' => 'receipts',
];
$table = $tableMap[$documentType] ?? null;
if (!$table)
  die('無効なドキュメントタイプです。');

// === 対象ドキュメント取得 ===
$stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
$stmt->execute([$documentId]);
$document = $stmt->fetch();
if (!$document)
  die('対象のドキュメントが存在しません。');

// === アップロード処理 ===
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/../secure_uploads/';
if (!is_dir($uploadDir))
  mkdir($uploadDir, 0755, true);

if (!empty($uploadedFile['tmp_name']) && is_uploaded_file($uploadedFile['tmp_name'])) {
  $originalName = $uploadedFile['name'];
  $uniqueName = uniqid('pdf_') . '.pdf';
  $targetPath = $uploadDir . $uniqueName;

  if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
    $stmt = $pdo->prepare("
      INSERT INTO document_attachments (document_type, document_id, file_name, file_path)
      VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
      $documentType,
      $documentId,
      encryptValue($originalName),
      encryptValue($targetPath),
    ]);
  }
}

// === 添付ファイル取得 ===
$attachStmt = $pdo->prepare("SELECT * FROM document_attachments WHERE document_type = ? AND document_id = ?");
$attachStmt->execute([$documentType, $documentId]);
$attachments = $attachStmt->fetchAll();

// === メール送信 ===
$mail = new PHPMailer(true);
try {
  $mail->CharSet = 'UTF-8';
  $mail->isSMTP();
  $mail->Host = $_ENV['MAIL_HOST'] ?? 'sandbox.smtp.mailtrap.io';
  $mail->Port = $_ENV['MAIL_PORT'] ?? 2525;
  $mail->SMTPAuth = true;
  $mail->Username = $_ENV['MAIL_USERNAME'];
  $mail->Password = $_ENV['MAIL_PASSWORD'];
  $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';
  $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@docuquest.co.jp', $_ENV['MAIL_FROM_NAME'] ?? '送信システム');
  $mail->addAddress($to, $document['customer_name'] ?? '');

  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body = nl2br($body);

  foreach ($attachments as $attachment) {
    $filePath = decryptValue($attachment['file_path']);
    $fileName = decryptValue($attachment['file_name']);
    if (file_exists($filePath)) {
      $mail->addAttachment($filePath, $fileName);
    }
  }

  $mail->send();

  $_SESSION['flash_message'] = 'メールを送信しました。';
  header("Location: /app/mail/{$documentType}_mail/mail_{$documentType}_done.php?id=" . urlencode($documentId));
  exit;

} catch (Exception $e) {
  error_log("Mail Error: " . $e->getMessage());
  $_SESSION['flash_message'] = 'メール送信に失敗しました。';
  header("Location: /app/mail/{$documentType}_mail/mail_{$documentType}_form.php?id=" . urlencode($documentId));
  exit;
}