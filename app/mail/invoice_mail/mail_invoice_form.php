<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php';

// セッションからエラーと入力値を取得
$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

$id = $_GET['id'] ?? null;

// DBから見積書情報を取得
$stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
$stmt->execute([$id]);
$document = $stmt->fetch();
if (!$document) {
  die('請求書が存在しません。');
}
$documentType = 'invoice';

// 添付ファイル取得
$attachStmt = $pdo->prepare("
    SELECT * FROM document_attachments 
    WHERE document_type = ? AND document_id = ?
");
$attachStmt->execute([$documentType, $id]);
$attachments = $attachStmt->fetchAll();
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/header.php'; ?>

<body class="bg-gray-100 min-h-screen flex flex-col">
  <main class="flex-grow flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-xl bg-white shadow-md rounded-lg p-6">
      <h1 class="text-xl font-semibold mb-4 text-center">請求書メール送信</h1>

      <form action="/app/mail/common/mail_send.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= htmlspecialchars($document['id']) ?>">
        <input type="hidden" name="document_type" value="<?= htmlspecialchars($documentType) ?>">
        <!-- 宛先メール -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">宛先メールアドレス</label>
          <input type="email" name="to" value="<?= htmlspecialchars($formData['to'] ?? $document['customer_email']) ?>"
            class="mt-1 block w-full border border-gray-300 rounded-md p-2" required>
          <?php if (!empty($errors['to'])): ?>
          <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['to']) ?></p>
          <?php endif; ?>
        </div>

        <!-- 件名 -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">件名</label>
          <input type="text" name="subject"
            value="<?= htmlspecialchars($formData['subject'] ?? "【請求書】{$document['customer_name']}様") ?>"
            class="mt-1 block w-full border border-gray-300 rounded-md p-2" required>
          <?php if (!empty($errors['subject'])): ?>
          <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['subject']) ?></p>
          <?php endif; ?>
        </div>

        <!-- 本文 -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">本文</label>
          <textarea name="body" rows="8" class="mt-1 block w-full border border-gray-300 rounded-md p-2" required><?= htmlspecialchars(
            $formData['body'] ?? <<<EOM
        {$document['customer_name']} 様

        お世話になっております。
        以下の納品書をお送りいたします。
        ご確認のほどよろしくお願いいたします。

        ────────────────────
        会社名:ドキュエスト株式会社
        担当：{$document['sales_rep_name']}
        ────────────────────
        EOM
          ) ?></textarea>
          <?php if (!empty($errors['body'])): ?>
          <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['body']) ?></p>
          <?php endif; ?>
        </div>

        <!-- 添付ファイル -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700">添付ファイル（PDF）</label>
          <input type="file" name="attachment" accept="application/pdf"
            class="mt-1 block w-full border border-gray-300 rounded-md p-2">
          <p class="text-sm text-gray-500">※任意のPDFを添付できます。</p>

          <?php if (!empty($attachments)): ?>
          <ul class="mt-2 list-disc list-inside">
            <?php foreach ($attachments as $attachment): ?>
            <li class="flex items-center justify-between border-b py-1">
              <span class="text-gray-800">
                <?= htmlspecialchars($attachment['file_name']) ?>
              </span>
              <button type="button" class="text-red-600 hover:underline text-sm"
                onclick="deleteAttachment(<?= (int) $attachment['id'] ?>, <?= (int) $document['id'] ?>, '<?= htmlspecialchars($documentType) ?>')">
                削除
              </button>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <?php if (!empty($errors['attachment'])): ?>
          <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['attachment']) ?></p>
          <?php endif; ?>
        </div>
        <button type="submit"
          class="w-full bg-blue-600 text-white px-2 py-2 rounded hover:bg-blue-700 transition-colors duration-150">
          メール送信
        </button>
      </form>
    </div>
  </main>
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/common/footer.php'; ?>
</body>
<script src="/public/js/file_delete.js"></script>