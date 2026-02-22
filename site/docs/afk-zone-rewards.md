# Валюта за AFK в зоне (раз в несколько минут, в зависимости от статуса)

**Установлено:** AxAFKZone на серверах 1.21 SandBox — одна зона, разные интервалы по группе:
- **Legend** (axafkzone.tier.legend): 5 мин, 1 монета
- **Premium** (axafkzone.tier.premium): 10 мин, 1 монета
- **VIP** (axafkzone.tier.vip): 15 мин, 1 монета
- **Обычные** (axafkzone.tier.default): 20 мин, 1 монета

**Интерфейс:**
- По центру снизу (actionbar): счётчик обратного отсчёта «До награды: HH:MM:SS»
- Сверху (bossbar): полоса с инфо о зоне и времени до награды
- Справа (scoreboard TAB): блок «AFK-зона» с текущим балансом монет

---

Телепорт при AFK отключён. Чтобы игроки получали валюту за нахождение в AFK-зоне с разным интервалом/суммой по статусу (обычный / VIP / Premium / Legend), нужен отдельный плагин.

## Что нужно

1. **Плагин** — один из двух:
   - **AFK Pool** (Spigot/Modrinth) — награда за AFK в регионе WorldGuard. Есть множители по правам (`afkpool.bonus`). **Уже установлен.** Нужен **WorldGuard**.
   - **AxAFKZone** (Paper Hangar) — свои зоны без WorldGuard, команды и права на зоны. Подходит для Paper 1.21.

2. **Экономика** — Vault + Essentials (уже есть), команда вида `eco give %p %m`.

3. **Зона** — либо регион WorldGuard с именем `afk`, либо зона в настройках AxAFKZone.

4. **Права по статусу** — в LuckPerms выдать, например, `afkpool.bonus` группам vip/premium/legend и в конфиге плагина задать множитель награды.

---

## Вариант A: AFK Pool + WorldGuard

1. Установить **WorldGuard** и **WorldGuardExtraFlags** (или только WorldGuard) для своей версии Paper.
2. Скачать **AFK Pool** (Spigot/Modrinth), совместимый с 1.21.
3. В игре создать регион WorldGuard с именем `afk` в нужном месте (команды WG: `//wand`, выделить область, `//region define afk`).
4. В конфиге AFK Pool задать:
   - `region-name: afk`
   - `command1-interval: 1200` (тики; 1200 ≈ 1 минута)
   - `command-1: eco give %p %m`
   - `command-1-enabled: true`
   - `min` / `max` — диапазон суммы за раз.
5. Для разных статусов: в плагине есть permission-based multiplier — выдать право `afkpool.bonus` группам vip/premium/legend и в конфиге указать множитель (см. описание плагина 2.1.0).

Ссылки: [Spigot AFK Pool](https://www.spigotmc.org/resources/afk-pool-lake-room-pit-rewards-reward-players-in-worldguard-regions-over-700-servers.108746/), [Modrinth AFK Pool](https://modrinth.com/plugin/afk-pool).

---

## Вариант B: AxAFKZone (без WorldGuard)

1. Скачать **AxAFKZone** с [Paper Hangar](https://hangar.papermc.io/Artillex-Studios/AxAFKZone) (Paper 1.20.2–1.21.11).
2. Положить JAR в `plugins/`, перезапустить сервер.
3. В конфиге AxAFKZone создать зону (формат см. в документации плагина), указать интервал награды и команду, например: `eco give {player} {amount}`.
4. Для разных статусов: использовать права доступа к зоне (per-zone permissions) и завести несколько зон с разным интервалом/наградой под группы vip, premium, legend — либо одну зону и проверять права в своей команде/скрипте, если плагин это позволяет.

---

## Итог

- **Телепорт при AFK отключён** во всех конфигах Essentials на серверах 1.21.
- Чтобы **выдавать валюту за AFK в определённом месте раз в несколько минут в зависимости от статуса**: установи один из плагинов выше, настрой зону/регион, интервал и команду `eco give`, затем настрой множители или разные зоны под VIP/Premium/Legend по документации выбранного плагина.
