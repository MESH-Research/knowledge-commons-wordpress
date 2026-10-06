import { test, expect, Page } from "@playwright/test";

/**
 * Regression test for issue #113: the settings step of the group-creation
 * wizard must not show the legacy "Attention Site Admin: Group forums
 * require the correct setup and configuration of a bbPress installation"
 * notice. Group forums get their own later wizard step from bbPress.
 *
 * Run as a super admin — the spurious notice only rendered for them.
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

test.describe("Group creation settings step", () => {
  test("no legacy forum-setup notice on the settings step", async ({ page }, testInfo) => {
    await secretLogin(page);

    await page.goto("/groups/create/step/group-details/", { waitUntil: "domcontentloaded" });
    await page.locator("#group-name").fill(`Wizard Check ${Date.now()}`);
    await page.locator("#group-desc").fill("Checking the settings step for the legacy forum notice.");
    await shot(page, testInfo, "01-details-step");

    await Promise.all([
      page.waitForURL(/group-settings/, { waitUntil: "domcontentloaded" }),
      page.locator("#group-creation-create").click(),
    ]);
    await shot(page, testInfo, "02-settings-step");

    const body = await page.textContent("body");
    expect(body).not.toContain("Attention Site Admin");
    expect(body).not.toContain("correct setup and configuration");
  });
});
