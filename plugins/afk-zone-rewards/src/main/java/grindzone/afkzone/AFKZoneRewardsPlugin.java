package grindzone.afkzone;

import net.kyori.adventure.bossbar.BossBar;
import net.kyori.adventure.text.serializer.legacy.LegacyComponentSerializer;
import org.bukkit.Bukkit;
import org.bukkit.Location;
import org.bukkit.entity.Player;
import org.bukkit.plugin.java.JavaPlugin;

import java.util.HashMap;
import java.util.Map;
import java.util.UUID;

/**
 * AFK-зона с наградами по привилегиям (Legend 5 мин, Premium 10 мин, VIP 15 мин, Default 20 мин).
 * Замена AxAFKZone без зависимости Libby.
 */
public class AFKZoneRewardsPlugin extends JavaPlugin {

    // Зона по координатам (world_sandbox, cuboid как в AxAFKZone/WorldGuard)
    private static final String ZONE_WORLD = "world_sandbox";
    private static final int MIN_X = 8659467;
    private static final int MAX_X = 8659518;
    private static final int MIN_Y = 62;
    private static final int MAX_Y = 87;
    private static final int MIN_Z = -3812039;
    private static final int MAX_Z = -3811998;

    // Permission -> интервал в секундах (от большего к меньшему для проверки)
    private static final String[][] TIERS = {
        {"axafkzone.tier.legend", "300"},   // 5 мин
        {"axafkzone.tier.premium", "600"},  // 10 мин
        {"axafkzone.tier.vip", "900"},      // 15 мин
        {"axafkzone.tier.default", "1200"}  // 20 мин
    };

    private static final LegacyComponentSerializer LEGACY = LegacyComponentSerializer.legacyAmpersand();

    private final Map<UUID, Integer> secondsInZone = new HashMap<>();
    private final Map<UUID, BossBar> playerBossBars = new HashMap<>();
    private int taskId = -1;

    // Центр AFK-зоны для телепорта (как warp afk)
    private static final double TP_X = 8659486.25;
    private static final double TP_Y = 71.0;
    private static final double TP_Z = -3812023.5;
    private static final float TP_YAW = -96.23f;
    private static final float TP_PITCH = -3.3f;

    @Override
    public void onEnable() {
        saveDefaultConfig();
        if (Bukkit.getPluginManager().isPluginEnabled("PlaceholderAPI")) {
            new AFKZoneRewardsExpansion(this).register();
        }
        var afkCmd = getCommand("afk");
        if (afkCmd != null) {
            afkCmd.setExecutor((sender, cmd, label, args) -> {
                if (!(sender instanceof Player p)) {
                    sender.sendMessage(LEGACY.deserialize("&cТолько для игроков!"));
                    return true;
                }
                if (args.length > 0) {
                    p.sendMessage(LEGACY.deserialize("&7Для баланса AFK-монет: &b/afkcoins balance"));
                    return true;
                }
                var world = Bukkit.getWorld(ZONE_WORLD);
                if (world == null) {
                    p.sendMessage(LEGACY.deserialize("&cМир " + ZONE_WORLD + " не найден!"));
                    return true;
                }
                p.teleport(new Location(world, TP_X, TP_Y, TP_Z, TP_YAW, TP_PITCH));
                p.sendMessage(LEGACY.deserialize("&aТелепорт в AFK-зону!"));
                return true;
            });
        }
        taskId = Bukkit.getScheduler().scheduleSyncRepeatingTask(this, this::tick, 20L, 20L);
        getLogger().info("AFKZoneRewards включён. Команда /afk, интервалы: Legend 5м, Premium 10м, VIP 15м, Default 20м.");
    }

    @Override
    public void onDisable() {
        if (taskId >= 0) Bukkit.getScheduler().cancelTask(taskId);
        playerBossBars.forEach((uuid, bar) -> {
            Player p = Bukkit.getPlayer(uuid);
            if (p != null && p.isOnline()) p.hideBossBar(bar);
        });
        playerBossBars.clear();
    }

