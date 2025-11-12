<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$current_page = 'Quotes';


// --- ページネーション設定 ---
const ITEMS_PER_PAGE = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));

try {
  $count_sql = 'SELECT COUNT(*) FROM quotes';
  $total_quotes = (int) $pdo->query($count_sql)->fetchColumn();
} catch (Exception $e) {
  error_log("Database Error (Count) in quotes/index.php: " . $e->getMessage());
  $total_quotes = 0;
}

$total_pages = ceil($total_quotes / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

try {
  $sql = '
  SELECT 
    q.id,
    q.document_type,
    q.customer_name,
    q.customer_email,
    q.sales_rep_id,
    q.sales_rep_name,
    q.issue_date,
    q.expiration_date,
    q.subtotal,
    q.tax,
    q.total_amount,
    q.status,
    q.created_at,
    GROUP_CONCAT(DISTINCT d.item_name ORDER BY d.id SEPARATOR ", ") AS item_name,
    GROUP_CONCAT(DISTINCT d.cost_type ORDER BY d.id SEPARATOR ", ") AS cost_type,
    GROUP_CONCAT(DISTINCT d.quantity  ORDER BY d.id SEPARATOR ", ") AS quantity 
  FROM quotes q
  LEFT JOIN quote_details d ON q.id = d.quote_id
  WHERE q.is_copy = 0
  GROUP BY q.id
  ORDER BY q.created_at DESC 
  LIMIT :limit OFFSET :offset
  ';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  error_log("Database Error in quotes/index.php: " . $e->getMessage());
  $quotes = [];
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <!-- メインコンテンツ -->
  <main class="flex-2 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">見積もり一覧</h1>
      <!--テンプレート作成ボタン-->
      <!-- 新規作成ボタン -->
      <div class="flex justify-end mb-4 space-x-2">
        <a href="/app/quotes/create.php"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md flex items-center">
          <i class="fas fa-plus mr-2"></i> 新規見積もり作成
        </a>
      </div>

      <!-- デスクトップ用テーブル -->
      <div class="hidden xl:block   bg-white shadow-xl rounded-xl overflow-hidden">
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
                  有効期限</th>
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
              <?php if (empty($quotes)): ?>
                <tr>
                  <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">
                    登録されている見積もりはありません。
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($quotes as $quote): ?>
                  <tr class="hover:bg-gray-50 transition whitespace-nowrap">
                    <td class="px-2 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($quote['document_type']) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-700">
                      <?= htmlspecialchars($quote['customer_name']) ?><br>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-700">
                      <?= htmlspecialchars($quote['cost_type'] ?? '（なし）') ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-700">
                      <?= htmlspecialchars($quote['item_name'] ?? '（明細なし）') ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-700"><?= htmlspecialchars($quote['customer_email']) ?></td>
                    <td class="px-2 py-4 text-sm text-gray-700"><?= htmlspecialchars($quote['sales_rep_name']) ?></td>
                    <td class="px-2 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars(date('Y年m月d日 ', strtotime($quote['issue_date']))) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars(date('Y年m月d日 ', strtotime($quote['expiration_date']))) ?>
                    </td>
                    <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($quote['subtotal']) ?></td>
                    <td class="px-2 py-4 text-sm text-gray-900">¥<?= number_format($quote['total_amount']) ?></td>
                    <td class="px-2 py-4 text-sm">
                      <span
                        class="px-2 py-1 inline-flex text-xs font-semibold rounded-full <?= $quote['status'] === '確定' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= htmlspecialchars($quote['status']) ?>
                      </span>
                    </td>
                    <td class="px-2 py-4 text-right text-sm font-medium">
                      <a href="/app/Quotes/view.php?id=<?= $quote['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-3">詳細</a>
                      <a href="/app/Quotes/edit.php?id=<?= $quote['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-3">編集</a>
                      <form action="/app/Quotes/delete.php" method="POST" class="inline"
                        onsubmit="return confirm('「<?= htmlspecialchars($quote['customer_name']) ?>」の見積もりを削除しますか？');">

                        <input type="hidden" name="id" value="<?= htmlspecialchars($quote['id']) ?>">
                        <!-- CSRFトークンを送信 -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <!-- 削除ボタン -->
                        <button type="submit" class="text-red-600 hover:text-red-900">
                          削除
                        </button>
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
        <?php foreach ($quotes as $quote): ?>
          <div class="bg-white shadow rounded-lg p-4">
            <div class="flex justify-between mb-1">
              <span class="font-semibold">種別:</span>
              <span><?= htmlspecialchars($quote['document_type']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">区分:</span>
              <span><?= htmlspecialchars($quote['cost_type'] ?? '（なし）') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">顧客名:</span>
              <span><?= htmlspecialchars($quote['customer_name']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">商品名:</span>
              <span> <?= htmlspecialchars($quote['item_name'] ?? '（明細なし）') ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">顧客メールアドレス:</span>
              <span><?= htmlspecialchars($quote['customer_email']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">営業担当:</span>
              <span><?= htmlspecialchars($quote['sales_rep_name']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">発行日:</span>
              <span><?= htmlspecialchars(date('Y-m-d', strtotime($quote['issue_date']))) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">有効期限:</span>
              <span><?= htmlspecialchars(date('Y-m-d', strtotime($quote['expiration_date']))) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">小計（税込）:</span>
              <span>¥<?= number_format($quote['subtotal']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">金額（税込）:</span>
              <span>¥<?= number_format($quote['total_amount']) ?></span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="font-semibold">ステータス:</span>
              <span><?= htmlspecialchars($quote['status']) ?></span>
            </div>
            <div class="flex justify-end space-x-2">
              <a href="/app/Quotes/view.php?id=<?= $quote['id'] ?>"
                class="text-indigo-600 hover:text-indigo-900 mr-3">詳細</a>
              <a href="/app/Quotes/edit.php?id=<?= $quote['id'] ?>" class="text-indigo-600 hover:atext-indigo-900">編集
              </a>
              <form action="/app/Quotes/delete.php" method="POST" class="inline"
                onsubmit="return confirm('「<?= htmlspecialchars($quote['customer_name']) ?>」の見積もりを削除しますか？');">

                <input type="hidden" name="id" value="<?= htmlspecialchars($quote['id']) ?>">
                <!-- CSRFトークンを送信 -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <!-- 削除ボタン -->
                <button type="submit" class="text-red-600 hover:text-red-900">
                  削除
                </button>
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