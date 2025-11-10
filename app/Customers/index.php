<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// 現在アクティブなページをサイドバーに伝えるための変数
$current_page_name = 'Customers';

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

// --- ページネーション設定 ---
const ITEMS_PER_PAGE = 8;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$total_customers = 0;
// 1. 全件数を取得
try {
  $count_sql = 'SELECT COUNT(*) FROM customers';
  $stmt = $pdo->query($count_sql);
  $total_customers = (int) $stmt->fetchColumn();
} catch (Exception $e) {
  error_log("Database Error (Count) in index.php: " . $e->getMessage());
}
$total_pages = ceil($total_customers / ITEMS_PER_PAGE);
$offset = ($current_page - 1) * ITEMS_PER_PAGE;
// ページ番号が大きすぎる場合の調整
if ($offset >= $total_customers && $total_customers > 0) {
  $current_page = max(1, $total_pages);
  $offset = ($current_page - 1) * ITEMS_PER_PAGE;
}
$customers = [];
// 顧客データを取得
try {
  $sql = 'SELECT id, company_name, email, created_at, updated_at FROM customers ORDER BY created_at DESC
  LIMIT :limit OFFSET :offset';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {

  $errorMessage = "顧客情報の読み込み中にエラーが発生しました。";
  error_log("Database Error in index.php: " . $e->getMessage());
}

?>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php';
?>

<div class="flex flex-1 relative">
  <?php
  // 2. 共通サイドバーの読み込み (headerの次に配置)
  require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php';
  ?>
  <!-- メインコンテンツ -->
  <main class="flex-1 p-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <h1 class="text-3xl font-extrabold text-gray-900 mb-6">顧客一覧</h1>


      <!-- 新規追加ボタン -->
      <div class="flex justify-end mb-4">
        <a href="/app/customers/create.php"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out flex items-center">
          <i class="fas fa-plus mr-2"></i>
          新規顧客追加
        </a>
      </div>

      <!-- 顧客一覧テーブル -->
      <div class="hidden md:block bg-white shadow-xl rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <!-- テーブルヘッダー -->
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  法人名
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider ">
                  メールアドレス
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  登録日
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  最終更新日
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> 操作</th>
              </tr>
            </thead>
            <!-- テーブルボディ -->
            <tbody class="bg-white divide-y divide-gray-200">
              <?php if (empty($customers)): ?>
                <tr>
                  <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                    登録されている顧客情報はありません。
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                  <tr>
                    <!-- 会社名 -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      <?= htmlspecialchars($customer['company_name']) ?>
                    </td>
                    <!-- メールアドレス -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 ">
                      <?= htmlspecialchars($customer['email']) ?>
                    </td>
                    <!-- 登録日 -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <?php $createdAt = new DateTime($customer['created_at']); ?>
                      <?= htmlspecialchars($createdAt->format('Y年m月d日')) ?>
                    </td>
                    <!-- 最終更新日 -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <?php $updatedAt = new DateTime($customer['updated_at']); ?>
                      <?= htmlspecialchars($updatedAt->format('Y年m月d日')) ?>
                    </td>
                    <!-- 操作ボタン -->
                    <td class="px-6 py-4 whitespace-nowrap  text-sm font-medium">
                      <a href="/app/Customers/edit.php?id=<?= $customer['id'] ?>"
                        class="text-indigo-600 hover:text-indigo-900 mr-4 transition duration-150">
                        編集
                      </a>
                      <a href="delete.php?id=<?= $customer['id'] ?>"
                        onclick="return confirm('「<?= htmlspecialchars($customer['company_name']) ?>」を本当に削除してもよろしいですか？');"
                        class="text-red-600 hover:text-red-900 transition duration-150">
                        削除
                      </a>
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
        <?php foreach ($customers as $customer): ?>
          <div class="bg-white shadow rounded-lg p-4">
            <div class="flex justify-between mb-1"><span
                class="font-semibold">法人名:</span><span><?= htmlspecialchars($customer['company_name']) ?></span></div>
            <div class="flex justify-between mb-1"><span
                class="font-semibold">メールアドレス:</span><span><?= htmlspecialchars($customer['email']) ?></span></div>
            <div class="flex justify-between mb-1"><span
                class="font-semibold">登録日:</span><span><?= date('Y年m月d日 H:i', strtotime($customer['created_at'])) ?></span>
            </div>
            <div class="flex justify-between mb-1"><span
                class="font-semibold">最終更新日:</span><span><?= date('Y年m月d日 H:i', strtotime($customer['updated_at'])) ?></span>
            </div>
            <div class="flex justify-end space-x-2">
              <a href="/app/Customers/edit.php?id=<?= $customer['id'] ?>"
                class="text-indigo-600 hover:text-indigo-900">編集</a>
              <a href="/app/Customers/delete.php?id=<?= $customer['id'] ?>"
                onclick="return confirm('「<?= htmlspecialchars($customer['company_name']) ?>」を削除しますか？');"
                class="text-red-600 hover:text-red-900">削除</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/render_pagination.php';
    ?>
  </main>
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php';
?>