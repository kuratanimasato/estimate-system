<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';

// URLパラメータからIDを取得
$id = $_GET['id'] ?? null;
if (!is_numeric($id)) {
  $_SESSION['flash_message'] = '無効なIDです。';
  header('Location: index.php');
  exit;
}

$current_page = 'Receipts';

// 顧客・担当者・商品を取得
$customers = $pdo->query("SELECT id, company_name, email FROM customers ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC);
$sales_reps = $pdo->query("SELECT id, name FROM sales_reps ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$products = $pdo->query("SELECT id, item_name, unit_price FROM items ORDER BY item_name")->fetchAll(PDO::FETCH_ASSOC);

// 請求書メイン情報を取得
$stmt = $pdo->prepare("SELECT * FROM  receipts WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$formData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formData) {
  $_SESSION['flash_message'] = 'データが見つかりません。';
  header('Location: index.php');
  exit;
}

// 明細情報を取得
$stmtDetail = $pdo->prepare("SELECT * FROM  receipt_details WHERE  receipt_id = :receipt_id ORDER BY order_index");
$stmtDetail->bindValue(':receipt_id', $id, PDO::PARAM_INT);
$stmtDetail->execute();
$itemsToDisplay = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);

$itemsToDisplay = array_map(function ($item) {
  $item['_id'] = $item['id'] ?? uniqid();
  return $item;
}, $itemsToDisplay);
// バリデーションルール
$rules = [
  'document_type' => ['required' => true],
  'customer_id' => ['required' => true],
  'customer_name' => ['required' => true, 'max' => 255],
  'customer_email' => ['required' => true, 'email' => true, 'max' => 255],
  'sales_rep_id' => ['required' => true],
  'sales_rep_name' => ['required' => true, 'max' => 255],
  'issue_date' => ['required' => true],
  'received_date' => ['required' => true],
  'payment_method' => ['required' => true, 'max' => 100],
  'subtotal' => ['numeric' => true, 'min' => 0],
  'tax' => ['numeric' => true, 'min' => 0],
  'total_amount' => ['numeric' => true, 'min' => 0],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // POSTデータを既存データに上書き
  foreach ($formData as $key => $value) {
    if (isset($_POST[$key])) {
      $formData[$key] = trim($_POST[$key]);
    }
  }

  // バリデーション実行
  $errors = validateForm($formData, $rules, [], $pdo, 'receipt', $id);

  // 明細チェック
  $items = $_POST['items'] ?? [];
  if (empty($items)) {
    $errors['items'] = '明細を最低1行追加してください。';
  } else {
    foreach ($items as $i => $item) {
      if (!is_numeric($item['quantity']) || $item['quantity'] <= 0)
        $errors['items'][$i]['quantity'] = '数量を正しく入力してください。';
      if (!is_numeric($item['unit_price']) || $item['unit_price'] < 0)
        $errors['items'][$i]['unit_price'] = '単価を正しく入力してください。';
      if (!in_array($item['cost_type'], ['月額利用料', '初期費用', '機材費']))
        $errors['items'][$i]['cost_type'] = '費用の区分が不正です。';
    }
  }

  // エラーがなければ更新
  if (empty($errors)) {
    $subtotal = 0;
    foreach ($items as $item) {
      $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
    }
    $tax = round($subtotal * 0.1, 2);
    $total_amount = $subtotal + $tax;

    try {
      $pdo->beginTransaction();

      $stmt = $pdo->prepare("
        UPDATE  receipts SET
          document_type = :document_type,
          customer_id = :customer_id,
          customer_name = :customer_name,
          customer_email = :customer_email,
          sales_rep_id = :sales_rep_id,
          sales_rep_name = :sales_rep_name,
          issue_date = :issue_date,
          received_date = :received_date,
          payment_method = :payment_method,
          subtotal = :subtotal,
          tax = :tax,
          total_amount = :total_amount,
          remarks=:remarks
          WHERE id = :id ");
      $stmt->execute([
        ':document_type' => $formData['document_type'],
        ':customer_id' => $formData['customer_id'],
        ':customer_name' => $formData['customer_name'],
        ':customer_email' => $formData['customer_email'],
        ':sales_rep_id' => $formData['sales_rep_id'],
        ':sales_rep_name' => $formData['sales_rep_name'],
        ':issue_date' => $formData['issue_date'],
        ':received_date' => $formData['received_date'],
        ':payment_method' => $formData['payment_method'],
        ':remarks' => $formData['remarks'],
        ':subtotal' => $subtotal,
        ':tax' => $tax,
        ':total_amount' => $total_amount,
        ':id' => $id
      ]);

      // 明細更新
      $pdo->prepare("DELETE FROM receipt_details WHERE receipt_id = :r_id")->execute([':r_id' => $id]);

      $stmtDetail = $pdo->prepare("
        INSERT INTO receipt_details
        (receipt_id, item_id, item_name, unit_price, cost_type, quantity, line_total, order_index)
        VALUES (:r_id, :item_id, :item_name, :unit_price, :cost_type, :quantity, :line_total, :order_index)
      ");
      foreach ($items as $index => $item) {
        $stmtDetail->execute([
          ':r_id' => $id,
          ':item_id' => !empty($item['item_id']) ? (int) $item['item_id'] : 0,
          ':item_name' => $item['item_name'],
          ':unit_price' => round($item['unit_price'], 2),
          ':cost_type' => $item['cost_type'],
          ':quantity' => $item['quantity'],
          ':line_total' => (float) $item['quantity'] * (float) $item['unit_price'],
          ':order_index' => $index
        ]);
      }

      $pdo->commit();
      $_SESSION['flash_message'] = '書類を更新しました。';
      if (empty($error)) {
        header('Location: index.php');
        exit;

      } else {
        echo '<pre>';
        print_r($errors);
        echo '</pre>';
      }

    } catch (PDOException $e) {
      $pdo->rollBack();
      $errors['general'] = 'データベースエラーが発生しました。';
      $errors['general'] .= '<br>' . htmlspecialchars($e->getMessage()); // 追加
    }
  }
}
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>
<div class="flex flex-1 relative">
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/sidebar.php'; ?>
  <main class="flex-1 p-6 bg-gray-50 w-full">
    <div class="max-w-3xl mx-auto bg-white shadow-xl rounded-xl p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">領収書編集画面</h1>

      <?php if (isset($errors['general'])): ?>
      <div class="p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg">
        <?= htmlspecialchars($errors['general']) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="edit.php? id=<?= htmlspecialchars($id) ?>"
        x-data="receptForm(<?= htmlspecialchars(json_encode($formData)) ?>, <?= htmlspecialchars(json_encode($itemsToDisplay)) ?>, <?= htmlspecialchars(json_encode($products)) ?>)"
        x-init="init()" @submit.prevent="submitForm($event)">

        <div class=" mb-4">
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
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="mb-6">
            <label for="received_date " class="block text-sm font-medium text-gray-700 mb-2">
              受領日 <span class="text-red-500">*</span>
            </label>
            <input type="date" id="received_date" name="received_date" x-model="form.received_date"
              @change="setReceivedDate"
              class="mt-1 block w-full px-4 py-2 border <?= isset($errors['received_date']) ? 'border-red-500' : 'border-gray-300' ?> rounded-lg">
            <?php if (isset($errors['received_date'])): ?>
            <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['received_date']) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <div class="mb-6">
          <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
            支払い方法
          </label>
          <select name="payment_method" id="payment_method" x-model="form.status"
            class="w-full border rounded-lg px-3 py-2">
            <option value="クレジットカード">クレジットカード</option>
            <option value="銀行振込">銀行振込</option>
            <option value="現金">現金</option>
          </select>
        </div>

        <div class="mb-6">
          <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">
            備考
          </label>
          <textarea id="remarks" name="remarks" x-model="form.remarks" rows="4"
            class="w-full border rounded-lg px-3 py-2 <?= isset($errors['remarks']) ? 'border-red-500' : 'border-gray-300' ?>"
            placeholder="例：納品日は翌営業日予定です"><?= htmlspecialchars($formData['remarks']) ?></textarea>
          <?php if (isset($errors['remarks'])): ?>
          <p class="mt-2 text-sm text-red-600"><?= htmlspecialchars($errors['remarks']) ?></p>
          <?php endif; ?>
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
              x-text="Math.floor(totalAmount).toLocaleString()">0</span> 円</div>
        </div>

        <div class="flex justify-end mt-6">
          <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-lg bg-white mr-2">戻る</a>
          <button type="submit" class=" inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium
            rounded-lg text-white bg-indigo-600 hover:bg-indigo-700">
            保存
          </button>
        </div>
      </form>
    </div>
  </main>
</div>
<script>
function receptForm(initialForm, initialItems, products) {
  return {
    form: {
      ...initialForm,
      items: initialItems.length ? initialItems : []
    },
    products: products,
    selectedProductId: '',
    subtotal: 0,
    tax: 0,
    totalAmount: 0,

    init() {
      this.calculateTotals();
    },

    calculateTotals() {
      this.subtotal = this.form.items.reduce((sum, item) => {
        const quantity = Number(item.quantity) || 0;
        const unitPrice = Number(item.unit_price) || 0;
        return sum + quantity * unitPrice;
      }, 0);

      this.tax = this.subtotal * 0.1;
      this.totalAmount = this.subtotal + this.tax;
    },

    addItem() {
      const product = this.products.find(p => p.id == this.selectedProductId);
      if (!product) return;

      const existing = this.form.items.find(i => i.item_id == product.id);
      if (existing) {
        existing.quantity++;
      } else {
        this.form.items.push({
          _id: Date.now(),
          item_id: product.id,
          item_name: product.item_name,
          unit_price: product.unit_price,
          quantity: 1,
          cost_type: '月額利用料'
        });
      }

      this.selectedProductId = '';
      this.calculateTotals();
    },

    removeItem(index) {
      this.form.items.splice(index, 1);
      this.calculateTotals();
    },

    updateCustomer(event) {
      const selected = event.target.selectedOptions[0];
      this.form.customer_name = selected.dataset.name || '';
      this.form.customer_email = selected.dataset.email || '';
    },

    updateSalesRep(event) {
      const selected = event.target.selectedOptions[0];
      this.form.sales_rep_name = selected.dataset.name || '';
    },

    setExpirationDate() {
      if (this.form.issue_date) {
        const issue = new Date(this.form.issue_date);
        issue.setDate(issue.getDate() + 30);
        this.form.expiration_date = issue.toISOString().split('T')[0];
      }
    },

    submitForm() {
      this.calculateTotals();
      this.$el.closest('form').submit();
    }
  };
}
</script>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>