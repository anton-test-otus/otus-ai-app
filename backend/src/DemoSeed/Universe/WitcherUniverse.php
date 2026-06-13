<?php

namespace App\DemoSeed\Universe;

use App\DemoSeed\DemoNoteDefinition;
use App\DemoSeed\DemoUniverseDefinition;
use App\DemoSeed\DemoVersionDefinition;

final class WitcherUniverse
{
    public static function definition(): DemoUniverseDefinition
    {
        return new DemoUniverseDefinition(
            email: 'witcher@demo.local',
            roles: ['ROLE_USER'],
            folders: [
                'Ведьмаки',
                'Чудовища',
                'Локации/Королевства',
                'Алхимия и знаки',
                'Квесты',
            ],
            tags: [
                'ведьмак',
                'чудовище',
                'локация',
                'квест',
                'алхимия',
                'знак',
                'контракт',
                'монстрология',
                'ведьмаки',
                'легенда',
            ],
            notes: [
                new DemoNoteDefinition(
                    key: 'bestiary',
                    title: 'Записи бестиария',
                    tags: ['монстрология', 'ведьмаки', 'легенда'],
                    isFavorite: true,
                    updatedAtOffset: '-2 hours',
                    content: <<<'MD'
## Бестиарий

Сводка чудовищ и контрактов. {{link:geralt|Геральт}}, {{link:vesemir|Весемир}}, {{link:velen|Велен}}, {{link:novigrad|Новигруд}}.

- **Чудовища:** {{link:griffin}}, {{link:striga}}, {{link:leshen}}, {{link:drowner}}
- **Люди:** {{link:yennefer|Еннифер}}, {{link:triss|Трисс}}, {{link:ciri|Цири}}
- **Квесты:** {{link:griffin-hunt}}, {{link:family-affairs}}, {{link:baron-quest}}

> Контракт — закон.

См. {{link:law-of-surprise|право неожиданности}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Бестиарий', 'Оглавление разделов.', '-14 days'),
                        new DemoVersionDefinition('Записи бестиария', <<<'MD'
## Черти

Добавлены типы чудовищ.
MD,
                            '-8 days'),
                        new DemoVersionDefinition('Записи бестиария', <<<'MD'
## Полный бестиарий

Ссылки на квесты и знаки.
MD,
                            '-4 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'law-of-surprise',
                    title: 'Закон неожиданности',
                    tags: ['легенда', 'ведьмак'],
                    isFavorite: true,
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Право неожиданности

Если ведьмак спасает жизнь, может потребовать «то, что уже есть, но не ожидается». Связано с {{link:ciri|Цири}} и {{link:geralt|Геральт}}.

См. {{link:bestiary|бестиарий}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Закон', 'Краткое определение.', '-12 days'),
                        new DemoVersionDefinition('Закон неожиданности', <<<'MD'
## Примеры

Связь с Цири.
MD,
                            '-7 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'signs-table',
                    title: 'Знаки и эффекты',
                    folderPath: 'Алхимия и знаки',
                    tags: ['знак', 'алхимия'],
                    updatedAtOffset: '-11 days',
                    content: <<<'MD'
## Знаки и эффекты

- {{link:cat-sign}} — ускорение
- {{link:quen-sign}} — щит
- {{link:aard-sign}} — толчок
- {{link:igni-sign}} — огонь

Подробнее — {{link:alchemy|алхимия}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'contract-template',
                    title: 'Шаблон контракта',
                    tags: ['контракт', 'ведьмак'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Шаблон

```text
Заказчик: ___
Цель: ___
Награда: ___
Срок: ___
Подпись ведьмака: ___
```

Использует Геральт в Велен.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'geralt',
                    title: 'Geralt of Rivia',
                    folderPath: 'Ведьмаки',
                    tags: ['ведьмак', 'ведьмаки'],
                    isFavorite: true,
                    updatedAtOffset: '-3 days',
                    content: <<<'MD'
## Геральт

Ведьмак из {{link:kaer-morhen|Каэр Морхен}}. Знак {{link:cat-sign|Кошачий}}. Связь с {{link:ciri|Цири}} и {{link:yennefer|Еннифер}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Geralt', 'Контрактник.', '-13 days'),
                        new DemoVersionDefinition('Geralt of Rivia', <<<'MD'
## Путь

Квесты и знаки.
MD,
                            '-7 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'yennefer',
                    title: 'Yennefer',
                    folderPath: 'Ведьмаки',
                    tags: ['ведьмак', 'легенда'],
                    isFavorite: true,
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Еннифер

Чародейка. Связь с {{link:geralt|Геральт}} и {{link:ciri|Цири}}. {{link:novigrad|Новигруд}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Yennefer', 'Чародейка, знакомая Геральту.', '-12 days'),
                        new DemoVersionDefinition('Еннифер', <<<'MD'
## Чародейка

Союзница {{link:geralt|Геральт}}. Живёт в {{link:novigrad|Новигруде}}.
MD,
                            '-7 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'triss',
                    title: 'Triss Merigold',
                    folderPath: 'Ведьмаки',
                    tags: ['ведьмак', 'легенда'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Трисс

Чародейка. Союзница {{link:geralt|Геральт}} в {{link:novigrad|Новигруд}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'ciri',
                    title: 'Ciri',
                    folderPath: 'Ведьмаки',
                    tags: ['легенда', 'ведьмаки'],
                    isFavorite: true,
                    updatedAtOffset: '-3 days',
                    content: <<<'MD'
## Цири

Пепельные волосы. {{link:law-of-surprise|Закон неожиданности}}. Путь с {{link:geralt|Геральт}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Ciri', 'Девочка из Cintra. Закон неожиданности.', '-11 days'),
                        new DemoVersionDefinition('Цири', <<<'MD'
## Наследница

Связана с {{link:geralt|Геральт}} через закон неожиданности.
MD,
                            '-6 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'vesemir',
                    title: 'Vesemir',
                    folderPath: 'Ведьмаки',
                    tags: ['ведьмак', 'ведьмаки'],
                    isFavorite: true,
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Весемир

Старший ведьмак {{link:kaer-morhen|Каэр Морхен}}. Наставник {{link:geralt|Геральт}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Vesemir', 'Старейший ведьмак крепости.', '-14 days'),
                        new DemoVersionDefinition('Весемир', <<<'MD'
## Наставник

Живёт в {{link:kaer-morhen|Каэр Морхен}}. Учит молодых ведьмаков.
MD,
                            '-9 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'lambert',
                    title: 'Lambert',
                    folderPath: 'Ведьмаки',
                    tags: ['ведьмак', 'ведьмаки'],
                    updatedAtOffset: '-12 days',
                    content: <<<'MD'
## Lambert

Ведьмак. Товарищ {{link:geralt|Геральт}} из {{link:kaer-morhen|Каэр Морхен}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'eskell',
                    title: 'Eskel',
                    folderPath: 'Ведьмаки',
                    tags: ['ведьмак', 'ведьмаки'],
                    updatedAtOffset: '-13 days',
                    content: <<<'MD'
## Eskel

Ведьмак. {{link:kaer-morhen|Каэр Морхен}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'griffin',
                    title: 'Griffin',
                    folderPath: 'Чудовища',
                    tags: ['чудовище', 'контракт'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Грифон

Контракт в Белом Саду. Квест {{link:griffin-hunt|охота}}. {{link:geralt|Геральт}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'striga',
                    title: 'Striga',
                    folderPath: 'Чудовища',
                    tags: ['чудовище', 'контракт', 'легенда'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Стрига

Проклятие в {{link:velen|Велен}}. Ранний контракт {{link:geralt|Геральт}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'leshen',
                    title: 'Leshen',
                    folderPath: 'Чудовища',
                    tags: ['чудовище', 'легенда'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Леший

Лесной дух. {{link:velen|Велен}}. Опасен для деревень.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'werewolf',
                    title: 'Werewolf',
                    folderPath: 'Чудовища',
                    tags: ['чудовище'],
                    updatedAtOffset: '-11 days',
                    content: <<<'MD'
## Оборотень

Встреча в Велен. Слабость к серебру.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'drowner',
                    title: 'Drowner',
                    folderPath: 'Чудовища',
                    tags: ['чудовище', 'монстрология'],
                    updatedAtOffset: '-14 days',
                    content: <<<'MD'
## Утопец

У воды в Велен и Новигруд.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'ghoul',
                    title: 'Ghoul',
                    folderPath: 'Чудовища',
                    tags: ['чудовище'],
                    updatedAtOffset: '-15 days',
                    content: <<<'MD'
## Упырь

Кладбища Велен. Слаб к огню.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'novigrad',
                    title: 'Novigrad',
                    folderPath: 'Локации/Королевства',
                    tags: ['локация', 'ведьмаки'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Новигруд

Город. {{link:yennefer|Еннифер}}, {{link:triss|Трисс}}, {{link:dandelion|Лютик}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'velen',
                    title: 'Velen',
                    folderPath: 'Локации/Королевства',
                    tags: ['локация', 'квест'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Велен

Земля никому не принадлежащая. {{link:baron-quest|Кровавый барон}}. Много стриг и леших.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'skellige',
                    title: 'Skellige',
                    folderPath: 'Локации/Королевства',
                    tags: ['локация', 'легенда'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Скеллиге

Острова. Квесты и монстры моря.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'kaer-morhen',
                    title: 'Kaer Morhen',
                    folderPath: 'Локации/Королевства',
                    tags: ['локация', 'ведьмаки'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Каэр Морхен

Крепость ведьмаков. {{link:vesemir|Весемир}} и {{link:geralt|Геральт}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'white-orchard',
                    title: 'White Orchard',
                    folderPath: 'Локации/Королевства',
                    tags: ['локация', 'квест'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Белый Сад

Начало пути Геральт. {{link:griffin|Грифон}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'cat-sign',
                    title: 'Cat Sign',
                    folderPath: 'Алхимия и знаки',
                    tags: ['знак', 'алхимия'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Кошачий

Знак скорости. Геральт использует часто.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'quen-sign',
                    title: 'Quen Sign',
                    folderPath: 'Алхимия и знаки',
                    tags: ['знак', 'алхимия'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Кваен

Защитный знак. Геральт.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'aard-sign',
                    title: 'Aard Sign',
                    folderPath: 'Алхимия и знаки',
                    tags: ['знак'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Аард

Толчок. Против утопцев.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'igni-sign',
                    title: 'Igni Sign',
                    folderPath: 'Алхимия и знаки',
                    tags: ['знак', 'алхимия'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Игни

Огненный знак. Против упырей.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'alchemy',
                    title: 'Алхимия',
                    folderPath: 'Алхимия и знаки',
                    tags: ['алхимия', 'ведьмак'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Алхимия

Масла и зелья. Ласточка. Знаки.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'swallow-potion',
                    title: 'Swallow Potion',
                    folderPath: 'Алхимия и знаки',
                    tags: ['алхимия'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Ласточка

Зелье регенерации. Алхимия.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'griffin-hunt',
                    title: 'Охота на грифона',
                    folderPath: 'Квесты',
                    tags: ['квест', 'чудовище', 'контракт'],
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Охота

Первый крупный контракт {{link:geralt|Геральт}} у Белого Сада. Цель — {{link:griffin|грифон}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'family-affairs',
                    title: 'Семейное дело',
                    folderPath: 'Квесты',
                    tags: ['квест', 'легенда'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Семья

Квест в {{link:velen|Велен}}. Связь с {{link:baron-quest|бароном}} и {{link:ciri|Цири}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'baron-quest',
                    title: 'Bloody Baron',
                    folderPath: 'Квесты',
                    tags: ['квест', 'локация'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Кровавый барон

Центр {{link:velen|Велен}}. {{link:family-affairs|семейное дело}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'dandelion',
                    title: 'Dandelion',
                    tags: ['легенда', 'ведьмаки'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Лютик

Бард. Друг {{link:geralt|Геральт}} в {{link:novigrad|Новигруд}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'emhyr',
                    title: 'Emhyr',
                    tags: ['легенда'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Эмгыр

Император Нильфгаарда. Связь с Цири.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'regis',
                    title: 'Regis',
                    tags: ['легенда', 'чудовище'],
                    updatedAtOffset: '-11 days',
                    content: <<<'MD'
## Regis

Вампир. Помогает {{link:geralt|Геральт}} в пути.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'troll',
                    title: 'Troll',
                    tags: ['чудовище'],
                    updatedAtOffset: '-16 days',
                    content: <<<'MD'
## Тролль

Под мостом в Велен. Комический персонаж.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'werewolf-hunt',
                    title: 'Охота на оборотня',
                    folderPath: 'Квесты',
                    tags: ['квест', 'чудовище', 'контракт'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Контракт

{{link:geralt|Геральт}} и {{link:werewolf|оборотень}} в {{link:velen|Велен}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'ciri-quest',
                    title: 'Поиск Ciri',
                    folderPath: 'Квесты',
                    tags: ['квест', 'легенда'],
                    updatedAtOffset: '-3 days',
                    content: <<<'MD'
## Поиск

{{link:geralt|Геральт}} ищет {{link:ciri|Цири}} через {{link:velen|Велен}} и {{link:novigrad|Новигруд}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'trial-mountains',
                    title: 'Испытание гор',
                    tags: ['ведьмаки', 'легенда'],
                    updatedAtOffset: '-13 days',
                    content: <<<'MD'
## Горы

Путь юного {{link:geralt|Геральт}} из {{link:kaer-morhen|Каэр Морхен}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'wolf-school',
                    title: 'Школа Волка',
                    tags: ['ведьмаки', 'легенда'],
                    updatedAtOffset: '-12 days',
                    content: <<<'MD'
## Школа

{{link:kaer-morhen|Каэр Морхен}}. {{link:vesemir|Весемир}}.
MD,
                ),
            ],
        );
    }
}
