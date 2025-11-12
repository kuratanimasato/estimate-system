<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

$current_page_name = 'Items';

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

// ページネーション設定
const ITEMS_PER_PAGE = 8;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$total_items = 0;

// 件数カウント
try {
  $stmt = $pdo->query('SELECT COUNT(*) FROM items');
  $total_items = (int) $stmt->fetchColumn();
} catch (Exception $e) {
  error_log("Database Error (Count) in items/index.php: " . $e->getMessage());
}

$total_pages = ceil($total_items / ITEMS_PER_PAGE);
$offset = ($current_page - 1) * ITEMS_PER_PAGE;

// データ取得
$items = [];


try {
  $sql = 'SELECT id, item_name, unit_price, cost_type, created_at, updated_at
          FROM items ORDER BY created_at DESC
          LIMIT :limit OFFSET :offset';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $errorMessage = "商品データの読み込み中にエラーが発生しました。";
  error_log("Database Error in items/index.php: " . $e->getMessage());
}
?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <main class="flex-1 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">商品マスタ</h1>
      <!-- 新規追加ボタン -->
      <div class="flex justify-end mb-4">
        <a href="/app/Products/create.php"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out flex items-center">
          <i class="fas fa-plus mr-2"></i>
          新規商品登録
        </a>
      </div>
      <!-- 商品一覧テーブル -->
      <div class="hidden md:block bg-white shadow-xl rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">項目名</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">費用区分</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">単価</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ">登録日</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> 最終更新日</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> 操作</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (empty($items)): ?>
                <tr>
                  <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                    登録されている商品はありません。
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                  <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                      <?= htmlspecialchars($item['item_name']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <?= htmlspecialchars($item['cost_type']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                      <?= number_format((float) $item['unit_price']) ?>
                    </td>

                    <td class="px-6 py-4 text-sm text-gray-500 hidden md:table-cell">
                      <?= (new DateTime($item['created_at']))->format('Y年m月d日') ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 hidden md:table-cell">
                      <?= (new DateTime($item['updated_at']))->format('Y年m月d日') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap  text-sm font-medium">
                      <a href="/app/Products/edit.php?id=<?= $item['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-4">編集</a>
                      <form action="/app/Products/delete.php" method="POST" class="inline"
                        onsubmit="return confirm('「<?= htmlspecialchars($item['item_name']) ?>」を削除しますか？');">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
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
      <!-- スマホ用カード表示 -->
      <div class="md:hidden space-y-4">
        <?php foreach ($items as $item): ?>
          <div class="bg-white shadow rounded-lg p-4">
            <div class="flex justify-between mb-1">
              <span class="font-semibold">項目名:</span>
              <span> <?= htmlspecialchars($item['item_name']) ?></span>
            </div>
            <div class="flex justify-between mb-1">
              <span class="font-semibold">費用区分:</span>
              <span> <?= htmlspecialchars($item['cost_type']) ?></span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="font-semibold">登録日:</span>
              <span> <?= (new DateTime($item['created_at']))->format('Y-m-d H:i') ?></span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="font-semibold">最終更新日:</span>
              <span> <?= (new DateTime($item['updated_at']))->format('Y-m-d H:i') ?></span>
            </div>
            <div class="flex justify-end space-x-2">
              <a href="/app/Products/edit.php?id=<?= $item['id'] ?>" class="text-indigo-600 hover:text-indigo-900">編集</a>
              <form action="/app/Products/delete.php" method="POST" class="inline"
                onsubmit="return confirm('「<?= htmlspecialchars($item['item_name']) ?>」を削除しますか？');">
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