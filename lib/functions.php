<?php

/**
 * 指定されたテーブル・カラムで値が一意か確認
 */
function isUniqueField(PDO $pdo, string $table, string $field, $value, $excludeId = null): bool
{
  // テーブル名とカラム名は安全にエスケープ（識別子として）
  $allowedTables = ['users', 'customers', 'products', 'quotes', 'items', 'customers', 'sales_reps', 'quote_details', 'quotes']; // ← 使用想定テーブルを制限
  $allowedFields = ['email', 'username', 'name', 'id', 'item_name', 'company_name', 'email', 'name']; // ← 許可フィールドも制限

  if (!in_array($table, $allowedTables, true) || !in_array($field, $allowedFields, true)) {
    throw new InvalidArgumentException('指定されたテーブルまたはフィールドは許可されていません。');
  }

  $sql = "SELECT COUNT(*) FROM {$table} WHERE {$field} = :value";

  if ($excludeId !== null) {
    $sql .= " AND id != :excludeId";
  }

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':value', $value);
  if ($excludeId !== null) {
    $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
  }
  $stmt->execute();

  return $stmt->fetchColumn() == 0;
}

/**
 * 汎用バリデーション関数
 *
 * @param array $data   入力データ ($_POSTなど)
 * @param array $rules  バリデーションルール
 * @param array $labels ラベル名（エラーメッセージ用）
 * @param PDO   $pdo    PDO接続
 * @param string|null $table  テーブル名（uniqueチェック用）
 * @param int|null $excludeId 更新時の除外ID
 * @return array エラーメッセージ配列
 */
function validateForm(array $data, array $rules, array $labels = [], ?PDO $pdo = null, ?string $table = null, ?int $excludeId = null): array
{
  $errors = [];

  foreach ($rules as $field => $rule) {
    $value = trim($data[$field] ?? '');
    $label = $labels[$field] ?? $field;

    // 必須チェック
    if (!empty($rule['required']) && $value === '') {
      $errors[$field] = $rule['message']['required'] ?? "{$label}は必須です。";
      continue;
    }

    // 未入力なら他の検証スキップ
    if ($value === '')
      continue;

    // 文字数（最大・最小）
    if (isset($rule['max']) && mb_strlen($value) > $rule['max']) {
      $errors[$field] = $rule['message']['max'] ?? "{$label}は{$rule['max']}文字以内で入力してください。";
      continue;
    }
    if (isset($rule['min']) && mb_strlen($value) < $rule['min']) {
      $errors[$field] = $rule['message']['min'] ?? "{$label}は{$rule['min']}文字以上で入力してください。";
      continue;
    }

    // メールアドレス形式
    if (!empty($rule['email']) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
      $errors[$field] = $rule['message']['email'] ?? "{$label}の形式が正しくありません。";
      continue;
    }

    // 数値チェック
    if (!empty($rule['numeric']) && !is_numeric($value)) {
      $errors[$field] = $rule['message']['numeric'] ?? "{$label}は数値で入力してください。";
      continue;
    }

    // 正規表現チェック
    if (!empty($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
      $errors[$field] = $rule['message']['pattern'] ?? "{$label}の形式が正しくありません。";
      continue;
    }

    // ユニークチェック
    if (!empty($rule['unique']) && $pdo && $table) {
      if (!isUniqueField($pdo, $table, $field, $value, $excludeId)) {
        $errors[$field] = $rule['message']['unique'] ?? "{$label}は既に登録されています。";
        continue;
      }
    }
  }

  return $errors;
}


// メールフォームのバリデーション
function validateMailForm(array $data, array $file): array
{
  $errors = [];

  // 宛先メールアドレス
  if (empty($data['to'])) {
    $errors['to'] = '宛先メールアドレスは必須です。';
  } elseif (!filter_var($data['to'], FILTER_VALIDATE_EMAIL)) {
    $errors['to'] = '宛先メールアドレスの形式が正しくありません。';
  }

  // 件名
  if (empty($data['subject'])) {
    $errors['subject'] = '件名は必須です。';
  } elseif (mb_strlen($data['subject']) > 255) {
    $errors['subject'] = '件名は255文字以内で入力してください。';
  }

  // 本文
  if (empty($data['body'])) {
    $errors['body'] = '本文は必須です。';
  } elseif (mb_strlen($data['body']) > 2000) {
    $errors['body'] = '本文は2000文字以内で入力してください。';
  }

  // 添付ファイルチェック（任意）
  if (!empty($file['attachment']['name'])) {
    $allowedTypes = ['application/pdf'];
    if (!in_array($file['attachment']['type'], $allowedTypes)) {
      $errors['attachment'] = '添付ファイルはPDFのみです。';
    }
    if ($file['attachment']['size'] > 5 * 1024 * 1024) {
      $errors['attachment'] = '添付ファイルは5MB以内でアップロードしてください。';
    }
  }

  return $errors;
}