<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        News::updateOrCreate(
            ['slug' => 'zapusk-proekta'],
            [
                'title' => 'Запуск проекта GRINDZONE',
                'excerpt' => 'Добро пожаловать! Мы рады объявить о старте проекта GRINDZONE — игровых серверов нового уровня. Minecraft, Rust, CS:GO и RPG режимы уже ждут вас.',
                'body' => <<<'MD'
Мы рады представить **GRINDZONE** — проект игровых серверов, созданный для тех, кто любит качественный геймплей и честную игру.

## Что мы предлагаем

- **Minecraft** — выживание, RPG, магия, skyblock и многое другое
- **Rust** — выживание в жестоком мире
- **CS:GO** — классические режимы и кастомные карты
- **RPG** — уникальные режимы и сюжеты

## Присоединяйтесь

Серверы работают 24/7, администрация всегда на связи. Регистрируйтесь на сайте, пополняйте баланс и получайте преимущества — или играйте бесплатно и наслаждайтесь честным геймплеем.

Подробная инструкция по подключению — в разделе [Как начать игру](/howtoplay).

До встречи на серверах!
MD,
                'image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800',
                'published_at' => now(),
            ]
        );
    }
}
