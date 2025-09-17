#!/usr/bin/env python3
"""
申請作成テスト
複数のユーザーがそれぞれのブラウザで申請を作成する
"""

import random
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

BASE_URL = "http://localhost:8080"

# 申請者リスト（各組織から複数選択） - 正しいメールアドレス形式
APPLICANTS = [
    {'email': 'hoshino.kazuko@wf.nrkk.technology', 'name': '星野和子', 'org': 1},
    {'email': 'sasada.junko@wf.nrkk.technology', 'name': '笹田純子', 'org': 1},
    {'email': 'saito.kazuaki@wf.nrkk.technology', 'name': '斉藤和明', 'org': 2},
    {'email': 'aoki.shota@wf.nrkk.technology', 'name': '青木翔太', 'org': 3},
    {'email': 'ishikawa.yuki@wf.nrkk.technology', 'name': '石川由紀', 'org': 4},
    {'email': 'ueda.takuya@wf.nrkk.technology', 'name': '上田拓也', 'org': 5},
    {'email': 'egawa.mai@wf.nrkk.technology', 'name': '江川舞', 'org': 6},
    {'email': 'ono.yuichi@wf.nrkk.technology', 'name': '大野雄一', 'org': 7},
    {'email': 'okada.saori@wf.nrkk.technology', 'name': '岡田沙織', 'org': 8},
    {'email': 'katayama.kenji@wf.nrkk.technology', 'name': '片山健司', 'org': 9},
    {'email': 'kawaguchi.miho@wf.nrkk.technology', 'name': '川口美穂', 'org': 10},
]

def create_chrome_driver():
    """Chrome WebDriverを作成"""
    chrome_options = webdriver.ChromeOptions()
    # chrome_options.add_argument('--headless')  # ヘッドフルモードで実行
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('--disable-gpu')
    chrome_options.add_argument('--disable-features=VizDisplayCompositor')
    chrome_options.add_argument('--disable-extensions')
    chrome_options.add_argument('--disable-plugins')
    chrome_options.add_argument('--disable-background-timer-throttling')
    chrome_options.add_argument('--disable-backgrounding-occluded-windows')
    chrome_options.add_argument('--disable-renderer-backgrounding')
    chrome_options.add_argument('--remote-debugging-port=9222')
    chrome_options.add_argument('--window-size=1920,1080')
    chrome_options.add_argument('--disable-blink-features=AutomationControlled')
    chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
    chrome_options.add_experimental_option('useAutomationExtension', False)

    print("    🔧 Creating new Chrome driver...")
    print("    ⏳ Installing Chrome driver via webdriver-manager...")
    service = Service(ChromeDriverManager().install())
    print("    ✓ Chrome driver installed")

    print("    ⏳ Starting Chrome browser...")
    driver = webdriver.Chrome(service=service, options=chrome_options)
    print("    ✅ Chrome browser started")

    return driver

def login(driver, email, password='password'):
    """ログイン処理"""
    driver.get(f"{BASE_URL}/login")
    wait = WebDriverWait(driver, 15)

    # ページの読み込み完了を待つ
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(1)

    # ログインフォーム入力
    email_input = wait.until(EC.element_to_be_clickable((By.NAME, "email")))
    email_input.clear()
    email_input.send_keys(email)

    password_input = wait.until(EC.element_to_be_clickable((By.NAME, "password")))
    password_input.clear()
    password_input.send_keys(password)

    # ログインボタンクリック
    login_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit']")))
    login_button.click()

    # ログイン後の画面を待つ
    time.sleep(3)

