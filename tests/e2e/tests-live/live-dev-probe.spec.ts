import { test, expect, Page } from "@playwright/test";

/**
 * Anonymous probe of the live hcommons-dev.org site: documents the state of
 * the groups/docs surfaces that recent fixes target. Run with
 * BASE_URL=https://hcommons-dev.org against the live site; headless Chromium
 * passes the CloudFront WAF JS challenge that blocks plain HTTP clients.
 *
 * These are observations, not assertions: the probe records titles,
 * headings and nav state with screenshots so the live state can be compared
 * before and after a deployment.
 */

async function shot(page: Page, testInfo: any, name: string) {
  const path = testInfo.outputPath(`${name}.png`);
  await page.screenshot({ path, fullPage: true });
  testInfo.attachments.push({ name, contentType: "image/png", path });
}

async function settle(page: Page) {
  // Give the WAF challenge time to solve and the page to re-navigate.
  await page.waitForTimeout(4000);
  await page.waitForLoadState("domcontentloaded").catch(() => {});
}

test.describe("Live hcommons-dev anonymous probe", () => {
  test("groups directory", async ({ page }, testInfo) => {
    await page.goto("/groups/", { waitUntil: "domcontentloaded" });
    await settle(page);
    await shot(page, testInfo, "live-01-groups-directory");
    console.log("GROUPS TITLE:", await page.title());
  });

  test("docs directory", async ({ page }, testInfo) => {
    await page.goto("/docs/", { waitUntil: "domcontentloaded" });
    await settle(page);
    await shot(page, testInfo, "live-02-docs-directory");
    console.log("DOCS TITLE:", await page.title());
    const h2 = await page.locator("h2.doc-title").count();
    console.log("DOCS H2 doc-title count:", h2);
  });

  test("a public group page", async ({ page }, testInfo) => {
    await page.goto("/groups/", { waitUntil: "domcontentloaded" });
    await settle(page);
    const firstGroup = page.locator('#groups-dir-list a[href*="/groups/"], .groups-list a[href*="/groups/"]').first();
    if (await firstGroup.count()) {
      const href = await firstGroup.getAttribute("href");
      console.log("VISITING GROUP:", href);
      await page.goto(href!, { waitUntil: "domcontentloaded" });
      await settle(page);
      await shot(page, testInfo, "live-03-group-page");
      console.log("GROUP TITLE:", await page.title());
    } else {
      console.log("NO GROUP LINKS FOUND ON DIRECTORY");
      await shot(page, testInfo, "live-03-no-groups");
    }
  });
});
