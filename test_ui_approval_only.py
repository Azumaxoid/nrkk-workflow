#!/usr/bin/env python3
"""
UI Approval Only Test
Artisanで申請を作成してからUIで承認をテストする
"""

import time
import subprocess
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

class UIApprovalOnlyTest:
    def __init__(self):
        self.base_url = "http://localhost:8080"
        # 田島和也で確実にテスト
        self.approver = {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'}

    def create_driver(self):
        """新しいChromeドライバーを作成"""
        chrome_options = Options()
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.implicitly_wait(5)
        return driver

    def create_application_via_artisan(self):
        """Artisanコマンドで申請を作成"""
        print("📝 Creating application via Artisan command...")
        
        try:
            # Artisanコマンドで申請作成
            result = subprocess.run([
                "docker", "exec", "approval-workflow-app", 
                "php", "artisan", "tinker", "--execute",
                """
                \\$user = \\App\\Models\\User::where('organization_id', 1)->where('role', 'applicant')->first();
                if (!\\$user) {
                    \\$user = \\App\\Models\\User::where('organization_id', 1)->first();
                }
                if (\\$user) {
                    \\$app = \\App\\Models\\Application::create([
                        'title' => 'UIテスト申請 - ' . time(),
                        'description' => 'Artisan経由で作成されたUI承認テスト用申請',
                        'type' => 'other',
                        'priority' => 'medium',
                        'applicant_id' => \\$user->id,
                        'status' => 'under_review',
                        'due_date' => now()->addDays(7)
                    ]);
                    
                    \\$flow = \\App\\Models\\ApprovalFlow::where('organization_id', \\$user->organization_id)->first();
                    if (\\$flow) {
                        \\$app->update(['approval_flow_id' => \\$flow->id]);
                        \\$flow->createApprovals(\\$app);
                        echo 'Application created: ' . \\$app->id . ' - ' . \\$app->title;
                    } else {
                        echo 'No approval flow found';
                    }
                } else {
                    echo 'No user found';
                }
                """
            ], capture_output=True, text=True)
            
            if result.returncode == 0:
                output = result.stdout.strip()
                print(f"   ✅ {output}")
                return True
            else:
                print(f"   ❌ Artisan error: {result.stderr}")
                return False
                
        except Exception as e:
            print(f"❌ Failed to create application via Artisan: {e}")
            return False

    def login_and_approve(self):
        """ログインして承認処理"""
        print(f"🔐 Testing UI approval with {self.approver['name']}...")
        
        driver = self.create_driver()
        wait = WebDriverWait(driver, 15)
        
        try:
            # Login
            driver.get(f"{self.base_url}/login")
            
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            password_field = driver.find_element(By.NAME, "password")
            
            email_field.clear()
            email_field.send_keys(self.approver['email'])
            password_field.clear()
            password_field.send_keys("password")
            
            login_button = driver.find_element(By.XPATH, "//button[@type='submit']")
            login_button.click()
            
            wait.until(EC.url_contains("/dashboard"))
            print(f"✅ {self.approver['name']} logged in")
            time.sleep(2)
            
            # Check approvals
            driver.get(f"{self.base_url}/applications/my-approvals")
            time.sleep(3)
            
            # Debug: Show page content
            page_text = driver.find_element(By.TAG_NAME, "body").text
            if "承認" in page_text:
                print("   ✓ Page contains approval content")
            else:
                print("   ⚠️ Page does not contain approval content")
                print(f"   Page title: {driver.title}")
                print(f"   Current URL: {driver.current_url}")
            
            approve_buttons = driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            button_count = len(approve_buttons)
            
            print(f"   📋 Found {button_count} approve buttons")
            
            if button_count > 0:
                print("   🎯 Attempting to approve first item...")
                button = approve_buttons[0]
                
                driver.execute_script("arguments[0].scrollIntoView(true);", button)
                time.sleep(1)
                driver.execute_script("arguments[0].click();", button)
                time.sleep(2)
                
                try:
                    comment_field = wait.until(EC.presence_of_element_located((By.NAME, "comment")))
                    comment_field.send_keys(f"{self.approver['name']}によるUI承認テスト")
                    
                    submit_btn = driver.find_element(By.ID, "approvalSubmit")
                    submit_btn.click()
                    
                    time.sleep(3)
                    print(f"   ✅ {self.approver['name']} approved successfully!")
                    approved = True
                    
                except Exception as e:
                    print(f"   ❌ Approval modal error: {e}")
                    approved = False
            else:
                print(f"   ℹ️ No approvals found for {self.approver['name']}")
                approved = False
            
            # Keep browser open for inspection
            print("   🔍 Keeping browser open for 10 seconds...")
            time.sleep(10)
            
            return approved
            
        finally:
            driver.quit()
            print("🚪 Browser closed")

    def run_ui_approval_test(self):
        """UIの承認のみをテスト"""
        print("🧪 UI Approval Only Test")
        print("=" * 40)
        
        # Phase 1: Artisanで申請作成
        print("\\n📋 PHASE 1: Create application via Artisan")
        if not self.create_application_via_artisan():
            print("❌ Failed to create application, stopping test")
            return
        
        # Wait a bit for the approval flow to be set up
        print("⏳ Waiting 5 seconds for approval flow setup...")
        time.sleep(5)
        
        # Phase 2: UIで承認
        print("\\n✅ PHASE 2: UI Approval Test")
        approved = self.login_and_approve()
        
        print("\\n🎉 UI APPROVAL TEST COMPLETED!")
        print(f"📊 UI Approval successful: {'Yes' if approved else 'No'}")

if __name__ == "__main__":
    test = UIApprovalOnlyTest()
    test.run_ui_approval_test()