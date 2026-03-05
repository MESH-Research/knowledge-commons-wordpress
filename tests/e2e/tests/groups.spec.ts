import { test, expect } from "../fixtures/auth.fixture";

test.describe.serial("Groups", () => {
  const groupName = `E2E Test Group ${Date.now()}`;
  // BuddyPress slugifies: lowercase, replace spaces with hyphens
  const groupSlug = groupName.toLowerCase().replace(/\s+/g, "-");

  test("create a group", async ({ authenticatedPage: page, capture }) => {
    await page.goto("/groups/create/step/group-details/");
    await page.waitForLoadState("networkidle");
    await capture("01-group-create-form");

    // Fill in group name and description (both required)
    await page.locator("#group-name").fill(groupName);
    await page.locator("#group-desc").fill("Automated test group created by Playwright.");

    // Wait briefly for slug to auto-populate
    await page.waitForTimeout(500);
    await capture("02-group-form-filled");

    // Submit the first step
    await page.locator("#group-creation-create").click();
    await page.waitForLoadState("networkidle");
    await capture("03-group-after-first-submit");

    // Walk through the creation wizard — click through remaining steps
    // Each step may have Next or Finish buttons; check for forum checkbox on each step
    for (let i = 0; i < 7; i++) {
      // Enable forum if the bbPress create checkbox exists (appears on Forum step)
      const forumCheckbox = page.locator("#bbp-create-group-forum");
      if (await forumCheckbox.isVisible({ timeout: 500 }).catch(() => false)) {
        await forumCheckbox.check();
        await capture(`04-group-forum-step-${i}`);
      }

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
    await capture("05-group-creation-complete");

    // Verify the group was created by navigating to it
    await page.goto(`/groups/${groupSlug}/`);
    await page.waitForLoadState("networkidle");
    await capture("06-group-page");
    await expect(page.locator("body")).toContainText(groupName);
  });

  test("post a forum topic in the group", async ({
    authenticatedPage: page,
    capture,
  }) => {
    test.skip(!groupSlug, "Group was not created");

    await page.goto(`/groups/${groupSlug}/forum/`);
    await page.waitForLoadState("networkidle");
    await capture("07-group-forum-page");

    // Click "New Topic" or find the new topic form
    const newTopicButton = page.locator('a:has-text("New Topic")');
    if (await newTopicButton.isVisible()) {
      await newTopicButton.click();
      await page.waitForLoadState("networkidle");
    }

    const topicTitle = `E2E Test Topic ${Date.now()}`;
    await page.locator("#bbp_topic_title").fill(topicTitle);
    await page.locator("#bbp_topic_content").fill("This is an automated test topic created by Playwright.");
    await capture("08-forum-topic-filled");

    await page.locator("#bbp_topic_submit").click();
    await page.waitForLoadState("networkidle");
    await capture("09-forum-topic-posted");

    // Verify topic was created
    await expect(page.locator("body")).toContainText(topicTitle);
  });

  test("delete the group", async ({ authenticatedPage: page, capture }) => {
    test.skip(!groupSlug, "Group was not created");

    await page.goto(`/groups/${groupSlug}/admin/delete-group/`);
    await page.waitForLoadState("networkidle");
    await capture("10-group-delete-page");

    // Check the confirmation checkbox
    const confirmCheckbox = page.locator("#delete-group-understand");
    if (await confirmCheckbox.isVisible()) {
      await confirmCheckbox.check();
    }

    // Click delete
    await page.locator("#delete-group-button").click();
    await page.waitForLoadState("networkidle");
    await capture("11-group-deleted");

    // Verify redirect to groups directory or confirmation
    expect(page.url()).toContain("/groups");
  });
});
