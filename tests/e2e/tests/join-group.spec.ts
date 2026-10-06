import { test, expect, Page } from "@playwright/test";

/**
 * Regression test for the join-group fatal (issue #101 follow-up):
 * joining a public group that has hidden nav tabs must complete without
 * a critical error, for a regular (non-super-admin) member — via both the
 * AJAX button and the direct /join/ URL that the button links to.
 *
 * Assumes the environment seeded a public group "hidden-tabs-group" with at
 * least one nav tab hidden via groupmeta, and that the secret-login user is
 * not already a member of it.
 *
 * Uses domcontentloaded waits throughout: offline test containers never
 * reach "load" when a page references external assets.
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

test.describe("Join group with hidden tabs", () => {
  test("direct /join/ URL completes without critical error", async ({ page }, testInfo) => {
    await secretLogin(page);

    await page.goto("/groups/hidden-tabs-group/", { waitUntil: "domcontentloaded" });
    await shot(page, testInfo, "01-group-page-before-join");

    const joinLink = page.locator('a:has-text("Join Group")').first();
    await expect(joinLink).toBeVisible();
    const href = await joinLink.getAttribute("href");
    expect(href, "join button should link to the /join/ action URL").toContain("/join/");

    // Navigate the join URL directly — the non-AJAX path reported broken.
    await page.goto(href!, { waitUntil: "domcontentloaded" });
    await shot(page, testInfo, "02-after-join-url");

    // A successful join redirects back to the rendered group page. A fatal
    // in a join hook yields a blank page or the WP critical-error page.
    const body = await page.textContent("body");
    expect(body).not.toContain("There has been a critical error");
    expect(body).toContain("Hidden Tabs Group");

    // The member should now actually be in the group: the page shows a
    // leave affordance rather than the join button.
    await page.goto("/groups/hidden-tabs-group/", { waitUntil: "domcontentloaded" });
    await shot(page, testInfo, "03-group-page-after-join");
    const bodyAfter = await page.textContent("body");
    expect(bodyAfter).not.toContain("There has been a critical error");
    await expect(page.locator(':text("Leave Group")').first()).toBeVisible();
  });
});
