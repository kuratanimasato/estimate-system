

  async function deleteAttachment(attachmentId, documentId, documentType) {
    if (!confirm('このファイルを削除してもよろしいですか？')) return;

    const formData = new FormData();
    formData.append('attachment_id', attachmentId);
    formData.append('document_id', documentId);
    formData.append('document_type', documentType);

    try {
      const res = await fetch('/app/mail/common/delete_attachment.php', {
        method: 'POST',
        body: formData
      });

      if (res.ok) {
        // ページを再読み込み（削除後の状態を反映）
        location.reload();
      } else {
        alert('削除に失敗しました。');
      }
    } catch (err) {
      alert('エラーが発生しました。');
      console.error(err);
    }
  }
