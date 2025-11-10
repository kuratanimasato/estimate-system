<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if (empty($main)) {
  echo '<p>納品書データがありません。</p>';
  return;
}
if (empty($details)) {
  echo '<p>納品書明細がありません。</p>';
  return;
}
$delivery = $main;

// 発行日
$delivery_note_date = date('Y-m-d');
// 納品日
$delivery_date = date('Y-m-d', strtotime('+30 days', strtotime($delivery_note_date)));

// 消費税率
$taxRate = 0.1; // 10%
?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書</title>
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
      margin-bottom: 2rem;
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

    tfoot td {
      font-weight: bold;
    }
    </style>
  </head>

  <body>
    <div style="margin: 2rem;">

      <div class="grid mb-2rem">
        <h1 class="title">納品書</h1>
        <div class="text-right">
          <strong>納品書番号：</strong><?= 'No.' . htmlspecialchars(sprintf('%04d', $delivery['id'])) ?><br>
          <strong>発行日：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($delivery_note_date))) ?>
        </div>
      </div>

      <div class="grid mb-2rem">
        <div class="billing-to">
          <h3>納品先</h3>
          <p><?= htmlspecialchars($delivery['customer_name']) ?></p>
          <p><?= htmlspecialchars($delivery['customer_email']) ?></p>
          <h3>担当</h3>
          <p><?= htmlspecialchars($delivery['sales_rep_name']) ?></p>
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
          <col style="width: 40%">
          <col style="width: 15%">
          <col style="width: 15%">
          <col style="width: 15%">
          <col style="width: 15%">
        </colgroup>
        <thead>
          <tr>
            <th>品目</th>
            <th>数量</th>
            <th>区分</th>
            <th>単価</th>
            <th>金額</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $subtotalAll = 0;
          foreach ($details as $detail):
            $subtotal = $detail['unit_price'] * $detail['quantity'];
            $subtotalAll += $subtotal;
            ?>
          <tr>
            <td class="text-left"><?= htmlspecialchars($detail['item_name']) ?></td>
            <td class="text-right"><?= number_format($detail['quantity']) ?>個</td>
            <td class="text-right"><?= htmlspecialchars($detail['cost_type']) ?></td>
            <td class="text-right">¥<?= number_format($detail['unit_price']) ?></td>
            <td class="text-right">¥<?= number_format($subtotal) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <?php
          $taxAll = round($subtotalAll * $taxRate);
          $totalAll = $subtotalAll + $taxAll;
          ?>
          <tr>
            <td colspan="4" class="text-right">小計</td>
            <td class="text-right">¥<?= number_format($subtotalAll) ?></td>
          </tr>
          <tr>
            <td colspan="4" class="text-right">消費税 (<?= $taxRate * 100 ?>%)</td>
            <td class="text-right">¥<?= number_format($taxAll) ?></td>
          </tr>
          <tr>
            <td colspan="4" class="text-right"><strong>合計金額</strong></td>
            <td class="text-right"><strong>¥<?= number_format($totalAll) ?></strong></td>
          </tr>
        </tfoot>
      </table>
      <p class="text-right">上記の通り、納品いたしました。</p>
      <p><strong>納品日：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($delivery_date))) ?></p>
      <p><strong>受領者：</strong> ___________________________</p>
      <p><strong>署名：</strong> ___________________________</p>

    </div>
  </body>

</html>