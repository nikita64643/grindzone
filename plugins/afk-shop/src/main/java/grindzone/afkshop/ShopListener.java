package grindzone.afkshop;

import org.bukkit.entity.Player;
import org.bukkit.event.EventHandler;
import org.bukkit.event.Listener;
import org.bukkit.event.inventory.InventoryClickEvent;
import org.bukkit.inventory.ItemStack;

public class ShopListener implements Listener {

    private final AFKShopPlugin plugin;

    public ShopListener(AFKShopPlugin plugin) {
        this.plugin = plugin;
    }

    @EventHandler
    public void onClick(InventoryClickEvent e) {
        if (!(e.getWhoClicked() instanceof Player p)) return;
        if (!(e.getInventory().getHolder() instanceof AFKShopHolder)) return;

        e.setCancelled(true);

        if (plugin.getCurrency() == null) return;
        ItemStack clicked = e.getCurrentItem();
        if (clicked == null) return;

        plugin.onPurchaseClick(p, e.getRawSlot(), clicked);
    }

}
