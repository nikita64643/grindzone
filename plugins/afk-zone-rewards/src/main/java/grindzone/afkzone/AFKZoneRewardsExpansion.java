package grindzone.afkzone;

import me.clip.placeholderapi.expansion.PlaceholderExpansion;
import org.bukkit.OfflinePlayer;
import org.jetbrains.annotations.NotNull;

/**
 * PlaceholderAPI expansion для AFKZoneRewards.
 * %afkzonerewards_in_zone% — "true" / "false"
 * %afkzonerewards_time_left% — "M:SS" до награды
 */
public class AFKZoneRewardsExpansion extends PlaceholderExpansion {

    private final AFKZoneRewardsPlugin plugin;

    public AFKZoneRewardsExpansion(AFKZoneRewardsPlugin plugin) {
        this.plugin = plugin;
    }

    @Override
    @NotNull
    public String getAuthor() {
        return "GrindZone";
    }

    @Override
    @NotNull
    public String getIdentifier() {
        return "afkzonerewards";
    }

    @Override
    @NotNull
    public String getVersion() {
        return plugin.getDescription().getVersion();
    }

    @Override
    public boolean persist() {
        return true;
    }

    @Override
    public String onRequest(OfflinePlayer player, @NotNull String params) {
        if (player == null || !player.isOnline()) return "";
        var p = player.getPlayer();
        if (p == null) return "";

        if (params.equalsIgnoreCase("in_zone")) {
            return plugin.isInZone(p) ? "true" : "false";
        }
        if (params.equalsIgnoreCase("time_left")) {
            return plugin.getTimeLeftFormatted(p);
        }
        return null;
    }
}
