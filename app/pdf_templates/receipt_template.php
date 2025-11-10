<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if (empty($main)) {
  echo '<p>領収書データがありません。</p>';
  return;
}
if (empty($details)) {
  echo '<p>領収書明細がありません。</p>';
  return;
}
$receipt = $main;

// 領収書用の日付設定
$issue_date = date('Y-m-d');
// 受領日
$receipt_date = date('Y-m-d', strtotime($receipt['received_date']));
?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>領収書</title>
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
      word-break: break-word;
      line-height: 1.5;
    }

    th {
      background-color: #f0f0f0;
      text-align: center;
    }

    img {
      display: block;
      margin-top: 5px;
      margin-bottom: 5px;
    }

    .grand-total {
      text-align: right;
      font-size: 16px;
      font-weight: bold;
      margin-top: auto;
    }
    </style>
  </head>

  <body>
    <div style="margin: 1.5rem;">

      <div class="grid mb-2rem">
        <h1 class="title">領収書</h1>
        <div class="text-right">
          <strong>領収書番号：</strong><?= 'No.' . htmlspecialchars(sprintf('%04d', $receipt['id'])) ?><br>
          <strong>発行日：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($issue_date))) ?>
        </div>
      </div>

      <div class="grid mb-2rem">
        <div class="billing-to">
          <h3>受領者（お客様）</h3>
          <p>
            <?= htmlspecialchars($receipt['customer_name']) ?><br>
            <?= htmlspecialchars($receipt['customer_email']) ?>
          </p>
          <h3>担当</h3>
          <p><?= htmlspecialchars($receipt['sales_rep_name']) ?></p>
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

      <?php $grand_total = 0; ?>
      <table>
        <colgroup>
          <col style=" width: 70%">
          <col style="width: 30%">
        </colgroup>
        <thead>
          <tr>
            <th>品目</th>
            <th>金額</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($details as $detail):
            $subtotal = $detail['unit_price'] * $detail['quantity'];
            $tax = floor($subtotal * 0.1);
            $total = $subtotal + $tax;
            $grand_total += $total;
            ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($detail['item_name']) ?></td>
            <td class="text-right">¥<?= number_format($total) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <tr>
        <td colspan="2" style="height:20px; border:none;"></td>
      </tr>
      <div class="grand-total">
        合計受領金額(税込)：¥<?= number_format($grand_total) ?>
      </div>

      <p><strong>お支払い方法：</strong><?= htmlspecialchars($receipt['payment_method']) ?></p>
      <p><strong>受領日：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($receipt_date))) ?></p>
      <p>上記の金額を正に領収いたしました。誠にありがとうございます。</p>

    </div>
  </body>

</html>