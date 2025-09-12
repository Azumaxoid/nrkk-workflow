#!/usr/bin/env python3
"""
Headless Multi-Organization Bulk Approval Test
ヘッドレスモードでの一括承認テスト
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

class HeadlessBulkTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}

    def setup_driver(self):
        """Chrome driver setup with headless mode"""
        print("🚀 Setting up headless Chrome driver...")
        
        chrome_options = Options()
        chrome_options.add_argument("--headless")  # ヘッドレスモード
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        chrome_options.add_argument("--disable-gpu")
        
        try:
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            self.driver.implicitly_wait(10)
            self.wait = WebDriverWait(self.driver, 15)
            print("✅ Headless Chrome driver ready")
        except Exception as e:
            print(f"❌ Failed to setup driver: {e}")
            raise

    def login_admin(self):
        """Login as admin"""
        print("🔐 Logging in as admin...")
        
        try:
            self.driver.get(f"{self.base_url}/login")
            
            email_field = self.wait.until(EC.presence_of_element_located((By.NAME, "email")))
            password_field = self.driver.find_element(By.NAME, "password")
            
            email_field.clear()
            email_field.send_keys(self.admin['email'])
            password_field.clear()
            password_field.send_keys("password")
            
            login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            login_button.click()
            
            self.wait.until(EC.url_contains("/dashboard"))
            print("✅ Admin logged in")
            return True
        except Exception as e:
            print(f"❌ Admin login failed: {e}")
            return False

    def create_application(self, title_prefix):
        """Create a single application"""
        app_title = f"{title_prefix} - {int(time.time())}"
        print(f"📝 Creating: {app_title}")
        
        try:
            self.driver.get(f"{self.base_url}/applications/create")
            
            title_field = self.wait.until(EC.presence_of_element_located((By.NAME, "title")))
            
            title_field.clear()
            title_field.send_keys(app_title)
            
            description_field = self.driver.find_element(By.NAME, "description")
            description_field.clear()
            description_field.send_keys(f"テスト申請: {app_title}\\n\\n自動生成された申請です。")
            
            # Select type
            try:
                type_select = Select(self.driver.find_element(By.NAME, "type"))
                type_select.select_by_value("other")
            except:
                pass
            
            # Submit
            submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            time.sleep(2)
            print(f"✅ Created: {app_title}")
            return True
            
        except Exception as e:
            print(f"❌ Failed to create {app_title}: {e}")
            return False

    def check_pending_approvals(self):
        """Check for pending approvals"""
        print("🔍 Checking pending approvals...")
        
        try:
            self.driver.get(f"{self.base_url}/applications/my-approvals")
            time.sleep(3)
            
            approve_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            pending_count = len(approve_buttons)
            
            print(f"📋 Found {pending_count} pending approvals")
            return pending_count
            
        except Exception as e:
            print(f"❌ Error checking approvals: {e}")
            return 0

    def run_quick_test(self):
        """Run a quick test"""
        print("🧪 Starting Headless Quick Test")
        print("=" * 40)
        
        try:
            self.setup_driver()
            
            if not self.login_admin():
                return
            
            # Create 3 applications
            print("\\n📋 Creating applications...")
            created = 0
            for i in range(3):
                if self.create_application(f"テスト申請{i+1}"):
                    created += 1
            
            print(f"\\n📊 Created {created} applications")
            
            # Check for approvals
            print("\\n✅ Checking for approvals...")
            pending = self.check_pending_approvals()
            
            print(f"\\n🎉 Test completed! Created: {created}, Pending: {pending}")
            
        except Exception as e:
            print(f"❌ Test failed: {e}")
            
        finally:
            if self.driver:
                self.driver.quit()

if __name__ == "__main__":
    test = HeadlessBulkTest()
    test.run_quick_test()