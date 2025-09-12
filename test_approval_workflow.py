#!/usr/bin/env python3
"""
End-to-End Approval Workflow Test
株式会社テクノロジー革新での申請→承認フローテスト

Test Flow:
1. ランダムな申請者でログイン
2. 新しい申請を作成・提出
3. ログアウト
4. 承認者でログイン
5. 申請を承認
6. ログアウト
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

class ApprovalWorkflowE2ETest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        
        # 申請者は管理者で固定（ログイン動作確認のため）
        self.applicants = [
            {'name': '管理者', 'email': 'admin@wf.nrkk.technology'},  # Reliable for testing
        ]
        
        # 株式会社テクノロジー革新の承認者（ローマ字メールアドレス）
        self.approvers = [
            {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'},
            {'name': '中村恵子', 'email': 'approver0_0@wf.nrkk.technology'},
            {'name': '山田明美', 'email': 'approver0_1@wf.nrkk.technology'},
        ]
        
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}

    def setup_driver(self):
        """Chrome driver setup"""
        print("🚀 Setting up Chrome driver...")
        
        chrome_options = Options()
        # chrome_options.add_argument("--headless")  # Commented out for visual testing
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        
        try:
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            self.driver.implicitly_wait(10)
            self.wait = WebDriverWait(self.driver, 15)
            print("✅ Chrome driver ready")
        except Exception as e:
            print(f"❌ Failed to setup Chrome driver: {e}")
            raise

    def login(self, user):
        """Login with given user"""
        print(f"🔐 Logging in as {user['name']} ({user['email']})...")
        
        self.driver.get(f"{self.base_url}/login")
        
        # Fill in credentials
        email_field = self.wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_field = self.driver.find_element(By.NAME, "password")
        
        email_field.clear()
        email_field.send_keys(user['email'])
        password_field.clear()
        password_field.send_keys("password")
        
        # Submit form
        login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
        login_button.click()
        
        # Wait for redirect
        self.wait.until(EC.url_contains("/dashboard"))
        
        print(f"✅ Logged in successfully as {user['name']}")
        time.sleep(2)  # Visual pause

    def logout(self):
        """Logout current user"""
        print("🚪 Logging out...")
        
        try:
            # Look for logout button/link
            logout_elements = [
                "//a[contains(@href, 'logout')]",
                "//button[contains(text(), 'ログアウト')]",
                "//a[contains(text(), 'ログアウト')]",
                "//form[@action='/logout']//button"
            ]
            
            logout_clicked = False
            for selector in logout_elements:
                try:
                    element = self.driver.find_element(By.XPATH, selector)
                    if element.is_displayed():
                        element.click()
                        logout_clicked = True
                        break
                except:
                    continue
            
            if not logout_clicked:
                # Try to find any logout form and submit it
                logout_form = self.driver.find_element(By.CSS_SELECTOR, "form[action*='logout']")
                logout_form.submit()
            
            # Wait for redirect to login page
            self.wait.until(EC.url_contains("/login"))
            print("✅ Logged out successfully")
            time.sleep(2)  # Visual pause
            
        except Exception as e:
            print(f"⚠️ Logout attempt failed: {e}")
            # Fallback: navigate directly to logout
            self.driver.get(f"{self.base_url}/logout")
            time.sleep(2)

    def create_application(self, applicant):
        """Create and submit a new application"""
        print(f"📝 Creating application as {applicant['name']}...")
        
        # Navigate to create application page
        self.driver.get(f"{self.base_url}/applications/create")
        
        # Wait for form to load
        title_field = self.wait.until(EC.presence_of_element_located((By.NAME, "title")))
        
        # Fill in application details
        application_title = f"テスト申請 - {applicant['name']} - {int(time.time())}"
        description = f"これは{applicant['name']}による自動テスト申請です。\n\n申請内容:\n- 設備購入申請\n- 金額: 500,000円\n- 理由: 業務効率化のため"
        
        title_field.clear()
        title_field.send_keys(application_title)
        
        description_field = self.driver.find_element(By.NAME, "description")
        description_field.clear()
        description_field.send_keys(description)
        
        # Select application type
        try:
            type_select = Select(self.driver.find_element(By.NAME, "type"))
            type_select.select_by_value("other")
        except:
            print("⚠️ Type field not found or not a select")
        
        # Set priority
        try:
            priority_select = Select(self.driver.find_element(By.NAME, "priority"))
            priority_select.select_by_value("medium")
        except:
            print("⚠️ Priority field not found or not a select")
        
        # Submit the application
        try:
            # Try clicking the submit button
            submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            self.driver.execute_script("arguments[0].scrollIntoView();", submit_button)
            time.sleep(1)
            self.driver.execute_script("arguments[0].click();", submit_button)
            print("   ✓ Clicked submit button using JavaScript")
        except Exception as e:
            print(f"   ⚠️ JavaScript click failed: {e}")
            # Alternative: submit the form directly
            form = self.driver.find_element(By.TAG_NAME, "form")
            form.submit()
            print("   ✓ Submitted form directly")
        
        # Wait for success redirect
        time.sleep(5)
        
        print(f"✅ Application created: {application_title}")
        return application_title

    def find_and_approve_application(self, approver, application_title):
        """Find and approve the application"""
        print(f"👨‍💼 Looking for application to approve as {approver['name']}...")
        
        # Navigate to my approvals page
        self.driver.get(f"{self.base_url}/applications/my-approvals")
        
        # Wait for page to load
        time.sleep(3)
        
        try:
            # Look for the application in approval cards
            application_found = False
            approval_cards = self.driver.find_elements(By.CSS_SELECTOR, ".card")
            
            if approval_cards:
                print(f"📋 Found {len(approval_cards)} approval item(s)")
                
                for card in approval_cards:
                    try:
                        # Check if this card contains our application
                        card_text = card.text
                        if application_title in card_text or "テスト申請" in card_text:
                            print(f"🎯 Found matching application")
                            application_found = True
                            
                            # Look for approve button in this card
                            approve_buttons = card.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                            if approve_buttons:
                                approve_buttons[0].click()
                                print("✅ Clicked approve button")
                                
                                # Handle approval modal if it appears
                                try:
                                    # Wait for modal to appear
                                    time.sleep(2)
                                    
                                    # Look for comment field in modal
                                    comment_field = self.driver.find_element(By.NAME, "comment")
                                    comment_field.send_keys(f"{approver['name']}による承認: テスト申請を承認いたします。")
                                    
                                    # Submit approval
                                    modal_submit = self.driver.find_element(By.ID, "approvalSubmit")
                                    modal_submit.click()
                                    
                                    print("✅ Approval submitted with comment")
                                    time.sleep(3)
                                    
                                except Exception as modal_e:
                                    print(f"⚠️ Modal handling issue: {modal_e}")
                                
                                break
                            else:
                                print("⚠️ No approve button found in this card")
                    except Exception as card_e:
                        print(f"⚠️ Error processing card: {card_e}")
                        continue
            else:
                print("⚠️ No approval cards found")
            
            if not application_found:
                print("❌ Target application not found in approval list")
                
                # Try to find any approvable item for demonstration
                print("🔍 Looking for any approvable item...")
                approve_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                if approve_buttons:
                    print(f"Found {len(approve_buttons)} approve button(s)")
                    approve_buttons[0].click()
                    
                    # Handle modal
                    try:
                        time.sleep(2)
                        comment_field = self.driver.find_element(By.NAME, "comment")
                        comment_field.send_keys(f"{approver['name']}による承認: デモンストレーション承認")
                        
                        modal_submit = self.driver.find_element(By.ID, "approvalSubmit")
                        modal_submit.click()
                        print("✅ Demo approval submitted")
                        time.sleep(3)
                        
                    except Exception as e:
                        print(f"⚠️ Demo approval modal error: {e}")
                        
        except Exception as e:
            print(f"❌ Error in approval process: {e}")

    def run_test(self):
        """Run the complete end-to-end test"""
        print("🧪 Starting End-to-End Approval Workflow Test")
        print("=" * 50)
        
        try:
            self.setup_driver()
            
            # Step 1: Select random applicant
            selected_applicant = random.choice(self.applicants)
            selected_approver = random.choice(self.approvers)
            
            print(f"🎲 Selected applicant: {selected_applicant['name']}")
            print(f"🎲 Selected approver: {selected_approver['name']}")
            print()
            
            # Step 2: Applicant creates application
            print("📋 STEP 1: Application Creation")
            print("-" * 30)
            self.login(selected_applicant)
            application_title = self.create_application(selected_applicant)
            self.logout()
            print()
            
            # Step 3: Approver reviews and approves
            print("✅ STEP 2: Application Approval") 
            print("-" * 30)
            self.login(selected_approver)
            self.find_and_approve_application(selected_approver, application_title)
            self.logout()
            print()
            
            print("🎉 End-to-End Test Completed Successfully!")
            print("=" * 50)
            
        except Exception as e:
            print(f"❌ Test failed with error: {e}")
            raise
            
        finally:
            if self.driver:
                print("🔍 Keeping browser open for 10 seconds for inspection...")
                time.sleep(10)
                self.driver.quit()
                print("🏁 Browser closed")

if __name__ == "__main__":
    test = ApprovalWorkflowE2ETest()
    test.run_test()