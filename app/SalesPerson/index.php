<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 現在アクティブなページをサイドバーに伝えるための変数
$current_page_name = 'SalesPerson';

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

// --- ページネーション設定 ---
const ITEMS_PER_PAGE = 8;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$total_SalesPerson = 0;
// 1. 全件数を取得
try {
  $count_sql = 'SELECT COUNT(*) FROM sales_reps';
  $stmt = $pdo->query($count_sql);
  $$total_SalesPerson = (int) $stmt->fetchColumn();
} catch (Exception $e) {
  error_log("Database Error (Count) in index.php: " . $e->getMessage());
}
$total_pages = ceil($$total_SalesPerson / ITEMS_PER_PAGE);
$offset = ($current_page - 1) * ITEMS_PER_PAGE;
// ページ番号が大きすぎる場合の調整
if ($offset >= $total_SalesPerson && $total_SalesPerson > 0) {
  $current_page = max(1, $total_pages);
  $offset = ($current_page - 1) * ITEMS_PER_PAGE;
}
$sales = [];
// 営業担当データを取得
try {
  $sql = 'SELECT id, name, created_at, updated_at FROM sales_reps ORDER BY created_at DESC
  LIMIT :limit OFFSET :offset';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {

  $errorMessage = "顧客情報の読み込み中にエラーが発生しました。";
  error_log("Database Error in index.php: " . $e->getMessage());
}

?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php';
?>

<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <main class="flex-1 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">営業担当一覧</h1>
      <!-- 新規追加ボタン -->
      <div class="flex justify-end mb-4">
        <a href="/app/SalesPerson/create.php"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md flex items-center">
          <i class="fas fa-plus mr-2"></i> 新規担当追加
        </a>
      </div>

      <!-- PC用テーブル表示 -->
      <div class="hidden md:block bg-white shadow-xl rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">担当名</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">登録日</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">最終更新日</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($sales as $sale): ?>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <?= htmlspecialchars($sale['name']) ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= date('Y年m月d日 ', strtotime($sale['created_at'])) ?>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <?= date('Y年m月d日 ', strtotime($sale['updated_at'])) ?>
                  </td>
                  <!-- 操作ボタン -->
                  <td class="px-6 py-4 whitespace-nowrap  text-sm font-medium">
                    <a href="/app/SalesPerson/edit.php?id=<?= $sale['id'] ?>"
                      class="text-indigo-600 hover:text-indigo-900 mr-4">編集</a>
                    <form action="/app/SalesPerson/delete.php" method="POST" class="inline"
                      onsubmit="return confirm('「<?= htmlspecialchars($sale['name']) ?>」を削除しますか？');">
                      <input type="hidden" name="id" value="<?= $sale['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                      <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- スマホ用カード表示 -->
      <div class="md:hidden space-y-4">
        <?php foreach ($sales as $sale): ?>
          <div class="bg-white shadow rounded-lg p-4">
            <div class="flex justify-between mb-1"><span
                class="font-semibold">担当名:</span><span><?= htmlspecialchars($sale['name']) ?></span></div>
            <div class="flex justify-between mb-1"><span
                class="font-semibold">登録日:</span><span><?= date('Y年m月d日 H:i', strtotime($sale['created_at'])) ?></span>
            </div>
            <div class="flex justify-between mb-1"><span
                class="font-semibold">最終更新日:</span><span><?= date('Y年m月d日 H:i', strtotime($sale['updated_at'])) ?></span>
            </div>
            <div class="flex justify-end space-x-2 mt-2">
              <a href="/app/SalesPerson/edit.php?id=<?= $sale['id'] ?>"
                class="text-indigo-600 hover:text-indigo-900 mr-4">編集</a>
              <form action="/app/SalesPerson/delete.php" method="POST" class="inline"
                onsubmit="return confirm('「<?= htmlspecialchars($sale['name']) ?>」を削除しますか？');">
                <input type="hidden" name="id" value="<?= $sale['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" class="text-red-600 hover:text-red-900">削除</button>
              </form>
              </td>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/render_pagination.php'; ?>
    </div>
  </main>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>