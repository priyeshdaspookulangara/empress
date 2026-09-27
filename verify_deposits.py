import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch()
        context = await browser.new_context(viewport={'width': 1280, 'height': 800})
        page = await context.new_page()

        # Login Admin
        await page.goto('http://localhost:8000/admin_login.php')
        await page.fill('input[name="username"]', 'admin')
        await page.fill('input[name="password"]', 'admin123')
        await page.click('button[type="submit"]')
        await page.wait_for_timeout(1000)

        # Go to Deposits Console
        await page.goto('http://localhost:8000/admin/deposits.php')
        await page.wait_for_timeout(1000)
        await page.screenshot(path='/home/jules/verification/screenshots/admin_deposits.png')

        await browser.close()

if __name__ == '__main__':
    asyncio.run(main())
