#!/usr/bin/env python3
"""
Complete UI Flow Test - Admin → Logout → Approver Login → Approve
完全なUIフロー - 管理者で申請作成 → ログアウト → 承認者でログイン → 承認
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

class CompleteUIFlowTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        
        # テスト用承認者
        self.approvers = [
            {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'},
            {'name': '中村恵子', 'email': 'approver0_0@wf.nrkk.technology'},
            {'name': '木村智子', 'email': 'approver1_0@wf.nrkk.technology'},
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
            self.driver.implicitly_wait(5)
            self.wait = WebDriverWait(self.driver, 15)
            print("✅ Chrome driver ready")
        except Exception as e:
            print(f"❌ Driver setup failed: {e}")
            raise

    def login(self, user):
        """Login with given user"""
        print(f"🔐 Logging in as {user['name']} ({user['email']})...")
        
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
            
            # Wait for dashboard
            self.wait.until(EC.url_contains("/dashboard"))
            print(f"✅ Logged in as {user['name']}")
            time.sleep(2)
            return True
            
        except Exception as e:
            print(f"❌ Login failed for {user['name']}: {e}")
            return False

    def logout(self):
        """Logout current user"""
        print("🚪 Logging out...")
        try:
            self.driver.get(f"{self.base_url}/logout")
            time.sleep(2)
            print("✅ Logged out successfully")
        except Exception as e:
            print(f"⚠️ Logout issue: {e}")

    def create_applications(self, count=3):
        """Create multiple applications"""
        print(f"📝 Creating {count} applications...")
        
        created = 0
        for i in range(count):
            try:
                # Navigate to create page
                self.driver.get(f"{self.base_url}/applications/create")
                
                # Wait for form
                title_field = self.wait.until(EC.presence_of_element_located((By.NAME, "title")))
                
                # Fill application
                app_title = f"UIテスト申請{i+1} - {int(time.time())}"
                title_field.clear()
                title_field.send_keys(app_title)
                
                description_field = self.driver.find_element(By.NAME, "description")
                description_field.clear()
                description_field.send_keys(f"申請{i+1}\\n\\nUIテスト用申請\\n予算: {random.randint(50, 200)}万円")
                
                # Select type
                try:
                    type_select = Select(self.driver.find_element(By.NAME, "type"))
                    type_select.select_by_value("other")
                except:
                    pass
                
                # Submit
                submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
                self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
                time.sleep(1)
                self.driver.execute_script("arguments[0].click();", submit_button)
                
                time.sleep(3)
                print(f"   ✅ Created: {app_title}")
                self.created_applications.append(app_title)
                created += 1
                
            except Exception as e:
                print(f"   ❌ Failed to create application {i+1}: {e}")
                
        return created

    def approve_applications(self, approver):
        """Approve all pending applications for given approver"""
        print(f"👨‍💼 Checking approvals for {approver['name']}...")
        
        try:
            # Navigate to approvals page
            self.driver.get(f"{self.base_url}/applications/my-approvals")
            time.sleep(3)
            
            # Find approve buttons
            approve_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            button_count = len(approve_buttons)
            
            print(f"   📋 Found {button_count} pending approvals")
            
            if button_count == 0:
                print("   ℹ️ No approvals to process")
                return 0
            
            approved = 0
            # Process each approval
            for i in range(min(3, button_count)):  # Limit to 3 for safety
                try:
                    # Re-find buttons as DOM changes
                    current_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                    
                    if i < len(current_buttons):
                        button = current_buttons[i]
                        
                        # Click approve
                        self.driver.execute_script("arguments[0].scrollIntoView(true);", button)
                        time.sleep(1)
                        self.driver.execute_script("arguments[0].click();", button)
                        time.sleep(2)
                        
                        # Handle modal
                        try:
                            comment_field = self.wait.until(EC.presence_of_element_located((By.NAME, "comment")))
                            comment_field.send_keys(f"{approver['name']}によるUI承認 - 申請{i+1}承認")
                            
                            submit_btn = self.driver.find_element(By.ID, "approvalSubmit")
                            submit_btn.click()
                            
                            approved += 1
                            time.sleep(2)
                            print(f"     ✅ Approved item {i+1}")
                            
                        except Exception as modal_e:
                            print(f"     ⚠️ Modal error: {str(modal_e)[:50]}")
                            
                except Exception as e:
                    print(f"     ⚠️ Approval {i+1} failed: {str(e)[:50]}")
                    continue
            
            print(f"   ✅ Approved {approved} items")
            return approved
            
        except Exception as e:
            print(f"❌ Approval process failed: {e}")
            return 0

    def run_complete_flow(self):
        """Run the complete UI flow test"""
        print("🧪 Complete UI Flow Test")
        print("=" * 50)
        
        try:
            self.setup_driver()
            
            # Phase 1: Admin creates applications
            print("\\n📋 PHASE 1: Admin creates applications")
            print("-" * 30)
            
            if not self.login(self.admin):
                return
            
            created_count = self.create_applications(3)
            print(f"✅ Created {created_count} applications")
            
            self.logout()
            
            # Phase 2: Approvers process applications
            print("\\n✅ PHASE 2: Approvers process applications")
            print("-" * 30)
            
            total_approved = 0
            for approver in self.approvers:
                print(f"\\n👤 Testing with {approver['name']}")
                
                if self.login(approver):
                    approved = self.approve_applications(approver)
                    total_approved += approved
                    self.logout()
                    time.sleep(2)  # Pause between approvers
                else:
                    print(f"   ⚠️ Skipping {approver['name']} due to login failure")
            
            # Phase 3: Final results
            print("\\n🎉 FLOW COMPLETED!")
            print("=" * 30)
            print(f"📊 Applications created: {created_count}")
            print(f"📊 Total approvals: {total_approved}")
            print(f"📊 Created apps: {len(self.created_applications)}")
            
        except Exception as e:
            print(f"❌ Complete flow test failed: {e}")
            
        finally:
            if self.driver:
                print("\\n🔍 Keeping browser open for 10 seconds for inspection...")
                time.sleep(10)
                self.driver.quit()
                print("🏁 Browser closed")

if __name__ == "__main__":
    test = CompleteUIFlowTest()
    test.run_complete_flow()