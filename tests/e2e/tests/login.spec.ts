import { test, expect } from "../fixtures/auth.fixture";

test.describe("Login bypass", () => {
  test("authenticates with valid secret key", async ({
    authenticatedPage: page,
    capture,
  }) => {
    // If we got here the fixture already verified redirect away from login.
    // Confirm we can access the admin bar or dashboard.
    const adminBar = page.locator("#wpadminbar");
    await expect(adminBar).toBeVisible({ timeout: 10000 });
    await capture("authenticated-homepage");
  });

  test("rejects invalid secret key", async ({ page }) => {
    const response = await page.goto(
      "/wp-login.php?secret_key=wrong-key-value"
    );
    // With a wrong key the bypass is skipped and CILogon OIDC takes over.
    // Without OIDC credentials configured this results in a wp_die error page.
    // The key assertion: no wordpress_logged_in cookie should be set.
    const cookies = await page.context().cookies();
    const loggedInCookie = cookies.find((c) =>
      c.name.startsWith("wordpress_logged_in")
    );
    expect(loggedInCookie).toBeUndefined();
  });
});
