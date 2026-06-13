<?php

namespace App\DemoSeed\Universe;

use App\DemoSeed\DemoNoteDefinition;
use App\DemoSeed\DemoUniverseDefinition;
use App\DemoSeed\DemoVersionDefinition;

final class WesterosUniverse
{
    public static function definition(): DemoUniverseDefinition
    {
        return new DemoUniverseDefinition(
            email: 'westeros@demo.local',
            roles: ['ROLE_USER'],
            folders: [
                'Дома/Север',
                'Дома/Юг',
                'Персонажи',
                'Локации',
                'Войны',
                'Интриги',
            ],
            tags: [
                'дом',
                'персонаж',
                'локация',
                'война',
                'интрига',
                'север',
                'юг',
                'драконы',
                'ночной-дозор',
                'совет',
            ],
            notes: [
                new DemoNoteDefinition(
                    key: 'seal',
                    title: 'Печать: зима близка',
                    tags: ['дом', 'север', 'совет'],
                    isFavorite: true,
                    updatedAtOffset: '-2 hours',
                    content: <<<'MD'
## Обзор

Сводная карта. {{link:winterfell|Винтерфелл}} и {{link:kings-landing|столица}}. {{link:stark|Старки}} и {{link:lannister|Ланнистеры}} у {{link:iron-throne|Железного трона}}.

- **Дома:** {{link:stark}}, {{link:bolton}}, {{link:tyrell}}, {{link:martell}}
- **Люди:** {{link:ned|Нед}}, {{link:daenerys|Дейнерис}}, {{link:tyrion|Тирион}}, {{link:jon|Джон}}
- **События:** {{link:war-five-kings}}, {{link:red-wedding}}, {{link:long-night}}

> «Зима близка» — призыв готовиться.

См. {{link:maester-council|совет мейстера}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Черновик', 'Список домов.', '-14 days'),
                        new DemoVersionDefinition('Печать', <<<'MD'
## Дома

Старки и Ланнистеры.
MD,
                            '-8 days'),
                        new DemoVersionDefinition('Печать: зима близка', <<<'MD'
## Обзор

Добавлены войны.
MD,
                            '-4 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'maester-council',
                    title: 'Совет мейстера',
                    tags: ['совет', 'интрига'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Совет

Решения двора {{link:kings-landing|Королевская Гавань}}. {{link:tyrion|Тирион}} и {{link:baelish|Мизинец}} спорят; {{link:cersei|Серсея}} у {{link:iron-throne|трона}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'houses-table',
                    title: 'Дома и девизы',
                    folderPath: 'Дома/Север',
                    tags: ['дом', 'север', 'юг'],
                    updatedAtOffset: '-12 days',
                    content: <<<'MD'
## Дома и девизы

- {{link:stark|Старки}} — «Зима близка», Север
- {{link:lannister|Ланнистеры}} — «Слушай мой рёв», Запад
- {{link:tyrell|Тиреллы}} — «Растём сильнее», Юг
- {{link:martell|Мартеллы}} — «Несгибаемые», Дорн
MD,
                ),
                new DemoNoteDefinition(
                    key: 'nights-oath',
                    title: 'Клятва ночной стражи',
                    tags: ['ночной-дозор', 'север'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Клятва

```text
Ночь сгустилась, и теперь моя вахта началась.
Я не возьму жену, не получу землю, не оставлю наследника.
Буду носить чёрное до конца моих дней...
```

У {{link:jon|Джон}} на {{link:wall|Стене}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'stark',
                    title: 'Дом Старков',
                    folderPath: 'Дома/Север',
                    tags: ['дом', 'север'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Старки

Древний дом {{link:winterfell|Винтерфелл}}. Глава — {{link:ned|Нед}}; дети: {{link:arya|Арья}}, {{link:sansa|Санса}}, {{link:jon|Джон}}.

Союзники — {{link:mormont|Мормонты}}. Враги — {{link:bolton|Болтоны}} после {{link:red-wedding|Красной свадьбы}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'bolton',
                    title: 'Дом Болтоны',
                    folderPath: 'Дома/Север',
                    tags: ['дом', 'север', 'интрига'],
                    updatedAtOffset: '-11 days',
                    content: <<<'MD'
## Болтоны

Дом, известный жестокостью. Конфликт со {{link:stark|Старки}} ведёт к {{link:red-wedding|Красной свадьбе}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'mormont',
                    title: 'Дом Мормонты',
                    folderPath: 'Дома/Север',
                    tags: ['дом', 'север'],
                    updatedAtOffset: '-13 days',
                    content: <<<'MD'
## Мормонты

Верные союзники {{link:stark|Старки}}. Поддержали {{link:jon|Джон}} на {{link:wall|Стена}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'lannister',
                    title: 'Дом Ланнистеры',
                    folderPath: 'Дома/Юг',
                    tags: ['дом', 'юг', 'интрига'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Ланнистеры

Богатейший дом. {{link:tywin|Тайвин}} — глава; дети: {{link:cersei|Серсея}}, {{link:jaime|Джейм}}, {{link:tyrion|Тирион}}.

Держат {{link:iron-throne|трон}} через {{link:joffrey|Джоффри}}. Центр {{link:war-five-kings|войны}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'tyrell',
                    title: 'Дом Тиреллы',
                    folderPath: 'Дома/Юг',
                    tags: ['дом', 'юг'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Тиреллы

Богатые земли юга. Союзы через {{link:margaery|Маргери}} и {{link:purple-wedding|фиолетовую свадьбу}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'martell',
                    title: 'Дом Мартеллы',
                    folderPath: 'Дома/Юг',
                    tags: ['дом', 'юг', 'интрига'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Мартеллы

Дорн. Связь с {{link:daenerys|Дейнерис}} и {{link:robert-rebellion|восстанием}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'baratheon',
                    title: 'Дом Баратеонов',
                    folderPath: 'Дома/Юг',
                    tags: ['дом', 'война'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Баратеоны

После {{link:robert-rebellion|восстания}} — короли. Ветви: {{link:stannis|Станнис}}, {{link:renly|Renly}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'ned',
                    title: 'Ned Stark',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'север'],
                    isFavorite: true,
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Нед

Lord {{link:winterfell|Винтерфелл}}. Честь и долг. Жертва интриг в {{link:kings-landing|Королевская Гавань}}.

Семья: {{link:arya|Арья}}, {{link:sansa|Санса}}, {{link:jon|Джон}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Ned', 'Lord Севера.', '-12 days'),
                        new DemoVersionDefinition('Ned Stark', 'Hand короля — расследование.', '-7 days'),
                        new DemoVersionDefinition('Ned Stark', 'Трагический финал в столице.', '-5 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'arya',
                    title: 'Arya Stark',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'север', 'интрига'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Арья

Дочь {{link:ned|Нед}}. Путь мести после {{link:red-wedding|Красной свадьбы}}. Список имён.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'tyrion',
                    title: 'Tyrion Lannister',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'юг', 'совет'],
                    isFavorite: true,
                    updatedAtOffset: '-3 days',
                    content: <<<'MD'
## Тирион

«Я пью и знаю вещи». Сын {{link:tywin|Тайвин}}. Hand короля, затем советник {{link:daenerys|Дейнерис}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Tyrion', 'Карлик из дома Ланнистеров.', '-12 days'),
                        new DemoVersionDefinition('Тирион', <<<'MD'
## Hand короля

Сын {{link:tywin|Тайвина}}. Известен остроумием и книгами.
MD,
                            '-7 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'daenerys',
                    title: 'Daenerys Targaryen',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'драконы', 'война'],
                    isFavorite: true,
                    updatedAtOffset: '-2 days',
                    content: <<<'MD'
## Дейнерис

«Мать драконов». {{link:drogo|Drogo}}, затем {{link:dragons|драконы}} — путь к {{link:iron-throne|трону}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Daenerys', 'Изгнанная принцесса. Ищет трон.', '-10 days'),
                        new DemoVersionDefinition('Дейнерис', <<<'MD'
## Khaleesi

Жена {{link:drogo|Drogo}}. Пробуждает {{link:dragons|драконов}}.
MD,
                            '-5 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'jon',
                    title: 'Jon Snow',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'север', 'ночной-дозор'],
                    updatedAtOffset: '-3 days',
                    content: <<<'MD'
## Джон

Bastard {{link:stark|Старки}}. Командор {{link:wall|Стена}}. Против {{{link:white-walkers|белые ходоки}}} в {{link:long-night|Длинной ночи}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'cersei',
                    title: 'Cersei Lannister',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'юг', 'интрига'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Серсея

Regent. Конфликт с {{link:tyrion|Тирион}} и {{link:tyrell|Тиреллы}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'jaime',
                    title: 'Jaime Lannister',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'юг', 'война'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Джейм

Kingsguard. Брат {{link:cersei|Серсея}}. Путь с {{link:brienne|Brienne}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'sansa',
                    title: 'Sansa Stark',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'север', 'интрига'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Санса

Survivor интриг. Уроки от {{link:baelish|Мизинец}}. Защита {{link:winterfell|Винтерфелл}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'baelish',
                    title: 'Petyr Baelish',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'интрига'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Мизинец

Architect {{link:red-wedding|Красной свадьбы}}. Спор с {{link:tyrion|Тирион}} в {{link:maester-council|совете}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'tywin',
                    title: 'Tywin Lannister',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'юг', 'война'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Тайвин

Patriarch {{link:lannister|Ланнистеры}}. Стратег {{link:war-five-kings|войны}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'brienne',
                    title: 'Brienne of Tarth',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'юг'],
                    updatedAtOffset: '-14 days',
                    content: <<<'MD'
## Brienne

Warrior чести. С {{{{link:jaime|Jaime}}}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'hodor',
                    title: 'Hodor',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'север'],
                    updatedAtOffset: '-15 days',
                    content: <<<'MD'
## Hodor

Слуга {{link:stark|Старки}}. Судьба связана с {{link:arya|Арья}} и {{link:long-night|Длинной ночью}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'winterfell',
                    title: 'Winterfell',
                    folderPath: 'Локации',
                    tags: ['локация', 'север', 'дом'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Винтерфелл

Seat {{link:stark|Старки}}. События: {{link:red-wedding|последствия войны}}, возвращение {{link:jon|Джон}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'kings-landing',
                    title: 'Kings Landing',
                    folderPath: 'Локации',
                    tags: ['локация', 'юг', 'интрига'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Королевская Гавань

Столица. {{link:iron-throne|Трон}}. Интриги {{link:cersei|Серсея}}, {{link:tyrion|Тирион}}, {{link:ned|Нед}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'wall',
                    title: 'The Wall',
                    folderPath: 'Локации',
                    tags: ['локация', 'север', 'ночной-дозор'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Стена

Лёд и {{link:wildlings|дикари}}. {{link:jon|Джон}} и {{{link:white-walkers|белые ходоки}}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'dragonstone',
                    title: 'Dragonstone',
                    folderPath: 'Локации',
                    tags: ['локация', 'драконы'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Dragonstone

Base {{link:daenerys|Дейнерис}} с {{link:dragons|драконами}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'eyrie',
                    title: 'The Eyrie',
                    folderPath: 'Локации',
                    tags: ['локация', 'интрига'],
                    updatedAtOffset: '-12 days',
                    content: <<<'MD'
## Орлиное гнездо

Seat Arryn. {{{{link:baelish|Мизинец}}}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'braavos',
                    title: 'Braavos',
                    folderPath: 'Локации',
                    tags: ['локация', 'интрига'],
                    updatedAtOffset: '-13 days',
                    content: <<<'MD'
## Braavos

Free city. Путь {{link:arya|Арья}} после {{link:red-wedding|Красной свадьбы}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'war-five-kings',
                    title: 'Война Пяти королей',
                    folderPath: 'Войны',
                    tags: ['война', 'интрига', 'дом'],
                    isFavorite: true,
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Война

Борьба {{{{link:stannis|Stannis}}}}, {{{{link:renly|Renly}}}}, {{{{link:joffrey|Joffrey}}}}, {{{{link:robb|Robb}}}}, {{{{link:balon|Balon}}}}. Перелом — {{{{link:red-wedding|Красная свадьба}}}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Война', 'Список претендентов.', '-11 days'),
                        new DemoVersionDefinition('Война Пяти королей', 'Битвы и интриги.', '-6 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'long-night',
                    title: 'Long Night',
                    folderPath: 'Войны',
                    tags: ['война', 'ночной-дозор', 'север'],
                    updatedAtOffset: '-3 days',
                    content: <<<'MD'
## Длинная ночь

Битва с {{{link:white-walkers|белые ходоки}}} у {{link:wall|Стена}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'robert-rebellion',
                    title: 'Robert Rebellion',
                    folderPath: 'Войны',
                    tags: ['война', 'дом'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Восстание

Сverжение Mad King. {{link:ned|Нед}} и Robert. Exile {{link:daenerys|Дейнерис}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'red-wedding',
                    title: 'Red Wedding',
                    folderPath: 'Интриги',
                    tags: ['интрига', 'война', 'север'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Красная свадьба

Massacre {{link:stark|Старки}}. {{link:bolton|Болтоны}}, {{link:baelish|Мизинец}}, {{link:tywin|Тайвин}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'purple-wedding',
                    title: 'Purple Wedding',
                    folderPath: 'Интриги',
                    tags: ['интрига', 'юг'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Фиолетовая свадьба

Poisoning {{link:joffrey|Джоффри}}. Обвинения на {{link:tyrion|Тирион}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'tower-joy',
                    title: 'Tower of Joy',
                    folderPath: 'Интриги',
                    tags: ['интрига', 'север'],
                    updatedAtOffset: '-14 days',
                    content: <<<'MD'
## Башня Радости

Secret о {{link:jon|Джон}}. Связь {{link:ned|Нед}} и Rhaegar.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'drogo',
                    title: 'Khal Drogo',
                    tags: ['персонаж', 'война'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Drogo

Первый муж {{link:daenerys|Дейнерис}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'dragons',
                    title: 'Dragons',
                    tags: ['драконы', 'война'],
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Драконы

Drogon, Rhaegal, Viserion — {{link:daenerys|Дейнерис}} в {{link:war-five-kings|войне}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'white-walkers',
                    title: 'White Walkers',
                    tags: ['север', 'война', 'ночной-дозор'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Белыеходоки

Enemy за {{link:wall|Стена}}. Цель {{link:long-night|Длинной ночи}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'wildlings',
                    title: 'Wildlings',
                    tags: ['север', 'ночной-дозор'],
                    updatedAtOffset: '-11 days',
                    content: <<<'MD'
## Дикари

За {{link:wall|Стена}}. {{link:jon|Джон}} объединил с Night Watch.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'iron-throne',
                    title: 'Iron Throne',
                    tags: ['локация', 'интрига', 'война'],
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Железный трон

Claimants: {{link:daenerys|Дейнерис}}, {{link:cersei|Серсея}}, {{link:stannis|Stannis}}, {{link:jon|Джон}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'stannis',
                    title: 'Stannis Baratheon',
                    tags: ['персонаж', 'война', 'драконы'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Stannis

Claimant. {{{{link:melisandre|Melisandre}}}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'melisandre',
                    title: 'Melisandre',
                    tags: ['персонаж', 'интрига', 'драконы'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Melisandre

Red Priestess. С {{{{link:stannis|Stannis}}}} и {{{{link:jon|Jon}}}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'joffrey',
                    title: 'Joffrey Baratheon',
                    tags: ['персонаж', 'интрига', 'юг'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Джоффри

Cruel king. Fall на {{link:purple-wedding|фиолетовой свадьбе}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'renly',
                    title: 'Renly Baratheon',
                    tags: ['персонаж', 'война'],
                    updatedAtOffset: '-13 days',
                    content: <<<'MD'
## Renly

Claimant. Союз с {{{{link:tyrell|Tyrell}}}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'robb',
                    title: 'Robb Stark',
                    tags: ['персонаж', 'север', 'война'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Робб

King in the North. Погиб на {{link:red-wedding|Красной свадьбе}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'balon',
                    title: 'Balon Greyjoy',
                    tags: ['персонаж', 'война'],
                    updatedAtOffset: '-14 days',
                    content: <<<'MD'
## Balon

Iron Islands claimant.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'margaery',
                    title: 'Margaery Tyrell',
                    tags: ['персонаж', 'юг', 'интрига'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Margaery

Queen. Брак с {{link:renly|Renly}} и {{link:joffrey|Джоффри}}.
MD,
                ),
            ],
        );
    }
}
