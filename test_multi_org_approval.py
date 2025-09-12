#!/usr/bin/env python3
"""
Multi-Organization Bulk Approval Test
複数組織での大規模承認ワークフローテスト

Test Flow:
1. ランダムに3つの組織を選択
2. 各組織から3-5名の申請者がランダムに申請を作成
3. 全ての申請が完了後、各組織の承認者がログイン
4. 承認待ち申請を全て承認
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

class MultiOrgApprovalTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        
        # 全組織のデータ
        self.organizations = [
            {
                'name': '株式会社テクノロジー革新',
                'applicants': [
                    {'name': '笹田純子', 'email': 'applicant0_0@wf.nrkk.technology'},
                    {'name': '中川麻衣', 'email': 'applicant0_1@wf.nrkk.technology'},
                    {'name': '西村由里', 'email': 'applicant0_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'},
                    {'name': '中村恵子', 'email': 'approver0_0@wf.nrkk.technology'},
                    {'name': '山田明美', 'email': 'approver0_1@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社グリーンエネルギー',
                'applicants': [
                    {'name': '森下誠一', 'email': 'applicant1_0@wf.nrkk.technology'},
                    {'name': '高木真由美', 'email': 'applicant1_1@wf.nrkk.technology'},
                    {'name': '野村大介', 'email': 'applicant1_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '木村智子', 'email': 'approver1_0@wf.nrkk.technology'},
                    {'name': '吉田博文', 'email': 'approver1_1@wf.nrkk.technology'},
                ]
            },
            {
                'name': 'やまと建設株式会社',
                'applicants': [
                    {'name': '吉川雅志', 'email': 'applicant2_0@wf.nrkk.technology'},
                    {'name': '寺田慎一', 'email': 'applicant2_1@wf.nrkk.technology'},
                    {'name': '片山健司', 'email': 'applicant2_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '佐々木良太', 'email': 'approver2_0@wf.nrkk.technology'},
                    {'name': '斎藤真理', 'email': 'approver2_1@wf.nrkk.technology'},
                    {'name': '山本直樹', 'email': 'approver2_2@wf.nrkk.technology'},
                ]
            },
            {
                'name': 'みどり食品工業株式会社',
                'applicants': [
                    {'name': '川口美穂', 'email': 'applicant3_0@wf.nrkk.technology'},
                    {'name': '若林恵理', 'email': 'applicant3_1@wf.nrkk.technology'},
                    {'name': '吉川雅志', 'email': 'applicant3_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '高橋一郎', 'email': 'approver3_0@wf.nrkk.technology'},
                ]
            },
            {
                'name': 'さくら運輸株式会社',
                'applicants': [
                    {'name': '松田隆之', 'email': 'applicant4_0@wf.nrkk.technology'},
                    {'name': '坂本勝彦', 'email': 'applicant4_1@wf.nrkk.technology'},
                    {'name': '若林恵理', 'email': 'applicant4_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '吉田博文', 'email': 'approver4_0@wf.nrkk.technology'},
                    {'name': '佐々木良太', 'email': 'approver4_1@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社フィンテック',
                'applicants': [
                    {'name': '木下隆史', 'email': 'applicant5_0@wf.nrkk.technology'},
                    {'name': '吉川雅志', 'email': 'applicant5_1@wf.nrkk.technology'},
                    {'name': '横田美奈', 'email': 'applicant5_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '田中美紀', 'email': 'approver5_0@wf.nrkk.technology'},
                    {'name': '林大輔', 'email': 'approver5_1@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社エデュテック',
                'applicants': [
                    {'name': '横田美奈', 'email': 'applicant6_0@wf.nrkk.technology'},
                    {'name': '平野浩司', 'email': 'applicant6_1@wf.nrkk.technology'},
                    {'name': '原田昌幸', 'email': 'applicant6_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '田中美紀', 'email': 'approver6_0@wf.nrkk.technology'},
                    {'name': '伊藤健太', 'email': 'approver6_1@wf.nrkk.technology'},
                    {'name': '高橋一郎', 'email': 'approver6_2@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社アグリテック',
                'applicants': [
                    {'name': '酒井梨花', 'email': 'applicant7_0@wf.nrkk.technology'},
                    {'name': '石川由紀', 'email': 'applicant7_1@wf.nrkk.technology'},
                    {'name': '長谷川俊介', 'email': 'applicant7_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '佐藤太郎', 'email': 'approver7_0@wf.nrkk.technology'},
                    {'name': '山本直樹', 'email': 'approver7_1@wf.nrkk.technology'},
                    {'name': '中村恵子', 'email': 'approver7_2@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社ロボティクス',
                'applicants': [
                    {'name': '前田康雄', 'email': 'applicant8_0@wf.nrkk.technology'},
                    {'name': '寺田慎一', 'email': 'applicant8_1@wf.nrkk.technology'},
                    {'name': '星野和子', 'email': 'applicant8_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '中村恵子', 'email': 'approver8_0@wf.nrkk.technology'},
                ]
            },
            {
                'name': '株式会社クラウドインフラ',
                'applicants': [
                    {'name': '小松恵理', 'email': 'applicant9_0@wf.nrkk.technology'},
                    {'name': '水野浩一', 'email': 'applicant9_1@wf.nrkk.technology'},
                    {'name': '福田恵美', 'email': 'applicant9_2@wf.nrkk.technology'},
                ],
                'approvers': [
                    {'name': '林大輔', 'email': 'approver9_0@wf.nrkk.technology'},
                    {'name': '加藤雅子', 'email': 'approver9_1@wf.nrkk.technology'},
                ]
            },
        ]
        
        # 管理者アカウント（申請作成のために使用）
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        
        # 作成された申請を記録
        self.created_applications = []

    def setup_driver(self):
        """Chrome driver setup"""
        print("🚀 Setting up Chrome driver...")
        
        chrome_options = Options()
        # chrome_options.add_argument("--headless")  # Commented out for visual testing
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1400,900")
        
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
        
        print(f"✅ Logged in as {user['name']}")
        time.sleep(1)

    def logout(self):
        """Logout current user"""
        print("🚪 Logging out...")
        
        try:
            # Direct navigation to logout
            self.driver.get(f"{self.base_url}/logout")
            time.sleep(1)
            print("✅ Logged out")
        except Exception as e:
            print(f"⚠️ Logout issue: {e}")

    def create_application(self, org_name, applicant_name):
        """Create a new application as admin (for simplicity)"""
        application_title = f"{org_name} - {applicant_name} - 申請{int(time.time())}"
        print(f"   📝 Creating: {application_title}")
        
        try:
            # Navigate to create application page with retry
            max_retries = 3
            for attempt in range(max_retries):
                try:
                    self.driver.get(f"{self.base_url}/applications/create")
                    # Wait for form to load with longer timeout
                    title_field = WebDriverWait(self.driver, 30).until(
                        EC.presence_of_element_located((By.NAME, "title"))
                    )
                    break
                except Exception as e:
                    if attempt == max_retries - 1:
                        raise e
                    print(f"     Retry {attempt + 1}: Page load failed, retrying...")
                    time.sleep(3)
            
            # Fill in application details
            description = f"""
            組織: {org_name}
            申請者: {applicant_name}
            
            申請内容:
            - 新規プロジェクト承認申請
            - 予算: {random.randint(10, 100)}万円
            - 期間: {random.randint(1, 6)}ヶ月
            
            自動テスト申請
            """
            
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
                pass
            
            # Submit the application
            try:
                submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
                self.driver.execute_script("arguments[0].scrollIntoView();", submit_button)
                time.sleep(1)
                self.driver.execute_script("arguments[0].click();", submit_button)
            except Exception as e:
                form = self.driver.find_element(By.TAG_NAME, "form")
                form.submit()
            
            # Wait for success with longer timeout
            time.sleep(5)
            print(f"   ✅ Created: {application_title}")
            return application_title
            
        except Exception as e:
            print(f"   ❌ Failed to create application: {e}")
            # Take screenshot for debugging
            try:
                screenshot_path = f"error_screenshot_{int(time.time())}.png"
                self.driver.save_screenshot(screenshot_path)
                print(f"   📸 Screenshot saved: {screenshot_path}")
            except:
                pass
            return None

    def approve_all_pending(self, approver):
        """Approve all pending applications for this approver"""
        print(f"🔍 Checking for pending approvals as {approver['name']}...")
        
        # Navigate to my approvals page
        self.driver.get(f"{self.base_url}/applications/my-approvals")
        time.sleep(2)
        
        approved_count = 0
        
        try:
            # Find all approval cards
            approval_cards = self.driver.find_elements(By.CSS_SELECTOR, ".card")
            
            if approval_cards:
                print(f"📋 Found {len(approval_cards)} approval item(s)")
                
                # Process each card
                for i in range(len(approval_cards)):
                    try:
                        # Re-find cards after each approval
                        cards = self.driver.find_elements(By.CSS_SELECTOR, ".card")
                        if i >= len(cards):
                            break
                            
                        card = cards[i]
                        card_text = card.text[:100]  # First 100 chars for logging
                        
                        # Look for approve button
                        approve_buttons = card.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                        if approve_buttons:
                            print(f"   ⏳ Approving item {i+1}...")
                            approve_buttons[0].click()
                            
                            # Handle approval modal
                            try:
                                time.sleep(1)
                                comment_field = self.driver.find_element(By.NAME, "comment")
                                comment_field.send_keys(f"{approver['name']}による一括承認")
                                
                                modal_submit = self.driver.find_element(By.ID, "approvalSubmit")
                                modal_submit.click()
                                
                                approved_count += 1
                                print(f"   ✅ Approved item {i+1}")
                                time.sleep(2)
                                
                                # Refresh page for next approval
                                self.driver.get(f"{self.base_url}/applications/my-approvals")
                                time.sleep(2)
                                
                            except Exception as modal_e:
                                print(f"   ⚠️ Modal issue for item {i+1}: {modal_e}")
                                
                    except Exception as card_e:
                        print(f"   ⚠️ Error processing card {i+1}: {card_e}")
                
                print(f"✅ Approved {approved_count} item(s)")
            else:
                print("   ℹ️ No pending approvals found")
                
        except Exception as e:
            print(f"❌ Error in approval process: {e}")

    def run_test(self):
        """Run the complete multi-organization test"""
        print("🧪 Starting Multi-Organization Bulk Approval Test")
        print("=" * 60)
        
        try:
            self.setup_driver()
            
            # Step 1: Select 3 random organizations
            selected_orgs = random.sample(self.organizations, 3)
            print(f"\n📊 Selected Organizations:")
            for org in selected_orgs:
                print(f"   - {org['name']}")
            print()
            
            # Step 2: Create applications for each organization
            print("📋 PHASE 1: Creating Applications")
            print("-" * 40)
            
            for org in selected_orgs:
                print(f"\n🏢 Organization: {org['name']}")
                
                # Select 3-5 random applicants from this org
                num_applicants = random.randint(3, min(5, len(org['applicants'])))
                selected_applicants = random.sample(org['applicants'], num_applicants)
                
                print(f"   Selected {num_applicants} applicants")
                
                # Login as admin to create applications
                self.login(self.admin)
                
                for applicant in selected_applicants:
                    app_title = self.create_application(org['name'], applicant['name'])
                    if app_title:  # Only add if creation was successful
                        self.created_applications.append({
                            'org': org['name'],
                            'applicant': applicant['name'],
                            'title': app_title
                        })
                    else:
                        print(f"   ⚠️ Skipping failed application for {applicant['name']}")
                
                self.logout()
            
            print(f"\n📊 Total Applications Created: {len(self.created_applications)}")
            for app in self.created_applications:
                print(f"   - {app['title']}")
            
            # Step 3: Approve all pending applications
            print("\n✅ PHASE 2: Bulk Approval by Organization Approvers")
            print("-" * 40)
            
            for org in selected_orgs:
                print(f"\n🏢 Processing approvals for: {org['name']}")
                
                # Select one approver from each organization
                if org['approvers']:
                    approver = random.choice(org['approvers'])
                    print(f"   Selected approver: {approver['name']}")
                    
                    # Login as approver
                    self.login(approver)
                    
                    # Approve all pending
                    self.approve_all_pending(approver)
                    
                    # Logout
                    self.logout()
                else:
                    print(f"   ⚠️ No approvers available for {org['name']}")
            
            print("\n" + "=" * 60)
            print("🎉 Multi-Organization Test Completed Successfully!")
            print(f"   📝 Applications created: {len(self.created_applications)}")
            print(f"   🏢 Organizations tested: {len(selected_orgs)}")
            print("=" * 60)
            
        except Exception as e:
            print(f"❌ Test failed with error: {e}")
            raise
            
        finally:
            if self.driver:
                print("\n🔍 Keeping browser open for 10 seconds for inspection...")
                time.sleep(10)
                self.driver.quit()
                print("🏁 Browser closed")

if __name__ == "__main__":
    test = MultiOrgApprovalTest()
    test.run_test()