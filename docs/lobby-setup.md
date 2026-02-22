# Лобби GrindZone

Одна точка входа для игроков. Позволяет потом добавить режимы.

## Схема

```
Игрок → localhost:25565 (Velocity) → Лобби (25580)
                                        ↓
                        /server sandbox165  или  /server sandbox121
                                        ↓
                        1.16.5 SandBox (25566)  1.21.10 SandBox (25570)
```

## Установка

```powershell
.\setup-velocity-lobby.ps1
```

Скрипт:
- Скачивает Velocity и Paper для лобби
- Создаёт `servers/proxy` и `servers/lobby`
- Настраивает бэкенды под Velocity (modern forwarding)

## Порты

| Сервис      | Порт  | Описание                    |
|-------------|-------|-----------------------------|
| Velocity    | 25565 | Единственный порт для игроков |
| Lobby       | 25580 | Внутренний                  |
| 1.16.5      | 25566 | Внутренний                  |
| 1.21.10     | 25570 | Внутренний                  |

## Запуск

```bash
node scripts/start-servers.js lobby
```

## Подключение

**localhost:25565** — игрок попадает в лобби.

В лобби:
- `/server sandbox165` — 1.16.5
- `/server sandbox121` — 1.21.10
- `/menu` — меню режимов (далее выбор сервера)
- `/servermenu` — меню выбора сервера (если нужно открыть напрямую)

## Датапак лобби (без мобов, всегда солнце)

Датапак `lobby_island` отключает мобов и фиксирует погоду. **После перезапуска** выполните в консоли лобби:
```
citizens reload
datapack enable "file/lobby_island"
```

## NPC для открытия меню

NPC хранится в `servers/lobby/plugins/Citizens/saves.yml`. Если нужно пересоздать вручную — см. `scripts/setup-lobby-npcs.ps1`.

## Перезапуск и переподключение

**Перезапуск только игровых серверов** (Velocity и лобби остаются):
```bash
node scripts/restart-game-servers.js
```
Игроки на sandbox165/sandbox121 увидят «Перезагрузка» и будут автоматически переподключены.

**Полный перезапуск** (включая Velocity):
```bash
node scripts/restart-all-servers.js lobby
```
Все игроки получат «Connection reset» — это ожидаемо, т.к. плагин переподключения работает на Velocity.

## Требования

- 1.21.10 должен быть на **Paper** (не Vanilla). Запусти `.\install-paper-luckperms.ps1` если ещё не ставил.
