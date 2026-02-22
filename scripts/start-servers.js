/**
 * Start Minecraft servers (1.16.5 SandBox, 1.21.10 SandBox).
 * Usage: node scripts/start-servers.js [1.16.5] [1.21.10]
 *   If no version given: both
 *
 * Lobby mode: node scripts/start-servers.js lobby
 *   Starts Velocity (25565) + Lobby (25580) + both game servers
 */

const path = require("path");
const fs = require("fs");
const { spawn } = require("child_process");

const ROOT = path.resolve(__dirname, "..");
const ARGV = process.argv.slice(2);
const LOBBY_MODE = ARGV.includes("lobby");
const SERVER_NAMES = ["SandBox"];
const VERSIONS = LOBBY_MODE
  ? ["1.16.5", "1.21.10"]
  : ARGV.filter((a) => a !== "lobby").length
    ? ARGV.filter((a) => a !== "lobby")
    : ["1.16.5", "1.21.10"];
const XMS = "1G";
const XMX = "2G";
const XMS_121 = "2G";
const XMX_121 = "3G";
const DELAY_BETWEEN_121_MS = 60000;
const isWindows = process.platform === "win32";

// JVM flags tuned for Minecraft servers (safe defaults).
// Notes:
// - Java 21: modern Aikar-style G1 flags.
// - Java 8: G1 flags (Paper 1.16.5 runs on Java 8 here).
const JVM_FLAGS_JAVA21 = [
  "-XX:+UseG1GC",
  "-XX:+ParallelRefProcEnabled",
  "-XX:MaxGCPauseMillis=200",
  "-XX:+UnlockExperimentalVMOptions",
  "-XX:+DisableExplicitGC",
  "-XX:+AlwaysPreTouch",
  "-XX:G1NewSizePercent=30",
  "-XX:G1MaxNewSizePercent=40",
  "-XX:G1HeapRegionSize=8M",
  "-XX:G1ReservePercent=20",
  "-XX:G1HeapWastePercent=5",
  "-XX:G1MixedGCCountTarget=4",
  "-XX:InitiatingHeapOccupancyPercent=15",
  "-XX:G1MixedGCLiveThresholdPercent=90",
  "-XX:G1RSetUpdatingPauseTimePercent=5",
  "-XX:SurvivorRatio=32",
  "-XX:+PerfDisableSharedMem",
  "-XX:MaxTenuringThreshold=1",
  "-Dusing.aikars.flags=https://mcflags.emc.gs",
  "-Daikars.new.flags=true",
];

const JVM_FLAGS_JAVA8 = [
  "-XX:+UseG1GC",
  "-XX:+ParallelRefProcEnabled",
  "-XX:MaxGCPauseMillis=200",
  "-XX:+UnlockExperimentalVMOptions",
  "-XX:+DisableExplicitGC",
  "-XX:+AlwaysPreTouch",
  "-XX:G1NewSizePercent=30",
  "-XX:G1MaxNewSizePercent=40",
  "-XX:G1HeapRegionSize=8M",
  "-XX:G1ReservePercent=20",
  "-XX:G1HeapWastePercent=5",
  "-XX:G1MixedGCCountTarget=4",
  "-XX:InitiatingHeapOccupancyPercent=15",
  "-XX:G1MixedGCLiveThresholdPercent=90",
  "-XX:G1RSetUpdatingPauseTimePercent=5",
  "-XX:SurvivorRatio=32",
  "-XX:+PerfDisableSharedMem",
  "-XX:MaxTenuringThreshold=1",
  "-Dusing.aikars.flags=https://mcflags.emc.gs",
  "-Daikars.new.flags=true",
];

function getJvmFlagsForVersion(ver) {
  if (ver === "1.21.10") return JVM_FLAGS_JAVA21;
  if (ver === "1.16.5") return JVM_FLAGS_JAVA8;
  return [];
}

function getJava21() {
  const env = process.env.JAVA_HOME;
  if (env) {
    const p = path.join(env.replace(/\/$/, ""), "bin", "java.exe");
    if (fs.existsSync(p)) return p;
  }
  const roots = [
    process.env["ProgramFiles"] || "C:\\Program Files",
    process.env["ProgramFiles(x86)"] || "C:\\Program Files (x86)",
  ];
  for (const root of roots) {
    try {
      const names = fs.readdirSync(path.join(root, "Eclipse Adoptium"), {
        withFileTypes: true,
      });
      const jdk21 = names.find(
        (d) => d.isDirectory() && d.name.startsWith("jdk-21"),
      );
      if (jdk21) {
        const exe = path.join(
          root,
          "Eclipse Adoptium",
          jdk21.name,
          "bin",
          "java.exe",
        );
        if (fs.existsSync(exe)) return exe;
      }
    } catch (_) {}
    try {
      const names = fs.readdirSync(path.join(root, "Microsoft"), {
        withFileTypes: true,
      });
      const jdk21 = names.find(
        (d) =>
          d.isDirectory() && (d.name.includes("21") || d.name === "jdk-21"),
      );
      if (jdk21) {
        const exe = path.join(root, "Microsoft", jdk21.name, "bin", "java.exe");
        if (fs.existsSync(exe)) return exe;
      }
    } catch (_) {}
  }
  return null;
}

