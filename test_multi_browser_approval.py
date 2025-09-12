#!/usr/bin/env python3
"""
Multi-Browser Approval Test
ユーザーごとに別々のブラウザを使用する一括承認テスト
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

class MultiBrowserApprovalTest:
    def __init__(self):
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        
        # テスト用承認者
        self.approvers = [
            {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'},
            {'name': '中村恵子', 'email': 'approver0_0@wf.nrkk.technology'},
            {'name': '木村智子', 'email': 'approver1_0@wf.nrkk.technology'},
        ]
        
        self.created_applications = []

    def create_driver(self):
        """新しいChromeドライバーインスタンスを作成"""
        chrome_options = Options()
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        chrome_options.add_argument(f"--window-position={random.randint(0, 200)},{random.randint(0, 200)}")
        
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.implicitly_wait(5)
        return driver

    def login_user(self, user):
        """指定ユーザーで新しいブラウザを起動してログイン"""
        print(f"🔐 Starting new browser for {user['name']}...")
        
        driver = self.create_driver()
        wait = WebDriverWait(driver, 15)
        
        try:
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

    def create_applications_with_admin(self, count=3):
        """管理者用ブラウザで申請を作成"""
        print(f"📝 Creating {count} applications with admin browser...")
        
        driver, wait = self.login_user(self.admin)
        if not driver:
            return 0
        
        created = 0
        try:
            for i in range(count):
                try:
                    driver.get(f"{self.base_url}/applications/create")
                    
                    title_field = wait.until(EC.presence_of_element_located((By.NAME, "title")))
                    
                    app_title = f"マルチブラウザ申請{i+1} - {int(time.time())}"
                    title_field.clear()
                    title_field.send_keys(app_title)
                    
                    description_field = driver.find_element(By.NAME, "description")
                    description_field.clear()
                    description_field.send_keys(f"申請{i+1}\\n\\nマルチブラウザテスト\\n予算: {random.randint(100, 500)}万円")
                    
                    try:
                        type_select = Select(driver.find_element(By.NAME, "type"))
                        type_select.select_by_value("other")
                    except:
                        pass
                    
                    submit_button = driver.find_element(By.XPATH, "//button[@type='submit']")
                    driver.execute_script("arguments[0].click();", submit_button)
                    
                    time.sleep(3)
                    print(f"   ✅ Created: {app_title}")
                    self.created_applications.append(app_title)
                    created += 1
                    
                except Exception as e:
                    print(f"   ❌ Failed to create application {i+1}: {e}")
                    
        finally:
            print(f"🚪 Closing admin browser...")
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
            driver.get(f"{self.base_url}/applications/my-approvals")
            time.sleep(3)
            
            approve_buttons = driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            button_count = len(approve_buttons)
            
            print(f"   📋 {approver['name']} found {button_count} pending approvals")
            
            if button_count > 0:
                # Process up to 3 approvals
                for i in range(min(3, button_count)):
                    try:
                        current_buttons = driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                        
                        if i < len(current_buttons):
                            button = current_buttons[i]
                            
                            driver.execute_script("arguments[0].scrollIntoView(true);", button)
                            time.sleep(1)
                            driver.execute_script("arguments[0].click();", button)
                            time.sleep(2)
                            
                            try:
                                comment_field = wait.until(EC.presence_of_element_located((By.NAME, "comment")))
                                comment_field.send_keys(f"{approver['name']}による承認 - マルチブラウザテスト")
                                
                                submit_btn = driver.find_element(By.ID, "approvalSubmit")
                                submit_btn.click()
                                
                                approved += 1
                                time.sleep(2)
                                print(f"     ✅ {approver['name']} approved item {i+1}")
                                
                            except Exception as modal_e:
                                print(f"     ⚠️ Modal error: {str(modal_e)[:50]}")
                                
                    except Exception as e:
                        print(f"     ⚠️ Approval {i+1} failed: {str(e)[:50]}")
                        continue
            
            print(f"   ✅ {approver['name']} approved {approved} items")
            
        finally:
            print(f"🚪 Closing {approver['name']}'s browser...")
            driver.quit()
            
        return approved

    def run_multi_browser_test(self):
        """マルチブラウザテストを実行"""
        print("🧪 Multi-Browser Approval Test")
        print("=" * 50)
        
        try:
            # Phase 1: 管理者ブラウザで申請作成
            print("\\n📋 PHASE 1: Admin creates applications (separate browser)")
            print("-" * 50)
            
            created_count = self.create_applications_with_admin(3)
            print(f"✅ Admin created {created_count} applications")
            
            if created_count == 0:
                print("❌ No applications created, stopping test")
                return
            
            # Wait between phases
            print("\\n⏳ Waiting 5 seconds between phases...")
            time.sleep(5)
            
            # Phase 2: 各承認者が別ブラウザで承認
            print("\\n✅ PHASE 2: Each approver uses separate browser")
            print("-" * 50)
            
            total_approved = 0
            for i, approver in enumerate(self.approvers):
                print(f"\\n👤 Approver {i+1}: {approver['name']}")
                approved = self.approve_with_user(approver)
                total_approved += approved
                
                # Wait between different approvers
                if i < len(self.approvers) - 1:
                    print("   ⏳ Waiting 3 seconds before next approver...")
                    time.sleep(3)
            
            # Results
            print("\\n🎉 MULTI-BROWSER TEST COMPLETED!")
            print("=" * 40)
            print(f"📊 Applications created: {created_count}")
            print(f"📊 Total approvals processed: {total_approved}")
            print(f"📊 Browsers used: {1 + len(self.approvers)} (1 admin + {len(self.approvers)} approvers)")
            
        except Exception as e:
            print(f"❌ Multi-browser test failed: {e}")

if __name__ == "__main__":
    test = MultiBrowserApprovalTest()
    test.run_multi_browser_test()