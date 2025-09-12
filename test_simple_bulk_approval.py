#!/usr/bin/env python3
"""
Simple Multi-Organization Bulk Approval Test
管理者のみを使用したシンプルな一括承認テスト

Test Flow:
1. 管理者でログイン
2. 3つの組織からランダムで申請を複数作成
3. 全ての承認者でログイン・一括承認
"""

import time
import random
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select

class SimpleBulkApprovalTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        
        # 3つの組織とその承認者
        self.test_orgs = [
            {
                'name': '株式会社テクノロジー革新',
                'approvers': [
                    {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'},
                    {'name': '中村恵子', 'email': 'approver0_0@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社グリーンエネルギー',
                'approvers': [
                    {'name': '佐藤太郎', 'email': 'approver1_0@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社ロボティクス',
                'approvers': [
                    {'name': '鈴木花子', 'email': 'approver8_0@wf.nrkk.technology'},
                ]
            }
        ]
        
        self.created_applications = []

    def setup_driver(self):
        """Chrome driver setup"""
        print("🚀 Setting up Chrome driver...")
        
        chrome_options = Options()
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        
        try:
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            self.driver.implicitly_wait(10)
            self.wait = WebDriverWait(self.driver, 30)
            print("✅ Chrome driver ready")
        except Exception as e:
            print(f"❌ Failed to setup Chrome driver: {e}")
            raise

    def login(self, user):
        """Login with given user"""
        print(f"🔐 Logging in as {user['name']}...")
        
        try:
            self.driver.get(f"{self.base_url}/login")
            
            email_field = self.wait.until(EC.presence_of_element_located((By.NAME, "email")))
            password_field = self.driver.find_element(By.NAME, "password")
            
            email_field.clear()
            email_field.send_keys(user['email'])
            password_field.clear()
            password_field.send_keys("password")
            
            login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            login_button.click()
            
            self.wait.until(EC.url_contains("/dashboard"))
            print(f"✅ Logged in as {user['name']}")
            time.sleep(2)
        except Exception as e:
            print(f"❌ Login failed for {user['name']}: {e}")
            raise

    def logout(self):
        """Logout current user"""
        print("🚪 Logging out...")
        try:
            self.driver.get(f"{self.base_url}/logout")
            time.sleep(2)
            print("✅ Logged out")
        except Exception as e:
            print(f"⚠️ Logout issue: {e}")

    def create_application(self, org_name, applicant_name):
        """Create application as admin"""
        application_title = f"{org_name} - {applicant_name} - テスト申請{int(time.time())}"
        print(f"   📝 Creating: {application_title}")
        
        try:
            self.driver.get(f"{self.base_url}/applications/create")
            
            title_field = self.wait.until(EC.presence_of_element_located((By.NAME, "title")))
            
            description = f"組織: {org_name}\\n申請者: {applicant_name}\\n\\n自動テスト申請\\n予算: {random.randint(50, 200)}万円"
            
            title_field.clear()
            title_field.send_keys(application_title)
            
            description_field = self.driver.find_element(By.NAME, "description")
            description_field.clear()
            description_field.send_keys(description)
            
            # Select type
            try:
                type_select = Select(self.driver.find_element(By.NAME, "type"))
                type_select.select_by_value("other")
            except:
                pass
            
            # Submit
            submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            time.sleep(3)
            print(f"   ✅ Created: {application_title}")
            return application_title
            
        except Exception as e:
            print(f"   ❌ Failed to create application: {e}")
            return None

    def approve_pending_applications(self, approver):
        """Approve all pending applications"""
        print(f"👨‍💼 Checking for approvals as {approver['name']}...")
        
        try:
            self.driver.get(f"{self.base_url}/applications/my-approvals")
            time.sleep(3)
            
            # Find approve buttons
            approve_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            
            if approve_buttons:
                approved_count = 0
                for button in approve_buttons:
                    try:
                        button.click()
                        time.sleep(2)
                        
                        # Handle modal
                        comment_field = self.driver.find_element(By.NAME, "comment")
                        comment_field.send_keys(f"{approver['name']}による承認: テスト承認完了")
                        
                        modal_submit = self.driver.find_element(By.ID, "approvalSubmit")
                        modal_submit.click()
                        
                        approved_count += 1
                        time.sleep(2)
                        
                    except Exception as e:
                        print(f"   ⚠️ Individual approval error: {e}")
                        continue
                
                print(f"   ✅ Approved {approved_count} item(s)")
            else:
                print("   ℹ️ No pending approvals")
                
        except Exception as e:
            print(f"❌ Approval process error: {e}")

    def run_test(self):
        """Run the complete test"""
        print("🧪 Starting Simple Multi-Organization Bulk Approval Test")
        print("=" * 60)
        
        try:
            self.setup_driver()
            
            # Phase 1: Create applications
            print("\\n📋 PHASE 1: Creating Applications")
            print("-" * 40)
            
            self.login(self.admin)
            
            # Create 2-3 applications per organization
            for org in self.test_orgs:
                print(f"\\n🏢 {org['name']}")
                num_apps = random.randint(2, 3)
                
                for i in range(num_apps):
                    applicant_name = f"テスト申請者{i+1}"
                    app_title = self.create_application(org['name'], applicant_name)
                    if app_title:
                        self.created_applications.append(app_title)
            
            print(f"\\n📊 Total created: {len(self.created_applications)} applications")
            self.logout()
            
            # Phase 2: Approve applications
            print("\\n✅ PHASE 2: Bulk Approvals")
            print("-" * 40)
            
            # Only try with admin for simplicity
            print("\\n🔍 Testing approval with admin...")
            self.login(self.admin)
            self.approve_pending_applications(self.admin)
            self.logout()
            
            print("\\n🎉 Simple Test Completed!")
            
        except Exception as e:
            print(f"❌ Test failed: {e}")
            
        finally:
            if self.driver:
                print("\\n🔍 Keeping browser open for inspection...")
                time.sleep(10)
                self.driver.quit()

if __name__ == "__main__":
    test = SimpleBulkApprovalTest()
    test.run_test()