function getJavaForVersion(ver) {
  if (ver === "1.16.5") {
    const java8 = path.join(
      ROOT,
      "tools",
      "java8",
      "current",
      "bin",
      "java.exe",
    );
    return fs.existsSync(java8) ? java8 : "java";
  }
  if (ver === "1.21.10") {
    const java21Local = path.join(
      ROOT,
      "tools",
      "java21",
      "current",
      "bin",
      "java.exe",
    );
    if (fs.existsSync(java21Local)) return java21Local;
    const java21 = getJava21();
    return java21 || "java";
  }
  return "java";
}

function getJarAndArgs(dir, ver) {
  if (ver === "1.16.5") {
    return fs.existsSync(path.join(dir, "server.jar"))
      ? { jar: "server.jar" }
      : null;
  }
  if (ver === "1.21.10") {
    return fs.existsSync(path.join(dir, "server.jar"))
      ? { jar: "server.jar" }
      : null;
  }
  return null;
}

function eulaAccepted(dir) {
  const eulaPath = path.join(dir, "eula.txt");
  if (!fs.existsSync(eulaPath)) return false;
  const content = fs.readFileSync(eulaPath, "utf8");
  return content.includes("eula=true");
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

function startProxyAndLobby() {
  const proxyDir = path.join(ROOT, "servers", "proxy");
  const lobbyDir = path.join(ROOT, "servers", "lobby");
  const java21 = getJavaForVersion("1.21.10");
  let n = 0;

  if (fs.existsSync(path.join(proxyDir, "velocity.jar"))) {
    const child = spawn(
      java21,
      ["-Xms512M", "-Xmx1G", ...JVM_FLAGS_JAVA21, "-jar", "velocity.jar", "nogui"],
      {
        cwd: proxyDir,
        detached: true,
        stdio: "ignore",
        windowsHide: isWindows,
      },
    );
    child.unref();
    n++;
    console.log("Started: proxy (PID %s)", child.pid);
  }

  if (
    fs.existsSync(path.join(lobbyDir, "server.jar")) &&
    eulaAccepted(lobbyDir)
  ) {
    const child = spawn(
      java21,
      ["-Xms1G", "-Xmx2G", ...JVM_FLAGS_JAVA21, "-jar", "server.jar", "nogui"],
      {
        cwd: lobbyDir,
        detached: true,
        stdio: "ignore",
        windowsHide: isWindows,
      },
    );
    child.unref();
    n++;
    console.log("Started: lobby (PID %s)", child.pid);
  }

  return n;
}

(async () => {
  let launched = 0;
  let prevWas121 = false;

  if (LOBBY_MODE) {
    launched += startProxyAndLobby();
    await sleep(5000);
  }

  for (const ver of VERSIONS) {
    for (const name of SERVER_NAMES) {
      const dir = path.join(ROOT, "servers", ver, name);
      if (!fs.existsSync(dir)) continue;
      if (!eulaAccepted(dir)) {
        console.warn(
          "Skip (EULA): %s / %s — set eula=true in eula.txt",
          ver,
          name,
        );
        continue;
      }
      const info = getJarAndArgs(dir, ver);
      if (!info) {
        console.warn("Skip (no jar): %s / %s", ver, name);
        continue;
      }
      let java = getJavaForVersion(ver);
      const is121 = ver === "1.21.10";
      if (is121 && (java === "java" || !java)) {
        console.warn(
          "Skip (Java 21): %s / %s — run .\\setup-java21.ps1 to install Java 21 in tools\\java21",
          ver,
          name,
        );
        continue;
      }
      if (prevWas121 && is121) {
        console.log(
          "Waiting %ds before next 1.21 server (world load)...",
          DELAY_BETWEEN_121_MS / 1000,
        );
        await sleep(DELAY_BETWEEN_121_MS);
      }
      const xms = is121 ? XMS_121 : XMS;
      const xmx = is121 ? XMX_121 : XMX;
      let args = [`-Xms${xms}`, `-Xmx${xmx}`, ...getJvmFlagsForVersion(ver)];
      if (is121) {
        args.unshift("-Dpaper.disablePluginRemapping=true");
        args.unshift(
          "-Dcom.ghostchu.quickshop.localization.text.SimpleTextManager.enableCrowdinOTA=false",
        );
      }
      args.push("-jar", info.jar, "nogui");
      const spawnOpts = {
        cwd: dir,
        detached: true,
        stdio: "ignore",
      };
      if (isWindows) {
        spawnOpts.windowsHide = true;
      }
      const child = spawn(java, args, spawnOpts);
      child.unref();
      launched++;
      console.log("Started: %s / %s (PID %s)", ver, name, child.pid);
      prevWas121 = is121;
    }
  }

  if (launched === 0) {
    console.log(
      "No servers started. Run setup-servers.ps1 and setup-servers-vanilla.ps1 if needed.",
    );
  } else {
    console.log(
      "Launched %d server(s). Logs: servers/<version>/<name>/logs/",
      launched,
    );
  }
})();
