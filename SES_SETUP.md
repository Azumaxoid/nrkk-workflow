# Amazon SES メール設定ガイド

## 概要
このアプリケーションはAmazon SES（Simple Email Service）を使用してメール通知を送信します。

## セットアップ手順

### 1. AWS アカウントの準備
1. [AWS Console](https://console.aws.amazon.com/)にログイン
2. IAMユーザーを作成または既存のユーザーを使用

### 2. IAM権限の設定
以下の権限を持つポリシーをユーザーに付与：
```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ses:SendEmail",
                "ses:SendRawEmail"
            ],
            "Resource": "*"
        }
    ]
}
```

### 3. SESの設定（東京リージョン：ap-northeast-1）
1. AWS ConsoleでSESサービスに移動
2. リージョンを「アジアパシフィック（東京）」に変更
3. **Email Addresses**で送信元メールアドレスを検証
4. サンドボックスモードの場合、受信者メールアドレスも検証が必要

### 4. アプリケーション設定
`.env`ファイルを編集：
```bash
# Mail Settings (Amazon SES)
MAIL_MAILER=ses
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="Approval Workflow"

# AWS Credentials for SES
AWS_ACCESS_KEY_ID=your-actual-access-key-id
AWS_SECRET_ACCESS_KEY=your-actual-secret-access-key
AWS_DEFAULT_REGION=ap-northeast-1
```

### 5. 設定のテスト
```bash
# Dockerコンテナ内でテストコマンドを実行
docker-compose exec app php artisan mail:test-ses your-email@example.com

# カスタムメッセージでテスト
docker-compose exec app php artisan mail:test-ses your-email@example.com \
  --subject="テストメール" \
  --message="これはSESのテストメールです"
```

## サンドボックスモードについて

### 制限事項
- 送信先メールアドレスの事前検証が必要
- 1日あたり200通まで
- 送信レート1秒あたり1通まで

### 本番環境への移行
1. AWS Consoleで「Request production access」をクリック
2. 用途説明を記入して申請
3. 承認後、制限が解除される

## トラブルシューティング

### エラー: Email address is not verified
**原因**: サンドボックスモードで未検証のメールアドレスに送信
**解決**: SESコンソールで受信者メールアドレスを検証

### エラー: Invalid security token
**原因**: AWS認証情報が正しくない
**解決**: `.env`のAWS_ACCESS_KEY_IDとAWS_SECRET_ACCESS_KEYを確認

### エラー: Could not connect to host
**原因**: リージョン設定の誤り
**解決**: AWS_DEFAULT_REGIONが正しいリージョンに設定されているか確認

## 代替設定（SMTPを使用する場合）

SESが利用できない場合は、`.env`を以下のように設定してSMTPを使用：

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Approval Workflow"
```

## 参考リンク
- [Amazon SES Documentation](https://docs.aws.amazon.com/ses/)
- [Laravel Mail Documentation](https://laravel.com/docs/10.x/mail)
- [AWS SDK for PHP](https://docs.aws.amazon.com/sdk-for-php/)