<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/csrf.php';
// 現在アクティブなページをサイドバーに伝えるための変数 (このページでは不要ですが、構造を維持)
$current_page = 'SalesPerson';

// エラーメッセージを格納する配列
$errors = [];
$formData = [
  'name' => '',
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';
$rules = [
  'name' => [
    'required' => true,
    'max' => 255,
    'unique' => true,
    'message' => [
      'required' => '名前は必ず入力してください。',
      'unique' => '名前は既に登録されています。'
    ]
  ],
];

$csrf_token = get_csrf_token();
// フォーム送信後の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token($_POST["csrf_token"] ?? null)) {
    echo "不正なリクエストです";
    exit();
  }
  // フォームから送信されたデータを取得
  $formData = [
    'name' => htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
  ];


  // バリデーションルールの定義
  $errors = validateForm($formData, $rules, [], $pdo, 'sales_reps');

  // エラーがなければデータベースに保存
  if (empty($errors)) {
    try {
      // トランザクション開始
      $pdo->beginTransaction();
      // プリペアドステートメントでデータを挿入
      $stmt = $pdo->prepare("INSERT INTO sales_reps (name,  created_at, updated_at) VALUES (:name, NOW(), NOW())");
      $stmt->execute([
        ':name' => $formData['name'],
      ]);
      $pdo->commit();

      // フラッシュメッセージをセッションに保存
      $_SESSION['flash_message'] = '担当を追加しました。';
      // 保存後、顧客一覧ページへリダイレクト
      header('Location: index.php');
      exit;

    } catch (PDOException $e) {
      // データベースエラーが発生した場合
      $pdo->rollBack();
      $errors[] = 'データベースエラーが発生しました: ' . $e->getMessage();
    }
  }
}
$name = htmlspecialchars($formData['name']);
?>
<?php
// 1. 共通ヘッダーの読み込み (ナビゲーションバーなどが含まれることを想定)
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php';
?>
<div class="flex flex-1 relative">
  <?php
  // 2. 共通サイドバーの読み込み (headerの次に配置)
  require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php';
  ?>
  <main class="flex-1 p-6 bg-gray-50 w-full">
    <div class="max-w-3xl mx-auto flex items-center justify-center min-h-[calc(100vh-4rem)]">
      <div class="w-full bg-white shadow-xl rounded-xl p-8">
        <h1 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">
          新規担当名登録
        </h1>
        <!-- 一般的なエラー表示 -->
        <?php if (isset($errors['general'])): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
            <strong class="font-bold">エラーが発生しました:</strong>
            <ul class="mt-1 list-disc list-inside">
              <li><?= htmlspecialchars($errors['general']) ?></li>
            </ul>
          </div>
        <?php endif; ?>
        <!-- フォーム -->
        <form id="formValidation" method="POST" action="create.php">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
          <!-- 担当名-->
          <div class="mb-6">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
              担当名 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="id" name="name" value="<?= htmlspecialchars($formData['name']) ?>"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="担当名を入力してください" maxlength="30">
            <?php if (isset($errors['name'])): ?>
              <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['name']) ?></p>
            <?php endif; ?>
          </div>

          <!-- ボタン -->
          <div class="flex justify-end space-x-4 pt-4">
            <a href="index.php"
              class="inline-flex justify-center py-2 px-6 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50">
              一覧に戻る
            </a>
            <button type="submit"
              class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
              担当情報を登録
            </button>
          </div>
        </form>
      </div>
  </main>
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php';
?>