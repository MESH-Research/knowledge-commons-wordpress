import { test, expect } from "../fixtures/auth.fixture";

/**
 * Regression tests for saving a brand-new doc from /docs/create.
 *
 * https://github.com/MESH-Research/knowledge-commons-wordpress/issues/118 —
 * saving the create form died in wp_nonce_ays() with "The link you followed
 * has expired."
 */

/** Fill the docs create form and submit it, returning the resulting body text. */
async function createDoc(page: any, title: string): Promise<string> {
  await page.locator("#doc-title").fill(title);

  // The content editor is TinyMCE (classic). Write into the underlying
  // textarea and remove the visual editor so the value survives submission.
  await page.evaluate(() => {
    const w = window as any;
    if (w.tinymce && w.tinymce.get("doc_content")) {
      w.tinymce.get("doc_content").setContent("Automated test content.");
    } else {
      const ta = document.querySelector(
        "#doc_content"
      ) as HTMLTextAreaElement | null;
      if (ta) {
        ta.value = "Automated test content.";
      }
    }
  });

  await page.locator("#doc-edit-submit").click();
  await page.waitForLoadState("networkidle");

  return page.locator("body").innerText();
}

test.describe.serial("Docs", () => {
  const groupName = `Docs E2E Group ${Date.now()}`;
  const groupSlug = groupName.toLowerCase().replace(/\s+/g, "-");

  test("create a group to hold docs", async ({
    authenticatedPage: page,
    capture,
  }) => {
    await page.goto("/groups/create/step/group-details/");
    await page.waitForLoadState("networkidle");

    await page.locator("#group-name").fill(groupName);
    await page
      .locator("#group-desc")
      .fill("Automated group for docs create tests.");
    await page.waitForTimeout(500);

    await page.locator("#group-creation-create").click();
    await page.waitForLoadState("networkidle");

    // Walk through the remaining wizard steps.
    for (let i = 0; i < 7; i++) {
      const finish = page.locator("#group-creation-finish");
      const next = page.locator("#group-creation-next");
      if (await finish.isVisible({ timeout: 500 }).catch(() => false)) {
        await finish.click();
        break;
      } else if (await next.isVisible({ timeout: 500 }).catch(() => false)) {
        await next.click();
        await page.waitForLoadState("networkidle");
      } else {
        break;
      }
    }

    await page.waitForLoadState("networkidle");
    await capture("01-docs-group-created");

    await page.goto(`/groups/${groupSlug}/`);
    await page.waitForLoadState("networkidle");
    await expect(page.locator("body")).toContainText(groupName);
  });

  test("save a new doc from /docs/create without a group", async ({
    authenticatedPage: page,
    capture,
  }) => {
    const docTitle = `E2E Doc No Group ${Date.now()}`;

    await page.goto("/docs/create/");
    await page.waitForLoadState("networkidle");
    await capture("02-docs-create-form");

    const bodyText = await createDoc(page, docTitle);
    await capture("03-docs-create-saved");

    expect(bodyText).not.toContain("The link you followed has expired");
    await expect(page.locator("body")).toContainText(docTitle);
  });

  test("save a new doc from /docs/create?group=... in a group", async ({
    authenticatedPage: page,
    capture,
  }) => {
    test.skip(!groupSlug, "Group was not created");

    const docTitle = `E2E Doc In Group ${Date.now()}`;

    await page.goto(`/docs/create/?group=${groupSlug}`);
    await page.waitForLoadState("networkidle");
    await capture("04-docs-create-group-form");

    const bodyText = await createDoc(page, docTitle);
    await capture("05-docs-create-group-saved");

    expect(bodyText).not.toContain("The link you followed has expired");
    await expect(page.locator("body")).toContainText(docTitle);
  });

  test("group docs tab links back to the group", async ({
    authenticatedPage: page,
    capture,
  }) => {
    test.skip(!groupSlug, "Group was not created");

    // https://github.com/MESH-Research/knowledge-commons-wordpress/issues/121
    // The group docs tab must render inside the group's own page, so there
    // is always a way back to the group (header title / group nav). A theme
    // that swaps in a standalone docs template here strands the visitor.
    await page.goto(`/groups/${groupSlug}/docs/`);
    await page.waitForLoadState("networkidle");
    await capture("06-group-docs-back-link");

    // The docs list itself rendered...
    await expect(page.locator("#buddypress")).toBeAttached();

    // ...and at least one link leads back to the group's home page. The
    // group's own docs tab URL (/groups/slug/docs/) must not satisfy this.
    const backLink = page.locator(`a[href$="/groups/${groupSlug}/"]`).first();
    await expect(backLink).toBeAttached();

    // The group's name is shown as page context, not just as a tab label.
    await expect(page.locator("body")).toContainText(groupName);
  });
});
