from playwright.sync_api import sync_playwright
import os

def test_welcome():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={'width': 1280, 'height': 800})
        page = context.new_page()

        # Admin Login
        page.goto("http://localhost:8000/admin_login.php")
        page.fill("input[name='username']", "admin")
        page.fill("input[name='password']", "admin123")
        page.click("button[type='submit']")
        page.wait_for_load_state("networkidle")

        # Navigate to welcome.php
        page.goto("http://localhost:8000/admin/welcome.php")
        page.wait_for_load_state("networkidle")

        os.makedirs("/home/jules/verification/screenshots", exist_ok=True)
        page.screenshot(path="/home/jules/verification/screenshots/admin_welcome.png")
        print("Welcome page screenshot taken.")

        browser.close()

if __name__ == "__main__":
    test_welcome()
