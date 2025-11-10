<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$current_page = 'Quotes';

// --- ページネーション設定 ---
const ITEMS_PER_PAGE = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));

// 総件数取得
$total_quotes = (int) $pdo->query('SELECT COUNT(*) FROM quotes')->fetchColumn();
$total_pages = ceil($total_quotes / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

// データ取得
$stmt = $pdo->prepare($sql = 'SELECT 
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
  LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>
  <main class="flex-1 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">見積もりコピー一覧</h1>

      <!-- デスクトップテーブル -->
      <div class="hidden md:block bg-white shadow-xl rounded-xl overflow-hidden">
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
                  区分</th>
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
                  有効期限</th>
                <th
                  class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  数量</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  小計</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  税</th>
                <th
                  class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  合計</th>
                <th
                  class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                  ステータス</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (empty($quotes)): ?>
              <tr>
                <td colspan="11" class="px-4 py-4 text-center text-sm text-gray-500">
                  見積もりはありません。
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($quotes as $quote): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-2 text-sm text-gray-900"><?= htmlspecialchars($quote['document_type']) ?></td>
                <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($quote['customer_name']) ?></td>
                <td class="px-2 py-4 text-sm text-gray-700">
                  <?= htmlspecialchars($quote['cost_type'] ?? '（なし）') ?>
                </td>
                <td class="px-2 py-4 text-sm text-gray-700"> <?= htmlspecialchars($quote['item_name'] ?? '（明細なし）') ?>
                </td>
                <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($quote['customer_email']) ?></td>
                <td class="px-4 py-2 text-sm text-gray-700"><?= htmlspecialchars($quote['sales_rep_name']) ?></td>
                <td class="px-4 py-2 text-sm text-gray-500">
                  <?= htmlspecialchars(date('Y-m-d', strtotime($quote['issue_date']))) ?>
                </td>
                <td class="px-4 py-2 text-sm text-gray-500">
                  <?= htmlspecialchars(date('Y-m-d', strtotime($quote['expiration_date']))) ?>
                </td>
                <td class="px-2 py-4 text-sm text-gray-700　 text-center">
                  <?= htmlspecialchars($quote['quantity'] ?? '（なし）') ?>個
                </td>
                <td class="px-4 py-2 text-sm text-right text-gray-900">¥<?= number_format($quote['subtotal']) ?></td>
                <td class="px-4 py-2 text-sm text-right text-gray-900">¥<?= number_format($quote['tax']) ?></td>
                <td class="px-4 py-2 text-sm text-right text-gray-900">¥<?= number_format($quote['total_amount']) ?>
                </td>
                <td class="px-2 py-4 text-center ">
                  <span
                    class="px-2 py-1 inline-flex text-xs font-semibold rounded-full <?= $quote['status'] === '確定' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                    <?= htmlspecialchars($quote['status']) ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-center">
                  <div class="flex justify-center  gap-2">
                    <!-- コピー ボタン -->
                    <a href="create.php?copy_id=<?= $quote['id'] ?>" onclick="return confirm('この見積書をコピーしますか？');"
                      class="inline-flex items-center justify-center bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-indigo-700 transition whitespace-nowrap">
                      コピー
                    </a>
                  </div>
                </td>
        </div>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
    <!-- スマホ用カード -->

    <!-- スマホ用カード表示 -->
    <div class="md:hidden space-y-4">
      <?php foreach ($quotes as $quote): ?>
      <div class="bg-white shadow rounded-lg p-4">
        <div class="flex justify-between mb-1"><span class="font-semibold">
            種別:</span><span><?= htmlspecialchars($quote['document_type']) ?></span></div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            顧客名:</span><span><?= htmlspecialchars($quote['customer_name']) ?></span></div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            区分:</span><span> <?= htmlspecialchars($quote['cost_type'] ?? '（なし）') ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            商品名:</span><span><?= htmlspecialchars($quote['item_name'] ?? '（明細なし）') ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            顧客メール:</span><span><?= htmlspecialchars($quote['customer_email']) ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            営業担当:</span><span><?= htmlspecialchars($quote['sales_rep_name']) ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            発行日:</span><span> <?= htmlspecialchars(date('Y-m-d', strtotime($quote['issue_date']))) ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            有効期限:</span><span> <?= htmlspecialchars(date('Y-m-d', strtotime($quote['expiration_date']))) ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            数量:</span><span> <?= htmlspecialchars($quote['quantity'] ?? '（なし）') ?>個</span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            小計:</span><span>¥<?= number_format($quote['subtotal']) ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            税:</span><span>¥<?= number_format($quote['tax']) ?></span>
        </div>
        <div class="flex justify-between mb-1"><span class="font-semibold">
            合計:</span><span>¥<?= number_format($quote['total_amount']) ?></span>
        </div>
        <td class="px-4 py-2  text-center">
          <div class="flex justify-end gap-2">
            <!-- コピー ボタン -->
            <a href="create.php?copy_id=<?= $quote['id'] ?>" onclick="return confirm('この見積書をコピーしますか？');"
              class="inline-flex items-center justify-center bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-indigo-700 transition whitespace-nowrap">
              コピー
            </a>
          </div>
        </td>
      </div>
      <?php endforeach; ?>
    </div>
</div>
</div>
</main>
</div><?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>