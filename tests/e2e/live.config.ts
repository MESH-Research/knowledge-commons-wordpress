import { defineConfig } from "@playwright/test";
import * as path from "path";

// Config for anonymous probes against a LIVE environment (BASE_URL required).
// Kept separate from playwright.config.ts so normal suite runs never touch
// live sites.
const timestamp = new Date().toISOString().replace(/[:.]/g, "-").slice(0, 19);

export default defineConfig({
  testDir: "./tests-live",
  fullyParallel: false,
  retries: 0,
  workers: 1,
  timeout: 60000,
  outputDir: path.join("test-results", `live-${timestamp}`, "artifacts"),
  reporter: [["list"]],
  use: {
    baseURL: process.env.BASE_URL || "https://hcommons-dev.org",
    screenshot: "on",
  },
  projects: [
    {
      name: "chromium",
      use: {
        browserName: "chromium",
        launchOptions: {
          args: ["--no-sandbox", "--disable-setuid-sandbox"],
        },
      },
    },
  ],
});
