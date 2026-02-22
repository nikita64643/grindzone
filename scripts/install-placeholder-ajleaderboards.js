/**
 * Download and install PlaceholderAPI + ajLeaderboards for 1.21.x SandBox.
 * PlaceholderAPI enables %vault_eco_balance% in TAB (install Vault expansion in-game).
 * ajLeaderboards allows placing signs with tops (e.g. top by balance).
 * Usage: node scripts/install-placeholder-ajleaderboards.js
 * Requires Node 18+.
 */

const path = require("path");
const fs = require("fs");
const https = require("https");
const http = require("http");

const ROOT = path.resolve(__dirname, "..");
const PLACEHOLDERAPI_URL =
  "https://cdn.modrinth.com/data/lKEzGugV/versions/p6u6fq4Q/PlaceholderAPI-2.12.1.jar";
const AJLEADERBOARDS_URL =
  "https://hangarcdn.papermc.io/plugins/ajgeiss0702/ajLeaderboards/versions/2.10.1/PAPER/ajLeaderboards-2.10.1.jar";
const VERSIONS = ["1.21.10"];
const SERVER_NAME = "SandBox";

function get(url) {
  return new Promise((resolve, reject) => {
    const lib = url.startsWith("https") ? https : http;
    const req = lib.get(
      url,
      { headers: { "User-Agent": "grindzone-setup/1.0" } },
      (res) => {
        if (res.statusCode === 302 || res.statusCode === 301) {
          get(res.headers.location).then(resolve).catch(reject);
          return;
        }
        const chunks = [];
        res.on("data", (c) => chunks.push(c));
        res.on("end", () => resolve(Buffer.concat(chunks)));
        res.on("error", reject);
      },
    );
    req.on("error", reject);
  });
}

function downloadToFile(url, destPath) {
  return new Promise((resolve, reject) => {
    const file = fs.createWriteStream(destPath);
    const lib = url.startsWith("https") ? https : http;
    const req = lib.get(
      url,
      { headers: { "User-Agent": "grindzone-setup/1.0" } },
      (res) => {
        if (res.statusCode === 302 || res.statusCode === 301) {
          downloadToFile(res.headers.location, destPath)
            .then(resolve)
            .catch(reject);
          return;
        }
        res.pipe(file);
        file.on("finish", () => {
          file.close();
          resolve();
        });
      },
    );
    req.on("error", (err) => {
      try {
        fs.unlinkSync(destPath);
      } catch (_) {}
      reject(err);
    });
  });
}

async function main() {
  const cacheDir = path.join(ROOT, "scripts", "cache");
  if (!fs.existsSync(cacheDir)) fs.mkdirSync(cacheDir, { recursive: true });

  const papiPath = path.join(cacheDir, "PlaceholderAPI-2.12.1.jar");
  if (!fs.existsSync(papiPath)) {
    console.log("Downloading PlaceholderAPI...");
    await downloadToFile(PLACEHOLDERAPI_URL, papiPath);
  } else {
    console.log("Using cached PlaceholderAPI.");
  }

  const ajlbPath = path.join(cacheDir, "ajLeaderboards-2.10.1.jar");
  if (!fs.existsSync(ajlbPath)) {
    console.log("Downloading ajLeaderboards...");
    await downloadToFile(AJLEADERBOARDS_URL, ajlbPath);
  } else {
    console.log("Using cached ajLeaderboards.");
  }

  for (const ver of VERSIONS) {
    const serverDir = path.join(ROOT, "servers", ver, SERVER_NAME);
    if (!fs.existsSync(serverDir)) {
      console.warn("Skip %s/%s (dir not found)", ver, SERVER_NAME);
      continue;
    }
    const pluginsDir = path.join(serverDir, "plugins");
    if (!fs.existsSync(pluginsDir))
      fs.mkdirSync(pluginsDir, { recursive: true });

    fs.copyFileSync(
      papiPath,
      path.join(pluginsDir, "PlaceholderAPI-2.12.1.jar"),
    );
    console.log("  %s/%s -> PlaceholderAPI", ver, SERVER_NAME);

    fs.copyFileSync(
      ajlbPath,
      path.join(pluginsDir, "ajLeaderboards-2.10.1.jar"),
    );
    console.log("  %s/%s -> ajLeaderboards", ver, SERVER_NAME);

    const expansionsDir = path.join(pluginsDir, "PlaceholderAPI", "expansions");
    if (!fs.existsSync(expansionsDir))
      fs.mkdirSync(expansionsDir, { recursive: true });
  }

  console.log("");
  console.log("Done. After first server start run in game (as op):");
  console.log("  /papi ecloud download Vault");
  console.log("  /papi ecloud download Essentials");
  console.log("  /papi reload");
  console.log("");
  console.log("Then to enable leaderboard signs (top by balance):");
  console.log("  /ajlb add %vault_eco_balance%");
  console.log("  Place blank signs, look at each and run:");
  console.log("  /ajlb signs add vault_eco_balance 1 alltime");
  console.log("  /ajlb signs add vault_eco_balance 2 alltime");
  console.log("  ... (3, 4, 5 for more positions)");
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
