#!/usr/bin/env python3

import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.service import Service

def debug_login():
    print("🔍 Debug Login Test")
    print("==================")
    
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
        
        # Navigate to login page
        print("\n1. 📍 Navigating to login page...")
        driver.get("http://localhost:8080/login")
        print(f"   Current URL: {driver.current_url}")
        print(f"   Page title: {driver.title}")
        
        # Check for login form
        print("\n2. 🔍 Looking for login form...")
        try:
            email_field = driver.find_element(By.NAME, "email")
            password_field = driver.find_element(By.NAME, "password")
            submit_button = driver.find_element(By.XPATH, "//button[@type='submit']")
            print("   ✓ Found email field")
            print("   ✓ Found password field")
            print("   ✓ Found submit button")
        except Exception as e:
            print(f"   ✗ Form elements not found: {e}")
            return
        
        # Fill in credentials
        print("\n3. 📝 Filling in credentials...")
        email_field.clear()
        email_field.send_keys("admin@wf.nrkk.technology")
        password_field.clear()
        password_field.send_keys("password")
        print("   ✓ Credentials entered")
        
        # Submit form
        print("\n4. 🚀 Submitting form...")
        submit_button.click()
        print("   ✓ Form submitted")
        
        # Wait and check result
        print("\n5. ⏳ Waiting for redirect...")
        time.sleep(3)  # Give time for processing
        
        final_url = driver.current_url
        final_title = driver.title
        
        print(f"   Final URL: {final_url}")
        print(f"   Final title: {final_title}")
        
        # Check for success indicators
        if "login" in final_url:
            print("   ⚠️ Still on login page - login may have failed")
            
            # Check for error messages
            try:
                error_messages = driver.find_elements(By.CSS_SELECTOR, ".alert, .error, .invalid-feedback")
                if error_messages:
                    for msg in error_messages:
                        if msg.text.strip():
                            print(f"   ❌ Error message: {msg.text}")
                else:
                    print("   ℹ️ No obvious error messages found")
            except Exception as e:
                print(f"   ⚠️ Could not check for error messages: {e}")
                
        else:
            print("   ✅ Redirected away from login - login likely succeeded")
            
            # Check what page we're on
            if "applications" in final_url:
                print("   🎯 Successfully redirected to applications page")
            elif "dashboard" in final_url:
                print("   🎯 Successfully redirected to dashboard page")
            else:
                print(f"   🤔 Redirected to unexpected page: {final_url}")
        
        # Keep browser open for manual inspection
        print("\n6. 🔍 Browser will stay open for 10 seconds for manual inspection...")
        time.sleep(10)
        
    except Exception as e:
        print(f"❌ Test failed with error: {e}")
    finally:
        driver.quit()
        print("\n🏁 Test completed")

if __name__ == "__main__":
    debug_login()