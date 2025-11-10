<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/auth_basic.php';

?>

<!-- コンテンツエリア -->
<div class="flex flex-1 relative">

  <!-- サイドバー -->
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>

  <!-- メインコンテンツ -->
  <main class="flex-1 p-6">
    <h1 class="text-2xl font-bold mb-6">ダッシュボード</h1>
    <!-- 新規作成 -->
    <section>
      <h2 class="text-lg font-semibold mb-3">作成画面</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          class="bg-white shadow rounded p-6 flex flex-col items-center justify-center hover:bg-blue-50 cursor-pointer">
          <i class="fa-solid fa-file-lines text-3xl"></i>
          <div class="mt-2 font-medium">
            <a href="/app/Quotes/index.php">見積書</a>
          </div>
        </div>
        <div
          class="bg-white shadow rounded p-6 flex flex-col items-center justify-center hover:bg-blue-50 cursor-pointer">
          <i class="fa-solid fa-file-invoice-dollar text-3xl"></i>
          <div class="mt-2 font-medium">
            <a href="/app/Invoices/index.php">請求書</a>
          </div>
        </div>
        <div
          class="bg-white shadow rounded p-6 flex flex-col items-center justify-center hover:bg-blue-50 cursor-pointer">
          <i class="fa-solid fa-truck text-3xl"></i>
          <div class="mt-2 font-medium">
            <a href="/app/Delivery_notes/index.php">納品書</a>
          </div>
        </div>
        <div
          class="bg-white shadow rounded p-6 flex flex-col items-center justify-center hover:bg-blue-50 cursor-pointer">
          <i class="fa-solid fa-receipt text-3xl"></i>
          <div class="mt-2 font-medium">
            <a href="/app/Receipts/index.php">
              領収書
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- 管理系 -->
    <section class="mt-9">
      <h2 class="text-lg font-semibold mb-2">管理・参照</h2>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- 商品マスタ -->
        <a href="../app/Products/index.php"
          class="bg-white shadow rounded p-6 flex flex-col justify-between hover:bg-gray-50 cursor-pointer">
          <i class="fa-solid fa-box-open text-2xl"></i>
          <div class="mt-2 font-medium">商品マスタ</div>
          <p class="text-sm text-gray-500 mt-1">20項目の単価管理</p>
        </a>

        <!-- 顧客管理 -->
        <a href="../app/Customers/index.php"
          class="bg-white shadow rounded p-6 flex flex-col justify-between hover:bg-gray-50 cursor-pointer">
          <i class="fa-solid fa-users text-2xl"></i>
          <div class="mt-2 font-medium">顧客管理マスタ</div>
          <p class="text-sm text-gray-500 mt-1">顧客情報の追加・編集</p>
        </a>

        <!-- 営業担当マスタ -->
        <a href="../app/SalesPerson/index.php"
          class="bg-white shadow rounded p-6 flex flex-col justify-between hover:bg-gray-50 cursor-pointer">
          <i class="fa-solid fa-user-tie text-2xl"></i>
          <div class="mt-2 font-medium">営業担当マスタ</div>
          <p class="text-sm text-gray-500 mt-1">営業マンの登録・管理</p>
        </a>

    </section>

    <!-- 下段 -->
    <section class="mt-8">
      <h2 class="text-lg font-semibold mb-3">その他</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white shadow rounded p-6 flex flex-col justify-between hover:bg-gray-50 cursor-pointer">
          <a href="/app/Copy/index.php">
            <i class="fa-solid fa-folder-open text-2xl"></i>
            <div class="mt-2 font-medium">見積もりのコピー</div>
            <p class="text-sm text-gray-500 mt-1">見積もりのコピー機能</p>
          </a>
        </div>
      </div>
    </section>
  </main>
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php';
?>