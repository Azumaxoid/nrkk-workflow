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
            service = Service(chrome_driver_path)
        else:
            # ローカル開発環境ではwebdriver-managerを使用
            service = Service(ChromeDriverManager().install())
        
        driver = webdriver.Chrome(service=service, options=chrome_options)
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

    def approve_with_user(self, approver):
        """指定承認者用ブラウザで承認処理"""
        print(f"👨‍💼 Starting approval browser for {approver['name']}...")
        
        driver, wait = self.login_user(approver)
        if not driver:
            return 0
            
        approved = 0
        try:
            driver.get(f"{self.base_url}/applications")
            time.sleep(3)
            
            # 「確認中」バッジがついた行を探す
            pending_rows = driver.find_elements(By.XPATH, "//span[contains(@class, 'badge') and contains(text(), '確認中')]/ancestor::tr")
            row_count = len(pending_rows)
            
            print(f"   📋 {approver['name']} found {row_count} pending approvals")
            
            if row_count > 0:
                # Process up to 3 approvals
                for i in range(min(3, row_count)):
                    try:
                        # 再度確認中の行を取得（DOM更新のため）
                        current_rows = driver.find_elements(By.XPATH, "//span[contains(@class, 'badge') and contains(text(), '確認中')]/ancestor::tr")
                        
                        if i < len(current_rows):
                            row = current_rows[i]
                            
                            # 行内のリンクをクリックして詳細ページに移動
                            link = row.find_element(By.TAG_NAME, "a")
                            link.click()
                            time.sleep(3)
                            print(f"     📍 詳細ページに移動: {driver.current_url}")
                            
                            try:
                                # 承認ボタンを探す
                                approve_button = wait.until(EC.element_to_be_clickable((By.CSS_SELECTOR, "button[onclick*='approve']")))
                                approve_button.click()
                                time.sleep(2)
                                print(f"     🔘 承認ボタンをクリック")
                                
                                # モーダルが表示されるのを待つ
                                modal = wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".modal")))
                                print(f"     ✅ 承認モーダルが表示されました")
                                
                                # コメント欄に入力
                                comment_field = modal.find_element(By.NAME, "comment")
                                comment_field.send_keys(f"{approver['name']}による承認 - マルチブラウザテスト")
                                print(f"     ✅ コメントを入力")
                                
                                # 承認を実行
                                submit_btn = modal.find_element(By.ID, "approvalSubmit")
                                submit_btn.click()
                                
                                approved += 1
                                time.sleep(3)
                                print(f"     ✅ {approver['name']} approved item {i+1}")
                                
                                # 申請一覧に戻る
                                driver.get(f"{self.base_url}/applications")
                                time.sleep(3)
                                
                            except Exception as modal_e:
                                print(f"     ⚠️ Modal error: {str(modal_e)[:50]}")
                                # エラー時は申請一覧に戻る
                                driver.get(f"{self.base_url}/applications")
                                time.sleep(3)
                                
                    except Exception as e:
                        print(f"     ⚠️ Approval {i+1} failed: {str(e)[:50]}")
                        # エラー時は申請一覧に戻る
                        driver.get(f"{self.base_url}/applications")
                        time.sleep(3)
                        continue
            
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
            
            # Phase 2: 各承認者が別ブラウザで承認
            print("\\n✅ PHASE 2: Each approver uses separate browser")
            print("-"*50)
            
            total_approved = 0
            for i, approver in enumerate(self.approvers):
                print(f"\\n👤 Approver {i+1}: {approver['name']}")
                approved = self.approve_with_user(approver)
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
            admin_approved = self.approve_with_user(self.admin)
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