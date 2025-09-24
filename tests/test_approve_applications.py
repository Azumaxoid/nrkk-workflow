#!/usr/bin/env python3
"""
承認処理テスト
各組織の承認者が別々のブラウザで承認処理を行う
組織2と5は「全て承認」、それ以外は「選択承認」を使用
"""

import json
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.common.exceptions import NoSuchElementException, TimeoutException

BASE_URL = "http://localhost:8080"

# 承認者リスト（各組織から1名） - 正しいメールアドレス形式
APPROVERS = [
    {'email': 'nakamura.keiko@wf.nrkk.technology', 'name': '中村恵子', 'org': 1, 'use_approve_all': True},
    {'email': 'kimura.tomoko@wf.nrkk.technology', 'name': '木村智子', 'org': 2, 'use_approve_all': True},
    {'email': 'admin@wf.nrkk.technology', 'name': '管理者', 'org': 3, 'use_approve_all': False},
    {'email': 'sato.taro@wf.nrkk.technology', 'name': '佐藤太郎', 'org': 4, 'use_approve_all': False},
    {'email': 'suzuki.hanako@wf.nrkk.technology', 'name': '鈴木花子', 'org': 5, 'use_approve_all': True, 'use_reject_all': True},
    {'email': 'takahashi.ichiro@wf.nrkk.technology', 'name': '高橋一郎', 'org': 6, 'use_approve_all': False},
    {'email': 'tanaka.miki@wf.nrkk.technology', 'name': '田中美紀', 'org': 7, 'use_approve_all': False, 'test_combination_bugs': True},
    {'email': 'ito.kenta@wf.nrkk.technology', 'name': '伊藤健太', 'org': 8, 'use_approve_all': False},
    {'email': 'watanabe.yumi@wf.nrkk.technology', 'name': '渡辺由美', 'org': 9, 'use_approve_all': False},
    {'email': 'yamamoto.naoki@wf.nrkk.technology', 'name': '山本直樹', 'org': 10, 'use_approve_all': False},
]

def create_chrome_driver():
    """Chrome WebDriverを作成"""
    chrome_options = webdriver.ChromeOptions()
    # chrome_options.add_argument('--headless')
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
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
    wait = WebDriverWait(driver, 10)

    # ログインフォーム入力
    email_input = wait.until(EC.presence_of_element_located((By.NAME, "email")))
    email_input.send_keys(email)

    password_input = driver.find_element(By.NAME, "password")
    password_input.send_keys(password)

    # ログインボタンクリック
    login_button = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    login_button.click()

    # ログイン後の画面を待つ
    time.sleep(2)

def process_approvals_with_approve_all(driver, wait, approver_name):
    """全て承認機能を使用した承認処理"""
    try:
        # 全て承認ボタンをIDで探す
        approve_all_button = wait.until(
            EC.element_to_be_clickable((By.ID, "approveAllBtn"))
        )
        print("   📋 Found 'Approve All' button")

        # ボタンをクリック
        approve_all_button.click()
        print("   ✅ Clicked 'Approve All' button")

        # モーダルが表示されるまで待つ
        try:
            modal = wait.until(EC.visibility_of_element_located((By.ID, "bulkApprovalModal")))
            print("   ✅ Approve All modal appeared")

            # コメントを入力
            comment_input = driver.find_element(By.ID, "bulkComment")
            comment_input.send_keys(f"全て承認 - {approver_name}")
            print("   ✅ Comment entered")

            # 送信ボタンをクリック
            submit_btn = driver.find_element(By.ID, "bulkApprovalSubmit")
            submit_btn.click()
            print("   ✅ Submit clicked for Approve All")

            # 処理完了を待つ
            time.sleep(3)

            # 成功メッセージを確認
            try:
                success_alert = driver.find_element(By.CSS_SELECTOR, ".alert-success")
                if success_alert:
                    print(f"   ✅ Approve All successful: {success_alert.text}")
                    return True
            except:
                pass

        except TimeoutException:
            print("   ❌ Approve All modal did not appear")
            return False

    except (NoSuchElementException, TimeoutException) as e:
        print(f"   ❌ Approve All failed: {e}")
        return False

