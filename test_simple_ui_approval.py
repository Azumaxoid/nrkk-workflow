#!/usr/bin/env python3
"""
Simple UI-based Approval Test
シンプルなUI承認テスト - 管理者のみで動作確認
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

class SimpleUIApprovalTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}

    def setup_driver(self):
        """Chrome driver setup with minimal options"""
        print("🚀 Setting up Chrome driver...")
        
        chrome_options = Options()
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        
        try:
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            self.driver.implicitly_wait(5)
            self.wait = WebDriverWait(self.driver, 15)
            print("✅ Chrome driver ready")
        except Exception as e:
            print(f"❌ Driver setup failed: {e}")
            raise

    def login_admin(self):
        """Login as admin"""
        print("🔐 Logging in as admin...")
        
        try:
            self.driver.get(f"{self.base_url}/login")
            print(f"   Navigated to: {self.driver.current_url}")
            
            email_field = self.wait.until(EC.presence_of_element_located((By.NAME, "email")))
            password_field = self.driver.find_element(By.NAME, "password")
            
            email_field.clear()
            email_field.send_keys(self.admin['email'])
            password_field.clear()
            password_field.send_keys("password")
            
            login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            login_button.click()
            
            # Wait for dashboard
            self.wait.until(EC.url_contains("/dashboard"))
            print(f"✅ Login successful - Current URL: {self.driver.current_url}")
            time.sleep(2)
            return True
            
        except Exception as e:
            print(f"❌ Admin login failed: {e}")
            print(f"   Current URL: {self.driver.current_url}")
            return False

    def create_single_application(self):
        """Create one application for testing"""
        print("📝 Creating a single test application...")
        
        try:
            # Navigate to create page
            create_url = f"{self.base_url}/applications/create"
            print(f"   Navigating to: {create_url}")
            self.driver.get(create_url)
            
            print(f"   Current URL after navigation: {self.driver.current_url}")
            
            # Wait for title field
            print("   Waiting for title field...")
            title_field = self.wait.until(EC.presence_of_element_located((By.NAME, "title")))
            print("   ✓ Title field found")
            
            # Fill basic info
            app_title = f"UIテスト申請 - {int(time.time())}"
            print(f"   Filling title: {app_title}")
            
            title_field.clear()
            title_field.send_keys(app_title)
            
            # Fill description
            print("   Filling description...")
            description_field = self.driver.find_element(By.NAME, "description")
            description_field.clear()
            description_field.send_keys("UIテスト用の申請です。\\n\\nSeleniumによる自動作成。")
            
            # Select type
            print("   Setting type...")
            try:
                type_select = Select(self.driver.find_element(By.NAME, "type"))
                type_select.select_by_value("other")
                print("   ✓ Type selected: other")
            except Exception as e:
                print(f"   ⚠️ Type selection failed: {e}")
            
            # Try to find and click submit
            print("   Looking for submit button...")
            submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            print("   ✓ Submit button found")
            
            # Scroll to button
            self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_button)
            time.sleep(1)
            
            print("   Clicking submit button...")
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            # Wait a bit
            time.sleep(3)
            print(f"   Current URL after submit: {self.driver.current_url}")
            print(f"✅ Application created: {app_title}")
            return True
            
        except Exception as e:
            print(f"❌ Application creation failed: {e}")
            print(f"   Current URL: {self.driver.current_url}")
            print(f"   Page title: {self.driver.title}")
            return False

    def check_pending_approvals(self):
        """Check for pending approvals"""
        print("🔍 Checking for pending approvals...")
        
        try:
            approval_url = f"{self.base_url}/applications/my-approvals"
            print(f"   Navigating to: {approval_url}")
            self.driver.get(approval_url)
            
            print(f"   Current URL: {self.driver.current_url}")
            time.sleep(3)
            
            # Look for approve buttons
            approve_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            button_count = len(approve_buttons)
            
            print(f"   Found {button_count} approve buttons")
            
            if button_count > 0:
                print("   Trying to approve first item...")
                first_button = approve_buttons[0]
                
                # Scroll and click
                self.driver.execute_script("arguments[0].scrollIntoView(true);", first_button)
                time.sleep(1)
                self.driver.execute_script("arguments[0].click();", first_button)
                time.sleep(2)
                
                # Handle modal
                try:
                    comment_field = self.wait.until(EC.presence_of_element_located((By.NAME, "comment")))
                    comment_field.send_keys("UIテスト承認 - 自動承認")
                    
                    submit_btn = self.driver.find_element(By.ID, "approvalSubmit")
                    submit_btn.click()
                    
                    time.sleep(3)
                    print("   ✅ Approval completed")
                    
                except Exception as modal_e:
                    print(f"   ⚠️ Modal handling failed: {modal_e}")
            
            return button_count
            
        except Exception as e:
            print(f"❌ Approval check failed: {e}")
            print(f"   Current URL: {self.driver.current_url}")
            return 0

    def run_simple_test(self):
        """Run simple UI test"""
        print("🧪 Simple UI Approval Test")
        print("=" * 40)
        
        try:
            self.setup_driver()
            
            # Step 1: Login
            if not self.login_admin():
                return
            
            # Step 2: Create application
            print("\\n📋 Creating application...")
            self.create_single_application()
            
            # Step 3: Check approvals
            print("\\n✅ Checking approvals...")
            pending_count = self.check_pending_approvals()
            
            print(f"\\n🎉 Simple UI test completed!")
            print(f"   Pending approvals found: {pending_count}")
            
        except Exception as e:
            print(f"❌ Simple UI test failed: {e}")
            
        finally:
            if self.driver:
                print("\\n🔍 Keeping browser open for 10 seconds...")
                time.sleep(10)
                self.driver.quit()
                print("🏁 Browser closed")

if __name__ == "__main__":
    test = SimpleUIApprovalTest()
    test.run_simple_test()