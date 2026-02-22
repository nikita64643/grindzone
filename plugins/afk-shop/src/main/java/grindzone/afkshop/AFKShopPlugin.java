package grindzone.afkshop;

import org.bukkit.Bukkit;
import org.bukkit.Material;
import org.bukkit.entity.Player;
import org.bukkit.inventory.Inventory;
import org.bukkit.inventory.ItemStack;
import org.bukkit.inventory.meta.ItemMeta;
import org.bukkit.inventory.meta.PotionMeta;
import org.bukkit.plugin.java.JavaPlugin;
import org.bukkit.potion.PotionType;
import su.nightexpress.coinsengine.api.CoinsEngineAPI;
import su.nightexpress.coinsengine.api.currency.Currency;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

public class AFKShopPlugin extends JavaPlugin {

    private static final String CURRENCY_ID = "afk";
    private Currency currency;

    @Override
    public void onEnable() {
        if (!Bukkit.getPluginManager().isPluginEnabled("CoinsEngine")) {
            getLogger().warning("CoinsEngine не найден! Магазин AFK отключён.");
            return;
        }
        currency = CoinsEngineAPI.getCurrency(CURRENCY_ID);
        if (currency == null) {
            getLogger().warning("Валюта 'afk' не найдена в CoinsEngine!");
            return;
        }
        getServer().getPluginManager().registerEvents(new ShopListener(this), this);
        Bukkit.getPluginCommand("afkshop").setExecutor((sender, cmd, label, args) -> {
            if (!(sender instanceof Player p)) {
                sender.sendMessage("§cТолько для игроков!");
                return true;
            }
            openShop(p);
            return true;
        });
        getLogger().info("AFKShop включён. Команда: /afkshop");
    }

    private void openShop(Player p) {
        String title = getConfig().getString("title", "§e§lII §6§lAFK SHOP §e§lII");
        Inventory inv = Bukkit.createInventory(new AFKShopHolder(), 45, net.kyori.adventure.text.serializer.legacy.LegacyComponentSerializer.legacySection().deserialize(title));

        for (String key : getConfig().getConfigurationSection("items").getKeys(false)) {
            var section = getConfig().getConfigurationSection("items." + key);
            int slot = section.getInt("slot");
            Material mat = Material.valueOf(section.getString("material"));
            int amount = section.getInt("amount", 1);
            int price = section.getInt("price");
            String name = section.getString("name");

            ItemStack item = createShopItem(mat, amount, price, name, section);
            inv.setItem(slot, item);
        }

        p.openInventory(inv);
    }

    private ItemStack createShopItem(Material mat, int amount, int price, String name, org.bukkit.configuration.ConfigurationSection section) {
        ItemStack item = new ItemStack(mat, amount);

        if (mat == Material.POTION || mat == Material.SPLASH_POTION || mat == Material.LINGERING_POTION) {
            PotionMeta meta = (PotionMeta) item.getItemMeta();
            if (meta != null && section.contains("potion-type")) {
                meta.setBasePotionType(PotionType.valueOf(section.getString("potion-type")));
                item.setItemMeta(meta);
            }
        }

        ItemMeta meta = item.getItemMeta();
        if (meta != null) {
            meta.setDisplayName(name != null ? name.replace("&", "§") : mat.name().replace("_", " "));
            List<String> lore = new ArrayList<>();
            lore.add("§7Цена: §b" + price + " AFK-монет");
            lore.add("");
            lore.add("§eНажмите, чтобы купить!");
            meta.setLore(lore);
            item.setItemMeta(meta);
        }
        return item;
    }

    public void onPurchaseClick(Player p, int slot, ItemStack clicked) {
        if (clicked == null || clicked.getType().isAir()) return;

        String itemKey = findItemBySlot(slot);
        if (itemKey == null) return;

        var section = getConfig().getConfigurationSection("items." + itemKey);
        if (section == null) return;

        int price = section.getInt("price");
        double balance = CoinsEngineAPI.getBalance(p, currency);

        if (balance < price) {
            p.sendMessage("§cНедостаточно AFK-монет! Нужно: §b" + price + "§c, у вас: §b" + (int) balance);
            return;
        }

        Material mat = Material.valueOf(section.getString("material"));
        int amount = section.getInt("amount", 1);

        ItemStack give = new ItemStack(mat, amount);
        if (mat == Material.POTION && section.contains("potion-type")) {
            PotionMeta pm = (PotionMeta) give.getItemMeta();
            if (pm != null) pm.setBasePotionType(PotionType.valueOf(section.getString("potion-type")));
            give.setItemMeta(pm);
        }

        CoinsEngineAPI.removeBalance(p, currency, price);
        p.getInventory().addItem(give);
        p.sendMessage("§aКуплено: §f" + amount + "x " + mat.name().replace("_", " ") + " §7за §b" + price + " AFK-монет");
    }

    private String findItemBySlot(int slot) {
        for (String key : getConfig().getConfigurationSection("items").getKeys(false)) {
            if (getConfig().getInt("items." + key + ".slot") == slot) return key;
        }
        return null;
    }

    public Currency getCurrency() { return currency; }
}
