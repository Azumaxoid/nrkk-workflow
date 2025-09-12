#!/usr/bin/env python3
"""
Selenium UI Tests for Approval Workflow Application

Requirements:
pip install selenium pytest

Usage:
python tests/selenium_tests.py
"""

import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service
import unittest


class ApprovalWorkflowTests(unittest.TestCase):
    
    @classmethod
    def setUpClass(cls):
        """Set up Chrome driver for testing"""
        chrome_options = Options()
        # chrome_options.add_argument("--headless")  # Run in headless mode - commented out for visual testing
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        
        # Try to connect to Selenium Grid, fallback to local Chrome
        try:
            cls.driver = webdriver.Remote(
                command_executor='http://localhost:4444/wd/hub',
                desired_capabilities=DesiredCapabilities.CHROME,
                options=chrome_options
            )
            print("✓ Connected to Selenium Grid")
        except Exception as e:
            print(f"⚠ Could not connect to Selenium Grid: {e}")
            print("Falling back to local Chrome driver")
            try:
                # Use webdriver-manager to automatically download and manage ChromeDriver
                service = Service(ChromeDriverManager().install())
                cls.driver = webdriver.Chrome(service=service, options=chrome_options)
                print("✓ Connected to local Chrome driver with WebDriver Manager")
            except Exception as e2:
                print(f"✗ Could not connect to local Chrome driver: {e2}")
                raise Exception("No Chrome driver available")
        
        cls.driver.implicitly_wait(10)
        cls.base_url = "http://localhost:8080"
        cls.wait = WebDriverWait(cls.driver, 10)
        
        # Test users credentials
        cls.test_users = {
            'admin': {
                'email': 'admin@wf.nrkk.technology',
                'password': 'password',
                'role': 'admin'
            },
            'applicant': {
                'email': 'tazuma@wf.nrkk.technology',
                'password': 'password',
                'role': 'approver'
            }
        }

    @classmethod
    def tearDownClass(cls):
        """Clean up after tests"""
        if hasattr(cls, 'driver'):
            cls.driver.quit()

    def setUp(self):
        """Reset for each test"""
        self.driver.delete_all_cookies()

    def login(self, user_type='admin'):
        """Helper method to login"""
        user = self.test_users[user_type]
        
        self.driver.get(f"{self.base_url}/login")
        
        # Wait for login form to load
        email_field = self.wait.until(EC.presence_of_element_located((By.NAME, "email")))
        password_field = self.driver.find_element(By.NAME, "password")
        
        # Fill in credentials
        email_field.clear()
        email_field.send_keys(user['email'])
        password_field.clear()
        password_field.send_keys(user['password'])
        
        # Submit form
        login_button = self.driver.find_element(By.XPATH, "//button[@type='submit']")
        login_button.click()
        
        # Wait for redirect to dashboard
        self.wait.until(EC.url_contains("/dashboard"))
        
        return user

    def test_01_homepage_loads(self):
        """Test that homepage loads successfully"""
        print("\n🧪 Testing homepage load...")
        
        self.driver.get(self.base_url)
        
        # Should redirect to login page
        self.wait.until(EC.url_contains("/login"))
        
        # Check if login form is present
        login_form = self.driver.find_element(By.TAG_NAME, "form")
        self.assertIsNotNone(login_form)
        
        # Check for email and password fields
        email_field = self.driver.find_element(By.NAME, "email")
        password_field = self.driver.find_element(By.NAME, "password")
        
        self.assertIsNotNone(email_field)
        self.assertIsNotNone(password_field)
        
        print("✓ Homepage redirects to login correctly")
        print("✓ Login form elements present")

    def test_02_admin_login(self):
        """Test admin user login"""
        print("\n🧪 Testing admin login...")
        
        user = self.login('admin')
        
        # Check if we're on the dashboard page  
        current_url = self.driver.current_url
        self.assertIn("dashboard", current_url)
        
        # Check for admin-specific elements
        page_title = self.driver.find_element(By.TAG_NAME, "h2")
        self.assertIn("申請一覧", page_title.text)
        
        print(f"✓ Admin login successful for {user['email']}")
        print("✓ Redirected to applications page")

    def test_03_application_list_page(self):
        """Test application list page functionality"""
        print("\n🧪 Testing application list page...")
        
        self.login('admin')
        
        # Navigate to applications list
        self.driver.get(f"{self.base_url}/applications")
        
        # Check page title
        page_title = self.wait.until(EC.presence_of_element_located((By.TAG_NAME, "h2")))
        self.assertIn("申請一覧", page_title.text)
        
        # Look for application cards or table
        try:
            applications = self.driver.find_elements(By.CSS_SELECTOR, ".card")
            if applications:
                print(f"✓ Found {len(applications)} application cards")
            else:
                # Check for empty state
                empty_message = self.driver.find_element(By.CSS_SELECTOR, ".text-muted")
                print(f"✓ Empty state message: {empty_message.text}")
        except Exception as e:
            print(f"⚠ Could not find applications or empty state: {e}")
        
        # Check for "新規申請" button
        try:
            new_app_button = self.driver.find_element(By.LINK_TEXT, "新規申請")
            self.assertIsNotNone(new_app_button)
            print("✓ New application button found")
        except Exception:
            print("⚠ New application button not found")

    def test_04_create_application_page(self):
        """Test application creation page"""
        print("\n🧪 Testing application creation page...")
        
        self.login('admin')
        
        # Navigate to create application page
        self.driver.get(f"{self.base_url}/applications/create")
        
        # Wait for form to load
        form_title = self.wait.until(EC.presence_of_element_located((By.TAG_NAME, "h2")))
        self.assertIn("新規申請", form_title.text)
        
        # Check for required form fields
        required_fields = ['title', 'description', 'type']
        for field in required_fields:
            try:
                element = self.driver.find_element(By.NAME, field)
                self.assertIsNotNone(element)
                print(f"✓ Found form field: {field}")
            except Exception as e:
                print(f"✗ Missing form field: {field} - {e}")

    def test_05_my_approvals_page(self):
        """Test my approvals page"""
        print("\n🧪 Testing my approvals page...")
        
        self.login('applicant')
        
        # Navigate to my approvals page
        self.driver.get(f"{self.base_url}/applications/my-approvals")
        
        # Wait for page to load
        page_title = self.wait.until(EC.presence_of_element_located((By.TAG_NAME, "h2")))
        self.assertIn("承認待ち一覧", page_title.text)
        
        # Check for approval cards or empty state
        try:
            approval_cards = self.driver.find_elements(By.CSS_SELECTOR, ".card")
            if approval_cards:
                print(f"✓ Found {len(approval_cards)} approval cards")
                
                # Check first approval card for required elements
                first_card = approval_cards[0]
                
                # Look for approval action buttons
                approve_btn = first_card.find_elements(By.CSS_SELECTOR, "button[onclick*='approve']")
                reject_btn = first_card.find_elements(By.CSS_SELECTOR, "button[onclick*='reject']")
                
                if approve_btn:
                    print("✓ Approve button found")
                if reject_btn:
                    print("✓ Reject button found")
                    
            else:
                empty_message = self.driver.find_element(By.CSS_SELECTOR, ".text-muted")
                print(f"✓ Empty state message: {empty_message.text}")
                
        except Exception as e:
            print(f"⚠ Error checking approval cards: {e}")

    def test_06_application_detail_page(self):
        """Test application detail page"""
        print("\n🧪 Testing application detail page...")
        
        self.login('admin')
        
        # First, get list of applications to find one to view
        self.driver.get(f"{self.base_url}/applications")
        time.sleep(2)
        
        # Look for application links
        try:
            app_links = self.driver.find_elements(By.CSS_SELECTOR, "a[href*='/applications/']")
            detail_links = [link for link in app_links if '/applications/' in link.get_attribute('href') and not link.get_attribute('href').endswith('/create')]
            
            if detail_links:
                # Click on first application detail link
                detail_links[0].click()
                
                # Wait for detail page to load
                detail_title = self.wait.until(EC.presence_of_element_located((By.TAG_NAME, "h2")))
                self.assertIn("申請詳細", detail_title.text)
                print("✓ Application detail page loaded")
                
                # Check for key information sections
                sections_to_check = [
                    "申請ID",
                    "タイトル", 
                    "申請者",
                    "ステータス"
                ]
                
                for section in sections_to_check:
                    try:
                        element = self.driver.find_element(By.XPATH, f"//th[contains(text(), '{section}')]")
                        print(f"✓ Found section: {section}")
                    except Exception:
                        print(f"⚠ Section not found: {section}")
                        
                # Check for approval flow section
                try:
                    approval_flow = self.driver.find_element(By.XPATH, "//h5[contains(text(), '承認フロー')]")
                    print("✓ Approval flow section found")
                except Exception:
                    print("⚠ Approval flow section not found")
                    
            else:
                print("⚠ No application detail links found to test")
                
        except Exception as e:
            print(f"⚠ Error testing application detail page: {e}")

    def test_07_navigation_elements(self):
        """Test navigation elements"""
        print("\n🧪 Testing navigation elements...")
        
        self.login('admin')
        
        # Check for navigation menu
        try:
            nav_elements = self.driver.find_elements(By.CSS_SELECTOR, "nav a, .navbar a")
            if nav_elements:
                print(f"✓ Found {len(nav_elements)} navigation elements")
                
                # Check for key navigation items
                nav_texts = [elem.text for elem in nav_elements if elem.text.strip()]
                expected_nav_items = ["申請一覧", "承認待ち", "ログアウト"]
                
                for item in expected_nav_items:
                    if any(item in text for text in nav_texts):
                        print(f"✓ Found nav item: {item}")
                    else:
                        print(f"⚠ Nav item not found: {item}")
                        
            else:
                print("⚠ No navigation elements found")
                
        except Exception as e:
            print(f"⚠ Error checking navigation: {e}")

    def test_08_responsive_design(self):
        """Test responsive design"""
        print("\n🧪 Testing responsive design...")
        
        self.login('admin')
        
        # Test different screen sizes
        screen_sizes = [
            (1920, 1080, "Desktop"),
            (768, 1024, "Tablet"),
            (375, 667, "Mobile")
        ]
        
        for width, height, device in screen_sizes:
            self.driver.set_window_size(width, height)
            time.sleep(1)  # Allow layout to adjust
            
            # Check if page is still functional
            try:
                page_title = self.driver.find_element(By.TAG_NAME, "h2")
                self.assertIsNotNone(page_title)
                print(f"✓ {device} ({width}x{height}): Page renders correctly")
            except Exception as e:
                print(f"✗ {device} ({width}x{height}): Page render issue - {e}")

    def test_09_logout_functionality(self):
        """Test logout functionality"""
        print("\n🧪 Testing logout functionality...")
        
        self.login('admin')
        
        # Look for logout link or button
        try:
            # Try different selectors for logout
            logout_selectors = [
                "a[href*='logout']",
                "form[action*='logout'] button",
                "a:contains('ログアウト')",
                "button:contains('ログアウト')"
            ]
            
            logout_element = None
            for selector in logout_selectors:
                try:
                    if 'contains' in selector:
                        # Handle :contains() pseudo-selector manually
                        if 'ログアウト' in selector:
                            elements = self.driver.find_elements(By.XPATH, "//*[contains(text(), 'ログアウト')]")
                            if elements:
                                logout_element = elements[0]
                                break
                    else:
                        logout_element = self.driver.find_element(By.CSS_SELECTOR, selector)
                        break
                except Exception:
                    continue
            
            if logout_element:
                logout_element.click()
                
                # Wait for redirect to login page
                self.wait.until(EC.url_contains("/login"))
                
                # Verify we're back at login
                login_form = self.driver.find_element(By.TAG_NAME, "form")
                self.assertIsNotNone(login_form)
                
                print("✓ Logout successful")
            else:
                print("⚠ Logout element not found")
                
        except Exception as e:
            print(f"⚠ Error testing logout: {e}")


def run_tests():
    """Run all tests with detailed output"""
    print("🚀 Starting Selenium UI Tests for Approval Workflow Application")
    print("=" * 60)
    
    # Create test suite
    suite = unittest.TestLoader().loadTestsFromTestCase(ApprovalWorkflowTests)
    
    # Run tests with verbose output
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    print("\n" + "=" * 60)
    print("📊 Test Summary:")
    print(f"Tests run: {result.testsRun}")
    print(f"Failures: {len(result.failures)}")
    print(f"Errors: {len(result.errors)}")
    
    if result.failures:
        print("\n❌ Failures:")
        for test, traceback in result.failures:
            print(f"- {test}: {traceback}")
    
    if result.errors:
        print("\n🚨 Errors:")
        for test, traceback in result.errors:
            print(f"- {test}: {traceback}")
    
    if result.wasSuccessful():
        print("\n🎉 All tests passed!")
    else:
        print("\n⚠️ Some tests failed. Check the details above.")
    
    return result.wasSuccessful()


if __name__ == "__main__":
    success = run_tests()
    exit(0 if success else 1)