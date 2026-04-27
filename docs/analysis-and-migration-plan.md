# 1. 全体要約

- 提供された `functions.php`（重複部分を除いた実質1セット）を分析し、アレルギーフードナビ機能を **Quick Edit管理機能** / **検索クエリ構築** / **結果描画** / **QRリダイレクト** に分解しました。
- ログイン障害リスクの高い要素（グローバル `echo` による早期出力、`init` での即時リダイレクト等）を優先的に隔離し、プラグイン内では「対象リクエスト時のみ実行」に変更しました。
- Search & Filter Pro 依存は採用せず、現行 `functions.php` 相当の GET パラメータ検索（`protein`, `hp`, `oi`, `maker`, `sort`）を独自クエリビルダで実装しました。

# 2. functions.php の機能分類一覧

1. 子テーマCSS読込（`wp_enqueue_scripts`）
2. 管理画面 Quick Edit（`ryouhou_food` の ACF 編集）
3. 検索条件組み立て（GET→`WP_Query` args）
4. 並び替え（`hydro_first` / メーカー順）
5. PDF関連UI・PDF HTML生成
6. QR画像生成・購入先リンク収集
7. `rf_go` リダイレクト
8. 固定ページ本文末尾への検索結果描画（`the_content`）

# 3. アレルギーフードナビ関連コード一覧

- `ryouhou_food` の Quick Edit ACF更新
- `rf_get()` 系 GETパラメータ解釈
- 除外検索（`protein` + `oi` + `hp`）
- メーカー絞り込み（`maker` OR条件）
- 並び替え（`hydro_flag` / メーカー優先順）
- 結果表示（`the_content`）
- QRリンク短縮導線（`rf_go`）

# 4. 危険箇所一覧

1. **グローバルスコープ出力**: `echo "<style>..."` がファイル読込時に実行される構造（ログイン・Cookie送出前の出力事故リスク）。
2. **重複定義**: 同関数・同定数ブロックが複製されており、再定義fatalリスク。
3. **リダイレクト経路の重複**: `template_redirect` と `init` の双方で `rf_go` 処理。
4. **責務混在**: 管理画面機能・フロント描画・PDF・外部通信が単一 `functions.php` に集中。

# 5. プラグイン化対象 / テーマ残し対象

## プラグイン化対象
- Quick Edit（管理機能）
- GET検索条件→`WP_Query` 変換
- 検索結果描画ロジック（最低限）
- `rf_go` リダイレクト

## テーマ残し対象
- CSSデザイン調整
- SWELL固有テンプレート装飾
- 見た目専用のHTML構造最適化

# 6. 独自プラグインの推奨フォルダ構成

```text
plugin/
└─ allergy-food-navi/
   ├─ allergy-food-navi.php
   └─ includes/
      ├─ class-afn-config.php
      ├─ class-afn-request-context.php
      ├─ class-afn-acf.php
      ├─ class-afn-utils.php
      ├─ class-afn-query-builder.php
      ├─ class-afn-frontend.php
      ├─ class-afn-redirect.php
      ├─ class-afn-quick-edit.php
      └─ class-afn-bootstrap.php
```

# 7. 実装計画（最小実装 → 段階移行）

1. **Phase 1（今回）**
   - Quick Edit / 検索条件構築 / 結果描画（簡易） / QRリダイレクトを安全に分離
2. **Phase 2**
   - PDF出力機能をクラス分離して移植（`template_redirect` 1経路に統一）
3. **Phase 3**
   - フロントHTML/CSSをテーマ側へ戻し、プラグインはデータ供給API中心へ整理
4. **Phase 4**
   - テストケース追加・オプション化・互換検証

# 8. 生成または提案するファイル一覧

## 生成済み
- `plugin/allergy-food-navi/includes/class-afn-config.php`
- `plugin/allergy-food-navi/includes/class-afn-utils.php`
- `plugin/allergy-food-navi/includes/class-afn-query-builder.php`
- `plugin/allergy-food-navi/includes/class-afn-frontend.php`
- `plugin/allergy-food-navi/includes/class-afn-redirect.php`
- `plugin/allergy-food-navi/includes/class-afn-quick-edit.php`

## 更新済み
- `plugin/allergy-food-navi/allergy-food-navi.php`
- `plugin/allergy-food-navi/includes/class-afn-bootstrap.php`
- `plugin/allergy-food-navi/includes/class-afn-acf.php`

# 9. 初回実装コード

- `Query_Builder::build_from_request()` に、現行仕様の除外検索・メーカー絞り込み・並び替えを移植。
- `Frontend::append_results()` で対象固定ページ + 検索時のみ結果表示。
- `Redirect::maybe_redirect_qr()` で `rf_go` を安全に 1 か所処理。
- `Quick_Edit` で管理画面のクイック編集UI/保存を移植。

# 10. 子テーマ側で削除・コメントアウト候補の旧コード一覧

- Quick Edit 一式（`manage_edit_*`, `quick_edit_custom_box`, `save_post_*`）
- `rf_get`, `rf_build_args_from_get`, `the_content` 末尾描画
- `rf_go` の `template_redirect` / `init` 両ブロック（`init` 側は削除推奨）
- グローバル `echo "<style>..."` ブロック

# 11. 動作確認手順

1. プラグイン有効化
2. `/wp-login.php` ログイン確認
3. `/wp-admin/edit.php?post_type=ryouhou_food` でQuick Edit動作確認
4. `/allergy-food-navi-for-dog/?protein=鶏肉` で結果表示確認
5. `/allergy-food-navi-for-dog/?sort=hydro_first` で並び確認
6. `/?rf_go=1&pid=<ID>&to=amazon` でリダイレクト確認

# 12. ロールバック手順

1. プラグイン停止
2. 子テーマ旧コードを戻す（またはGit revert）
3. ログイン・管理画面・対象固定ページを順に再確認

# 13. 残課題 / TODO

- PDF出力機能（HTML/CSS/QR大量出力）の段階移植
- `amazon_link` / `aff_amazon` 等のフィールド名ゆれ統一
- `main_protein` など表示専用項目の責務分離
- 実データ件数でのメーカー順ソート性能確認（`post__in` 構築コスト）
