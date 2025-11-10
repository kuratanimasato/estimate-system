<!-- オーバーレイ（モバイル時のみ表示） -->
<div id="overlay" class="hidden fixed inset-0 bg-gray-100 bg-opacity-50 z-40 md:hidden">

</div>

<!-- サイドバー -->
<aside id="sidebar" class="bg-white shadow-lg w-64 p-4 space-y-4 fixed inset-y-0 left-0 transform -translate-x-full transition-transform duration-200 z-50
      md:relative md:translate-x-0 md:flex-shrink-0 md:z-auto">
  <nav class="space-y-2">
    <a href="/public/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
        class="fa-solid fa-chart-line mr-2"></i>
      ダッシュボード</a>
    <a href="/app/Quotes/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
        class="fa-solid fa-file-lines mr-2"></i> 見積書</a>
    <a href="/app/Invoices/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
        class="fa-solid fa-file-invoice-dollar mr-2"></i>
      請求書</a>
    <a href="/app/Delivery_notes/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
        class="fa-solid fa-truck mr-2"></i> 納品書</a>
    <a href="/app/Receipts/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
        class="fa-solid fa-receipt mr-2"></i> 領収書</a>


    <!-- マスタ管理メニュー -->
    <div x-data="{ open: false }" x-id="['master-dropdown']" class="relative">
      <button x-ref="button" x-on:click="open = ! open" :aria-expanded="open" :aria-controls="$id('master-dropdown')"
        type="button" class="w-full flex items-center px-3 py-2 rounded hover:bg-gray-200 text-left">
        <i class="fa-solid fa-screwdriver-wrench mr-2"></i>
        <span class="flex-1">マスタ管理</span>
        <svg class="size-4 ml-2 transition-transform duration-200" :class="{ 'rotate-180': open, 'rotate-0': !open }"
          xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
          <path fill-rule="evenodd"
            d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
            clip-rule="evenodd" />
        </svg>
      </button>
      <div x-ref="panel" x-show="open" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95" :id="$id('master-dropdown')" x-cloak
        class="pl-6 space-y-1 mt-1 text-sm">
        <a href="/app/Products/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
            class="fa-solid fa-box-open mr-2"></i>
          商品マスタ</a>
        <a href="/app/Customers/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
            class="fa-solid fa-users mr-2"></i> 顧客マスタ</a>
        <a href="/app/SalesPerson/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
            class="fa-solid fa-user-tie mr-2"></i>
          営業担当マスタ</a>
      </div>
    </div>
    <a href="/app/Copy/index.php" class="block px-3 py-2 rounded hover:bg-gray-200"><i
        class="fa-solid fa-folder-open mr-2"></i>
      見積もりコピー</a>
  </nav>
</aside>