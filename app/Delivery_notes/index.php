<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$current_page = 'Delivery_notes';


// --- ページネーション設定 ---
const ITEMS_PER_PAGE = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));

try {
  $count_sql = 'SELECT COUNT(*) FROM  delivery_notes ';
  $total_invoices = (int) $pdo->query($count_sql)->fetchColumn();
} catch (Exception $e) {
  error_log("Database Error (Count) in delivery_notes/index.php: " . $e->getMessage());
  $total_invoices = 0;
}

$total_pages = ceil($total_invoices / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;
try {
  $sql = '
SELECT 
    n.id,
    n.document_type,
    n.customer_name,
    n.customer_id,
    n.customer_email,
    n.sales_rep_id,
    n.sales_rep_name,
    n.issue_date,
    n.expiration_date,
    n.subtotal,
    n.tax,
    n.total_amount,
    n.status,
    n.created_at,
    GROUP_CONCAT(d.cost_type ORDER BY d.id SEPARATOR ", ") AS cost_type,
    GROUP_CONCAT(d.item_name ORDER BY d.id SEPARATOR ", ") AS item_name,
    GROUP_CONCAT(d.quantity ORDER BY d.id SEPARATOR ", ") AS quantity
    FROM delivery_notes AS n
    LEFT JOIN delivery_note_details AS d 
      ON n.id = d.delivery_note_id
    GROUP BY n.id
    ORDER BY n.created_at DESC 
    LIMIT :limit OFFSET :offset;
  ';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $delivery_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Database Error in invoices/index.php: " . $e->getMessage());
  $delivery_notes = [];
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <!-- メインコンテンツ -->
  <main class="flex-3 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">納品書一覧</h1>

      <!-- 新規作成ボタン -->
      <div class="flex justify-end mb-4 space-x-2">
        <a href="/app/Delivery_notes/create.php"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md flex items-center">
          <i class="fas fa-plus mr-2"></i> 新規納品書作成
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
                  納品日</th>
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
              <?php if (empty($delivery_notes)): ?>
                <tr>
                  <td colspan="10" class="px-6 py-4 text-center  text-sm text-gray-500 ">
                    登録されている請求はありません。
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($delivery_notes as $delivery_note): ?>
                  <tr class="hover:bg-gray-50 transition whitespace-nowrap">
                    <td class="px-4 py-4 text-sm font-medium text-gray-900">
                      <?= htmlspecialchars($delivery_note['document_type']) ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700">
                      <?= htmlspecialchars($delivery_note['customer_name']) ?><br>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700">
                      <?= htmlspecialchars($delivery_note['cost_type'] ?? '（なし）') ?>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700">
                      <?= htmlspecialchars($delivery_note['item_name'] ?? '（明細なし）') ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-700"><?= htmlspecialchars($delivery_note['customer_email']) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-700"><?= htmlspecialchars($delivery_note['sales_rep_name']) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars(date('Y年m月d日 ', strtotime($delivery_note['issue_date']))) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars(date('Y年m月d日 ', strtotime($delivery_note['expiration_date']))) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($delivery_note['subtotal']) ?></td>
                    <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($delivery_note['total_amount']) ?></td>
                    <td class="px-2 py-4 text-sm">
                      <span
                        class="px-2 py-1 inline-flex text-xs font-semibold rounded-full <?= $delivery_note['status'] === '未送付' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= htmlspecialchars($delivery_note['status']) ?>
                      </span>
                    </td>
                    <td class="px-2 py-4 text-right text-sm font-medium">
                      <a href="/app/Delivery_notes/view.php?id=<?= $delivery_note['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-3">詳細</a>
                      <a href="/app/Delivery_notes/edit.php?id=<?= $delivery_note['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-3">編集</a>
                      <form action="/app/Delivery_notes/delete.php" method="POST" class="inline"
                        onsubmit="return confirm('「<?= htmlspecialchars($delivery_note['customer_name']) ?>」の納品書を削除しますか？');">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($delivery_note['id']) ?>">
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
        <?php foreach ($delivery_notes as $delivery_note): ?>
          <div class="bg-white shadow rounded-lg p-4">
            <div class="flex justify-between mb-1">
              <span class="font-semibold">種別:</span>
              <span><?= htmlspecialchars($delivery_note['document_type']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">区分:</span>
              <span><?= htmlspecialchars($delivery_note['cost_type'] ?? '（なし）') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">顧客名:</span>
              <span><?= htmlspecialchars($delivery_note['customer_name']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">商品名:</span>
              <span> <?= htmlspecialchars($delivery_note['item_name'] ?? '（明細なし）') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">顧客メールアドレス:</span>
              <span><?= htmlspecialchars($delivery_note['customer_email']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">営業担当:</span>
              <span><?= htmlspecialchars($delivery_note['sales_rep_name']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">発行日:</span>
              <span><?= htmlspecialchars(date('Y-m-d', strtotime($delivery_note['issue_date']))) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">支払い期限:</span>
              <span><?= htmlspecialchars(date('Y-m-d', strtotime($delivery_note['expiration_date']))) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">小計（税込）:</span>
              <span>¥<?= number_format($delivery_note['subtotal']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">金額（税込）:</span>
              <span>¥<?= number_format($delivery_note['total_amount']) ?></span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="font-semibold">ステータス:</span>
              <span><?= htmlspecialchars($delivery_note['status']) ?></span>
            </div>
            <div class="flex justify-end space-x-2">
              <a href="/app/Delivery_notes/view.php?id=<?= $delivery_note['id'] ?>"
                class="text-indigo-600 hover:text-indigo-900 mr-3">詳細</a>
              <a href="/app/Delivery_notes/edit.php?id=<?= $delivery_note['id'] ?>"
                class="text-indigo-600 hover:atext-indigo-900">編集
              </a>
              <form action="/app/Delivery_notes/delete.php" method="POST" class="inline"
                onsubmit="return confirm('「<?= htmlspecialchars($delivery_note['customer_name']) ?>」の納品書を削除しますか？');">
                <input type="hidden" name="id" value="<?= htmlspecialchars($delivery_note['id']) ?>">
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