def process_approvals_with_reject_all(driver, wait, approver_name):
    """全て却下機能を使用した却下処理"""
    try:
        # 全て却下ボタンを探す
        reject_all_button = wait.until(
            EC.element_to_be_clickable((By.ID, "rejectAllBtn"))
        )
        print("   📋 Found 'Reject All' button")

        # ボタンをクリック
        reject_all_button.click()
        print("   ✅ Clicked 'Reject All' button")

        # モーダルが表示されるまで待つ
        try:
            modal = wait.until(EC.visibility_of_element_located((By.ID, "bulkRejectionModal")))
            print("   ✅ Reject All modal appeared")

            # コメントを入力
            comment_input = driver.find_element(By.ID, "bulkRejectComment")
            comment_input.send_keys(f"全て却下 - {approver_name}")
            print("   ✅ Comment entered")

            # 送信ボタンをクリック
            submit_btn = driver.find_element(By.ID, "bulkRejectionSubmit")
            submit_btn.click()
            print("   ✅ Submit clicked for Reject All")

            # 処理完了を待つ
            time.sleep(3)

            # 成功メッセージを確認
            try:
                success_alert = driver.find_element(By.CSS_SELECTOR, ".alert-success")
                if success_alert:
                    print(f"   ✅ Reject All successful: {success_alert.text}")
                    return True
            except:
                pass

        except TimeoutException:
            print("   ❌ Reject All modal did not appear")
            return False

    except (NoSuchElementException, TimeoutException) as e:
        print(f"   ❌ Reject All failed: {e}")
        return False

def test_combination_bugs(driver, wait, approver_name):
    """組み合わせバグをテストする単体処理"""
    try:
        # 承認待ちのアイテムを1つ選択
        approval_cards = driver.find_elements(By.CSS_SELECTOR, ".card")
        if len(approval_cards) > 0:
            # 最初のカードから申請IDを取得
            first_card = approval_cards[0]
            approval_link = first_card.find_element(By.CSS_SELECTOR, "a[href*='/approvals/']")
            approval_link.click()
            time.sleep(2)

            # 承認テスト - bulk_mode + 空コメントでバグを誘発
            print("   🐛 Testing approve() combination bug (bulk_mode + empty comment)")
            try:
                # 承認ボタンをクリック
                approve_btn = driver.find_element(By.CSS_SELECTOR, "button[formaction*='/approve']")

                # JavaScriptで bulk_mode パラメータを追加した form data を送信
                driver.execute_script("""
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = arguments[0].getAttribute('formaction');

                    var csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    form.appendChild(csrfInput);

                    var bulkModeInput = document.createElement('input');
                    bulkModeInput.type = 'hidden';
                    bulkModeInput.name = 'bulk_mode';
                    bulkModeInput.value = '1';
                    form.appendChild(bulkModeInput);

                    var commentInput = document.createElement('input');
                    commentInput.type = 'hidden';
                    commentInput.name = 'comment';
                    commentInput.value = '';
                    form.appendChild(commentInput);

                    document.body.appendChild(form);
                    form.submit();
                """, approve_btn)
                print("   ✅ Submitted approve with bulk_mode=1 and empty comment")
                time.sleep(2)
            except Exception as e:
                print(f"   ❌ Approve combination bug test failed: {e}")

            # 承認一覧に戻る
            driver.get(f"{BASE_URL}/my-approvals")
            time.sleep(2)

            # 次のアイテムで却下テスト
            approval_cards = driver.find_elements(By.CSS_SELECTOR, ".card")
            if len(approval_cards) > 0:
                print("   🐛 Testing reject() combination bug (POST + reason parameter)")
                second_card = approval_cards[0]
                approval_link = second_card.find_element(By.CSS_SELECTOR, "a[href*='/approvals/']")
                approval_link.click()
                time.sleep(2)

                try:
                    # 却下ボタンのformactionを取得
                    reject_btn = driver.find_element(By.CSS_SELECTOR, "button[formaction*='/reject']")

                    # JavaScriptで reason パラメータが空文字列の form data を送信
                    driver.execute_script("""
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = arguments[0].getAttribute('formaction');

                        var csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        form.appendChild(csrfInput);

                        var reasonInput = document.createElement('input');
                        reasonInput.type = 'hidden';
                        reasonInput.name = 'reason';
                        reasonInput.value = '';
                        form.appendChild(reasonInput);

                        var commentInput = document.createElement('input');
                        commentInput.type = 'hidden';
                        commentInput.name = 'comment';
                        commentInput.value = 'Valid comment for rejection';
                        form.appendChild(commentInput);

                        document.body.appendChild(form);
                        form.submit();
                    """, reject_btn)
                    print("   ✅ Submitted reject with empty reason and valid comment")
                    time.sleep(2)
                except Exception as e:
                    print(f"   ❌ Reject combination bug test failed: {e}")

    except Exception as e:
        print(f"   ❌ Combination bug test failed: {e}")

