import { test, expect, Page } from "@playwright/test";

/**
 * Visual verification pass over the group management surfaces fixed for
 * issues #114 (general settings screen: landing-page dropdown populated,
 * hidden tabs manageable) and #105 (events subnav present with New Event).
 * Screenshots are attached for review.
 */

const SECRET_KEY = process.env.SECRET_LOGIN_KEY || "e2e-test-key-change-me";
const GROUP = "hidden-tabs-group";

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

test.describe("Group management surfaces", () => {
  test("settings screen has landing-page options and nav table", async ({ page }, testInfo) => {
    await secretLogin(page);

    await page.goto(`/groups/${GROUP}/admin/group-settings/`, { waitUntil: "domcontentloaded" });
    await page.waitForTimeout(1500); // landing-page dropdown fills over admin-ajax
    await shot(page, testInfo, "01-group-settings-screen");

    const select = page.locator("#group-landing-page-select");
    await expect(select).toBeVisible();
    const optionCount = await select.locator("option").count();
    expect(optionCount, "landing page dropdown must offer options (issue #114)").toBeGreaterThan(0);

    const navSettings = page.locator('input[name^="group-nav-settings"]');
    expect(await navSettings.count(), "show/hide nav table must list items (issue #114)").toBeGreaterThan(0);
  });

  test("events subnav is present with New Event", async ({ page }, testInfo) => {
    await secretLogin(page);

    await page.goto(`/groups/${GROUP}/events/`, { waitUntil: "domcontentloaded" });
    await shot(page, testInfo, "02-group-events");

    const body = await page.textContent("body");
    expect(body).not.toContain("There has been a critical error");
    await expect(page.getByRole("link", { name: "New Event" }).first()).toBeVisible();
  });
});
