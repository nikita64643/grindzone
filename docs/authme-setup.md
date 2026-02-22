# AuthMe: авторизация через лобби

Игрок подключается: **Velocity (25565) → Лобби (25580)**. В лобби обязан `/register` или `/login`. После входа может `/server sandbox165` или `/server sandbox121`.

Авторизация **одна** для всей сети, сессия и учётка общие (общая MySQL).

## Установка

### 1. База данных

Создайте БД для AuthMe (или используйте уже существующую):

```sql
CREATE DATABASE authme;
```

Данные для подключения берутся из `site/.env`:
- `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`
- либо `AUTHME_DB_*` (если заданы — имеют приоритет)

Имя БД по умолчанию: **authme** (если не задано `AUTHME_DB_DATABASE`).

### 2. Запуск скрипта

```powershell
.\install-authme-lobby.ps1
```

Скрипт:
1. Ставит AuthMe на **лобби**, **1.16.5 SandBox**, **1.21.10 SandBox**
2. Ставит AuthMeVelocity на **Velocity** и все бэкенды
3. Запускает все серверы
4. Ждёт 30 сек, патчит AuthMe (MySQL, сессии, Velocity)
5. Создаёт конфиг AuthMeVelocity (auth-servers = lobby)
6. Перезапускает серверы

## Команды

- `/register <пароль> <подтверждение>` — регистрация
- `/login <пароль>` — вход

## Затемнение экрана и заставка при авторизации

- **applyBlindEffect: true** — неавторизованные видят тёмный экран (слепота)
- **teleportUnAuthedToSpawn: true** — телепорт на спавн
- **messageInterval: 3** — каждые 3 сек в чат приходит напоминание: «Регистрация: /reg ...» или «Авторизация: /login ...»

## Поведение

1. Игрок подключается к Velocity → попадает в лобби.
2. AuthMeVelocity блокирует чат, команды и `/server` до авторизации.
3. Игрок выполняет `/login` или `/register` в лобби.
4. После успешного входа можно использовать `/server sandbox165` или `/server sandbox121`.
5. Пароль сервера **не связан** с паролем сайта.

## Сессии

- `sessionExpireOnIpChange: false` — при входе с нового IP сессия сохраняется.
- `sessions.enabled: true` — сессии включены.

## Плагины

- **AuthMe** — лобби, 1.16.5 SandBox, 1.21.10 SandBox
- **AuthMeVelocity** — Velocity + все бэкенды
- Общая база MySQL для всех AuthMe