def process_approvals_selective(driver, wait, approver_name, max_approvals=3):
    """選択的承認機能を使用した承認処理"""
    approved_count = 0

    try:
        # すべて選択チェックボックスをクリック
        try:
            select_all_checkbox = driver.find_element(By.ID, "selectAll")
            select_all_checkbox.click()
            print("   ☑️ Selected all approvals")
            time.sleep(0.5)

            # 選択された項目数を確認
            selected_count = len(driver.find_elements(By.CSS_SELECTOR, 'input[type="checkbox"][id^="approval_"]:checked'))
        except Exception as e:
            print(f"   ❌ Failed to select all: {e}")
            return 0

        if selected_count > 0:
            print(f"   ✅ Selected {selected_count} approvals")

            # 選択したものを承認ボタンをIDで探す
            approve_selected_button = driver.find_element(By.ID, "bulkApproveBtn")
            approve_selected_button.click()
            print("   ✅ Clicked 'Approve Selected' button")

            # モーダルが表示されるまで待つ
            try:
                modal = wait.until(EC.visibility_of_element_located((By.ID, "bulkApprovalModal")))
                print("   ✅ Bulk approval modal appeared")

                # コメントを入力
                comment_input = driver.find_element(By.ID, "bulkComment")
                comment_input.send_keys(f"一括承認 - {approver_name}")
                print("   ✅ Comment entered")

                # 送信ボタンをクリック
                submit_btn = driver.find_element(By.ID, "bulkApprovalSubmit")
                submit_btn.click()
                print("   ✅ Submit clicked")

                approved_count = selected_count
                print(f"   ✅ Approved {approved_count} selected items")

                # 処理完了を待つ
                time.sleep(3)

            except TimeoutException:
                print("   ❌ Bulk approval modal did not appear")

    except Exception as e:
        print(f"   ❌ Selective approval failed: {e}")

    return approved_count

