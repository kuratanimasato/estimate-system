<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

$id = $_GET['id'] ?? null;
if (!$id || !ctype_digit($id)) {
  die('不正なアクセスです。');
}

?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<body class="bg-gray-100 min-h-screen flex flex-col">
  <!-- メイン領域 -->
  <main class="flex-grow flex items-center justify-center p-4 sm:p-6">
    <div class="w-full max-w-xl bg-white p-10 md:p-8 rounded-lg shadow-lg">
      <h1 class="text-2xl font-bold text-gray-800 mb-4 text-center">メール送信完了</h1>

      <?php
      if (!empty($_SESSION['flash_message'])) {
        echo '<p class="bg-green-100 text-green-800 p-4 rounded-lg border border-green-400 mb-6 text-base">';
        echo htmlspecialchars($_SESSION['flash_message']);
        echo '</p>';
        unset($_SESSION['flash_message']);
      }
      ?>

      <a class="inline-block mt-4 text-blue-600 hover:text-blue-800 text-lg transition-colors duration-150 font-medium text-center"
        href="/app/Delivery_notes/view.php?id=<?= urlencode($id) ?>">
        納品書詳細に戻る
      </a>
    </div>
  </main>
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>
</body>