# NewRelic機能復旧プロンプト

以下のプロンプトを別のClaude Codeセッションで実行してNewRelic機能を復旧してください：

---

**プロンプト:**

この Laravel 10 の承認ワークフローアプリケーションに New Relic APM 機能を再実装してください。

以下の要件でお願いします：

1. **NewRelicService クラスの作成**
   - パス: `app/Services/NewRelicService.php`
   - 機能: アプリケーションのパフォーマンス情報収集、エラートラッキング、カスタムメトリクス送信
   - メソッド: カスタムイベント送信、メトリクス記録、エラー記録機能

2. **NewRelicServiceProvider の作成**
   - パス: `app/Providers/NewRelicServiceProvider.php`
   - NewRelicService をサービスコンテナに登録
   - config/app.php の providers 配列に自動登録

3. **BulkApprovalTest コマンドでの利用**
   - `app/Console/Commands/BulkApprovalTest.php` で NewRelicService を利用
   - テスト実行時のメトリクス送信とパフォーマンス監視

4. **Docker 対応**
   - New Relic PHP エージェントを含む Dockerfile.newrelic の作成
   - docker-compose.newrelic.yml でのコンテナ構成
   - 環境変数での設定管理

5. **セットアップドキュメント**
   - NEWRELIC_SETUP.md でのインストール・設定手順

現在のアプリケーション構成：
- Laravel 10
- Docker コンテナ環境
- MariaDB データベース
- 承認ワークフロー機能

New Relic ライセンスキーとアプリ名は環境変数で設定可能にしてください。

---

このプロンプトを使用して NewRelic 機能を復旧できます。