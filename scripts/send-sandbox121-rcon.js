/**
 * Send RCON commands to 1.21.10 SandBox server.
 * Usage: node scripts/send-sandbox121-rcon.js [command1] [command2] ...
 */

const net = require("net");

const HOST = "127.0.0.1";
const PORT = 35570;
const PASS = "minecruft_rcon_2026";

const PACKET_AUTH = 3;
const PACKET_COMMAND = 2;
const PACKET_RESPONSE = 0;

function createPacket(id, type, payload) {
  const payloadBuf = Buffer.from(payload + "\0", "utf8");
  const buf = Buffer.alloc(4 + 4 + 4 + payloadBuf.length);
  let off = 0;
  buf.writeInt32LE(4 + 4 + payloadBuf.length, off);
  off += 4;
  buf.writeInt32LE(id, off);
  off += 4;
  buf.writeInt32LE(type, off);
  off += 4;
  payloadBuf.copy(buf, off);
  return buf;
}

function parsePacket(data) {
  const id = data.readInt32LE(4);
  const type = data.readInt32LE(8);
  const payload = data.toString("utf8", 12, data.length - 1);
  return { id, type, payload };
}

function sendRcon(host, port, pass, commands) {
  return new Promise((resolve, reject) => {
    const sock = net.createConnection({ host, port }, () => {});
    let reqId = 1;
    const results = [];
    let pending = 0;

    function onData(data) {
      const p = parsePacket(data);
      if (p.type === PACKET_AUTH && p.id === -1) {
        reject(new Error("RCON auth failed - wrong password"));
        sock.destroy();
        return;
      }
      if (p.type === PACKET_RESPONSE) {
        results.push(p.payload);
        pending--;
        if (pending === 0) sock.end();
      }
    }

    sock.on("data", onData);
    sock.on("error", reject);
    sock.on("close", () => {
      if (pending === 0) resolve(results);
    });

    sock.on("connect", () => {
      sock.write(createPacket(reqId++, PACKET_AUTH, pass));
      setTimeout(() => {
        for (const cmd of commands) {
          sock.write(createPacket(reqId++, PACKET_COMMAND, cmd));
          pending++;
        }
      }, 100);
    });
  });
}

async function main() {
  const commands = process.argv.length > 2 ? process.argv.slice(2) : ["tab reload"];

  try {
    const results = await sendRcon(HOST, PORT, PASS, commands);
    for (let i = 0; i < commands.length; i++) {
      const r = results[i] || "";
      console.log("> " + commands[i] + (r ? "\n" + r.trim() : ""));
    }
    console.log("Done.");
  } catch (e) {
    console.error("RCON error:", e.message);
    process.exit(1);
  }
}

main();
