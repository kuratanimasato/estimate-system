<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if (empty($main)) {
  echo '<p>請求書データがありません。</p>';
  return;
}
if (empty($details)) {
  echo '<p>請求明細がありません。</p>';
  return;
}

$invoice = $main;

// 請求書用の日付設定
$invoice_date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+30 days', strtotime($invoice_date))); // 支払期限
?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>請求書</title>
    <style>
    html {
      font-size: 14px;
    }

    body {
      color: #333;
    }

    .title {
      text-align: center;
      margin-bottom: 1rem;
    }

    .text-right {
      text-align: right;
    }

    .text-left {
      text-align: left;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 1rem;
    }

    th,
    td {
      border: 1px solid #333;
      padding: 6px 8px;
      line-height: 1.5;
      vertical-align: middle;
    }

    th {
      background-color: #f0f0f0;
    }

    th.text-left {
      text-align: left;
    }

    td.text-left {
      text-align: left;
    }

    td.text-right {
      text-align: right;
    }

    tfoot tr td {
      font-weight: bold;
      background-color: #fafafa;
    }

    .grid {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      margin-bottom: 1rem;
    }

    .grid div {
      width: 48%;
    }

    .grand-total {
      text-align: right;
      font-size: 16px;
      font-weight: bold;
      margin-top: 0.5rem;
    }
    </style>
  </head>

  <body>
    <div style="margin: 2rem;">

      <h1 class="title">請求書</h1>

      <div class="text-right mb-2rem">
        <strong>請求書番号：</strong><?= 'No.' . htmlspecialchars(sprintf('%04d', $invoice['id'])) ?><br>
        <strong>発行日：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($invoice_date))) ?>
      </div>

      <div class="grid mb-2rem">
        <div class="billing-to">
          <h3>請求先</h3>
          <p>
            <?= htmlspecialchars($invoice['customer_name']) ?><br>
            <?= htmlspecialchars($invoice['customer_email']) ?>
          </p>
          <h3>担当</h3>
          <p><?= htmlspecialchars($invoice['sales_rep_name']) ?></p>
        </div>
        <div class="from">
          <h3>発行元</h3>
          <table style="border: none; width: 100%;">
            <tr>
              <td style="border: none; vertical-align: top; padding: 0;">
                ドキュエスト株式会社<br>
                〒600-0000 群馬県 高崎市栄町3-11 高崎バナーズビル<br>
                TEL: 075-000-0000 / Email: noreply@docuquest.co.jp
              </td>
              <td style="border: none; vertical-align: top; text-align: right; padding: 0; width: 70px;">
                <img src="/public/img/mystamp.png" alt="ドキュエスト株式会社" width="70">
              </td>
            </tr>
          </table>
        </div>
      </div>

      <table>
        <colgroup>
          <col style="width: 50%">
          <col style="width: 10%">
          <col style="width: 10%">
          <col style="width: 15%">
          <col style="width: 15%">
        </colgroup>
        <thead>
          <tr>
            <th class="text-left">品目</th>
            <th>数量</th>
            <th>単価</th>
            <th>小計</th>
            <th>金額(税込)</th>
          </tr>
        </thead>
        <tbody>
          <?php $grand_total = 0; ?>
          <?php foreach ($details as $detail):
            $subtotal = $detail['unit_price'] * $detail['quantity'];
            $tax = floor($subtotal * 0.1);
            $total = $subtotal + $tax;
            $grand_total += $total;
            ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($detail['item_name']) ?></td>
            <td class="text-right"><?= number_format($detail['quantity']) ?>個</td>
            <td class="text-right">¥<?= number_format($detail['unit_price']) ?></td>
            <td class="text-right">¥<?= number_format($subtotal) ?></td>
            <td class="text-right">¥<?= number_format($total) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="text-right">合計金額</td>
            <td class="text-right">¥<?= number_format($grand_total) ?></td>
          </tr>
        </tfoot>
      </table>

      <p><strong>お支払い期限：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($due_date))) ?></p>
      <p><strong>振込先：</strong> 群馬銀行 高崎支店 普通 1234567 ドキュエスト株式会社</p>
      <p>平素よりご愛顧賜り、誠にありがとうございます。下記の通りご請求申し上げます。</p>

    </div>
  </body>

</html>