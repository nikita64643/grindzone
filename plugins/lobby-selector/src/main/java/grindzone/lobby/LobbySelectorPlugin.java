package grindzone.lobby;

import org.bukkit.Bukkit;
import org.bukkit.ChatColor;
import org.bukkit.Location;
import org.bukkit.Material;
import org.bukkit.command.Command;
import org.bukkit.command.CommandExecutor;
import org.bukkit.command.CommandSender;
import org.bukkit.NamespacedKey;
import org.bukkit.World;
import org.bukkit.entity.Player;
import org.bukkit.entity.Villager;
import org.bukkit.event.EventHandler;
import org.bukkit.event.Listener;
import org.bukkit.event.block.Action;
import org.bukkit.event.inventory.InventoryClickEvent;
import org.bukkit.event.player.PlayerDropItemEvent;
import org.bukkit.event.player.PlayerInteractEvent;
import org.bukkit.event.player.PlayerInteractEntityEvent;
import org.bukkit.event.player.PlayerJoinEvent;
import org.bukkit.event.player.PlayerPortalEvent;
import org.bukkit.inventory.Inventory;
import org.bukkit.inventory.ItemStack;
import org.bukkit.inventory.meta.ItemMeta;
import org.bukkit.event.player.PlayerTeleportEvent.TeleportCause;
import org.bukkit.persistence.PersistentDataType;
import org.bukkit.plugin.java.JavaPlugin;

import net.kyori.adventure.text.Component;
import net.kyori.adventure.text.format.NamedTextColor;
import net.kyori.adventure.text.format.TextDecoration;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.UUID;

public class LobbySelectorPlugin extends JavaPlugin implements Listener, CommandExecutor {

    private static final String MENU_TITLE = "Выбор сервера";
    private static final String MENU_VILLAGER_TAG = "lobby_menu";
    private static final double SPAWN_X = 1.5;
    private static final double SPAWN_Y = 7;
    private static final double SPAWN_Z = 1.5;

    private static final long PORTAL_COOLDOWN_MS = 5000;

    private final Map<UUID, String> pendingServer = new HashMap<>();
    private final Map<UUID, Long> portalLastTrigger = new HashMap<>();
    private NamespacedKey menuKey;

    @Override
    public void onEnable() {
        menuKey = new NamespacedKey(this, "menu_villager");
        getServer().getPluginManager().registerEvents(this, this);
        getCommand("cleanvillagers").setExecutor(this);
        Bukkit.getScheduler().runTaskLater(this, () -> spawnMenuVillager(false), 40L);
        Bukkit.getScheduler().runTaskLater(this, () -> spawnMenuVillager(false), 200L);
        Bukkit.getScheduler().runTaskLater(this, () -> spawnMenuVillager(false), 400L);
    }

    @Override
    public boolean onCommand(CommandSender sender, Command command, String label, String[] args) {
        if ("cleanvillagers".equals(command.getName())) {
            spawnMenuVillager(true);
            sender.sendMessage(ChatColor.GREEN + "Жители очищены, оставлен один меню-житель.");
            return true;
        }
        return false;
    }

    @EventHandler
    public void onPlayerJoin(PlayerJoinEvent e) {
        if ("world_lobby".equals(e.getPlayer().getWorld().getName())) {
            Bukkit.getScheduler().runTaskLater(this, () -> spawnMenuVillager(false), 20L);
        }
    }

    private void spawnMenuVillager() {
        spawnMenuVillager(false);
    }

    private void spawnMenuVillager(boolean loadAllChunks) {
        World w = Bukkit.getWorld("world_lobby");
        if (w == null) return;
        int range = loadAllChunks ? 5 : 2;
        for (int cx = -range; cx <= range; cx++) {
            for (int cz = -range; cz <= range; cz++) {
                w.getChunkAt(cx, cz).load(true);
            }
        }
        Location loc = new Location(w, SPAWN_X, SPAWN_Y, SPAWN_Z);
        Location spawnPoint = w.getSpawnLocation();
        loc.setDirection(spawnPoint.toVector().subtract(loc.toVector()));
        List<Villager> all = new ArrayList<>(w.getEntitiesByClass(Villager.class));
        Villager keep = null;
        for (Villager v : all) {
            if (keep == null && isMenuVillager(v)) {
                keep = v;
            } else {
                v.remove();
            }
        }
        if (keep != null) {
            keep.teleport(loc);
            keep.setCustomNameVisible(false);
            keep.customName(Component.empty());
            return;
        }
        w.spawn(loc, Villager.class, vill -> {
            vill.setAI(false);
            vill.setInvulnerable(true);
            vill.setSilent(true);
            vill.setPersistent(true);
            vill.setCustomNameVisible(false);
            vill.customName(Component.empty());
            vill.getPersistentDataContainer().set(menuKey, PersistentDataType.STRING, MENU_VILLAGER_TAG);
        });
    }

