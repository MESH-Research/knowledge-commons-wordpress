import { test as base, Page } from "@playwright/test";
import * as fs from "fs";
import * as path from "path";

const SECRET_KEY = process.env.SECRET_LOGIN_KEY || "e2e-test-key-change-me";

/** Capture a screenshot and raw HTML for a test step */
async function captureStep(page: Page, testInfo: any, stepName: string) {
  const safeName = stepName.replace(/[^a-zA-Z0-9-_]/g, "_");
  const stepDir = testInfo.outputDir;

  // Ensure directory exists
  fs.mkdirSync(stepDir, { recursive: true });

  // Screenshot
  const screenshotPath = path.join(stepDir, `${safeName}.png`);
  await page.screenshot({ path: screenshotPath, fullPage: true });
  testInfo.attachments.push({
    name: `${stepName} (screenshot)`,
    contentType: "image/png",
    path: screenshotPath,
  });

  // Raw HTML
  const htmlPath = path.join(stepDir, `${safeName}.html`);
  const html = await page.content();
  fs.writeFileSync(htmlPath, html);
  testInfo.attachments.push({
    name: `${stepName} (html)`,
    contentType: "text/html",
    path: htmlPath,
  });
}

export const test = base.extend<{
  authenticatedPage: Page;
  capture: (stepName: string) => Promise<void>;
}>({
  authenticatedPage: async ({ page }, use) => {
    const loginUrl = `/wp-login.php?secret_key=${encodeURIComponent(SECRET_KEY)}`;
    await page.goto(loginUrl);

    // After successful bypass the user is redirected away from wp-login.php
    await page.waitForURL((url) => !url.pathname.includes("wp-login.php"), {
      timeout: 15000,
    });

    await use(page);
  },

  capture: async ({ page }, use, testInfo) => {
    const fn = async (stepName: string) => {
      await captureStep(page, testInfo, stepName);
    };
    await use(fn);
  },
});

export { expect } from "@playwright/test";