def test_approve_applications():
    """承認処理テスト"""
    print("🧪 Approval Processing Test")
    print("=" * 50)
    print(f"🔗 Base URL: {BASE_URL}")
    print(f"👥 Testing with {len(APPROVERS)} approvers")
    print("🚀 Starting test execution...")

    total_approved = 0
    approval_results = []

    for approver in APPROVERS:
        print(f"\n👤 Approver: {approver['name']} (Organization {approver['org']})")
        print("=" * 40)

        driver = None
        try:
            # 新しいブラウザセッションを開始
            driver = create_chrome_driver()

            # ログイン
            print(f"🔐 Logging in as {approver['name']}...")
            login(driver, approver['email'])
            print(f"✅ {approver['name']} logged in successfully")

            # 承認一覧ページへ移動 - applicationsBtnをクリック
            applications_btn = WebDriverWait(driver, 15).until(
                EC.element_to_be_clickable((By.ID, "applicationsBtn"))
            )
            applications_btn.click()
            time.sleep(2)

            wait = WebDriverWait(driver, 10)

            # 承認待ち件数を確認
            approval_cards = driver.find_elements(By.CSS_SELECTOR, ".card")
            pending_count = len(approval_cards)
            print(f"   📋 Found {pending_count} pending approvals")

            if pending_count > 0:
                if approver.get('use_reject_all', False):
                    # 全て却下機能を使用してバグを誘発
                    print(f"   🎯 Organization {approver['org']}: Using 'Reject All' feature (bug test)")
                    success = process_approvals_with_reject_all(driver, wait, approver['name'])
                    if success:
                        approved_count = pending_count
                        total_approved += approved_count
                        print(f"   ✅ {approver['name']} rejected ALL {approved_count} items")
                    else:
                        approved_count = 0
                        print(f"   ❌ {approver['name']} failed to reject all")
                elif approver.get('test_combination_bugs', False):
                    # 組み合わせバグをテスト
                    print(f"   🎯 Organization {approver['org']}: Testing combination bugs")
                    test_combination_bugs(driver, wait, approver['name'])
                    approved_count = 0  # バグテストのため実際の承認数は0
                elif approver['use_approve_all']:
                    # 全て承認機能を使用
                    print(f"   🎯 Organization {approver['org']}: Using 'Approve All' feature")
                    success = process_approvals_with_approve_all(driver, wait, approver['name'])
                    if success:
                        approved_count = pending_count
                        total_approved += approved_count
                        print(f"   ✅ {approver['name']} approved ALL {approved_count} items")
                    else:
                        approved_count = 0
                        print(f"   ❌ {approver['name']} failed to approve all")
                else:
                    # 選択的承認機能を使用
                    print(f"   🎯 Organization {approver['org']}: Using 'Selective Approval' feature")
                    approved_count = process_approvals_selective(driver, wait, approver['name'])
                    total_approved += approved_count
                    print(f"   ✅ {approver['name']} approved {approved_count} items")

                method = 'reject_all' if approver.get('use_reject_all', False) else \
                         'combination_bugs' if approver.get('test_combination_bugs', False) else \
                         'approve_all' if approver['use_approve_all'] else 'selective'

                approval_results.append({
                    'approver': approver['name'],
                    'org': approver['org'],
                    'method': method,
                    'approved_count': approved_count
                })
            else:
                print(f"   ℹ️ No pending approvals for {approver['name']}")
                method = 'reject_all' if approver.get('use_reject_all', False) else \
                         'combination_bugs' if approver.get('test_combination_bugs', False) else \
                         'approve_all' if approver['use_approve_all'] else 'selective'

                approval_results.append({
                    'approver': approver['name'],
                    'org': approver['org'],
                    'method': method,
                    'approved_count': 0
                })

        except Exception as e:
            print(f"❌ Error for {approver['name']}: {e}")
            approval_results.append({
                'approver': approver['name'],
                'org': approver['org'],
                'method': 'approve_all' if approver['use_approve_all'] else 'selective',
                'approved_count': 0,
                'error': str(e)
            })

        finally:
            if driver:
                driver.quit()
                print(f"🚪 Closed {approver['name']}'s browser")

        # 承認者間の待機
        time.sleep(3)

    # 結果サマリー
    print("\n" + "=" * 50)
    print("🎉 APPROVAL PROCESSING TEST COMPLETED!")
    print("=" * 50)
    print(f"📊 Total approvals processed: {total_approved}")
    print(f"📊 Approvers tested: {len(APPROVERS)}")

    # 承認方法別の統計
    approve_all_count = sum(1 for r in approval_results if r['method'] == 'approve_all')
    selective_count = sum(1 for r in approval_results if r['method'] == 'selective')

    print(f"\n📊 Approval Methods:")
    print(f"   Approve All: {approve_all_count} approvers (Org 2, 5)")
    print(f"   Selective: {selective_count} approvers")

    # 組織別の統計
    print(f"\n📊 Approvals by Organization:")
    for result in approval_results:
        status = "✅" if result['approved_count'] > 0 else "⚠️"
        print(f"   {status} Organization {result['org']} ({result['approver']}): {result['approved_count']} approvals ({result['method']})")

    # 結果をファイルに保存
    with open('approval_results.json', 'w') as f:
        json.dump(approval_results, f, ensure_ascii=False, indent=2)
    print(f"\n📁 Approval results saved to approval_results.json")

    return approval_results

if __name__ == "__main__":
    test_approve_applications()