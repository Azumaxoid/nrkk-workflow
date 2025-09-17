#!/usr/bin/env python3
"""
ユーザーごとに別々のブラウザを使用する一括承認テスト

フロー:
1. 申請者が申請を作成（3件）
2. 承認者が別ブラウザで承認処理

Requirements:
pip install selenium webdriver-manager
"""

import time
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait, Select
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

class MultiBrowserApprovalTest:
    def __init__(self):
        self.base_url = os.getenv("APP_URL", "http://localhost:8080")
        # テスト用申請者（一般ユーザー）
        self.applicants = [
            {'name': '星野和子', 'email': 'hoshino.kazuko@wf.nrkk.technology'},
            {'name': '笹田純子', 'email': 'sasada.junko@wf.nrkk.technology'},
            {'name': '斉藤和明', 'email': 'saito.kazuaki@wf.nrkk.technology'},
        ]
        
        # テスト用承認者  
        self.approvers = [
            {'name': '中村恵子', 'email': 'nakamura.keiko@wf.nrkk.technology'},
            {'name': '木村智子', 'email': 'kimura.tomoko@wf.nrkk.technology'},
        ]
        
        # 管理者（最終承認者）
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        
        self.created_applications = []

    def create_driver(self):
        """新しいChromeドライバーインスタンスを作成"""
        print("    🔧 Creating new Chrome driver...")
        chrome_options = Options()
        
        # コンテナ環境での必須オプション
        chrome_options.add_argument('--headless')  # コンテナではヘッドレスモード
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--disable-gpu')
        chrome_options.add_argument('--disable-features=VizDisplayCompositor')
        chrome_options.add_argument('--window-size=1920,1080')
        chrome_options.add_argument('--remote-debugging-port=9222')
        chrome_options.add_argument('--disable-extensions')
        chrome_options.add_argument('--disable-plugins')
        chrome_options.add_argument('--disable-background-timer-throttling')
        chrome_options.add_argument('--disable-backgrounding-occluded-windows')
        chrome_options.add_argument('--disable-renderer-backgrounding')
        
        # コンテナ環境でのChromeDriverパス
        chrome_driver_path = os.getenv('CHROME_DRIVER_PATH')
        if chrome_driver_path and os.path.exists(chrome_driver_path):
            print(f"    ✓ Using Chrome driver at: {chrome_driver_path}")
            service = Service(chrome_driver_path)
        else:
            # ローカル開発環境ではwebdriver-managerを使用
            print("    ⏳ Installing Chrome driver via webdriver-manager...")
            service = Service(ChromeDriverManager().install())
            print("    ✓ Chrome driver installed")

        print("    ⏳ Starting Chrome browser...")
        driver = webdriver.Chrome(service=service, options=chrome_options)
        print("    ✅ Chrome browser started")
        return driver

    def login_user(self, user):
        """指定ユーザーでログイン"""
        driver = self.create_driver()
        wait = WebDriverWait(driver, 10)
        
        try:
            print(f"🔐 Starting new browser for {user['name']}...")
            driver.get(f"{self.base_url}/login")
            
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            password_field = driver.find_element(By.NAME, "password")
            
            email_field.clear()
            email_field.send_keys(user['email'])
            password_field.clear()
            password_field.send_keys("password")
            
            login_button = driver.find_element(By.XPATH, "//button[@type='submit']")
            login_button.click()
            
            wait.until(EC.url_contains("/dashboard"))
            print(f"✅ {user['name']} logged in successfully")
            time.sleep(2)
            
            return driver, wait
            
        except Exception as e:
            print(f"❌ Login failed for {user['name']}: {e}")
            driver.quit()
            return None, None

    def create_applications_with_applicants(self, count=3):
        """申請者用ブラウザで申請を作成"""
        print(f"📝 Creating {count} applications with applicant browsers...")
        
        created = 0
        for i in range(count):
            applicant = self.applicants[i % len(self.applicants)]
            print(f"🔐 Starting new browser for {applicant['name']}...")
            
            driver, wait = self.login_user(applicant)
            if not driver:
                continue
                
            try:
                driver.get(f"{self.base_url}/applications/create")
                time.sleep(2)
                
                # Check if we're on the right page
                current_url = driver.current_url
                print(f"   📍 Current URL: {current_url}")
                
                timestamp = int(time.time())
                app_title = f"マルチブラウザ申請{i+1} - {timestamp}"
                
                title_field = wait.until(EC.presence_of_element_located((By.NAME, "title")))
                title_field.clear()
                title_field.send_keys(app_title)
                print(f"   ✓ Filled title: {app_title}")
                
                description_field = driver.find_element(By.NAME, "description")
                description_field.clear()
                description_field.send_keys(f"{applicant['name']}による申請\\n\\nマルチブラウザテスト用の申請書です。")
                print("   ✓ Filled description")
                
                try:
                    type_select = Select(driver.find_element(By.NAME, "type"))
                    type_select.select_by_value("other")
                    print("   ✓ Selected type: other")
                except Exception as e:
                    print(f"   ⚠️ Could not select type: {e}")
                
                try:
                    priority_select = Select(driver.find_element(By.NAME, "priority"))
                    priority_select.select_by_value("medium")
                    print("   ✓ Selected priority: medium")
                except Exception as e:
                    print(f"   ⚠️ Could not select priority: {e}")
                
                submit_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[type='submit'].btn-primary")))
                print("   ✓ Found submit button, clicking...")
                submit_button.click()
                
                time.sleep(3)
                
                # Check if we were redirected (success) or stayed on same page (error)
                new_url = driver.current_url
                print(f"   📍 After submit URL: {new_url}")
                
                if new_url != current_url and "applications/create" not in new_url:
                    print(f"   ✅ Created: {app_title}")
                    self.created_applications.append(app_title)
                    created += 1
                else:
                    # Look for error messages
                    error_elements = driver.find_elements(By.CSS_SELECTOR, ".invalid-feedback, .alert-danger")
                    if error_elements:
                        for error in error_elements:
                            if error.is_displayed():
                                print(f"   ❌ Validation error: {error.text}")
                    else:
                        print("   ❌ Form submission failed - no redirect occurred")
                
            except Exception as e:
                print(f"   ❌ Failed to create application {i+1}: {e}")
                # Print current page source for debugging if needed
                try:
                    print(f"   📍 Current URL on error: {driver.current_url}")
                except:
                    pass
            finally:
                print(f"🚪 Closing {applicant['name']}'s browser...")
                driver.quit()
                    
        return created

    def approve_with_user(self, approver, org_index):
        """指定承認者用ブラウザで承認処理（組織インデックスに応じて全承認か選択承認を使い分け）"""
        print(f"👨‍💼 Starting approval browser for {approver['name']} (Organization {org_index + 1})...")

        driver, wait = self.login_user(approver)
        if not driver:
            return 0

        approved = 0
        try:
            # 承認待ち一覧ページへ移動 - applicationsBtnをクリック
            applications_btn = wait.until(EC.element_to_be_clickable((By.ID, "applicationsBtn")))
            applications_btn.click()
            time.sleep(3)

            # 2番目(index=1)と5番目(index=4)の組織は「全て承認」を使用
            if org_index == 1 or org_index == 4:
                print(f"   🎯 Organization {org_index + 1}: Using 'Approve All' feature")

                # 承認待ちの数を確認
                approval_cards = driver.find_elements(By.CSS_SELECTOR, ".card")
                pending_count = len(approval_cards)
                print(f"   📋 Found {pending_count} pending approvals")

                if pending_count > 0:
                    try:
                        # 「全て承認」ボタンをIDで検索してクリック
                        approve_all_button = wait.until(EC.element_to_be_clickable((By.ID, "approveAllBtn")))
                        approve_all_button.click()
                        print("   ✅ Clicked 'Approve All' button")
                        time.sleep(2)

                        # alertを処理
                        try:
                            alert = driver.switch_to.alert
                            print(f"   📢 Alert: {alert.text[:50]}...")
                            alert.accept()
                            print("   ✅ Alert accepted")
                        except:
                            print("   ⚠️ No alert found")

                        # モーダルを待つ
                        try:
                            modal = wait.until(EC.visibility_of_element_located((By.ID, "bulkApprovalModal")))
                            print("   ✅ Modal appeared")

                            # コメント入力（IDを使用）
                            comment = f"組織{org_index + 1} - {approver['name']}による全承認"
                            comment_field = driver.find_element(By.ID, "bulkComment")
                            comment_field.send_keys(comment)
                            print("   ✅ Comment entered")

                            # 送信ボタンクリック（IDを使用）
                            submit_btn = driver.find_element(By.ID, "bulkApprovalSubmit")
                            submit_btn.click()
                            print("   ✅ Submit clicked")
                            time.sleep(5)

                            # 結果確認
                            driver.get(f"{self.base_url}/my-approvals")
                            time.sleep(3)
                            remaining = driver.find_elements(By.CSS_SELECTOR, ".card")
                            approved = pending_count - len(remaining)
                            print(f"   ✅ Approved {approved} items using 'Approve All'")

                        except Exception as e:
                            print(f"   ⚠️ Modal handling error: {str(e)[:50]}")

                    except Exception as e:
                        print(f"   ❌ Approve All failed: {str(e)[:50]}")

            else:
                # それ以外の組織は「選択したものを承認」を使用
                print(f"   🎯 Organization {org_index + 1}: Using 'Selective Approval' feature")

                # 承認待ちカードを確認
                approval_cards = driver.find_elements(By.CSS_SELECTOR, ".card")
                total_pending = len(approval_cards)
                print(f"   📋 Found {total_pending} pending approvals")

                if total_pending > 0:
                    # 「すべて選択」チェックボックスをクリック
                    try:
                        print(f"   ☑️ Try to Select all items)")
                        select_all_checkbox = driver.find_element(By.ID, "selectAll")
                        select_all_checkbox.click()
                        selected_count = len(driver.find_elements(By.CSS_SELECTOR, 'input[type="checkbox"][id^="approval_"]:checked'))
                        print(f"   ☑️ Selected all approvals ({selected_count} items)")
                        time.sleep(0.5)
                    except Exception as e:
                        print(f"   ❌ Failed to select all: {e}")
                        selected_count = 0

                    if selected_count > 0:
                        print(f"   ✅ Selected {selected_count} approvals")

                        # 一括承認ボタンをクリック
                        try:
                            # 一括承認ボタンをIDで検索してクリック
                            bulk_approve_btn = wait.until(EC.element_to_be_clickable((By.ID, "bulkApproveBtn")))
                            bulk_approve_btn.click()
                            time.sleep(2)

                            # モーダルを待つ
                            try:
                                modal = wait.until(EC.visibility_of_element_located((By.ID, "bulkApprovalModal")))
                                print("   ✅ Bulk approval modal appeared")

                                # コメント入力（IDを使用）
                                comment = f"組織{org_index + 1} - {approver['name']}による選択承認"
                                comment_field = driver.find_element(By.ID, "bulkComment")
                                comment_field.send_keys(comment)
                                print("   ✅ Comment entered")

                                # 実行ボタンクリック（IDを使用）
                                submit_btn = driver.find_element(By.ID, "bulkApprovalSubmit")
                                submit_btn.click()
                                print("   ✅ Submit clicked")
                                time.sleep(5)

                                approved = selected_count
                                print(f"   ✅ Approved {approved} selected items")

                            except Exception as e:
                                print(f"   ⚠️ Modal handling error: {str(e)[:50]}")

                        except Exception as e:
                            print(f"   ❌ Bulk approve failed: {str(e)[:50]}")

            print(f"   ✅ {approver['name']} approved {approved} items")

        except Exception as e:
            print(f"❌ Error during approval: {e}")
        finally:
            print(f"🚪 Closing {approver['name']}'s browser...")
            driver.quit()

        return approved

    def run_test(self):
        """メインテストの実行"""
        print("🧪 Multi-Browser Approval Test")
        print("="*50)
        print(f"🔗 Base URL: {self.base_url}")
        print("🚀 Starting test execution...")
        
        try:
            # Phase 1: 申請者が申請作成（別ブラウザで）
            print("\\n📋 PHASE 1: Applicants create applications (separate browsers)")
            print("-"*50)
            created_count = self.create_applications_with_applicants(3)
            print(f"✅ Created {created_count} applications")
            
            if created_count == 0:
                print("❌ No applications created, stopping test")
                return
                
            # Wait between phases
            print("\\n⏳ Waiting 5 seconds between phases...")
            time.sleep(5)
            
            # Phase 2: 各承認者が別ブラウザで承認（組織インデックスを渡す）
            print("\\n✅ PHASE 2: Each approver uses separate browser")
            print("-"*50)

            total_approved = 0
            for i, approver in enumerate(self.approvers):
                print(f"\\n👤 Approver {i+1}: {approver['name']}")
                # approverのインデックスを組織インデックスとして使用
                approved = self.approve_with_user(approver, i)
                total_approved += approved

                # Wait between different approvers
                if i < len(self.approvers) - 1:
                    print("   ⏳ Waiting 3 seconds before next approver...")
                    time.sleep(3)
            
            # Wait before admin phase
            print("\\n⏳ Waiting 5 seconds before admin phase...")
            time.sleep(5)
            
            # Phase 3: 管理者が最終承認
            print("\\n👑 PHASE 3: Admin final approval")
            print("-"*50)
            print(f"\\n👤 Admin: {self.admin['name']}")
            # 管理者は最後の組織として扱う（通常の選択承認）
            admin_approved = self.approve_with_user(self.admin, len(self.approvers))
            total_approved += admin_approved
            
            # Final results
            print("\\n🎉 MULTI-BROWSER TEST COMPLETED!")
            print("="*40)
            print(f"📊 Applications created: {created_count}")
            print(f"📊 Total approvals processed: {total_approved}")
            print(f"📊 Browsers used: {created_count + len(self.approvers) + 1} ({created_count} applicants + {len(self.approvers)} approvers + 1 admin)")
            
        except Exception as e:
            print(f"❌ Test failed with error: {e}")

if __name__ == "__main__":
    test = MultiBrowserApprovalTest()
    test.run_test()