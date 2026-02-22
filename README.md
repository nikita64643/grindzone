# GrindZone: серверы Minecraft

Два сервера выживания:

| Версия   | Тип     | Папка                | Порт  |
|----------|---------|----------------------|-------|
| **1.16.5** | Paper   | `servers\1.16.5\SandBox` | 25566 (внутренний, через лобби) |
| **1.21.10** | Paper | `servers\1.21.10\SandBox` | 25570 |

## Требования

- **Windows**
- **1.16.5**: Java 8 (x64). `setup-servers.ps1` скачает Eclipse Temurin 8 в `tools\java8\current` (если нет).
- **1.21.10**: Java 21. `.\setup-java21.ps1` или системная Java 21 в PATH.

## Установка

### 1.16.5 (Paper)

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\setup-servers.ps1
```

### 1.21.10 (Vanilla)

```powershell
.\setup-java21.ps1
.\setup-servers-vanilla.ps1
```

### Paper + LuckPerms (1.21.10, опционально)

Для прав и групп (донат VIP/Premium/Legend):

```powershell
.\install-paper-luckperms.ps1 -Version "1.21.10"
```

## EULA

В каждой папке сервера в `eula.txt` выставить `eula=true`.

## Запуск

```powershell
# Один сервер
.\servers\1.16.5\SandBox\start.ps1 -Xms 1G -Xmx 2G
.\servers\1.21.10\SandBox\start.ps1 -Xms 1G -Xmx 2G

# Оба (через Node.js)
node scripts/start-servers.js
```

## Лобби (одна точка входа)

Для одной точки входа и будущих режимов:

```powershell
.\setup-velocity-lobby.ps1
# Установи eula=true в servers\lobby\eula.txt
node scripts/start-servers.js lobby
```

Подключение: **localhost:25565**. В лобби: `/server sandbox165` или `/server sandbox121`. Подробнее — `docs/lobby-setup.md`.
