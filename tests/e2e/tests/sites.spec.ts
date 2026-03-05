import { test, expect } from "../fixtures/auth.fixture";

test.describe.serial("Sites", () => {
  const siteSlug = "e2etestsite";

  test("create a site", async ({ authenticatedPage: page, capture }) => {
    // First, clean up any existing test site via network admin
    await page.goto("/wp/wp-admin/network/sites.php");
    await page.waitForLoadState("networkidle");
    await capture("01-sites-network-admin");

    const existingRow = page.locator(`tr:has-text("${siteSlug}")`);
    if (await existingRow.isVisible({ timeout: 2000 }).catch(() => false)) {
      // Delete existing site first
      await existingRow.hover();
      await existingRow.locator('a:has-text("Delete")').click();
      await page.waitForLoadState("networkidle");
      const confirmBtn = page.locator("#submit");
      if (await confirmBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
        await confirmBtn.click();
        await page.waitForLoadState("networkidle");
      }
      await capture("02-sites-cleanup-done");
    }

    // Now create the site
    await page.goto("/sites/create/");
    await page.waitForLoadState("networkidle");
    await capture("03-sites-create-form");

    const blogNameInput = page.locator("#blogname");
    await blogNameInput.fill(siteSlug);

    const blogTitleInput = page.locator("#blog_title");
    if (await blogTitleInput.isVisible()) {
      await blogTitleInput.fill("E2E Test Site");
    }
    await capture("04-sites-form-filled");

    await page.locator("#submit").click();
    await page.waitForLoadState("networkidle");
    await capture("05-sites-created");

    // The success page contains "Congratulations" or shows the new site URL
    const bodyText = await page.locator("body").textContent();
    const pageUrl = page.url();
    expect(
      bodyText?.toLowerCase().includes("congrat") ||
        bodyText?.toLowerCase().includes("success") ||
        bodyText?.includes(siteSlug) ||
        pageUrl.includes(siteSlug)
    ).toBeTruthy();
  });

  test("verify site exists in network admin", async ({
    authenticatedPage: page,
    capture,
  }) => {
    await page.goto("/wp/wp-admin/network/sites.php");
    await page.waitForLoadState("networkidle");
    await capture("06-sites-verify-network-admin");

    const siteRow = page.locator(`tr:has-text("${siteSlug}")`);
    await expect(siteRow).toBeVisible({ timeout: 5000 });
  });

  test("delete the site", async ({ authenticatedPage: page, capture }) => {
    await page.goto("/wp/wp-admin/network/sites.php");
    await page.waitForLoadState("networkidle");

    const siteRow = page.locator(`tr:has-text("${siteSlug}")`);
    await expect(siteRow).toBeVisible({ timeout: 5000 });

    // Hover to reveal action links and click Delete
    await siteRow.hover();
    await siteRow.locator('a:has-text("Delete")').click();
    await page.waitForLoadState("networkidle");
    await capture("07-sites-delete-confirm");

    // Confirm permanent deletion
    await page.locator("#submit").click();
    await page.waitForLoadState("networkidle");
    await capture("08-sites-deleted");

    // Verify we're back on sites list and the site is gone
    await expect(page).toHaveURL(/sites\.php/);
    const deletedRow = page.locator(`tr:has-text("${siteSlug}")`);
    await expect(deletedRow).not.toBeVisible({ timeout: 5000 });
  });
});
