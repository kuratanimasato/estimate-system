<?php
/**
 * ページネーションのHTMLを出力する関数。
 * このファイルは、以下の変数が定義されているページから require して使用することを想定しています。
 * - $total_pages: 総ページ数 (必須)
 * - $current_page: 現在のページ番号 (必須)
 * - $total_names: 総件数 (必須) - または $total_customers など、使用する変数名に合わせて変更してください。
 * - ITEMS_PER_PAGE: 1ページあたりの表示件数 (必須)
 */
// 総ページ数が1以下の場合は何も表示しない
if (!isset($total_pages) || $total_pages <= 1) {
  return;
}

// 総件数の変数名が $total_customers の可能性もあるため、どちらか存在する方を使います
$display_total_count = $total_names ?? $total_customers ?? 0;
?>

<nav aria-label="ページナビゲーション" class="flex justify-center mt-6">
  <ul class="flex items-center space-x-1 text-sm">
    <li class="<?php echo ($current_page <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
      <a class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-200"
        href="<?php echo ($current_page <= 1) ? '#' : '?page=' . ($current_page - 1); ?>" aria-label="Previous">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>

    <?php
    // 表示するページ番号の範囲を計算（前後2ページ）
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);

    // 1ページ目と省略記号
    if ($start_page > 1) {
      echo '<li class=""><a class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-200 text-gray-700" href="?page=1">1</a></li>';
      if ($start_page > 2) {
        echo '<li class="px-2 text-gray-500">...</li>';
      }
    }
    ?>

    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
    <li class="">
      <a class="flex items-center justify-center w-8 h-8 rounded 
                         <?php echo ($i === $current_page)
                           ? 'bg-blue-600 text-white font-semibold'
                           : 'hover:bg-gray-200 text-gray-700'; ?>" href="?page=<?php echo $i; ?>">
        <?php echo $i; ?>
      </a>
    </li>
    <?php endfor; ?>

    <?php
    // 最終ページと省略記号
    if ($end_page < $total_pages) {
      if ($end_page < $total_pages - 1) {
        echo '<li class="px-2 text-gray-500">...</li>';
      }
      echo '<li class=""><a class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-200 text-gray-700" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    ?>

    <li class="<?php echo ($current_page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
      <a class="flex items-center justify-center w-8 h-8 rounded hover:bg-gray-200"
        href="<?php echo ($current_page >= $total_pages) ? '#' : '?page=' . ($current_page + 1); ?>" aria-label="Next">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>
  </ul>
</nav>