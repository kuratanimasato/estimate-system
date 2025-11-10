const btn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay"); // HTML内にこのIDの要素が必要です

// 1. メニューボタンの処理（要素が存在する場合のみ）
if (btn && sidebar && overlay) {
  // メニューボタン (btn) をクリックしたときの動作
  btn.addEventListener("click", () => {
    // サイドバーの非表示クラス（-translate-x-full）を切り替えて開閉する
    sidebar.classList.toggle("-translate-x-full");

    // オーバーレイの表示/非表示を切り替える
    overlay.classList.toggle("hidden");

    // ボタン自身を隠す（必要に応じて。隠さない場合はこの行を削除）
    // btn.classList.toggle("hidden");

    // スクロールを禁止/許可する
    document.body.classList.toggle("overflow-hidden");
  });

	// オーバーレイ (overlay) をクリックしたときの動作
	// オーバーレイが表示されている状態でクリックされたら閉じる
	overlay.addEventListener("click", () => {
		// サイドバーを閉じる
		sidebar.classList.add("-translate-x-full");
		// オーバーレイを非表示にする
		overlay.classList.add("hidden");
		// ボタンを表示する（もし開くときに隠していた場合）
		// btn.classList.remove("hidden");
		// スクロールを許可する
		document.body.classList.remove("overflow-hidden");
	});
}
