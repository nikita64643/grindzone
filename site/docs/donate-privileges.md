# Привилегии доната и LuckPerms

## Как выдать привилегию

### 1. Через донат на сайте
Пользователь указывает **ник Minecraft** в настройках профиля, покупает привилегию с баланса — группа (vip/premium/legend) выдаётся на выбранный сервер по RCON автоматически.

### 2. В игре (команды LuckPerms)
Если у тебя есть право `luckperms.*` (выдаётся через `php artisan luckperms:grant-admin ник`):

- Выдать группу игроку на текущем сервере:
  ```
  /lp user <ник> parent add vip
  /lp user <ник> parent add premium
  /lp user <ник> parent add legend
  ```

- Посмотреть группы игрока:
  ```
  /lp user <ник> info
  ```

- Список всех групп:
  ```
  /lp group list
  ```

### 3. Через RCON (консоль или скрипт)
С сайта уже отправляется `lp user <ник> parent add <группа>` после оплаты. Вручную с консоли сервера можно выполнить ту же команду.

---

## Где смотреть функционал привилегий

### На сайте
- **Список и описание** привилегий задаются в `config/donate.php` → `privileges`: ключ (vip/premium/legend), `name`, `description`, `price`.
- Там же в `lp_permissions` перечислены **примеры прав** для каждой группы — по ним можно настроить группы в LuckPerms.

### В игре (LuckPerms)
- Фактический функционал дают **права (permissions)** у группы, а не сама группа.
- Создать группы и выдать права:
  ```
  /lp creategroup vip
  /lp creategroup premium
  /lp creategroup legend
  ```
  Затем для каждой группы задать права, например для VIP:
  ```
  /lp group vip permission set essentials.hat true
  /lp group vip permission set essentials.nick true
  ...
  ```
- Какие права нужны — зависит от плагинов на сервере (EssentialsX, GriefPrevention и т.д.). Примеры по привилегиям см. в `config/donate.php` → `lp_permissions`.

### Итог
- **Описание для игроков** — в `config/donate.php` (и на странице доната).
- **Реальный функционал в игре** — права у групп в LuckPerms; без нужных плагинов (EssentialsX, /home, защита участков и т.д.) права ничего не дадут, их нужно ставить под твой набор плагинов.
