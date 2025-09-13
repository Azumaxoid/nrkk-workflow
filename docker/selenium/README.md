# Selenium UI Test Container

承認ワークフローアプリケーションのマルチブラウザUIテスト用Dockerコンテナ

## 構成

- **Python 3.11**: テスト実行環境
- **Google Chrome**: ヘッドレスブラウザ
- **ChromeDriver**: Selenium WebDriver
- **Xvfb**: 仮想ディスプレイ

## ローカル実行

### 1. Dockerイメージのビルド

```bash
# プロジェクトルートで実行
docker build -f docker/selenium/Dockerfile -t approval-workflow-selenium .
```

### 2. 単発テスト実行

```bash
# アプリケーションが http://localhost:8080 で動作している場合
docker run --rm \
  -e APP_URL=http://host.docker.internal:8080 \
  --network host \
  approval-workflow-selenium
```

### 3. 6回繰り返しテスト実行

```bash
docker run --rm \
  -e APP_URL=http://host.docker.internal:8080 \
  --network host \
  approval-workflow-selenium \
  bash -c "for i in {1..6}; do echo '=== Test Run $i/6 ==='; python tests/test_multi_browser_approval.py; echo ''; sleep 5; done"
```

## Kubernetes実行

### 1. イメージをK8sクラスタに配布

```bash
# イメージをレジストリにプッシュ
docker tag approval-workflow-selenium your-registry.com/approval-workflow-selenium:latest
docker push your-registry.com/approval-workflow-selenium:latest
```

### 2. 単発Job実行

```bash
kubectl apply -f k8s/selenium-test-job.yaml
```

### 3. 定期実行（CronJob）

```bash
kubectl apply -f k8s/selenium-test-cronjob.yaml
```

### 4. 実行状況確認

```bash
# Job一覧
kubectl get jobs

# Pod一覧
kubectl get pods

# ログ確認
kubectl logs job/approval-workflow-selenium-test
```

### 5. CronJobの確認

```bash
# CronJob一覧
kubectl get cronjobs

# 実行履歴
kubectl get jobs --selector=app=approval-workflow-test
```

## 環境変数

| 変数名 | デフォルト値 | 説明 |
|--------|-------------|------|
| `APP_URL` | `http://localhost:8080` | テスト対象アプリケーションのURL |
| `DISPLAY` | `:99` | 仮想ディスプレイ番号 |
| `CHROME_DRIVER_PATH` | 自動検出 | ChromeDriverのパス |

## トラブルシューティング

### Chrome起動エラー
- `--no-sandbox`オプションが必要
- 共有メモリ(`/dev/shm`)のサイズが不足している場合は`--disable-dev-shm-usage`

### ネットワーク接続エラー
- K8s環境では`APP_URL`にサービス名を指定
- ローカル環境では`host.docker.internal`を使用

### メモリ不足
- Kubernetes Jobのリソース制限を調整
- 複数ブラウザの同時実行数を削減

## ファイル構成

```
docker/selenium/
├── Dockerfile          # コンテナイメージ定義
├── entrypoint.sh       # エントリポイントスクリプト
└── README.md           # このファイル

k8s/
├── selenium-test-job.yaml     # 単発実行Job
└── selenium-test-cronjob.yaml # 定期実行CronJob

requirements-test.txt   # Python依存関係
```