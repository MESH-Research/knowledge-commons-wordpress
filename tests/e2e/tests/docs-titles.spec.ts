import { test, expect, Page } from "@playwright/test";

/**
 * Regression test for issue #117: the BuddyPress Docs directory and
 * doc-creation screens must carry a visible page heading and a document
 * <title>. The heading comes from the theme's Docs template; the <title>
 * requires title-tag support because those screens render through a classic
 * PHP template that bypasses the block-template canvas.
 */

const SECRET_KEY = process.env.SECRET_LOGIN_KEY || "e2e-test-key-change-me";

async function secretLogin(page: Page) {
  await page.goto(`/wp-login.php?secret_key=${encodeURIComponent(SECRET_KEY)}`, {
    waitUntil: "domcontentloaded",
  });
  await page.waitForURL((url) => !url.pathname.includes("wp-login.php"), {
    timeout: 15000,
    waitUntil: "domcontentloaded",
  });
}

async function shot(page: Page, testInfo: any, name: string) {
  const path = testInfo.outputPath(`${name}.png`);
  await page.screenshot({ path, fullPage: true });
  testInfo.attachments.push({ name, contentType: "image/png", path });
}

test.describe("Docs page titles", () => {
  test("create screen has heading and document title", async ({ page }, testInfo) => {
    await secretLogin(page);

    await page.goto("/docs/create/", { waitUntil: "domcontentloaded" });
    await shot(page, testInfo, "01-docs-create");

    await expect(page.locator("h2.doc-title").first()).toHaveText("Create a Doc");
    expect(await page.title()).toContain("Create a Doc");
  });

  test("directory has heading and document title", async ({ page }, testInfo) => {
    await secretLogin(page);

    await page.goto("/docs/", { waitUntil: "domcontentloaded" });
    await shot(page, testInfo, "02-docs-directory");

    await expect(page.locator("h2.doc-title").first()).not.toBeEmpty();
    expect((await page.title()).trim()).not.toBe("");
  });
});
