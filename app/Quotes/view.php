<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$current_page = 'Quotes';


// URLパラメータからID取得
$id = $_GET['id'] ?? null;

if (!$id || !ctype_digit($id)) {
  die('不正なアクセスです。');
}

try {
  // 見積もり本体
  $stmt = $pdo->prepare('SELECT * FROM quotes WHERE id = :id');
  $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt->execute();
  $quote = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$quote) {
    die('対象の見積もりが存在しません。');
  }

  // 明細データ取得
  $stmt_details = $pdo->prepare('SELECT * FROM quote_details WHERE quote_id = :id ORDER BY id ASC');
  $stmt_details->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt_details->execute();
  $details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
  error_log("Database Error in Quotes/view.php: " . $e->getMessage());
  die('データベースエラーが発生しました。');
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <main class="flex-2 p-6 bg-gray-50">
    <div class="max-w-5xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">見積もり詳細画面</h1>

      <!-- 戻るボタン -->
      <div class="mb-6">
        <a href="/lib/pdf_generate.php?type=estimate&id=<?= urlencode($quote['id']) ?>" target="_blank"
          class="bg-green-600 hover:bg-green-500 text-white px-2 py-1 rounded shadow-sm">
          PDF出力
        </a>
        <a href="/app/mail/quote_mail/mail_quote_form.php?id=<?= $quote['id'] ?>"
          class="bg-green-600 hover:bg-green-500 text-white px-2 py-1 rounded shadow-sm ml-2">メール送信</a>

      </div>
      <!-- 見積もり基本情報 -->
      <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">基本情報</h2>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500">種別</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($quote['document_type']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">顧客名</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($quote['customer_name']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">顧客メール</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($quote['customer_email']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">営業担当</dt>
            <dd class="text-gray-900"><?= htmlspecialchars($quote['sales_rep_name']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">発行日</dt>
            <dd class="text-gray-900"><?= htmlspecialchars(date('Y年m月d日', strtotime($quote['issue_date']))) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">有効期限</dt>
            <dd class="text-gray-900"><?= htmlspecialchars(date('Y年m月d日', strtotime($quote['expiration_date']))) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">小計</dt>
            <dd class="text-gray-900">¥<?= number_format($quote['subtotal']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">消費税(10%)</dt>
            <dd class="text-gray-900">¥<?= number_format($quote['tax']) ?></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">合計金額</dt>
            <dd class="text-gray-900 font-semibold text-lg text-blue-700">¥<?= number_format($quote['total_amount']) ?>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">ステータス</dt>
            <dd>
              <span
                class="inline-flex items-center px-2 py-1 rounded-full text-sm font-semibold <?= $quote['status'] === '確定' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                <?= htmlspecialchars($quote['status']) ?>
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
    </div>
  </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>