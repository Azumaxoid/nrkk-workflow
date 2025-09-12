#!/usr/bin/env python3
"""
Single Application Flow Test
1件の申請を作成してすぐに承認フローをテストする
"""

import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import Select

class SingleAppFlowTest:
    def __init__(self):
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        # 田島和也で確実にテスト
        self.approver = {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'}

    def create_driver(self, window_position=(0,0)):
        """新しいChromeドライバーを作成"""
        chrome_options = Options()
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1000,700")
        chrome_options.add_argument(f"--window-position={window_position[0]},{window_position[1]}")
        
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.implicitly_wait(5)
        return driver

    def login_user(self, user, driver):
        """指定したドライバーでユーザーログイン"""
        wait = WebDriverWait(driver, 15)
        
        print(f"🔐 Logging in {user['name']}...")
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
        print(f"✅ {user['name']} logged in")
        time.sleep(2)
        return wait

    def create_single_application(self, driver, wait):
        """1件の申請を作成"""
        print("📝 Creating single application...")
        
        driver.get(f"{self.base_url}/applications/create")
        
        title_field = wait.until(EC.presence_of_element_located((By.NAME, "title")))
        
        app_title = f"シングルテスト申請 - {int(time.time())}"
        title_field.clear()
        title_field.send_keys(app_title)
        
        description_field = driver.find_element(By.NAME, "description")
        description_field.clear()
        description_field.send_keys("単一申請テスト\\n\\n1件のみの申請で承認フローをテスト")
        
        try:
            type_select = Select(driver.find_element(By.NAME, "type"))
            type_select.select_by_value("other")
        except:
            pass
        
        submit_button = driver.find_element(By.XPATH, "//button[@type='submit']")
        driver.execute_script("arguments[0].click();", submit_button)
        
        time.sleep(5)  # 承認フロー開始を待つ
        print(f"✅ Application created: {app_title}")
        return app_title

    def check_approvals(self, driver, wait, user):
        """承認待ち案件をチェック"""
        print(f"🔍 {user['name']} checking approvals...")
        
        driver.get(f"{self.base_url}/applications/my-approvals")
        time.sleep(3)
        
        # ページソースを少し表示してデバッグ
        page_text = driver.find_element(By.TAG_NAME, "body").text
        if "承認" in page_text:
            print("   ✓ Page contains '承認' text")
        else:
            print("   ⚠️ Page does not contain '承認' text")
        
        approve_buttons = driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
        button_count = len(approve_buttons)
        
        print(f"   📋 Found {button_count} approve buttons")
        
        if button_count > 0:
            print("   🎯 Trying to approve first item...")
            button = approve_buttons[0]
            
            driver.execute_script("arguments[0].scrollIntoView(true);", button)
            time.sleep(1)
            driver.execute_script("arguments[0].click();", button)
            time.sleep(2)
            
            try:
                comment_field = wait.until(EC.presence_of_element_located((By.NAME, "comment")))
                comment_field.send_keys(f"{user['name']}による単一申請承認")
                
                submit_btn = driver.find_element(By.ID, "approvalSubmit")
                submit_btn.click()
                
                time.sleep(3)
                print(f"   ✅ {user['name']} approved successfully!")
                return True
                
            except Exception as e:
                print(f"   ❌ Approval modal error: {e}")
                return False
        else:
            print(f"   ℹ️ No approvals found for {user['name']}")
            return False

    def run_single_flow_test(self):
        """単一申請フローテストを実行"""
        print("🧪 Single Application Flow Test")
        print("=" * 40)
        
        # Phase 1: 管理者で申請作成
        print("\\n📋 PHASE 1: Admin creates single application")
        admin_driver = self.create_driver(window_position=(100, 100))
        
        try:
            admin_wait = self.login_user(self.admin, admin_driver)
            app_title = self.create_single_application(admin_driver, admin_wait)
            
            print("🚪 Closing admin browser...")
            admin_driver.quit()
            
            # Wait for approval flow to be set up
            print("⏳ Waiting 10 seconds for approval flow setup...")
            time.sleep(10)
            
            # Phase 2: 承認者で承認
            print("\\n✅ PHASE 2: Approver checks and approves")
            approver_driver = self.create_driver(window_position=(600, 100))
            
            try:
                approver_wait = self.login_user(self.approver, approver_driver)
                approved = self.check_approvals(approver_driver, approver_wait, self.approver)
                
                print("\\n🎉 SINGLE FLOW TEST COMPLETED!")
                print(f"📊 Application: {app_title}")
                print(f"📊 Approved: {'Yes' if approved else 'No'}")
                
                print("\\n🔍 Keeping approver browser open for 10 seconds...")
                time.sleep(10)
                
            finally:
                print("🚪 Closing approver browser...")
                approver_driver.quit()
                
        except Exception as e:
            print(f"❌ Single flow test failed: {e}")
            admin_driver.quit()

if __name__ == "__main__":
    test = SingleAppFlowTest()
    test.run_single_flow_test()