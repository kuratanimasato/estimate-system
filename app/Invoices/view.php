<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$current_page = 'Invoices';


// URLパラメータからID取得
$id = $_GET['id'] ?? null;

if (!$id || !ctype_digit($id)) {
  die('不正なアクセスです。');
}

try {
  // 請求書本体
  $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = :id');
  $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt->execute();
  $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$invoice) {
    die('対象の請求書が存在しません。');
  }

  // 明細データ取得
  $stmt_details = $pdo->prepare('SELECT * FROM invoice_details WHERE invoice_id = :id ORDER BY id ASC');
  $stmt_details->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt_details->execute();
  $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
  error_log("Database Error in Invoices/view.php: " . $e->getMessage());
  die('データベースエラーが発生しました。');
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <main class="flex-2 p-6 bg-gray-50">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">請求書詳細画面</h1>

      <!-- 戻るボタン -->
      <div class="mb-6">
        <a href="/lib/pdf_generate.php?id=<?= urlencode($invoice['id']) ?>&type=invoice" target="_blank"
          class=" bg-green-600 hover:bg-green-500 text-white px-2 py-1 rounded shadow-sm">
          PDF出力
        </a>
        <a href="/app/mail/invoice_mail/mail_invoice_form.php?id=<?= $invoice['id'] ?>"
          class="bg-green-600 hover:bg-green-500 text-white px-2 py-1 rounded shadow-sm ml-2">メール送信</a>

      </div>
      <!-- 請求書基本情報 -->
      <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">基本情報</h2>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">種別</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($invoice['document_type']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">顧客名</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($invoice['customer_name']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">顧客メール</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($invoice['customer_email']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">営業担当</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($invoice['sales_rep_name']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">発行日</dt>
            <dd class="text-gray-900"><?= htmlspecialchars(date('Y年m月d日', strtotime($invoice['issue_date']))) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">支払い期限</dt>
            <dd class="text-gray-900"><?= htmlspecialchars(date('Y年m月d日', strtotime($invoice['expiration_date']))) ?>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">小計</dt>
            <dd class="text-gray-900">¥<?= number_format($invoice['subtotal']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">消費税(10%)</dt>
            <dd class="text-gray-900">¥<?= number_format($invoice['tax']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">合計金額</dt>
            <dd class="text-gray-900 font-semibold text-lg text-blue-700">
              ¥<?= number_format($invoice['total_amount']) ?>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">ステータス</dt>
            <dd>
              <span
                class="inline-flex items-center px-2 py-1 rounded-full text-sm font-semibold <?= $invoice['status'] === '確定' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                <?= htmlspecialchars($invoice['status']) ?>
              </span>
            </dd>
          </div>
        </dl>
      </div>

      <!-- 明細一覧 -->
      <div class="bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">明細情報</h2>

        <?php if (empty($details)): ?>
        <p class="text-gray-500 text-sm">明細が登録されていません。</p>
        <?php else: ?>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">品名</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">区分</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">数量</th>
                <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">単価</th>
                <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">小計</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($details as $detail): ?>
              <tr>
                <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars($detail['item_name']) ?></td>
                <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($detail['cost_type']) ?></td>
                <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($detail['quantity']) ?>個</td>
                <td class="px-4 py-2 text-sm text-right text-gray-700">¥<?= number_format($detail['unit_price']) ?></td>
                <td class="px-4 py-2 text-sm text-right text-gray-900 font-medium">
                  ¥<?= number_format($detail['line_total']) ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- 操作ボタン -->
      <div class="mt-6 flex justify-end space-x-4">
        <a href="/app/Invoices/edit.php?id=<?= $invoice['id'] ?>"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow">編集</a>
        <a href="/app/Invoices/delete.php?id=<?= $invoice['id'] ?>" onclick="return confirm('削除してもよろしいですか？');"
          class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow">削除</a>
      </div>
    </div>
  </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>