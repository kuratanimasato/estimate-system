<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';

if (empty($main)) {
  echo '<p>見積書データがありません。</p>';
  return;
}
if (empty($details)) {
  echo '<p>見積書明細がありません。</p>';
  return;
}
$quote = $main;
?>
<!DOCTYPE html>
<html lang="ja">

  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>見積書</title>
    <style>
    html {
      font-size: 14px;
    }

    body {
      padding: 3rem;
      color: #333;
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

    td.text-right {
      text-align: right;
    }

    td.text-left {
      text-align: left;
    }

    .title {
      text-align: center;
      margin-bottom: 1rem;
    }

    .text-right {
      text-align: right;
    }

    .mb-2rem {
      margin-bottom: 2rem;
    }
    </style>
  </head>

  <body>
    <div class="grid mb-2rem">
      <h1 class="title">見積書</h1>
      <div class="text-right">
        <strong>見積書番号：</strong><?= 'No.', htmlspecialchars(sprintf('%04d', $quote['id'])) ?><br />
        <strong>発行日：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($quote['created_at']))) ?>
      </div>
    </div>

    <div class="grid mb-2rem">
      <div class="billing-to">
        <h3>御見積先</h3>
        <p><?= htmlspecialchars($quote['customer_name']) ?></p>
        <p><?= htmlspecialchars($quote['customer_email']) ?></p>
        <h3>担当</h3>
        <p><?= htmlspecialchars($quote['sales_rep_name']) ?></p>
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
        <col style="width: 40%" /> <!-- 品目 -->
        <col style="width: 15%" /> <!-- 数量 -->
        <col style="width: 15%" /> <!-- 区分 -->
        <col style="width: 15%" /> <!-- 単価 -->
        <col style="width: 15%" /> <!-- 金額 -->
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
        <?php foreach ($details as $detail):
          $subtotal = $detail['unit_price'] * $detail['quantity'];
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
        <tr>
          <td colspan="4" class="text-right"><strong>小計</strong></td>
          <td class="text-right">¥<?= number_format($quote['subtotal'] ?? 0) ?></td>
        </tr>
        <tr>
          <td colspan="4" class="text-right">消費税 (10%)</td>
          <td class="text-right">¥<?= number_format($quote['tax'] ?? 0) ?></td>
        </tr>
        <tr>
          <td colspan="4" class="text-right"><strong>合計金額</strong></td>
          <td class="text-right"><strong>¥<?= number_format($quote['total_amount'] ?? 0) ?></strong></td>
        </tr>
      </tfoot>
    </table>

    <p><strong>有効期限：</strong><?= htmlspecialchars(date('Y年m月d日', strtotime($quote['expiration_date']))) ?></p>
    <p>ご不明な点がございましたら、お気軽にお問い合わせください。</p>
    <p>今後ともよろしくお願い申し上げます。</p>
  </body>

</html>