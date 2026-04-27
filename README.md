# Allergy Food Navi for Dog

このリポジトリは、`imokenpihiroyuki-rgb/-` から `imokenpihiroyuki-rgb/Allergy-food-navi-for-dog` へ移行した作業先です。

## 現在の状態
- SWELL子テーマ `functions.php` から切り出したアレルギーフードナビ機能を、`plugin/allergy-food-navi/` 配下の独自プラグインとして管理しています。
- ログイン障害を再発させないため、ログイン/管理画面/AJAX/REST/cron で不要処理を実行しないガードを入れています。

## 主なディレクトリ
- `plugin/allergy-food-navi/`: 独自プラグイン本体
- `docs/analysis-and-migration-plan.md`: 分析内容と段階移行計画

## 次に実施する想定
- ステージング環境での動作確認（ログイン・検索結果・Quick Edit・rf_go）
- 旧 `functions.php` からの未移植機能（PDF関連）の段階移行
