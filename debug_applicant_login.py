#!/usr/bin/env python3

import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

def debug_applicant_login():
    print("🔍 Debug Applicant Login Test")
    print("=============================")
    
    # Setup Chrome driver
    chrome_options = Options()
    # chrome_options.add_argument("--headless")  # Comment out for visual debugging
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    
    try:
        service = Service(ChromeDriverManager().install())
        driver = webdriver.Chrome(service=service, options=chrome_options)
        print("✓ Chrome driver initialized")
    except Exception as e:
        print(f"✗ Failed to initialize Chrome driver: {e}")
        return
    
    try:
        driver.implicitly_wait(10)
        wait = WebDriverWait(driver, 10)
        
        # Test different user types
        test_users = [
            {'name': 'Admin', 'email': 'admin@wf.nrkk.technology'},
            {'name': '田島和也', 'email': 'tazuma@wf.nrkk.technology'},
            {'name': '川口美穂', 'email': '川口美穂00@wf.nrkk.technology'},
        ]
        
        for user in test_users:
            print(f"\n🔐 Testing login for {user['name']} ({user['email']})")
            print("-" * 40)
            
            # Navigate to login page
            driver.get("http://localhost:8080/login")
            time.sleep(1)
            
            # Fill in credentials
            email_field = wait.until(EC.presence_of_element_located((By.NAME, "email")))
            password_field = driver.find_element(By.NAME, "password")
            
            email_field.clear()
            email_field.send_keys(user['email'])
            password_field.clear()
            password_field.send_keys("password")
            
            # Submit form
            submit_button = driver.find_element(By.XPATH, "//button[@type='submit']")
            submit_button.click()
            
            # Wait and check result
            time.sleep(3)
            
            final_url = driver.current_url
            final_title = driver.title
            
            print(f"   Final URL: {final_url}")
            print(f"   Final title: {final_title}")
            
            if "login" in final_url:
                print("   ❌ Still on login page - login failed")
                
                # Check for error messages
                try:
                    error_messages = driver.find_elements(By.CSS_SELECTOR, ".alert-danger, .error, .invalid-feedback")
                    if error_messages:
                        for msg in error_messages:
                            if msg.text.strip():
                                print(f"   🚨 Error: {msg.text}")
                except Exception:
                    pass
                    
            elif "dashboard" in final_url:
                print("   ✅ Successfully redirected to dashboard")
            else:
                print(f"   🤔 Redirected to: {final_url}")
            
            # Logout for next test
            try:
                driver.get("http://localhost:8080/logout")
                time.sleep(1)
            except:
                pass
        
        # Keep browser open for inspection
        print(f"\n🔍 Browser will stay open for 5 seconds...")
        time.sleep(5)
        
    except Exception as e:
        print(f"❌ Test failed with error: {e}")
    finally:
        driver.quit()
        print("\n🏁 Test completed")

if __name__ == "__main__":
    debug_applicant_login()