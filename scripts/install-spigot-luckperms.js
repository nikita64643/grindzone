/**
 * Install Spigot (replaces Vanilla/Paper server.jar) and LuckPerms for 1.21.x servers.
 * Downloads pre-built Spigot from ServerJars (fast). Fallback: BuildTools.
 * Usage: node scripts/install-spigot-luckperms.js [1.21.10]
 */

const path = require("path");
const fs = require("fs");
const https = require("https");
const http = require("http");
const { execSync } = require("child_process");

const ROOT = path.resolve(__dirname, "..");
const CACHE_DIR = path.join(ROOT, "scripts", "cache");
const BUILD_DIR = path.join(ROOT, "scripts", "spigot-build");
const BUILDTOOLS_JAR = path.join(BUILD_DIR, "BuildTools.jar");
const LUCKPERMS_URL =
  "https://cdn.modrinth.com/data/Vebnzrzj/versions/OrIs0S6b/LuckPerms-Bukkit-5.5.17.jar";
const SERVER_NAMES = ["SandBox"];

function downloadToFile(url, destPath) {
  return new Promise((resolve, reject) => {
    const lib = url.startsWith("https") ? https : http;
    const file = fs.createWriteStream(destPath);
    const req = lib.get(
      url,
      { headers: { "User-Agent": "grindzone-setup/1.0" } },
      (res) => {
        if (res.statusCode === 302 || res.statusCode === 301) {
          file.close();
          fs.unlink(destPath, () => {});
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

function getJava21() {
  const java21Local = path.join(
    ROOT,
    "tools",
    "java21",
    "current",
    "bin",
    "java.exe",
  );
  if (fs.existsSync(java21Local)) return java21Local;
  const env = process.env.JAVA_HOME;
  if (env) {
    const p = path.join(env.replace(/[/\\]$/, ""), "bin", "java.exe");
    if (fs.existsSync(p)) return p;
  }
  return "java";
}

const SERVERJARS_URL = "https://serverjars.org/download/spigot";

async function downloadSpigot(version) {
  const destPath = path.join(CACHE_DIR, `spigot-${version}.jar`);
  if (fs.existsSync(destPath)) {
    console.log("Using cached Spigot %s.", version);
    return destPath;
  }
  const url = `${SERVERJARS_URL}/${version}`;
  console.log("Downloading Spigot %s from ServerJars...", version);
  if (!fs.existsSync(CACHE_DIR)) fs.mkdirSync(CACHE_DIR, { recursive: true });
  try {
    await downloadToFile(url, destPath);
    if (fs.statSync(destPath).size < 100000) {
      fs.unlinkSync(destPath);
      throw new Error("Downloaded file too small, likely HTML error page");
    }
  } catch (err) {
    throw new Error(
      `Failed to download Spigot ${version} from ServerJars: ${err.message}. Try BuildTools manually.`,
    );
  }
  return destPath;
}

function buildSpigot(version) {
  const java = getJava21();
  console.log("Building Spigot %s with BuildTools (Java 21)...", version);
  try {
    execSync(`"${java}" -jar BuildTools.jar --rev ${version}`, {
      cwd: BUILD_DIR,
      stdio: "inherit",
      maxBuffer: 50 * 1024 * 1024,
    });
  } catch (err) {
    throw new Error(
      `BuildTools failed for ${version}. Ensure Java 21 is installed (run .\\setup-java21.ps1).`,
    );
  }
  const files = fs.readdirSync(BUILD_DIR, { withFileTypes: true });
  const allSpigot = files.filter(
    (f) =>
      f.isFile() && f.name.startsWith("spigot-") && f.name.endsWith(".jar"),
  );
  const spigotJar = allSpigot.find((f) => f.name.includes(version));
  if (!spigotJar) {
    throw new Error(
      `Spigot jar not found in ${BUILD_DIR} after build. Found: ${allSpigot.map((f) => f.name).join(", ") || "none"}`,
    );
  }
  return path.join(BUILD_DIR, spigotJar.name);
}

async function main() {
  const versions = process.argv.slice(2).length
    ? process.argv.slice(2)
    : ["1.21.10"];

  const luckPermsPath = path.join(CACHE_DIR, "LuckPerms-Bukkit-5.5.17.jar");
  if (!fs.existsSync(luckPermsPath)) {
    console.log("Downloading LuckPerms...");
    if (!fs.existsSync(CACHE_DIR)) fs.mkdirSync(CACHE_DIR, { recursive: true });
    await downloadToFile(LUCKPERMS_URL, luckPermsPath);
  } else {
    console.log("Using cached LuckPerms jar.");
  }

  const spigotJars = {};
  for (const ver of versions) {
    const versionDir = path.join(ROOT, "servers", ver);
    if (!fs.existsSync(versionDir)) {
      console.warn("Skip %s (dir not found)", ver);
      continue;
    }
    try {
      spigotJars[ver] = await downloadSpigot(ver);
    } catch (err) {
      console.warn(err.message);
      const paperPath = path.join(CACHE_DIR, `paper-${ver}-*.jar`);
      const paperFiles = fs
        .readdirSync(CACHE_DIR)
        .filter((n) => n.startsWith(`paper-${ver}-`) && n.endsWith(".jar"));
      if (paperFiles.length > 0) {
        const p = path.join(CACHE_DIR, paperFiles[paperFiles.length - 1]);
        console.log(
          "Using cached Paper %s (Spigot-compatible): %s",
          ver,
          paperFiles[paperFiles.length - 1],
        );
        spigotJars[ver] = p;
      } else if (fs.existsSync(BUILDTOOLS_JAR)) {
        spigotJars[ver] = buildSpigot(ver);
      } else {
        throw err;
      }
    }
  }

  for (const ver of versions) {
    const spigotPath = spigotJars[ver];
    if (!spigotPath || !fs.existsSync(spigotPath)) continue;

    for (const name of SERVER_NAMES) {
      const serverDir = path.join(ROOT, "servers", ver, name);
      if (!fs.existsSync(serverDir)) continue;

      const serverJar = path.join(serverDir, "server.jar");
      const backupJar = path.join(serverDir, "server-paper.jar");
      const backupVanilla = path.join(serverDir, "server-vanilla.jar");
      if (fs.existsSync(serverJar)) {
        if (!fs.existsSync(backupJar) && !fs.existsSync(backupVanilla)) {
          console.log(
            "Backup server.jar -> server-paper.jar in %s/%s",
            ver,
            name,
          );
          fs.copyFileSync(serverJar, backupJar);
        }
      }
      console.log("Installing Spigot %s in %s/%s", ver, ver, name);
      fs.copyFileSync(spigotPath, serverJar);

      const pluginsDir = path.join(serverDir, "plugins");
      if (!fs.existsSync(pluginsDir))
        fs.mkdirSync(pluginsDir, { recursive: true });
      const lpDest = path.join(pluginsDir, "LuckPerms-Bukkit-5.5.17.jar");
      fs.copyFileSync(luckPermsPath, lpDest);
      console.log("  -> plugins/LuckPerms-Bukkit-5.5.17.jar");

      const startPs1 = path.join(serverDir, "start.ps1");
      if (fs.existsSync(startPs1)) {
        let content = fs.readFileSync(startPs1, "utf8");
        content = content.replace(
          /vanilla server \(server\.jar\)/gi,
          "Spigot server (server.jar)",
        );
        content = content.replace(
          /setup-servers-vanilla\.ps1/gi,
          "install-spigot-luckperms.ps1",
        );
        fs.writeFileSync(startPs1, content);
      }
    }
  }

  console.log("");
  console.log("Done. Restart each server so Spigot and LuckPerms load.");
  console.log("First start may take longer (Spigot/LuckPerms setup).");
  console.log("To restore Paper: copy server-paper.jar back to server.jar.");
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
