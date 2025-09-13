# New Relic Integration Setup

このドキュメントでは、承認ワークフローアプリケーションにNew Relic APMを統合する手順を説明します。

## 前提条件

1. New Relicアカウント（無料アカウントでも可）
2. New Relicライセンスキー

## Docker統合版の使用方法（推奨）

### 1. 環境設定ファイルの準備

`.env.newrelic`ファイルをコピーして設定してください：

```bash
cp .env.newrelic .env
```

ライセンスキーを設定：
```env
NEWRELIC_LICENSE_KEY=your-actual-newrelic-license-key
NEWRELIC_APP_NAME="Approval Workflow Production"
```

### 2. New Relic統合版でのビルドと起動

```bash
# New Relic統合版でビルド・起動
docker-compose -f docker-compose.newrelic.yml up -d --build

# インフラストラクチャ監視も含める場合
docker-compose -f docker-compose.newrelic.yml --profile monitoring up -d --build
```

### 3. 動作確認

```bash
# コンテナの状態確認
docker-compose -f docker-compose.newrelic.yml ps

# New Relicエージェントのログ確認
docker exec approval-workflow-app-newrelic cat /var/log/newrelic/php_agent.log
```

## 手動設定手順（既存環境）

### 1. 環境変数の設定

`.env`ファイルに以下の設定を追加してください：

```env
# New Relic Settings
NEWRELIC_LICENSE_KEY=your-actual-license-key-here
NEWRELIC_APP_NAME="Approval Workflow"
NEWRELIC_LOG_LEVEL=info
```

### 2. New Relic PHP Agentのインストール（推奨）

本格的な監視を行う場合は、New Relic PHP Agentをサーバーにインストールしてください：

#### Ubuntu/Debian:
```bash
echo 'deb http://apt.newrelic.com/debian/ newrelic non-free' | sudo tee /etc/apt/sources.list.d/newrelic.list
wget -O- https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
sudo apt-get update
sudo apt-get install newrelic-php5
```

#### Docker環境:
Dockerfileに以下を追加：
```dockerfile
RUN curl -L https://download.newrelic.com/php_agent/scripts/newrelic-install.sh | bash
RUN NR_INSTALL_SILENT=1 newrelic-install install
```

### 3. アプリケーションの再起動

設定変更後、アプリケーションを再起動してください：

```bash
docker-compose down
docker-compose up -d --build
```

## 監視される項目

### 1. HTTPリクエスト
- リクエストレスポンス時間
- エラー率
- スループット
- ルート別パフォーマンス

### 2. データベースクエリ
- クエリ実行時間
- スロークエリの検出（1秒以上）
- クエリ回数

### 3. 承認ワークフロー固有のメトリクス
- 申請作成イベント
- 承認処理イベント
- 一括承認テストのパフォーマンス

### 4. カスタムイベント
- `ApprovalWorkflow`: 一般的なワークフローイベント
- `BulkApprovalTest`: 一括承認テストの結果
- `SlowQuery`: スロークエリの詳細

## New Relicダッシュボードで確認できる情報

### パフォーマンスメトリクス
- `applications_per_second`: 毎秒処理される申請数
- `processing_time_seconds`: 処理時間
- `performance_score`: パフォーマンススコア

### ワークフロー分析
- 組織別の処理時間
- 申請タイプ別のパフォーマンス
- 承認者の処理速度

## トラブルシューティング

### New Relicに データが表示されない場合

1. ライセンスキーが正しく設定されているか確認
2. New Relic PHP Agentがインストールされているか確認
3. アプリケーションログでエラーをチェック

### PHPエクステンションなしで使用する場合

PHP New Relicエクステンションがインストールされていない環境でも、アプリケーションは正常に動作します。New Relic機能は自動的に無効化され、エラーは発生しません。

## コードでの使用例

```php
use App\Services\NewRelicService;

// サービス取得
$newRelic = app(NewRelicService::class);

// カスタムメトリクス記録
$newRelic->recordUserAction('application_approved', $userId, [
    'organization_id' => $orgId,
    'processing_time' => $processingTime
]);

// エラー記録
$newRelic->noticeError($exception);

// パフォーマンス測定
$startTime = microtime(true);
// ... 処理 ...
$endTime = microtime(true);
$newRelic->recordApprovalPerformance($orgName, $count, $endTime - $startTime);
```

## 費用について

- New Relic Freeアカウント: 100GB/月まで無料
- データ取り込み量を抑えるため、重要なイベントのみを記録
- 本番環境では適切なデータ保持期間を設定することを推奨

## セキュリティ

- ライセンスキーは絶対に公開リポジトリにコミットしないでください
- `.env`ファイルは`.gitignore`に含まれています
- 環境変数として安全に管理してください