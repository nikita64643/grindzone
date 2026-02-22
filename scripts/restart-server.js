/**
 * Restart a single Minecraft server by port and server dir (version + name).
 * Usage: node scripts/restart-server.js --port <port> --version <versionFolder> --name <serverName>
 *   versionFolder: 1.16.5, 1.21.10
 *   Example: node scripts/restart-server.js --port 25570 --version 1.21.10 --name SandBox
 * Or: node scripts/restart-server.js --port <port> --dir <absolutePathToServerDir>
 *
 * Finds process listening on port, kills it, waits 2s, starts server via Node (java spawn).
 */

const path = require("path");
const fs = require("fs");
const { spawn, execSync } = require("child_process");

const ROOT = path.resolve(__dirname, "..");
const XMS = "1G";
const XMX = "2G";
const isWindows = process.platform === "win32";

// JVM flags tuned for Minecraft servers (safe defaults).
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
  if (ver === "1.21.10" || ver === "1.20.4" || ver === "1.20.6") return JVM_FLAGS_JAVA21;
  if (ver === "1.16.5") return JVM_FLAGS_JAVA8;
  return [];
}

function parseArgs() {
  const args = process.argv.slice(2);
  const out = { port: null, version: null, name: null, dir: null };
  for (let i = 0; i < args.length; i++) {
    if (args[i] === "--port" && args[i + 1]) {
      out.port = parseInt(args[i + 1], 10);
      i++;
    } else if (args[i] === "--version" && args[i + 1]) {
      out.version = args[i + 1];
      i++;
    } else if (args[i] === "--name" && args[i + 1]) {
      out.name = args[i + 1];
      i++;
    } else if (args[i] === "--dir" && args[i + 1]) {
      out.dir = path.resolve(args[i + 1]);
      i++;
    }
  }
  return out;
}

function findPidByPort(port) {
  if (!Number.isInteger(port) || port <= 0) return null;
  try {
    if (isWindows) {
      const out = execSync(`netstat -ano | findstr ":${port}"`, {
        encoding: "utf8",
        maxBuffer: 1024 * 1024,
      });
      const lines = out.split(/\r?\n/).filter(Boolean);
      for (const line of lines) {
        const m = line.trim().match(/\s+(\d+)\s*$/);
        if (m && line.includes("LISTENING")) {
          const pid = parseInt(m[1], 10);
          if (pid > 0) return pid;
        }
      }
    } else {
      const out = execSync(`lsof -ti :${port}`, { encoding: "utf8" }).trim();
      const pid = parseInt(out, 10);
      if (pid > 0) return pid;
    }
  } catch (_) {
    // no process on port
  }
  return null;
}

function killPid(pid) {
  if (!pid || pid <= 0) return;
  try {
    if (isWindows) {
      execSync(`taskkill /PID ${pid} /F`, { stdio: "ignore" });
    } else {
      process.kill(pid, "SIGTERM");
    }
  } catch (_) {}
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
  if (ver === "1.21.10" || ver === "1.20.4" || ver === "1.20.6") {
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
  // Velocity proxy
  if (fs.existsSync(path.join(dir, "velocity.jar"))) {
    return { jar: "velocity.jar", isProxy: true };
  }
  if (ver === "1.16.5") {
    return fs.existsSync(path.join(dir, "server.jar"))
      ? { jar: "server.jar" }
      : null;
  }
  if (ver === "1.21.10" || ver === "1.20.4" || ver === "1.20.6") {
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

function main() {
  const opts = parseArgs();
  const port = opts.port;
  let serverDir = opts.dir;
  let version = opts.version;

  if (!port || port <= 0) {
    console.error(
      "Usage: node restart-server.js --port <port> (--version <ver> --name <name> | --dir <path>)",
    );
    process.exit(1);
  }

  if (!serverDir) {
    if (!opts.version || !opts.name) {
      console.error("Provide either --dir <path> or --version and --name.");
      process.exit(1);
    }
    serverDir = path.join(ROOT, "servers", opts.version, opts.name);
    version = opts.version;
  } else {
    const rel = path.relative(ROOT, serverDir);
    const parts = rel.split(path.sep);
    if (opts.version) {
      version = opts.version;
    } else if (parts[0] === "servers" && parts.length >= 2) {
      version = parts[1] === "lobby" ? "1.21.10" : parts[1];
    } else {
      version = "1.16.5";
    }
  }

  if (!fs.existsSync(serverDir)) {
    console.error("Server dir not found:", serverDir);
    process.exit(1);
  }

  function sleepSync(ms) {
    const deadline = Date.now() + ms;
    while (Date.now() < deadline) {}
  }

  const pid = findPidByPort(port);
  if (pid) {
    console.log("Stopping process PID %s on port %s...", pid, port);
    killPid(pid);
    sleepSync(2000);
  } else {
    console.log("No process on port %s, starting server...", port);
  }
  runStart(serverDir, version);
}

function runStart(serverDir, version) {
  const info = getJarAndArgs(serverDir, version);
  if (!info) {
    console.error("No server jar found in %s", serverDir);
    process.exit(1);
  }
  if (!info.isProxy && !eulaAccepted(serverDir)) {
    console.error(
      "EULA not accepted in %s. Set eula=true in eula.txt.",
      serverDir,
    );
    process.exit(1);
  }

  const java = getJavaForVersion(info.isProxy ? "1.21.10" : version);
  const mem = info.isProxy ? { xms: "512M", xmx: "1G" } : { xms: XMS, xmx: XMX };
  const args = [`-Xms${mem.xms}`, `-Xmx${mem.xmx}`, ...getJvmFlagsForVersion("1.21.10")];
  if (version === "1.21.10" && !info.isProxy) {
    args.push(
      "-Dcom.ghostchu.quickshop.localization.text.SimpleTextManager.enableCrowdinOTA=false",
    );
    args.push("-Dpaper.disablePluginRemapping=true");
  }
  args.push("-jar", info.jar, "nogui");
  const spawnOpts = {
    cwd: serverDir,
    detached: true,
    stdio: "ignore",
  };
  if (isWindows) {
    spawnOpts.windowsHide = true;
  }

  const child = spawn(java, args, spawnOpts);
  child.unref();
  console.log("Started server in %s (PID %s)", serverDir, child.pid);
}

main();
