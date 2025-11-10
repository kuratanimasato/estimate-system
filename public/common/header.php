<!doctype html>
<html lang="ja">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>見積もり・請求書 かんたん作成システム</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script type="module">
    import "https://esm.sh/twind@4.1.0/shim"
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Font Awesome 読み込み -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
      type="text/css" />
  </head>

  <body class="min-h-screen flex flex-col bg-gray-50">

    <!-- ヘッダー -->
    <header class="bg-gray-800 text-white flex items-center justify-between px-4 py-3 relative z-100 w-full">
      <a href="/public/index.php" class="text-xl font-bold">見積もり・請求書 システム</a>
      <!-- モバイル用メニュー -->
      <button id="menuBtn" class="block md:hidden p-2 rounded hover:bg-gray-700">
        <i class="fa-solid fa-bars"></i>
      </button>
    </header>