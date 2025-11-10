<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

$current_page = 'Products';
$errors = [];

// GETパラメータからID取得
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  header('Location: index.php');
  exit;
}

// DBから既存データ取得
try {
  $stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
  $stmt->execute([':id' => $id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$item) {
    $_SESSION['flash_message'] = '対象の商品が存在しません。';
    header('Location: index.php');
    exit;
  }
} catch (PDOException $e) {
  error_log($e->getMessage());
  $_SESSION['flash_message'] = 'データ取得中にエラーが発生しました。';
  header('Location: index.php');
  exit;
}

// 初期値
$formData = [
  'item_name' => $item['item_name'],
  'unit_price' => $item['unit_price'],
  'cost_type' => $item['cost_type'],
];

$rules = [
  'item_name' => [
    'required' => true,
    'max' => 255,
    'unique' => true,
    'message' => [
      'required' => '商品名は必ず入力してください。',
      'unique' => '商品名は既に登録されています。'
    ]
  ],
  'unit_price' => [
    'required' => true,
    'numeric' => true,
    'message' => [
      'required' => '単価は必ず入力してください。',
      'numeric' => '単価は数値で入力してください。'
    ]
  ],
  'cost_type' => [
    'required' => true,
    'in' => ['月額利用料', '初期費用', '機材費'],
    'message' => [
      'required' => '費用区分を選択してください。',
      'in' => '費用区分が正しくありません。'
    ]
  ],

];

// POST送信時
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $formData = [
    'item_name' => trim($_POST['item_name'] ?? ''),
    'unit_price' => trim($_POST['unit_price'] ?? ''),
    'cost_type' => trim($_POST['cost_type'] ?? ''),
    'quantity' => trim($_POST['quantity'] ?? '')
  ];


  $errors = validateForm($formData, $rules, [], $pdo, 'items', $id);
  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("UPDATE items SET item_name = :item_name, unit_price= :unit_price, cost_type = :cost_type, updated_at = NOW() WHERE id = :id");
      $stmt->execute([
        ':id' => $id,
        ':item_name' => $formData['item_name'],
        ':unit_price' => round($formData['unit_price'], 2),
        ':cost_type' => $formData['cost_type'],
      ]);
      $_SESSION['flash_message'] = '商品を更新しました。';
      header('Location: index.php');
      exit;
    } catch (PDOException $e) {
      $errors['general'] = 'データベースエラーが発生しました。';
      error_log('Database Error: ' . $e->getMessage());
    }
  }
}
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>
<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>
  <main class="flex-1 p-6 bg-gray-50 w-full">
    <div class="max-w-3xl mx-auto flex items-center justify-center min-h-[calc(100vh-4rem)]">
      <div class="w-full bg-white shadow-xl rounded-xl p-8">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">商品情報編集</h1>

        <?php if (isset($errors['general'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4">
          <?= htmlspecialchars($errors['general']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
          <!-- 商品名 -->
          <div class="mb-6">
            <label for="item_name" class="block text-sm font-medium text-gray-700 mb-2">
              商品名 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="item_name" name="item_name" value="<?= htmlspecialchars($formData['item_name']) ?>"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['item_name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg"
              placeholder="クラウドサービス利用料">
            <?php if (isset($errors['item_name'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['item_name']) ?></p>
            <?php endif; ?>
          </div>

          <!-- 単価 -->
          <div class="mb-6">
            <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-2">
              単価 (円) <span class="text-red-500">*</span>
            </label>
            <input type="number" step="1" min="0" id="unit_price" name="unit_price"
              value="<?= htmlspecialchars((int) $formData['unit_price']) ?>"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['unit_price']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg"
              placeholder="例: 1200">
            <?php if (isset($errors['unit_price'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['unit_price']) ?></p>
            <?php endif; ?>
          </div>

          <!-- 費用区分 -->
          <div class="mb-6">
            <label for="cost_type" class="block text-sm font-medium text-gray-700 mb-2">
              費用区分 <span class="text-red-500">*</span>
            </label>
            <select id="cost_type" name="cost_type"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['cost_type']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg">
              <option value="">選択してください</option>
              <option value="月額利用料" <?= $formData['cost_type'] === '月額' ? 'selected' : '' ?>>月額利用料</option>
              <option value="初期費用" <?= $formData['cost_type'] === '初期' ? 'selected' : '' ?>>初期費用</option>
              <option value="機材費" <?= $formData['cost_type'] === '機材' ? 'selected' : '' ?>>機材費</option>
            </select>
            <?php if (isset($errors['cost_type'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['cost_type']) ?></p>
            <?php endif; ?>
          </div>

          <!-- ボタン -->
          <div class="flex justify-end space-x-4 pt-4">
            <a href="index.php" class="py-2 px-6 border border-gray-300 rounded-lg bg-white">一覧に戻る</a>
            <button type="submit" class="py-2 px-6 rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">更新
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>