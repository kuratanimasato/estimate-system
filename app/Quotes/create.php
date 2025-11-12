<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/csrf.php';
// URLパラメータからdocument_typeを取得
$url_document_type = $_GET['type'] ?? null;


// 現在アクティブなページをサイドバーに伝えるための変数
$current_page = 'Quotes';
// DBから各種情報を取得 (itemsテーブルも必要と仮定)
$customers = $pdo->query("SELECT id, company_name, email FROM customers ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);
$sales_reps = $pdo->query("SELECT id, name FROM sales_reps ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, item_name, unit_price FROM items ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

// フォームの初期値（quotesテーブルのカラム）
$formData = [
  'document_type' => '見積書', // デフォルトを納品書に設定 (必要に応じて変更)
  'customer_id' => '',
  'customer_name' => '',
  'customer_email' => '',
  'sales_rep_id' => '',
  'sales_rep_name' => '',
  'issue_date' => date('Y-m-d'), // 発行日の初期値設定
  'expiration_date' => date('Y-m-d', strtotime('+30 days')), // 有効期限の初期値設定
  'subtotal' => 0,
  'tax' => 0,
  'total_amount' => 0,
  'status' => '作成中',
  'is_copy' => 0
];

// 明細データ取得
$stmtItems = $pdo->prepare("SELECT * FROM quote_details WHERE quote_id = :quote_id ORDER BY order_index");
$copyItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
$stmtItems->execute([':quote_id' => $_GET['id'] ?? 0]);
foreach ($copyItems as $item) {
  $itemsToDisplay[] = [
    '_id' => microtime(true) + rand(), // Alpine.js のキー用
    'item_id' => $item['item_id'],
    'item_name' => $item['item_name'],
    'unit_price' => $item['unit_price'],
    'cost_type' => $item['cost_type'],
    'quantity' => $item['quantity'],
  ];
}

// エラーメッセージを格納する配列
$errors = [];




// バリデーションルール
// subtotal, tax, total_amountはサーバーサイドで計算するため、requiredは除外
$rules = [
  'document_type' => [
    'required' => true,
    'in' => ['見積書', '請求書', '納品書', '領収書'],
    'message' => [
      'required' => '書類タイプを選択してください。',
      'in' => '書類タイプの値が不正です。'
    ]
  ],
  'customer_id' => [
    'required' => true,
    'exists' => 'customers.id',
    'message' => [
      'required' => '顧客を選択してください。',
      'exists' => '選択された顧客は存在しません。'
    ]
  ],
  'customer_name' => [
    'required' => true,
    'max' => 255,
    'message' => [
      'required' => '顧客名は必ず入力してください。',
      'max' => '顧客名は255文字以内で入力してください。'
    ]
  ],
  'customer_email' => [
    'required' => true,
    'email' => true,
    'max' => 255,
    'message' => [
      'required' => 'メールアドレスは必ず入力してください。',
      'email' => 'メールアドレスの形式が正しくありません。',
      'max' => 'メールアドレスは255文字以内で入力してください。'
    ]
  ],
  'sales_rep_id' => [
    'required' => true,
    'exists' => 'sales_reps.id',
    'message' => [
      'required' => '担当者を選択してください。',
      'exists' => '選択された担当者は存在しません。'
    ]
  ],
  'sales_rep_name' => [
    'required' => true,
    'max' => 255,
    'message' => [
      'required' => '担当者名は必ず入力してください。',
      'max' => '担当者名は255文字以内で入力してください。'
    ]
  ],
  'issue_date' => [
    'required' => true,
    'date' => true,
    'message' => [
      'required' => '発行日は必ず入力してください。',
      'date' => '発行日の日付形式が正しくありません。'
    ]
  ],
  'expiration_date' => [
    'nullable' => true,
    'date' => true,
    'message' => [
      'date' => '有効期限の日付形式が正しくありません。'
    ]
  ],
  'status' => [
    'in' => ['作成中', '提出済', '承認済'],
    'message' => [
      'in' => 'ステータスの値が不正です。'
    ]
  ],
  // 計算値のため、必須チェックはしない
  'subtotal' => ['numeric' => true, 'min' => 0],
  'tax' => ['numeric' => true, 'min' => 0],
  'total_amount' => ['numeric' => true, 'min' => 0],
];
$fieldLabels = [
  'document_type' => '書類タイプ',
  'customer_id' => '顧客',
  'customer_name' => '顧客名',
  'customer_email' => '顧客メールアドレス',
  'sales_rep_id' => '担当者',
  'sales_rep_name' => '担当者名',
  'issue_date' => '発行日',
  'expiration_date' => '有効期限',
  'subtotal' => '小計',
  'tax' => '消費税',
  'total_amount' => '合計金額',
  'status' => 'ステータス'
];
// フォーム送信後の処理
$csrf_token = get_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!validate_csrf_token($_POST["csrf_token"] ?? null)) {
    echo "不正なリクエストです";
    exit();
  }

  // フォームデータをformDataに反映（値のサニタイズは外部関数に依存）
  foreach (array_keys($formData) as $key) {
    if (isset($_POST[$key])) {
      $formData[$key] = trim($_POST[$key]);
    }
  }

  // quotes本体のバリデーション
  $errors = validateForm($formData, $rules, [], $pdo, 'quotes');

  $excludeId = isset($_POST['id']) ? (int) $_POST['id'] : null;

  // 明細（quote_details）のバリデーション
  $items = $_POST['items'] ?? [];
  if (empty($items)) {
    $errors['items'] = '明細を最低1行追加してください。';
  } else {
    foreach ($items as $index => $item) {
      // 数量、単価が数値で、0以上であることを確認（必須チェックも兼ねる）
      if (!is_numeric($item['quantity']) || $item['quantity'] <= 0) {
        $errors['items'][$index]['quantity'] = '数量を正しく入力してください。';
      }
      if (!is_numeric($item['unit_price']) || $item['unit_price'] < 0) {
        $errors['items'][$index]['unit_price'] = '単価を正しく入力してください。';
      }
      if (!in_array($item['cost_type'], ['月額利用料', '初期費用', '機材費'])) {
        $errors['items'][$index]['cost_type'] = '費用の区分が不正です。';
      }
      if (isset($errors['items']) && !empty(array_filter($errors['items']))) {
        $errors['items'] = '明細に不正な値があります。';
      }
    }
  }

  // エラーがなければ登録処理に進む
  $errors = validateForm($formData, $rules, [], $pdo, 'quote');
  if (empty($errors)) {
    // 明細の計算と登録
    $subtotal = 0;
    foreach ($items as $item) {
      $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
    }

    $tax = round($subtotal * 0.10, 2); // 10%で計算し、四捨五入（小数点第2位まで）
    $total_amount = $subtotal + $tax;

    try {
      $pdo->beginTransaction();

      // 1. quotes 本体登録
      // 小計・税・合計を計算値で上書きしてから登録
      $formData['subtotal'] = $subtotal;
      $formData['tax'] = $tax;
      $formData['total_amount'] = $total_amount;

      // updated_at は SQL スキーマにないため削除（あれば残してください）
      $stmt = $pdo->prepare("
    INSERT INTO quotes 
        (document_type, customer_id, customer_name, customer_email, sales_rep_id, sales_rep_name,
        issue_date, expiration_date, subtotal, tax, total_amount, is_copy, status, created_at)
    VALUES
        (:document_type, :customer_id, :customer_name, :customer_email, :sales_rep_id, :sales_rep_name,
        :issue_date, :expiration_date, :subtotal, :tax, :total_amount, :is_copy, :status, NOW())
");

      // quotesに必要なデータのみを抽出して実行
      $executeData = array_intersect_key($formData, array_flip([
        'document_type',
        'customer_id',
        'customer_name',
        'customer_email',
        'sales_rep_id',
        'sales_rep_name',
        'issue_date',
        'expiration_date',
        'subtotal',
        'tax',
        'total_amount',
        'status',
        'is_copy'
      ]));

      $stmt->execute($executeData);
      $quote_id = $pdo->lastInsertId();

      // 2. quote_details 登録
      $stmtDetail = $pdo->prepare("
                INSERT INTO quote_details
                    (quote_id, item_id, item_name, unit_price, cost_type, quantity, line_total, order_index)
                VALUES
                    (:quote_id, :item_id, :item_name, :unit_price, :cost_type, :quantity, :line_total, :order_index)
            ");
      echo $stmt->queryString;

      foreach ($items as $index => $item) {
        $line_total = (float) $item['quantity'] * (float) $item['unit_price'];

        $stmtDetail->execute([
          ':quote_id' => $quote_id,
          ':item_id' => !empty($item['item_id']) ? (int) $item['item_id'] : 0,
          ':item_name' => $item['item_name'],
          ':unit_price' => round($item['unit_price'], 2),
          ':cost_type' => $item['cost_type'],
          ':quantity' => $item['quantity'],
          ':line_total' => round((float) $item['quantity'] * (float) $item['unit_price'], 2),
          ':order_index' => $index
        ]);
      }

      // ※ 元のコードにあった UPDATE 処理は、INSERT時に計算値を登録したので不要です。

      $pdo->commit();

      $_SESSION['flash_message'] = '書類を登録しました。';
      // 納品書一覧など、適切なページへリダイレクト
      header('Location: index.php');
      exit;

    } catch (PDOException $e) {
      $pdo->rollBack();
      $errors['general'] = 'データベースエラーが発生しました。時間を置いて再度お試しください。';
      $errors['general'] .= '<br>' . htmlspecialchars($e->getMessage());
      error_log('Database Error: ' . $e->getMessage());
      // エラー時に入力データを再表示するため、明細データを$formDataに復元（ここでは割愛）
    }
  }
}
// POSTデータがない場合、またはエラーでフォームを再表示する場合、初期値を上書き
$itemsToDisplay = isset($_POST['items']) ? $_POST['items'] : [];

?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>
<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>
  <main class="flex-1 p-6 bg-gray-50 w-full">
    <div class="max-w-3xl mx-auto bg-white shadow-xl rounded-xl p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">新規見積もり作成</h1>

      <?php if (isset($errors['general'])): ?>
        <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
          <?= htmlspecialchars($errors['general']) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="create.php"
        x-data="quoteForm(<?= htmlspecialchars(json_encode($formData)) ?>, <?= htmlspecialchars(json_encode($itemsToDisplay)) ?>, <?= htmlspecialchars(json_encode($products)) ?>)"
        @submit.prevent="submitForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
        <div class="mb-4">
          <label class="block font-medium text-sm mb-2">書類タイプ</label>
          <select name="document_type" x-model="form.document_type" class="w-full border rounded-lg px-3 py-2">
            <option value="">選択してください</option>
            <?php foreach (['見積書', '請求書', '納品書', '領収書'] as $type): ?>
              <option value="<?= $type ?>"><?= $type ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['document_type'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['document_type']) ?></p>
          <?php endif; ?>
        </div>
        <div class="mb-4">
          <label class="block font-medium text-sm mb-2">顧客</label>
          <select id="customer_id" name="customer_id" x-model.number="form.customer_id" @change="updateCustomer"
            class="w-full border rounded-lg px-3 py-2">
            <option value="">顧客を選択</option>
            <?php foreach ($customers as $customer): ?>
              <option value="<?= $customer['id'] ?>" data-name="<?= htmlspecialchars($customer['company_name']) ?>"
                data-email="<?= htmlspecialchars($customer['email']) ?>">
                <?= htmlspecialchars($customer['company_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="customer_name" :value="form.customer_name">
          <input type="hidden" name="customer_email" :value="form.customer_email">
          <?php if (isset($errors['customer_id'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['customer_id']) ?></p>
          <?php endif; ?>
        </div>

        <div class="mb-4">
          <label for="customer_email" class="block font-medium text-sm mb-2">
            顧客メールアドレス <span class="text-red-500">*</span>
          </label>
          <input type="email" id="customer_email" name="customer_email" x-model="form.customer_email"
            class="w-full border rounded-lg px-3 py-2 <?= isset($errors['customer_email']) ? 'border-red-500' : 'border-gray-300' ?>"
            placeholder="例: example@company.com">
          <?php if (isset($errors['customer_email'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['customer_email']) ?></p>
          <?php endif; ?>
        </div>
        <div class="mb-4">
          <label class="block font-medium text-sm mb-2">担当者</label>
          <select id="sales_rep_id" name="sales_rep_id" x-model.number="form.sales_rep_id" @change="updateSalesRep"
            class="w-full border rounded-lg px-3 py-2">
            <option value="">担当者を選択</option>
            <?php foreach ($sales_reps as $s): ?>
              <option value="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>">
                <?= htmlspecialchars($s['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="hidden" name="sales_rep_name" :value="form.sales_rep_name">
          <?php if (isset($errors['sales_rep_id'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['sales_rep_id']) ?></p>
          <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="mb-6">
            <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-2">
              発行日 <span class="text-red-500">*</span>
            </label>
            <input type="date" id="issue_date" name="issue_date" x-model="form.issue_date" @change="setExpirationDate"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['issue_date']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg">
            <?php if (isset($errors['issue_date'])): ?>
              <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['issue_date']) ?></p>
            <?php endif; ?>
          </div>
          <div class="mb-6">
            <label for="expiration_date" class="block text-sm font-medium text-gray-700 mb-2">
              有効期限（自動で発行日＋30日）
            </label>
            <input type="date" id="expiration_date" name="expiration_date" x-model="form.expiration_date"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['expiration_date']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg">
            <?php if (isset($errors['expiration_date'])): ?>
              <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['expiration_date']) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="mb-6">
          <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
            ステータス
          </label>
          <select name="status" id="status" x-model="form.status" class="w-full border rounded-lg px-3 py-2">
            <option value="作成中">作成中</option>
            <option value="提出済">提出済</option>
            <option value="承認済">承認済</option>
          </select>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-4 border-t pt-4">明細</h2>
        <?php if (isset($errors['items'])): ?>
          <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
            <?= htmlspecialchars($errors['items']) ?>
          </div>
        <?php endif; ?>

        <div class="mb-4">
          <label class="block font-medium text-sm mb-2">商品選択</label>
          <select x-model="selectedProductId" @change="addItem" class="w-full border rounded-lg px-3 py-2">
            <option value="">商品を選択すると明細に追加されます</option>
            <template x-for="product in products" :key="product.id">
              <option :value="product.id"
                x-text="product.item_name + ' (' + Number(product.unit_price).toLocaleString() + '円)'"></option>
            </template>
          </select>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full bg-white border border-gray-200">
            <thead>
              <tr class="bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                <th class="py-3 px-4 border-b">品名</th>
                <th class="py-3 px-4 border-b w-20">区分</th>
                <th class="py-3 px-4 border-b w-24">単価</th>
                <th class="py-3 px-4 border-b w-20">数量</th>
                <th class="py-3 px-4 border-b w-28 text-right">合計</th>
                <th class="py-3 px-4 border-b w-12"></th>
              </tr>
            </thead>
            <tbody id="quote-details-body">
              <template x-for="(item, index) in form.items" :key="item._id">
                <tr>
                  <td class="py-2 px-4 border-b">
                    <input type="hidden" :name="'items[' + index + '][item_id]'" :value="item.item_id">
                    <input type="text" :name="'items[' + index + '][item_name]'" x-model="item.item_name"
                      class="w-full border-0 p-0 m-0" required>
                  </td>
                  <td class="py-2 px-4 border-b">
                    <select :name="'items[' + index + '][cost_type]'" x-model="item.cost_type"
                      class="w-full border-0 p-0 m-0" required>
                      <option value="月額利用料">月額利用料</option>
                      <option value="初期費用">初期費用</option>
                      <option value="機材費">機材費</option>
                    </select>
                  </td>
                  <td class="py-2 px-4 border-b">
                    <input type="number" :name="'items[' + index + '][unit_price]'" x-model.number="item.unit_price"
                      @input="calculateTotals" class="w-full border-0 p-0 m-0 text-right" min="0" step="0.01" required>
                  </td>
                  <td class="py-2 px-4 border-b">
                    <input type="number" :name="'items[' + index + '][quantity]'" x-model.number="item.quantity"
                      @input="calculateTotals" class="w-full border-0 p-0 m-0 text-right" min="1" required>
                  </td>
                  <td class="py-2 px-4 border-b text-right"
                    x-text="((item.quantity || 0) * (item.unit_price || 0)).toLocaleString()"></td>
                  <td class="py-2 px-4 border-b text-center">
                    <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700">削除</button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <input type="hidden" name="subtotal" :value="subtotal.toFixed(2)">
        <input type="hidden" name="tax" :value="tax.toFixed(2)">
        <input type="hidden" name="total_amount" :value="totalAmount.toFixed(2)">

        <div class="mt-6 p-4 border-t border-gray-200 text-right space-y-2">
          <div class="text-lg">小計: <span x-text="Math.floor(subtotal).toLocaleString()">0</span> 円</div>
          <div class="text-lg">消費税(10%): <span x-text="Math.floor(tax).toLocaleString()">0</span> 円</div>
          <div class="text-xl font-bold text-indigo-600">合計金額: <span
              x-text="Math.floor(totalAmount).toLocaleString()">0</span>
            円</div>
        </div>

        <div class="flex justify-end mt-6">
          <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg bg-white mr-2">戻る</a>
          <button type="submit"
            class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
            見積書を作成
          </button>
        </div>
      </form>
    </div>
  </main>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>