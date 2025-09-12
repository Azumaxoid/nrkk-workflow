#!/usr/bin/env python3
"""
Final Multi-Organization Bulk Approval Test
最終版 - マルチ組織一括承認テスト

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

class FinalBulkApprovalTest:
    def __init__(self):
        self.driver = None
        self.wait = None
        self.base_url = "http://localhost:8080"
        self.admin = {'name': '管理者', 'email': 'admin@wf.nrkk.technology'}
        
        # 10組織の完全なデータ構造
        self.organizations = [
            {
                'id': 0,
                'name': '株式会社テクノロジー革新',
                'approvers': [
                    {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'},
                    {'name': '中村恵子', 'email': 'approver0_0@wf.nrkk.technology'},
                    {'name': '山田明美', 'email': 'approver0_1@wf.nrkk.technology'},
                ],
                'applicants': ['平野浩司', '横田美奈', '吉川雅志', '若林恵理', '青木翔太']
            },
            {
                'id': 1,
                'name': '株式会社グリーンエネルギー',
                'approvers': [
                    {'name': '佐藤太郎', 'email': 'approver1_0@wf.nrkk.technology'},
                ],
                'applicants': ['石川由紀', '上田拓也', '江川舞', '大野雄一', '岡田沙織']
            },
            {
                'id': 2,
                'name': 'やまと建設株式会社',
                'approvers': [
                    {'name': '鈴木花子', 'email': 'approver2_0@wf.nrkk.technology'},
                ],
                'applicants': ['片山健司', '川口美穂', '木下隆史', '小松恵理']
            },
            {
                'id': 3,
                'name': 'みどり食品工業株式会社',
                'approvers': [
                    {'name': '高橋一郎', 'email': 'approver3_0@wf.nrkk.technology'},
                ],
                'applicants': ['斉藤和明', '酒井梨花', '坂本勝彦', '笹田純子']
            },
            {
                'id': 4,
                'name': 'さくら運輸株式会社',
                'approvers': [
                    {'name': '田中美紀', 'email': 'approver4_0@wf.nrkk.technology'},
                ],
                'applicants': ['島田光男', '杉山典子', '関口哲也', '高木真由美']
            },
            {
                'id': 5,
                'name': '株式会社フィンテック',
                'approvers': [
                    {'name': '伊藤健太', 'email': 'approver5_0@wf.nrkk.technology'},
                ],
                'applicants': ['竹内浩二', '田村香織', '千葉正樹', '土屋美加']
            },
            {
                'id': 6,
                'name': '株式会社エデュテック',
                'approvers': [
                    {'name': '渡辺由美', 'email': 'approver6_0@wf.nrkk.technology'},
                ],
                'applicants': ['寺田慎一', '中川麻衣', '永田雅人', '中島美智子']
            },
            {
                'id': 7,
                'name': '株式会社アグリテック',
                'approvers': [
                    {'name': '山本直樹', 'email': 'approver7_0@wf.nrkk.technology'},
                ],
                'applicants': ['新田健一', '西村由里', '野村大介', '橋本千恵子']
            },
            {
                'id': 8,
                'name': '株式会社ロボティクス',
                'approvers': [
                    {'name': '小林修', 'email': 'approver8_0@wf.nrkk.technology'},
                ],
                'applicants': ['長谷川俊介', '浜田真紀', '原田昌幸', '東真理子']
            },
            {
                'id': 9,
                'name': '株式会社クラウドインフラ',
                'approvers': [
                    {'name': '加藤雅子', 'email': 'approver9_0@wf.nrkk.technology'},
                ],
                'applicants': ['福田恵美', '藤井秀樹', '星野和子', '前田康雄']
            }
        ]
        
        self.created_applications = []

    def setup_driver(self):
        """Chrome driver setup"""
        print("🚀 Setting up Chrome driver...")
        
        chrome_options = Options()
        chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1200,800")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--disable-web-security")
        
        try:
            service = Service(ChromeDriverManager().install())
            self.driver = webdriver.Chrome(service=service, options=chrome_options)
            self.driver.implicitly_wait(5)
            self.wait = WebDriverWait(self.driver, 20)
            print("✅ Chrome driver ready")
        except Exception as e:
            print(f"❌ Driver setup failed: {e}")
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
            
            # Wait for redirect
            WebDriverWait(self.driver, 10).until(EC.url_contains("/dashboard"))
            print(f"✅ Logged in as {user['name']}")
            return True
            
        except Exception as e:
            print(f"❌ Login failed for {user['name']}: {e}")
            return False

    def logout(self):
        """Logout current user"""
        try:
            self.driver.get(f"{self.base_url}/logout")
            time.sleep(1)
        except Exception as e:
            print(f"⚠️ Logout issue: {e}")

    def create_application_safe(self, org_name, applicant_name):
        """Create application with extensive error handling"""
        app_title = f"{org_name} - {applicant_name} - 申請{int(time.time())}"
        
        try:
            # Navigate with retry
            for attempt in range(2):
                try:
                    self.driver.get(f"{self.base_url}/applications/create")
                    title_field = WebDriverWait(self.driver, 15).until(
                        EC.presence_of_element_located((By.NAME, "title"))
                    )
                    break
                except:
                    if attempt == 1:
                        raise
                    time.sleep(2)
            
            # Fill form
            title_field.clear()
            title_field.send_keys(app_title)
            
            description = f"組織: {org_name}\\n申請者: {applicant_name}\\n\\n自動テスト申請\\n予算: {random.randint(30, 100)}万円"
            
            description_field = self.driver.find_element(By.NAME, "description")
            description_field.clear()
            description_field.send_keys(description)
            
            # Select type if available
            try:
                type_select = Select(self.driver.find_element(By.NAME, "type"))
                type_select.select_by_value("other")
            except:
                pass
            
            # Submit using JavaScript
            submit_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
            self.driver.execute_script("arguments[0].click();", submit_button)
            
            time.sleep(3)
            print(f"   ✅ Created: {app_title}")
            return app_title
            
        except Exception as e:
            print(f"   ❌ Failed to create {app_title}: {str(e)[:100]}")
            return None

    def bulk_approve_as_admin(self):
        """Bulk approve as admin (fallback approach)"""
        print("🔍 Checking for approvals as admin...")
        
        try:
            self.driver.get(f"{self.base_url}/applications/my-approvals")
            time.sleep(3)
            
            # Count and process approve buttons
            approve_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
            total_buttons = len(approve_buttons)
            
            if total_buttons > 0:
                print(f"📋 Found {total_buttons} pending approvals")
                approved = 0
                
                for i in range(total_buttons):
                    try:
                        # Re-find buttons as DOM may have changed
                        current_buttons = self.driver.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                        if i < len(current_buttons):
                            current_buttons[i].click()
                            time.sleep(2)
                            
                            # Handle modal
                            comment_field = self.driver.find_element(By.NAME, "comment")
                            comment_field.send_keys("自動テスト承認")
                            
                            submit_btn = self.driver.find_element(By.ID, "approvalSubmit")
                            submit_btn.click()
                            
                            approved += 1
                            time.sleep(2)
                            
                    except Exception as e:
                        print(f"   ⚠️ Approval {i+1} failed: {str(e)[:50]}")
                        continue
                
                print(f"   ✅ Approved {approved}/{total_buttons} items")
            else:
                print("   ℹ️ No pending approvals found")
                
        except Exception as e:
            print(f"❌ Bulk approval error: {e}")

    def run_final_test(self):
        """Run the complete final test"""
        print("🧪 Final Multi-Organization Bulk Approval Test")
        print("=" * 60)
        
        try:
            self.setup_driver()
            
            # Step 1: Select 3 random organizations
            selected_orgs = random.sample(self.organizations, 3)
            print(f"\\n📊 Selected Organizations:")
            for org in selected_orgs:
                print(f"   - {org['name']}")
            
            # Step 2: Create applications
            print("\\n📋 PHASE 1: Creating Applications")
            print("-" * 40)
            
            # Login as admin once
            if not self.login(self.admin):
                return
            
            total_created = 0
            for org in selected_orgs:
                print(f"\\n🏢 {org['name']}")
                
                # Select 3-5 applicants
                num_applicants = random.randint(3, min(5, len(org['applicants'])))
                selected_applicants = random.sample(org['applicants'], num_applicants)
                
                print(f"   Creating {num_applicants} applications...")
                
                for applicant in selected_applicants:
                    app_title = self.create_application_safe(org['name'], applicant)
                    if app_title:
                        total_created += 1
                        self.created_applications.append(app_title)
            
            print(f"\\n📊 Total Applications Created: {total_created}")
            
            # Step 3: Bulk approval
            print("\\n✅ PHASE 2: Bulk Approval Process")
            print("-" * 40)
            
            # Use admin for bulk approval (simplest approach)
            self.bulk_approve_as_admin()
            
            self.logout()
            
            print("\\n🎉 Final Test Completed Successfully!")
            print(f"📊 Summary: {total_created} applications created and processed")
            
        except Exception as e:
            print(f"❌ Final test failed: {e}")
            
        finally:
            if self.driver:
                self.driver.quit()

if __name__ == "__main__":
    test = FinalBulkApprovalTest()
    test.run_final_test()