    private void tick() {
        for (Player p : Bukkit.getOnlinePlayers()) {
            if (!p.isOnline()) continue;
            if (!isInZone(p.getLocation())) {
                secondsInZone.remove(p.getUniqueId());
                hideBossBar(p);
                continue;
            }

            int sec = secondsInZone.getOrDefault(p.getUniqueId(), 0) + 1;
            secondsInZone.put(p.getUniqueId(), sec);

            int interval = getIntervalSeconds(p);
            if (sec >= interval) {
                giveReward(p);
                secondsInZone.put(p.getUniqueId(), 0);
            }
            updateBossBar(p);
            sendActionBar(p);
        }
    }

    public boolean isInZone(Location loc) {
        if (loc == null || !ZONE_WORLD.equals(loc.getWorld().getName())) return false;
        int x = loc.getBlockX();
        int y = loc.getBlockY();
        int z = loc.getBlockZ();
        return x >= MIN_X && x <= MAX_X && y >= MIN_Y && y <= MAX_Y && z >= MIN_Z && z <= MAX_Z;
    }

    public boolean isInZone(Player p) {
        return p != null && isInZone(p.getLocation());
    }

    /** Формат M:SS до награды, или пустая строка если не в зоне */
    public String getTimeLeftFormatted(Player p) {
        if (!isInZone(p)) return "";
        int sec = secondsInZone.getOrDefault(p.getUniqueId(), 0);
        int interval = getIntervalSeconds(p);
        int left = Math.max(0, interval - sec);
        int min = left / 60;
        int s = left % 60;
        return min + ":" + (s < 10 ? "0" : "") + s;
    }

    private void sendActionBar(Player p) {
        if (!getConfig().getBoolean("actionbar.enabled", true)) return;
        String time = getTimeLeftFormatted(p);
        if (time.isEmpty()) return;
        String msg = getConfig().getString("actionbar.format", "&eДо награды: &a%time%")
            .replace("%time%", time);
        p.sendActionBar(LEGACY.deserialize(msg));
    }

    private void updateBossBar(Player p) {
        if (!getConfig().getBoolean("bossbar.enabled", true)) return;
        int sec = secondsInZone.getOrDefault(p.getUniqueId(), 0);
        int interval = getIntervalSeconds(p);
        float progress = Math.min(1f, (float) sec / interval);
        String time = getTimeLeftFormatted(p);
        String name = getConfig().getString("bossbar.format", "&a⌚ AFK-зона &7| До награды: &e%time%")
            .replace("%time%", time);

        BossBar bar = playerBossBars.computeIfAbsent(p.getUniqueId(), u -> {
            BossBar b = BossBar.bossBar(LEGACY.deserialize(name), progress, BossBar.Color.GREEN, BossBar.Overlay.NOTCHED_20);
            p.showBossBar(b);
            return b;
        });
        bar.name(LEGACY.deserialize(name));
        bar.progress(progress);
    }

    private void hideBossBar(Player p) {
        BossBar bar = playerBossBars.remove(p.getUniqueId());
        if (bar != null) p.hideBossBar(bar);
    }

    private int getIntervalSeconds(Player p) {
        for (String[] tier : TIERS) {
            if (p.hasPermission(tier[0])) {
                return Integer.parseInt(tier[1]);
            }
        }
        return 1200; // default 20 мин
    }

    private void giveReward(Player p) {
        String cmd = getConfig().getString("reward-command", "afk give %player% 1")
            .replace("%player%", p.getName());
        Bukkit.dispatchCommand(Bukkit.getConsoleSender(), cmd);
        String title = getConfig().getString("reward-title", "&aВы получили &f1 AFK-монету&a!");
        if (!title.isEmpty()) {
            p.sendMessage(net.kyori.adventure.text.serializer.legacy.LegacyComponentSerializer.legacyAmpersand().deserialize(title));
        }
    }
}
