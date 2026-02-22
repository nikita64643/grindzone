/**
 * Restart all Minecraft servers: kill processes on known ports, then start all via start-servers.js.
 * Usage: node scripts/restart-all-servers.js [version ...]
 *   If no version given: 1.16.5, 1.21.10
 *
 * Stop only:
 *   node scripts/restart-all-servers.js --stop-only
 *   node scripts/restart-all-servers.js stop
 */

const path = require("path");
const fs = require("fs");
const { spawn } = require("child_process");

const ROOT = path.resolve(__dirname, "..");

const PORTS = [25565, 25566, 25570, 25580];

function findPidByPort(port) {
  const { execSync } = require("child_process");
  const isWindows = process.platform === "win32";
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
  } catch (_) {}
  return null;
}

function killPid(pid) {
  const { execSync } = require("child_process");
  const isWindows = process.platform === "win32";
  if (!pid || pid <= 0) return;
  try {
    if (isWindows) {
      execSync(`taskkill /PID ${pid} /F`, { stdio: "ignore" });
    } else {
      process.kill(pid, "SIGTERM");
    }
  } catch (_) {}
}

function sleepSync(ms) {
  const deadline = Date.now() + ms;
  while (Date.now() < deadline) {}
}

const args = process.argv.slice(2);
const stopOnly = args.includes("--stop-only") || args.includes("stop");
const versionsArgv = args.filter((a) => a !== "--stop-only" && a !== "stop");
const versions = versionsArgv.length ? versionsArgv : ["1.16.5", "1.21.10"];

let killed = 0;
for (const port of PORTS) {
  const pid = findPidByPort(port);
  if (pid) {
    console.log("Stopping PID %s on port %s...", pid, port);
    killPid(pid);
    killed++;
  }
}
if (killed > 0) {
  console.log("Waiting 2s...");
  sleepSync(2000);
}

if (stopOnly) {
  console.log("Stopped %d server(s).", killed);
  process.exit(0);
}

const pluginsDir = (ver) =>
  path.join(ROOT, "servers", ver, "SandBox", "plugins");
for (const ver of ["1.21.10"]) {
  const dir = pluginsDir(ver);
  const nxJar = path.join(dir, "NxAFKZone-1.0.0.jar");
  const nxFolder = path.join(dir, "NxAFKZone");
  if (fs.existsSync(nxJar)) {
    try {
      fs.unlinkSync(nxJar);
      console.log("Removed NxAFKZone jar on %s", ver);
    } catch (e) {
      console.warn("Could not remove NxAFKZone jar on %s: %s", ver, e.message);
    }
  }
  if (fs.existsSync(nxFolder)) {
    try {
      fs.readdirSync(nxFolder).forEach((f) => {
        fs.unlinkSync(path.join(nxFolder, f));
      });
      fs.rmdirSync(nxFolder);
      console.log("Removed NxAFKZone folder on %s", ver);
    } catch (e) {
      console.warn(
        "Could not remove NxAFKZone folder on %s: %s",
        ver,
        e.message,
      );
    }
  }
  // AxAFKZone 1.9.0 не загружается (ClassNotFoundException: Libby) — оставляем отключённым
  // const axafkDisabled = path.join(dir, "AxAFKZone-1.9.0.jar.disabled");
  // const axafkJar = path.join(dir, "AxAFKZone-1.9.0.jar");
  // if (fs.existsSync(axafkDisabled)) { ... }
}

console.log("Starting all servers...");
const startScript = path.join(__dirname, "start-servers.js");
const child = spawn(process.execPath, [startScript, ...versions], {
  cwd: ROOT,
  stdio: "inherit",
});
child.on("close", (code) => process.exit(code ?? 0));