def create_application(driver, applicant_name, index):
    """申請を作成"""
    wait = WebDriverWait(driver, 15)
    driver.get(f"{BASE_URL}/dashboard")

    # 申請作成ページへ - dashboardのnewApplicationBtnをクリック
    new_application_btn = wait.until(EC.element_to_be_clickable((By.ID, "newApplicationBtn")))
    new_application_btn.click()

    # ページの読み込み完了を待つ
    wait.until(EC.presence_of_element_located((By.TAG_NAME, "body")))
    time.sleep(2)

    print(f"   📍 Current URL: {driver.current_url}")

    # フォーム入力
    title = f"テスト申請_{applicant_name}_{index}_{int(time.time())}"
    title_input = wait.until(EC.element_to_be_clickable((By.NAME, "title")))
    title_input.clear()
    title_input.send_keys(title)
    print(f"   ✓ Filled title: {title}")

    description_input = wait.until(EC.element_to_be_clickable((By.NAME, "description")))
    description_input.clear()
    description_input.send_keys(f"これは{applicant_name}による{index}番目のテスト申請です")
    print("   ✓ Filled description")

    types = ['purchase', 'expense', 'leave', 'other']
    priorities = ['low', 'medium', 'high']

    # Type選択
    selected_type = random.choice(types)
    type_select = wait.until(EC.element_to_be_clickable((By.NAME, "type")))
    type_select.click()
    time.sleep(0.5)
    type_option = wait.until(EC.element_to_be_clickable((By.XPATH, f"//select[@name='type']/option[@value='{selected_type}']")))
    type_option.click()
    print(f"   ✓ Selected type: {selected_type}")

    # Priority選択
    selected_priority = random.choice(priorities)
    priority_select = wait.until(EC.element_to_be_clickable((By.NAME, "priority")))
    priority_select.click()
    time.sleep(0.5)
    priority_option = wait.until(EC.element_to_be_clickable((By.XPATH, f"//select[@name='priority']/option[@value='{selected_priority}']")))
    priority_option.click()
    print(f"   ✓ Selected priority: {selected_priority}")

    # 申請ボタンクリック
    submit_button = wait.until(EC.element_to_be_clickable((By.ID, "submitApplicationBtn")))
    print("   ✓ Found submit button, clicking...")
    submit_button.click()

    # 申請後のページ遷移を待つ
    time.sleep(3)

    current_url = driver.current_url
    print(f"   📍 After submit URL: {current_url}")

    if '/applications/' in current_url:
        application_id = current_url.split('/')[-1]
        print(f"   ✅ Created: {title} (ID: {application_id})")
        return application_id
    else:
        print("   ❌ Failed to create application")
        return None
    driver.get(f"{BASE_URL}/applications/create")

def test_create_applications():
    """複数ユーザーで申請を作成するテスト"""
    print("🧪 Application Creation Test")
    print("=" * 50)
    print(f"🔗 Base URL: {BASE_URL}")
    print(f"👥 Testing with {len(APPLICANTS)} applicants")
    print("🚀 Starting test execution...")

    created_applications = []

    for applicant in APPLICANTS:
        print(f"\n👤 Applicant: {applicant['name']} (Organization {applicant['org']})")
        print("=" * 40)

        driver = None
        try:
            # 新しいブラウザセッションを開始
            driver = create_chrome_driver()

            # ログイン
            print(f"🔐 Logging in as {applicant['name']}...")
            login(driver, applicant['email'])
            print(f"✅ {applicant['name']} logged in successfully")

            # 2-3個の申請を作成
            num_applications = random.randint(2, 3)
            print(f"📝 Creating {num_applications} applications...")

            for i in range(1, num_applications + 1):
                print(f"📝 Creating {i} / {num_applications} applications...")
                app_id = create_application(driver, applicant['name'], i)
                if app_id:
                    created_applications.append({
                        'applicant': applicant['name'],
                        'org': applicant['org'],
                        'application_id': app_id
                    })
                time.sleep(1)  # 申請間の待機

            print(f"✅ Created {num_applications} applications for {applicant['name']}")

        except Exception as e:
            print(f"❌ Error for {applicant['name']}: {e}")

        finally:
            if driver:
                driver.quit()
                print(f"🚪 Closed {applicant['name']}'s browser")

        # ユーザー間の待機
        time.sleep(2)

    # 結果サマリー
    print("\n" + "=" * 50)
    print("🎉 APPLICATION CREATION TEST COMPLETED!")
    print("=" * 50)
    print(f"📊 Total applications created: {len(created_applications)}")
    print(f"📊 Applicants tested: {len(APPLICANTS)}")

    # 組織別の統計
    org_stats = {}
    for app in created_applications:
        org = app['org']
        if org not in org_stats:
            org_stats[org] = 0
        org_stats[org] += 1

    print("\n📊 Applications by Organization:")
    for org in sorted(org_stats.keys()):
        print(f"   Organization {org}: {org_stats[org]} applications")

    return created_applications

if __name__ == "__main__":
    created_apps = test_create_applications()

    # 作成された申請IDをファイルに保存（承認テストで使用）
    import json
    with open('created_applications.json', 'w') as f:
        json.dump(created_apps, f, ensure_ascii=False, indent=2)
    print(f"\n📁 Application IDs saved to created_applications.json")