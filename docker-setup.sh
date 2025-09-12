#!/bin/bash

echo "=== 申請承認ワークフローシステム Docker セットアップ ==="

# Docker と Docker Compose のチェック
if ! command -v docker &> /dev/null; then
    echo "❌ Docker がインストールされていません"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose がインストールされていません"
    exit 1
fi

echo "✅ Docker と Docker Compose が利用可能です"

# 既存のコンテナを停止・削除
echo "🔄 既存のコンテナを停止・削除中..."
docker-compose down -v 2>/dev/null || true

# .env ファイルの作成
if [ ! -f .env ]; then
    echo "📝 .env ファイルを作成中..."
    cp .env.example .env
    
    # Docker用の設定に更新
    sed -i.bak 's/DB_HOST=127.0.0.1/DB_HOST=database/' .env
    sed -i.bak 's/DB_PASSWORD=/DB_PASSWORD=rootpassword/' .env
    sed -i.bak 's/APP_URL=http:\/\/localhost:8000/APP_URL=http:\/\/localhost:8080/' .env
    rm .env.bak 2>/dev/null || true
    echo "✅ .env ファイルが作成されました"
else
    echo "✅ .env ファイルが既に存在します"
fi

# Docker イメージのビルドとコンテナの起動
echo "🐳 Docker コンテナを起動中..."
docker-compose up -d --build

# データベースの準備完了まで待機
echo "⏳ データベースの準備完了を待機中..."
sleep 30

# Composer の依存関係インストール
echo "📦 Composer 依存関係をインストール中..."
docker-compose exec app composer install --no-dev --optimize-autoloader

# Laravel アプリケーションキーの生成
echo "🔑 Laravel アプリケーションキーを生成中..."
docker-compose exec app php artisan key:generate --force

# ストレージディレクトリの権限設定
echo "📁 ストレージディレクトリの権限を設定中..."
docker-compose exec app chmod -R 775 storage bootstrap/cache

# データベースマイグレーション実行
echo "🗄️ データベースマイグレーション実行中..."
docker-compose exec app php artisan migrate --force

# シードデータ投入
echo "🌱 シードデータを投入中..."
docker-compose exec app php artisan db:seed --force

# 設定キャッシュのクリア
echo "🧹 キャッシュをクリア中..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

echo ""
echo "🎉 セットアップが完了しました！"
echo ""
echo "📋 アクセス情報:"
echo "   - アプリケーション: http://localhost:8080"
echo "   - phpMyAdmin: http://localhost:8081"
echo ""
echo "👤 テストユーザー:"
echo "   - 申請者: applicant@example.com / password"
echo "   - 確認者: reviewer@example.com / password" 
echo "   - 承認者: approver@example.com / password"
echo "   - 管理者: admin@example.com / password"
echo ""
echo "🛠️ 便利なコマンド:"
echo "   - ログ確認: docker-compose logs -f"
echo "   - コンテナ停止: docker-compose down"
echo "   - 完全リセット: docker-compose down -v && ./docker-setup.sh"
echo ""
echo "✅ 準備完了！ブラウザでアプリケーションにアクセスしてください。"