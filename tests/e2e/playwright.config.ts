import { defineConfig } from "@playwright/test";
import * as path from "path";

// Create timestamped output directory
const timestamp = new Date().toISOString().replace(/[:.]/g, "-").slice(0, 19);

export default defineConfig({
  testDir: "./tests",
  fullyParallel: false,
  retries: 1,
  workers: 1,
  outputDir: path.join("test-results", timestamp, "artifacts"),
  reporter: [
    ["list"],
    ["html", { outputFolder: path.join("test-results", timestamp, "html-report"), open: "never" }],
  ],
  use: {
    baseURL: process.env.BASE_URL || "http://hcommons.test",
    screenshot: "on",
    trace: "on-first-retry",
    video: "off",
  },
  projects: [
    {
      name: "chromium",
      use: {
        browserName: "chromium",
        launchOptions: {
          args: [
            "--no-sandbox",
            "--disable-setuid-sandbox",
          ],
        },
      },
    },
  ],
});