    private boolean isMenuVillager(Villager v) {
        return MENU_VILLAGER_TAG.equals(v.getPersistentDataContainer().get(menuKey, PersistentDataType.STRING));
    }

    private boolean isMenuCompass(ItemStack item) {
        if (item == null || item.getType() != Material.COMPASS) return false;
        ItemMeta meta = item.getItemMeta();
        if (meta == null || !meta.hasDisplayName()) return false;
        String name = net.kyori.adventure.text.serializer.plain.PlainTextComponentSerializer.plainText().serialize(meta.displayName());
        return name.contains("МЕНЮ") || name.toLowerCase().contains("menu");
    }

    @EventHandler
    public void onPortalEnter(PlayerPortalEvent e) {
        if (!"world_lobby".equals(e.getFrom().getWorld().getName())) return;

        Player p = e.getPlayer();

        // End portal → телепорт на 0 7 7
        if (e.getCause() == TeleportCause.END_PORTAL || e.getCause() == TeleportCause.END_GATEWAY) {
            e.setCancelled(true);
            Location dest = new Location(p.getWorld(), 0.5, 7, 7.5, p.getLocation().getYaw(), p.getLocation().getPitch());
            Bukkit.getScheduler().runTaskLater(this, () -> p.teleport(dest), 2L);
            return;
        }

        // Nether portal → меню режимов
        if (e.getCause() != TeleportCause.NETHER_PORTAL) return;
        long now = System.currentTimeMillis();
        if (portalLastTrigger.getOrDefault(p.getUniqueId(), 0L) + PORTAL_COOLDOWN_MS > now) return;
        portalLastTrigger.put(p.getUniqueId(), now);
        e.setCancelled(true);
        Location exit = new Location(p.getWorld(), 0.5, 7, 8, p.getLocation().getYaw(), p.getLocation().getPitch());
        Bukkit.getScheduler().runTaskLater(this, () -> {
            p.teleport(exit);
            p.performCommand("menu");
        }, 2L);
    }

    @EventHandler
    public void onCompassDrop(PlayerDropItemEvent e) {
        if (isMenuCompass(e.getItemDrop().getItemStack())) e.setCancelled(true);
    }

    @EventHandler
    public void onCompassUse(PlayerInteractEvent e) {
        if (e.getAction() != Action.RIGHT_CLICK_AIR && e.getAction() != Action.RIGHT_CLICK_BLOCK) return;
        ItemStack item = e.getItem();
        if (!isMenuCompass(item)) return;
        e.setCancelled(true);
        e.getPlayer().performCommand("menu");
    }

    @EventHandler
    public void onVillagerClick(PlayerInteractEntityEvent e) {
        if (!(e.getRightClicked() instanceof Villager)) return;
        Villager v = (Villager) e.getRightClicked();
        Player p = e.getPlayer();
        if (isMenuVillager(v)) {
            e.setCancelled(true);
            p.performCommand("menu");
            return;
        }
        String name = v.customName() != null
                ? net.kyori.adventure.text.serializer.plain.PlainTextComponentSerializer.plainText().serialize(v.customName())
                : "";
        if (name.contains("1.7.10") || name.contains("1,7,10")) {
            e.setCancelled(true);
            openServerMenu(p, "sandbox17", "1.7.10");
        } else if (name.contains("1.21.10") || name.contains("1,21,10")) {
            e.setCancelled(true);
            openServerMenu(p, "sandbox121", "1.21.10");
        }
    }

    private void openServerMenu(Player p, String serverId, String serverName) {
        Inventory inv = Bukkit.createInventory(null, 9, Component.text(MENU_TITLE + " - " + serverName));
        ItemStack survival = new ItemStack(Material.GRASS_BLOCK);
        ItemMeta survivalMeta = survival.getItemMeta();
        survivalMeta.displayName(Component.text("Выживание", NamedTextColor.GREEN));
        survival.setItemMeta(survivalMeta);
        inv.setItem(4, survival);
        pendingServer.put(p.getUniqueId(), serverId);
        p.openInventory(inv);
    }

    @EventHandler
    public void onMenuClick(InventoryClickEvent e) {
        if (!(e.getWhoClicked() instanceof Player)) return;
        String title = net.kyori.adventure.text.serializer.plain.PlainTextComponentSerializer.plainText().serialize(e.getView().title());
        if (title.contains(MENU_TITLE)) {
            e.setCancelled(true);
            Player p = (Player) e.getWhoClicked();
            String server = pendingServer.remove(p.getUniqueId());
            if (server != null && e.getCurrentItem() != null && !e.getCurrentItem().getType().isAir()) {
                p.closeInventory();
                p.performCommand("server " + server);
            }
        }
    }
}
