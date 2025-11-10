<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
// 現在アクティブなページをサイドバーに伝えるための変数 (このページでは不要ですが、構造を維持)
$current_page = 'Customers';

// エラーメッセージを格納する配列
$errors = [];

// 共通ファイルの読み込み (パスを修正)
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

// 1. GETパラメータからIDを取得
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
  // IDが無効な場合は一覧ページにリダイレクト
  header('Location: index.php');
  exit;
}

$rules = [
  'company_name' => ['required' => true, 'max' => 255],
  'email' => ['required' => true, 'max' => 255, 'email' => true]
];

// 2. フォーム送信時 (POST) の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // フォームから送信されたデータを取得
  $formData = [
    'id' => $id,
    'company_name' => trim($_POST['company_name'] ?? ''),
    'email' => trim($_POST['email'] ?? '')
  ];

  // バリデーションの実行
  $errors = validateForm($formData, $rules, [], $pdo, 'customers', $id);

  // エラーがなければデータベースに保存
  if (empty($errors)) {
    try {
      // トランザクション開始
      $pdo->beginTransaction();

      // プリペアドステートメントでデータを更新 (UPDATE)
      $stmt = $pdo->prepare("UPDATE customers SET company_name = :company_name, email = :email, updated_at = NOW() WHERE id = :id");
      $stmt->execute([
        ':company_name' => $formData['company_name'],
        ':email' => $formData['email'],
        ':id' => $id,
      ]);

      // すべての処理が成功したらコミット
      $pdo->commit();

      // フラッシュメッセージをセッションに保存
      $_SESSION['flash_message'] = '顧客情報を更新しました。';

      // 更新後、顧客一覧ページへリダイレクト
      header('Location: index.php');
      exit;

    } catch (PDOException $e) {
      // エラーが発生したらロールバック
      $pdo->rollBack();
      // データベースエラーが発生した場合
      $errors['general'] = 'データベースエラーが発生しました: ';
      error_log('Database Error: ' . $e->getMessage());
    }
  }
} else {
  // 3. 初期表示 (GET) の処理
  try {
    // IDを元に顧客情報を取得
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $customer = $stmt->fetch();

    if (!$customer) {
      // 顧客が見つからない場合は一覧へ
      header('Location: index.php');
      exit;
    }
    // 取得したデータをフォームの初期値として設定
    $formData = $customer;

  } catch (PDOException $e) {
    $errors['general'] = '顧客情報の取得中にエラーが発生しました: ' . $e->getMessage();
    // エラー発生時はフォームを空にする
    $formData = [
      'id' => $id,
      'company_name' => '',
      'email' => ''
    ];
  }
}
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
          顧客情報編集
        </h1>

        <!-- 一般的なエラー表示 -->
        <?php if (isset($errors['general'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4" role="alert">
          <strong class="font-bold">エラーが発生しました:</strong>
          <span class="block sm:inline"><?= htmlspecialchars($errors['general']) ?></span>
        </div>
        <?php endif; ?>

        <!-- フォーム -->
        <form method="POST" action="edit.php?id=<?= htmlspecialchars($id) ?>" novalidate>
          <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']) ?>">
          <!-- 法人名 -->
          <div class="mb-6">
            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
              法人名 <span class="text-red-500">*</span>
            </label>
            <input type="text" id="company_name" name="company_name"
              value="<?= htmlspecialchars($formData['company_name']) ?>"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['company_name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="株式会社〇〇" maxlength="30">
            <?php if (isset($errors['company_name'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['company_name']) ?></p>
            <?php endif; ?>
          </div>

          <!-- メールアドレス -->
          <div class="mb-6">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
              メールアドレス <span class="text-red-500">*</span>
            </label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['email']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="example@example.com" maxlength="30">
            <?php if (isset($errors['email'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['email']) ?></p>
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
              顧客情報を更新
            </button>
          </div>
        </form>
      </div>
  </main>
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php';
?>