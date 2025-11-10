<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$current_page = 'Invoice';


// --- ページネーション設定 ---
const ITEMS_PER_PAGE = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));

try {
  $count_sql = 'SELECT COUNT(*) FROM  invoices ';
  $total_invoices = (int) $pdo->query($count_sql)->fetchColumn();
} catch (Exception $e) {
  error_log("Database Error (Count) in invoice/index.php: " . $e->getMessage());
  $total_invoices = 0;
}

$total_pages = ceil($total_invoices / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;
try {
  $sql = '
  SELECT 
    i.id,
    i.document_type,
    i.customer_name,
    i.customer_email,
    i.sales_rep_id,
    i.sales_rep_name,
    i.issue_date,
    i.expiration_date,
    i.subtotal,
    i.tax,
    i.total_amount,
    i.status,
    i.created_at,
    GROUP_CONCAT(DISTINCT d.cost_type ORDER BY d.id SEPARATOR ", ") AS cost_type,
    GROUP_CONCAT(DISTINCT d.item_name ORDER BY d.id SEPARATOR ", ") AS item_name,
    GROUP_CONCAT(DISTINCT d.quantity ORDER BY d.id SEPARATOR ", ") AS quantity
  FROM invoices i
  LEFT JOIN invoice_details d ON i.id = d.invoice_id
  GROUP BY i.id
  ORDER BY i.created_at DESC 
  LIMIT :limit OFFSET :offset
  ';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Database Error in invoices/index.php: " . $e->getMessage());
  $invoices = [];
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <!-- メインコンテンツ -->
  <main class="flex-3 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">請求一覧</h1>

      <!-- 新規作成ボタン -->
      <div class="flex justify-end mb-4 space-x-2">
        <a href="/app/Invoices/create.php"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md flex items-center">
          <i class="fas fa-plus mr-2"></i> 新規請求作成
        </a>
      </div>

      <!-- デスクトップ用テーブル -->
      <div class="hidden xl:block bg-white shadow-xl rounded-xl overflow-hidden ">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  種別</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  顧客名</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  区分</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  商品名</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  顧客メール</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  営業担当</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  発行日</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  支払い期限</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  小計</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  合計</th>
                <th
                  class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  ステータス</th>
                <th
                  class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider  whitespace-nowrap">
                  操作</th>
              </tr>
            </thead>


            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (empty($invoices)): ?>
              <tr>
                <td colspan="10" class="px-6 py-4 text-center  text-sm text-gray-500 ">
                  登録されている請求はありません。
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($invoices as $invoice): ?>
              <tr class="hover:bg-gray-50 transition whitespace-nowrap">
                <td class="px-4 py-4 text-sm font-medium text-gray-900">
                  <?= htmlspecialchars($invoice['document_type']) ?>
                </td>
                <td class="px-4 py-4 text-sm text-gray-700">
                  <?= htmlspecialchars($invoice['customer_name']) ?><br>
                </td>
                <td class="px-4 py-4 text-sm text-gray-700">
                  <?= htmlspecialchars($invoice['cost_type'] ?? '（なし）') ?>
                </td>
                <td class="px-4 py-4 text-sm text-gray-700">
                  <?= htmlspecialchars($invoice['item_name'] ?? '（明細なし）') ?>
                </td>
                <td class="px-2 py-4 text-sm text-gray-700"><?= htmlspecialchars($invoice['customer_email']) ?></td>
                <td class="px-2 py-4 text-sm text-gray-700"><?= htmlspecialchars($invoice['sales_rep_name']) ?></td>
                <td class="px-2 py-4 text-sm text-gray-500">
                  <?= htmlspecialchars(date('Y年m月d日 ', strtotime($invoice['issue_date']))) ?>
                </td>
                <td class="px-2 py-4 text-sm text-gray-500">
                  <?= htmlspecialchars(date('Y年m月d日 ', strtotime($invoice['expiration_date']))) ?>
                </td>
                <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($invoice['subtotal']) ?></td>
                <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($invoice['total_amount']) ?></td>
                <td class="px-2 py-4 text-sm">
                  <span
                    class="px-2 py-1 inline-flex text-xs font-semibold rounded-full <?= $invoice['status'] === '未払い' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                    <?= htmlspecialchars($invoice['status']) ?>
                  </span>
                </td>
                <td class="px-2 py-4 text-right text-sm font-medium">
                  <a href="/app/Invoices/view.php?id=<?= $invoice['id'] ?>"
                    class="text-indigo-600 hover:text-indigo-900 mr-3">詳細</a>
                  <a href="/app/Invoices/edit.php?id=<?= $invoice['id'] ?>"
                    class="text-indigo-600 hover:text-indigo-900 mr-3">編集</a>
                  <a href="/app/Invoices/delete.php?id=<?= $invoice['id'] ?>"
                    onclick="return confirm('「<?= htmlspecialchars($invoice['customer_name']) ?>」の請求書を削除しますか？');"
                    class="text-red-600 hover:text-red-900">削除</a>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- スマホ用カード -->
      <div class="xl:hidden space-y-4">
        <?php foreach ($invoices as $invoice): ?>
        <div class="bg-white shadow rounded-lg p-4">
          <div class="flex justify-between mb-1">
            <span class="font-semibold">種別:</span>
            <span><?= htmlspecialchars($invoice['document_type']) ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">区分:</span>
            <span><?= htmlspecialchars($invoice['cost_type'] ?? '（なし）') ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">顧客名:</span>
            <span><?= htmlspecialchars($invoice['customer_name']) ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">商品名:</span>
            <span> <?= htmlspecialchars($invoice['item_name'] ?? '（明細なし）') ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">顧客メールアドレス:</span>
            <span><?= htmlspecialchars($invoice['customer_email']) ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">営業担当:</span>
            <span><?= htmlspecialchars($invoice['sales_rep_name']) ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">発行日:</span>
            <span><?= htmlspecialchars(date('Y-m-d', strtotime($invoice['issue_date']))) ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">支払い期限:</span>
            <span><?= htmlspecialchars(date('Y-m-d', strtotime($invoice['expiration_date']))) ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">小計（税込）:</span>
            <span>¥<?= number_format($invoice['subtotal']) ?></span>
          </div>
          <div class="flex justify-between mb-1">
            <span class="font-semibold">金額（税込）:</span>
            <span>¥<?= number_format($invoice['total_amount']) ?></span>
          </div>
          <div class="flex justify-between mb-2">
            <span class="font-semibold">ステータス:</span>
            <span><?= htmlspecialchars($invoice['status']) ?></span>
          </div>
          <div class="flex justify-end space-x-2">
            <a href="/app/Invoices/view.php?id=<?= $invoice['id'] ?>"
              class="text-indigo-600 hover:text-indigo-900 mr-3">詳細</a>
            <a href="/app/Invoices/edit.php?id=<?= $invoice['id'] ?>" class="text-indigo-600 hover:atext-indigo-900">編集
            </a>
            <a href="/app/Invoices/delete.php?id=<?= $invoice['id'] ?>"
              onclick="return confirm('「<?= htmlspecialchars($invoice['customer_name']) ?>」の請求書を削除しますか？');"
              class="text-red-600 hover:text-red-900">削除</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/render_pagination.php'; ?>
    </div>
  </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>