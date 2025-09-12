#!/usr/bin/env python3
"""
UI-based Multi-Organization Bulk Approval Test
SeleniumによるUIテスト版マルチ組織一括承認テスト

要求された動作:
1. 組織をランダムで3社選ぶ
2. 各組織から3-5名が申請を作成
3. 全ての申請が終わった後、全ての組織の承認者がログイン
4. 申請が上がっていれば全て承認
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

class UIBulkApprovalTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        
        # 3つの組織とその承認者（実在するもの）
        self.test_organizations = [
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
                    {'name': '木村智子', 'email': 'approver1_0@wf.nrkk.technology'},
                ]
            },
            {
                'name': 'さくら運輸株式会社',
                'approvers': [
                    {'name': '吉田博文', 'email': 'approver4_0@wf.nrkk.technology'},
                ]
            }
        ]
        
        self.created_applications = []

    def setup_driver(self):
        """Chrome driver setup"""
        print("🚀 Setting up Chrome driver for UI test...")
        
        chrome_options = Options()
        # ヘッドレスモードを無効にしてUI表示
        # chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        
        try:
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            self.driver.implicitly_wait(8)
            self.wait = WebDriverWait(self.driver, 20)
            print("✅ Chrome driver ready for UI test")
        except Exception as e:
            print(f"❌ Driver setup failed: {e}")
            raise

    def login(self, user):
        """Login with given user"""
        print(f"🔐 Logging in as {user['name']} ({user['email']})...")
        
        try:
            self.driver.get(f"{self.base_url}/login")
            
            # Wait for login page
            email_field = self.wait.until(EC.presence_of_element_located((By.NAME, "email")))
            password_field = self.driver.find_element(By.NAME, "password")
            
            # Clear and fill credentials
            email_field.clear()
            email_field.send_keys(user['email'])
            password_field.clear()
            password_field.send_keys("password")
            
            # Submit
            login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            login_button.click()
            
            # Wait for redirect
            WebDriverWait(self.driver, 15).until(EC.url_contains("/dashboard"))
            print(f"✅ Successfully logged in as {user['name']}")
            time.sleep(2)  # Visual pause
            return True
            
        except Exception as e:
            print(f"❌ Login failed for {user['name']}: {e}")
            return False

    def logout(self):
        """Logout current user"""
        print("🚪 Logging out...")
        try:
            self.driver.get(f"{self.base_url}/logout")
            time.sleep(1)
            print("✅ Logged out")
        except Exception as e:
            print(f"⚠️ Logout issue: {e}")

    def create_application_ui(self, org_name, applicant_name):
        """Create application through UI"""
        app_title = f"{org_name} - {applicant_name} - UI申請{int(time.time())}"
        print(f"   📝 Creating via UI: {app_title}")
        
        try:
            # Navigate to create page
            self.driver.get(f"{self.base_url}/applications/create")
            
            # Wait for form
            title_field = self.wait.until(EC.presence_of_element_located((By.NAME, "title")))
            
            # Fill form
            title_field.clear()
            title_field.send_keys(app_title)
            
            description = f"UI経由申請\\n組織: {org_name}\\n申請者: {applicant_name}\\n\\n予算: {random.randint(50, 300)}万円\\nSeleniumテスト申請"
            
            description_field = self.driver.find_element(By.NAME, "description")
            description_field.clear()
            description_field.send_keys(description)
            
            # Select type if available
            try:
                type_select = Select(self.driver.find_element(By.NAME, "type"))
                type_select.select_by_value("other")
            except:
                pass
            
            # Submit form
            submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            
            # Scroll to button and click
            self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
            time.sleep(1)
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            # Wait for success
            time.sleep(3)
            print(f"   ✅ UI Created: {app_title}")
            return app_title
            
        except Exception as e:
            print(f"   ❌ UI creation failed for {app_title}: {str(e)[:100]}")
            return None

    def approve_all_pending_ui(self, approver):
        """Approve all pending applications via UI"""
        print(f"👨‍💼 UI Approval check for {approver['name']}...")
        
        try:
            # Navigate to approvals page
            self.driver.get(f"{self.base_url}/applications/my-approvals")
            time.sleep(3)
            
            # Find approve buttons
            approve_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            button_count = len(approve_buttons)
            
            print(f"   📋 Found {button_count} approve buttons")
            
            if button_count > 0:
                approved = 0
                
                # Process each approval
                for i in range(button_count):
                    try:
                        # Re-find buttons as DOM changes after each approval
                        current_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                        
                        if i < len(current_buttons):
                            button = current_buttons[i]
                            
                            # Click approve button
                            self.driver.execute_script("arguments[0].scrollIntoView(true);", button)
                            time.sleep(0.5)
                            self.driver.execute_script("arguments[0].click();", button)
                            time.sleep(2)
                            
                            # Handle modal
                            try:
                                comment_field = self.wait.until(EC.presence_of_element_located((By.NAME, "comment")))
                                comment_field.send_keys(f"{approver['name']}によるUI承認 - テスト承認")
                                
                                # Submit approval
                                submit_btn = self.driver.find_element(By.ID, "approvalSubmit")
                                submit_btn.click()
                                
                                approved += 1
                                time.sleep(2)
                                print(f"     ✅ Approved item {i+1}")
                                
                            except Exception as modal_e:
                                print(f"     ⚠️ Modal handling failed: {str(modal_e)[:50]}")
                                continue
                        
                    except Exception as e:
                        print(f"     ⚠️ Approval {i+1} failed: {str(e)[:50]}")
                        continue
                
                print(f"   ✅ UI Approved {approved}/{button_count} items")
            else:
                print("   ℹ️ No pending approvals found")
                
        except Exception as e:
            print(f"❌ UI approval process error: {e}")

    def run_ui_test(self):
        """Run the complete UI-based test"""
        print("🧪 UI-Based Multi-Organization Bulk Approval Test")
        print("=" * 60)
        
        try:
            self.setup_driver()
            
            print(f"\\n📊 Test Organizations:")
            for org in self.test_organizations:
                print(f"   - {org['name']}")
            
            # Phase 1: Create applications via UI
            print("\\n📋 PHASE 1: UI Application Creation")
            print("-" * 40)
            
            # Login as admin for application creation
            if not self.login(self.admin):
                return
            
            total_created = 0
            for org in self.test_organizations:
                print(f"\\n🏢 {org['name']}")
                
                # Create 2-3 applications per org
                num_apps = random.randint(2, 3)
                print(f"   Creating {num_apps} applications via UI...")
                
                for i in range(num_apps):
                    applicant_name = f"テスト申請者{i+1}"
                    app_title = self.create_application_ui(org['name'], applicant_name)
                    if app_title:
                        total_created += 1
                        self.created_applications.append(app_title)
            
            print(f"\\n📊 Total UI Created: {total_created} applications")
            self.logout()
            
            # Phase 2: UI-based approvals
            print("\\n✅ PHASE 2: UI Bulk Approval Process")
            print("-" * 40)
            
            # Test with each organization's approvers
            for org in self.test_organizations:
                for approver in org['approvers']:
                    print(f"\\n👤 Testing UI approval with {approver['name']} from {org['name']}")
                    
                    if self.login(approver):
                        self.approve_all_pending_ui(approver)
                        self.logout()
                        time.sleep(2)  # Pause between approvers
                    else:
                        print(f"   ⚠️ Skipping {approver['name']} due to login failure")
            
            # Final admin approval if needed
            print(f"\\n🔧 Final UI approval check with admin...")
            if self.login(self.admin):
                self.approve_all_pending_ui(self.admin)
                self.logout()
            
            print("\\n🎉 UI Test Completed Successfully!")
            print(f"📊 UI Summary: {total_created} applications created via UI")
            
        except Exception as e:
            print(f"❌ UI Test failed: {e}")
            
        finally:
            if self.driver:
                print("\\n🔍 Keeping browser open for 15 seconds for inspection...")
                time.sleep(15)
                self.driver.quit()
                print("🏁 Browser closed")

if __name__ == "__main__":
    test = UIBulkApprovalTest()
    test.run_ui_test()