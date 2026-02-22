# Создание NPC-жителя для открытия меню в лобби
# Выполните ПОСЛЕ первого запуска лобби (когда Citizens загрузится)
# Требуется: быть OP в игре
#
# Примечание: в этом проекте NPC обычно хранится в `servers/lobby/plugins/Citizens/saves.yml`.
# Этот скрипт нужен только если вы хотите пересоздать NPC вручную.
#
# 1. Подключитесь к localhost:25565, получите OP: /op YourName
# 2. Подойдите к нужной точке (например 0 7 1)
# 3. Выполните:
#    /npc create "ВЫЖИВАНИЕ"
#    /npc type villager
#    /npc rename ""
#    /npc command add -p menu
#
# Теперь при клике откроется меню `/menu` (DeluxeMenus).

Write-Host "Инструкция по созданию NPC:"
Write-Host "1. /op YourName"
Write-Host "2. Встаньте в точке для NPC, выполните:"
Write-Host "   /npc create `"ВЫЖИВАНИЕ`""
Write-Host "   /npc type villager"
Write-Host "   /npc rename `"`""
Write-Host "   /npc command add -p menu"
