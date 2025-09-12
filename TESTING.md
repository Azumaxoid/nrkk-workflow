# UI Testing with Selenium

このドキュメントでは、Selenium を使用したUIテストの実行方法について説明します。

## 概要

このプロジェクトには、以下のUIテストが含まれています：

- **Pythonベース** (`tests/selenium_tests.py`): 包括的なSeleniumテストスイート
- **テストランナー** (`run_selenium_tests.sh`): 自動環境チェック付きテスト実行スクリプト

## 前提条件

### 必須要件
- Docker & Docker Compose (アプリケーション実行用)
- Python 3.x
- pip3

### オプション要件
- Google Chrome または Chromium (ローカル実行用)

## テスト実行方法

### 1. 簡単な方法（推奨）

テストランナースクリプトを使用：

```bash
./run_selenium_tests.sh
```

このスクリプトは自動的に以下を行います：
- 必要なPythonパッケージのインストール
- Dockerアプリケーションの起動確認
- Selenium Gridの利用可能性チェック
- テストの実行

### 2. 手動実行

#### 前準備
```bash
# Python依存関係のインストール
pip3 install selenium pytest

# アプリケーションの起動
docker-compose up -d

# サービスの起動を待つ（約30秒）
```

#### テスト実行
```bash
python3 tests/selenium_tests.py
```

## テスト構成

### 含まれるテスト

1. **test_01_homepage_loads** - ホームページの読み込み確認
2. **test_02_admin_login** - 管理者ログイン機能
3. **test_03_application_list_page** - 申請一覧ページ
4. **test_04_create_application_page** - 申請作成ページ
5. **test_05_my_approvals_page** - 承認待ち一覧ページ
6. **test_06_application_detail_page** - 申請詳細ページ
7. **test_07_navigation_elements** - ナビゲーション要素
8. **test_08_responsive_design** - レスポンシブデザイン
9. **test_09_logout_functionality** - ログアウト機能

### テストユーザー

テストには以下のユーザーが使用されます：

```python
test_users = {
    'admin': {
        'email': 'admin@wf.nrkk.technology',
        'password': 'password',
        'role': 'admin'
    },
    'applicant': {
        'email': 'tazuma@wf.nrkk.technology', 
        'password': 'password',
        'role': 'approver'
    }
}
```

## Selenium Grid（オプション）

Selenium Gridが利用可能な場合、テストは自動的にGridを使用します：

- **Selenium Hub**: http://localhost:4444
- **Grid Console**: http://localhost:4444/grid/console

Gridが利用できない場合は、ローカルのChromeドライバーにフォールバックします。

## トラブルシューティング

### よくある問題

1. **Chrome driver not found**
   ```bash
   # macOS
   brew install --cask chromedriver
   
   # Ubuntu/Debian  
   sudo apt-get install chromium-chromedriver
   
   # または、webdriver-managerを使用
   pip install webdriver-manager
   ```

2. **Application not responding**
   ```bash
   # サービス状態の確認
   docker-compose ps
   
   # ログの確認
   docker-compose logs
   
   # サービスの再起動
   docker-compose restart
   ```

3. **Permission denied for test runner**
   ```bash
   chmod +x run_selenium_tests.sh
   ```

### デバッグモード

テストのデバッグには以下のオプションを使用：

```python
# selenium_tests.py内のsetUpClass()メソッドで
# chrome_options.add_argument("--headless") をコメントアウト
# ブラウザGUIが表示されてテスト実行を確認可能
```

## 継続的インテグレーション

CI/CD環境でのテスト実行例：

```yaml
# .github/workflows/tests.yml
name: UI Tests
on: [push, pull_request]

jobs:
  ui-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Set up Python
        uses: actions/setup-python@v2
        with:
          python-version: 3.x
      - name: Start services
        run: docker-compose up -d
      - name: Wait for services
        run: sleep 30
      - name: Run UI tests
        run: ./run_selenium_tests.sh
```

## テスト結果の解釈

- ✅ **Pass**: テストが正常に完了
- ❌ **Fail**: テストが失敗（要修正）  
- ⚠️ **Warning**: テストは通過したが注意が必要

詳細なログとスクリーンショットはテスト実行時に出力されます。

## カスタマイズ

### 新しいテストの追加

`tests/selenium_tests.py`に新しいテストメソッドを追加：

```python
def test_10_custom_functionality(self):
    """Custom test description"""
    print("\\n🧪 Testing custom functionality...")
    
    # テストロジック
    self.login('admin')
    # ... テスト実装
    
    print("✓ Custom test completed")
```

### テスト設定の変更

- **ベースURL**: `self.base_url = "http://localhost:8080"`
- **タイムアウト**: `self.driver.implicitly_wait(10)`
- **ウィンドウサイズ**: `screen_sizes` リストを変更

テストの実行方法について質問がある場合は、プロジェクトメンテナーにお問い合わせください。