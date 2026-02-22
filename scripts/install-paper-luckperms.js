/**
 * Install Paper (replaces Vanilla server.jar) and LuckPerms for 1.21.x servers.
 * Usage: node scripts/install-paper-luckperms.js [1.21.10] [1.21.11]
 *   If no version given: installs for 1.21.10.
 *   Requires Node 18+ (fetch).
 */

const path = require("path");
const fs = require("fs");
const https = require("https");

const ROOT = path.resolve(__dirname, "..");
const LUCKPERMS_URL =
  "https://cdn.modrinth.com/data/Vebnzrzj/versions/OrIs0S6b/LuckPerms-Bukkit-5.5.17.jar";
const SERVER_NAMES = ["SandBox"];

function get(url) {
  return new Promise((resolve, reject) => {
    const req = https.get(
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
    const req = https.get(
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
      fs.unlink(destPath, () => {});
      reject(err);
    });
  });
}

async function getLatestPaperBuild(paperVersion) {
  const url = `https://api.papermc.io/v2/projects/paper/versions/${paperVersion}/builds`;
  const data = await get(url);
  const json = JSON.parse(data.toString());
  const builds = json.builds || [];
  if (builds.length === 0)
    throw new Error("No Paper builds found for " + paperVersion);
  const latest = builds[builds.length - 1];
  const buildNum = latest.build;
  const fileName =
    latest.downloads?.application?.name ||
    `paper-${paperVersion}-${buildNum}.jar`;
  return {
    build: buildNum,
    url: `https://api.papermc.io/v2/projects/paper/versions/${paperVersion}/builds/${buildNum}/downloads/${fileName}`,
    name: fileName,
  };
}

async function main() {
  const versions = process.argv.slice(2).length
    ? process.argv.slice(2)
    : ["1.21.10"];

  const luckPermsPath = path.join(
    ROOT,
    "scripts",
    "cache",
    "LuckPerms-Bukkit-5.5.17.jar",
  );
  if (!fs.existsSync(luckPermsPath)) {
    console.log("Downloading LuckPerms...");
    await downloadToFile(LUCKPERMS_URL, luckPermsPath);
  } else {
    console.log("Using cached LuckPerms jar.");
  }

  for (const ver of versions) {
    const versionDir = path.join(ROOT, "servers", ver);
    if (!fs.existsSync(versionDir)) {
      console.warn("Skip %s (dir not found)", ver);
      continue;
    }
    console.log("Fetching latest Paper %s build...", ver);
    const paper = await getLatestPaperBuild(ver);
    console.log("Paper %s: %s", ver, paper.name);

    const paperPath = path.join(ROOT, "scripts", "cache", paper.name);
    const cacheDir = path.dirname(paperPath);
    if (!fs.existsSync(cacheDir)) fs.mkdirSync(cacheDir, { recursive: true });
    if (!fs.existsSync(paperPath)) {
      console.log("Downloading Paper %s...", ver);
      await downloadToFile(paper.url, paperPath);
    } else {
      console.log("Using cached Paper jar.");
    }

    for (const name of SERVER_NAMES) {
      const serverDir = path.join(versionDir, name);
      if (!fs.existsSync(serverDir)) continue;

      const serverJar = path.join(serverDir, "server.jar");
      const backupJar = path.join(serverDir, "server-vanilla.jar");
      if (fs.existsSync(serverJar) && !fs.existsSync(backupJar)) {
        console.log(
          "Backup server.jar -> server-vanilla.jar in %s/%s",
          ver,
          name,
        );
        fs.copyFileSync(serverJar, backupJar);
      }
      console.log("Installing Paper %s in %s/%s", ver, ver, name);
      fs.copyFileSync(paperPath, serverJar);

      const pluginsDir = path.join(serverDir, "plugins");
      if (!fs.existsSync(pluginsDir))
        fs.mkdirSync(pluginsDir, { recursive: true });
      const lpDest = path.join(pluginsDir, "LuckPerms-Bukkit-5.5.17.jar");
      fs.copyFileSync(luckPermsPath, lpDest);
      console.log("  -> plugins/LuckPerms-Bukkit-5.5.17.jar");
    }
  }

  console.log("");
  console.log("Done. Restart each server so Paper and LuckPerms load.");
  console.log("First start may take longer (Paper/LuckPerms setup).");
  console.log(
    "To restore Vanilla: copy server-vanilla.jar back to server.jar and remove plugins folder.",
  );
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
