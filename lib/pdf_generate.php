<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // TCPDF
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$id = $_GET['id'] ?? null;
if (!$id || !ctype_digit($id))
  die('不正なアクセスです。');

// ドキュメント種別
$type = $_GET['type'] ?? 'estimate';
$allowed = [
  'estimate' => [
    'path' => $_SERVER['DOCUMENT_ROOT'] . '/app/pdf_templates/estimate_template.php',
    'title' => '見積書',
    'main_table' => 'quotes',
    'detail_table' => 'quote_details',
    'main_id' => 'id',
    'detail_fk' => 'quote_id',
  ],
  'invoice' => [
    'path' => $_SERVER['DOCUMENT_ROOT'] . '/app/pdf_templates/invoice_template.php',
    'title' => '請求書',
    'main_table' => 'invoices',
    'detail_table' => 'invoice_details',
    'main_id' => 'id',
    'detail_fk' => 'invoice_id',
  ],
  'delivery' => [
    'path' => $_SERVER['DOCUMENT_ROOT'] . '/app/pdf_templates/delivery_template.php',
    'title' => '納品書',
    'main_table' => 'delivery_notes',
    'detail_table' => 'delivery_note_details',
    'main_id' => 'id',
    'detail_fk' => 'delivery_note_id',
  ],
  'receipt' => [
    'path' => $_SERVER['DOCUMENT_ROOT'] . '/app/pdf_templates/receipt_template.php',
    'title' => '領収書',
    'main_table' => 'receipts',
    'detail_table' => 'receipt_details',
    'main_id' => 'id',
    'detail_fk' => 'receipt_id',
  ],
];

if (!array_key_exists($type, $allowed)) {
  die('不正なドキュメント種別です。');
}

// データ取得（main + details）
$main_table = $allowed[$type]['main_table'];
$detail_table = $allowed[$type]['detail_table'];
$main_id = $allowed[$type]['main_id'];
$detail_fk = $allowed[$type]['detail_fk'];

$stmt = $pdo->prepare("SELECT * FROM {$main_table} WHERE {$main_id} = :id");
$stmt->execute([':id' => $id]);
$main = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$main)

  die('データが見つかりません。');

$stmt_details = $pdo->prepare("SELECT * FROM {$detail_table} WHERE {$detail_fk} = :id");
$stmt_details->execute([':id' => $id]);
$details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

//テンプレート存在の確認
$template_path = $allowed[$type]['path'];
if (!is_file($template_path)) {
  error_log("テンプレートが見つかりません: {$template_path}");
  die('テンプレートが見つかりません。');
}

// TCPDF設定
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('MyApp');
$pdf->SetTitle($allowed[$type]['title']);
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('kozminproregular', '', 10);

// HTML生成
ob_start();
require $template_path; // テンプレート内で $main, $details を利用
$html = ob_get_clean();

// PDF出力
$filename = "{$type}_" . $main[$main_id] . '.pdf';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output($filename, 'I');