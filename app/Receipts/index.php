<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$current_page = 'Receipts';

// --- ページネーション設定 ---
const ITEMS_PER_PAGE = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));

try {
  $total_sql = 'SELECT COUNT(*) FROM receipts';
  $total_receipts = (int) $pdo->query($total_sql)->fetchColumn();
} catch (Exception $e) {
  error_log("Database Error (Count) in receipts/index.php: " . $e->getMessage());
  $total_receipts = 0;
}

$total_pages = ceil($total_receipts / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

try {
  $sql = "
        SELECT 
            r.id,
            r.document_type,
            r.customer_id,
            r.customer_name,
            r.customer_email,
            r.sales_rep_id,
            r.sales_rep_name,
            r.issue_date,
            r.received_date,
            r.payment_method,
            r.subtotal,
            r.tax,
            r.total_amount,
            r.remarks,
            r.created_at,
            GROUP_CONCAT(ri.item_name ORDER BY ri.id SEPARATOR ', ') AS item_names,
            GROUP_CONCAT(ri.unit_price ORDER BY ri.id SEPARATOR ', ') AS unit_prices,
            GROUP_CONCAT(ri.quantity ORDER BY ri.id SEPARATOR ', ') AS quantities
        FROM receipts AS r
        LEFT JOIN receipt_details AS ri ON r.id = ri.receipt_id
        GROUP BY r.id
        ORDER BY r.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Database Error in receipts/index.php: " . $e->getMessage());
  $receipts = [];
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <main class="flex-3 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">領収書一覧</h1>

      <!-- 新規作成ボタン -->
      <div class="flex justify-end mb-4 space-x-2">
        <a href="/app/receipts/create.php"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md flex items-center">
          <i class="fas fa-plus mr-2"></i> 新規領収書作成
        </a>
      </div>

      <!-- デスクトップ用テーブル -->
      <div class="hidden xl:block bg-white shadow-xl rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  種別</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  顧客名</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  商品名</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  顧客メール</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  営業担当</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  発行日</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  受領日</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  支払い方法</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  小計</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  合計</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  備考</th>
                <th
                  class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  操作</th>
              </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (empty($receipts)): ?>
                <tr>
                  <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">
                    登録されている領収書はありません。
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($receipts as $receipt): ?>
                  <tr class="hover:bg-gray-50 transition whitespace-nowrap">
                    <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($receipt['document_type'] ?? '領収書') ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900"><?= htmlspecialchars($receipt['customer_name']) ?></td>
                    <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($receipt['item_names'] ?? '（明細なし）') ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($receipt['customer_email'] ?? '-') ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($receipt['sales_rep_name'] ?? '-') ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars(date('Y年m月d日 ', strtotime($receipt['issue_date']))) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars(date('Y年m月d日 ', strtotime($receipt['received_date']))) ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700"><?= htmlspecialchars($receipt['payment_method'] ?? '-') ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($receipt['subtotal']) ?></td>
                    <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($receipt['total_amount']) ?></td>
                    <td class="px-4 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars($receipt['remarks'] ?? '-') ?>
                    </td>
                    <td class="px-2 py-4 text-right text-sm font-medium">
                      <a href="/app/receipts/view.php?id=<?= $receipt['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-3">詳細</a>
                      <a href="/app/receipts/edit.php?id=<?= $receipt['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-3">編集</a>
                      <form action="/app/receipts/delete.php" method="POST" class="inline"
                        onsubmit="return confirm('「<?= htmlspecialchars($receipt['customer_name']) ?>」の領収書を削除しますか？');">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($receipt['id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                      </form>
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
        <?php foreach ($receipts as $receipt): ?>
          <div class="bg-white shadow rounded-lg p-4">
            <div class="flex justify-between mb-1">
              <span class="font-semibold">種別:</span>
              <span><?= htmlspecialchars($receipt['document_type'] ?? '領収書') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">顧客名:</span>
              <span><?= htmlspecialchars($receipt['customer_name']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">商品名:</span>
              <span><?= htmlspecialchars($receipt['item_names'] ?? '（明細なし）') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">顧客メール:</span>
              <span><?= htmlspecialchars($receipt['customer_email'] ?? '-') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">営業担当:</span>
              <span><?= htmlspecialchars($receipt['sales_rep_name'] ?? '-') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">発行日:</span>
              <span><?= htmlspecialchars(date('Y-m-d', strtotime($receipt['issue_date']))) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">受領日:</span>
              <span><?= htmlspecialchars(date('Y-m-d', strtotime($receipt['received_date']))) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">支払い方法:</span>
              <span><?= htmlspecialchars($receipt['payment_method'] ?? '-') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">受領日:</span>
              <span><?= htmlspecialchars($receipt['received_date'] ?? '-') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">小計:</span>
              <span>¥<?= number_format($receipt['subtotal']) ?></span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="font-semibold">合計:</span>
              <span>¥<?= number_format($receipt['total_amount']) ?></span>
            </div>
            <div class="flex justify-end space-x-2">
              <a href="/app/receipts/view.php?id=<?= $receipt['id'] ?>"
                class="text-indigo-600 hover:text-indigo-900">詳細</a>
              <a href="/app/receipts/edit.php?id=<?= $receipt['id'] ?>"
                class="text-indigo-600 hover:text-indigo-900">編集</a>
              <form action="/app/receipts/delete.php" method="POST" class="inline"
                onsubmit="return confirm('「<?= htmlspecialchars($receipt['customer_name']) ?>」の領収書を削除しますか？');">
                <input type="hidden" name="id" value="<?= htmlspecialchars($receipt['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/render_pagination.php'; ?>
    </div>
  </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>