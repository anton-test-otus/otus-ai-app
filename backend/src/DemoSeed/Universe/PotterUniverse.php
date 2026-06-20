<?php

namespace App\DemoSeed\Universe;

use App\DemoSeed\DemoNoteDefinition;
use App\DemoSeed\DemoUniverseDefinition;
use App\DemoSeed\DemoVersionDefinition;

final class PotterUniverse
{
    public static function definition(): DemoUniverseDefinition
    {
        return new DemoUniverseDefinition(
            email: 'hogwarts@demo.local',
            roles: ['ROLE_USER'],
            folders: [
                'Хогвартс/Факультеты',
                'Хогвартс/Предметы',
                'Персонажи',
                'Заклинания',
                'Артефакты',
                'События',
            ],
            tags: [
                'персонаж',
                'факультет',
                'заклинание',
                'артефакт',
                'событие',
                'хогвартс',
                'тёмная-магия',
                'квиддич',
                'учебник',
                'гриффиндор',
                'школа',
                'магия',
                'смерть',
                'дружба',
                'пророчество',
            ],
            notes: [
                new DemoNoteDefinition(
                    key: 'guide',
                    title: 'Краткий путеводитель',
                    tags: ['хогвартс', 'учебник', 'школа'],
                    isFavorite: true,
                    updatedAtOffset: '-2 hours',
                    content: <<<'MD'
## Карта знаний

Добро пожаловать в личную энциклопедию магического мира. Начните с {{link:hogwarts-castle|школы}}, затем изучите {{link:gryffindor}}, {{link:slytherin}}, {{link:ravenclaw}} и {{link:hufflepuff}}.

### Ключевые темы

- **Персонажи:** {{link:harry}}, {{link:hermione}}, {{link:dumbledore}}, {{link:voldemort}}
- **События:** {{link:triwizard}}, {{link:hogwarts-battle}}, {{link:philosophers-stone-event}}
- **Артефакты:** {{link:invisibility-cloak}}, {{link:elder-wand}}, {{link:deathly-hallows}}

> «Счастье можно найти даже в самые тёмные времена, если не забывать обращаться к свету.» — {{link:dumbledore|директор}}

Справочник по заклинаниям: {{link:expelliarmus}}, {{link:patronus}}, {{link:lumos}}. Подробнее о сообществе — {{link:magic-society}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Черновик путеводителя', <<<'MD'
## Черновик

Список тем для будущих заметок: факультеты, заклинания, артефакты.
MD,
                            '-14 days'),
                        new DemoVersionDefinition('Путеводитель, версия 2', <<<'MD'
## Путеводитель

Добавлены факультеты: Гриффиндор, Слизерин, Когтевран, Пуффендуй.
MD,
                            '-9 days'),
                        new DemoVersionDefinition('Краткий путеводитель', <<<'MD'
## Карта знаний

Добро пожаловать. Начните с заметок о Хогвартсе и главных персонажах.
MD,
                            '-4 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'magic-society',
                    title: 'Магическое сообщество',
                    tags: ['хогвартс', 'событие'],
                    isFavorite: true,
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Обзор

Британское магическое сообщество сосредоточено вокруг {{link:ministry}}, торговой улицы {{link:diagon-alley}} и школы {{link:hogwarts-castle|Хогвартс}}.

### Структура

1. Министерство магии — законы и безопасность
2. Школа — образование молодых волшебников
3. Тайное общество — защита от {{link:voldemort|Тёмного Лорда}}

Связанные события: {{link:philosophers-stone-event}}, {{link:chamber-secrets}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Магическое сообщество', <<<'MD'
## Общество

Министерство контролирует законы. Хогвартс готовит новое поколение волшебников.
MD,
                            '-8 days'),
                        new DemoVersionDefinition('Магическое сообщество', 'Краткая заметка о министерстве и школе.', '-12 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'house-table',
                    title: 'Факультеты Хогвартса',
                    folderPath: 'Хогвартс/Факультеты',
                    tags: ['факультет', 'хогвартс', 'учебник'],
                    updatedAtOffset: '-11 days',
                    content: <<<'MD'
## Сравнение факультетов

- {{link:gryffindor}} — «Доблесть отважных», храбрость
- {{link:slytherin}} — «Не касайся слизняка», амбиции
- {{link:ravenclaw}} — «Ум превыше всего», мудрость
- {{link:hufflepuff}} — «Справедливость прежде всего», трудолюбие

Подробнее о распределении — {{link:sorting-hat}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'gryffindor',
                    title: 'Гриффиндор',
                    folderPath: 'Хогвартс/Факультеты',
                    tags: ['факультет', 'гриффиндор', 'хогвартс'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Гриффиндор

Факультет храбрости и благородства. Главные представители: {{link:harry}}, {{link:hermione}}, {{link:ron}}, {{link:dumbledore|Альбус}}.

> Девиз: «Доблесть отважных».

Связанные события: {{link:triwizard}}, {{link:hogwarts-battle}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'slytherin',
                    title: 'Слизерин',
                    folderPath: 'Хогвартс/Факультеты',
                    tags: ['факультет', 'хогвартс', 'тёмная-магия'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Слизерин

Факультет амбиций и хитрости. Известные выпускники включают {{link:voldemort|Тома Реддла}} и {{link:snape|Северуса}}.

Часто ассоциируется с {{link:horcruxes|крестражами}} и тёмной магией, хотя не каждый ученик следует этому пути.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'ravenclaw',
                    title: 'Когтевран',
                    folderPath: 'Хогвартс/Факультеты',
                    tags: ['факультет', 'хогвартс', 'учебник'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Когтевран

Факультет мудрости и остроумия. Ценит знания, логику и творческое мышление.

{{link:hermione}} могла бы процветать здесь, но распределяющая шляпа отправила её в {{link:gryffindor|Гриффиндор}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'hufflepuff',
                    title: 'Пуффендуй',
                    folderPath: 'Хогвартс/Факультеты',
                    tags: ['факультет', 'хогвартс'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Пуффендуй

Факультет трудолюбия, терпения и честности. Часто недооценивают, но именно здесь воспитывают надёжных союзников.

> «Справедливость прежде всего» — девиз, который редко звучит громко, но всегда держит слово.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'potions',
                    title: 'Зельеварение',
                    folderPath: 'Хогвартс/Предметы',
                    tags: ['учебник', 'хогвартс'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Предмет

Изучение приготовления магических зелий. Преподаёт {{link:snape|Северус Снейп}} — строгий, но блестящий мастер.

Связано с {{link:potion-recipe|рецептами}} и {{link:philosophers-stone|Философским камнем}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'defense',
                    title: 'Защита от тёмных искусств',
                    folderPath: 'Хогвартс/Предметы',
                    tags: ['учебник', 'хогвартс', 'заклинание'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Защита от тёмных искусств

Практический предмет против проклятий и опасных существ. {{link:harry}} и {{link:hermione}} особенно сильны в применении {{link:patronus|патронус}} и {{link:expelliarmus}}.

Преподаватели менялись, но уроки всегда актуальны перед {{link:hogwarts-battle|битвой}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'charms',
                    title: 'Заговоры',
                    folderPath: 'Хогвартс/Предметы',
                    tags: ['учебник', 'заклинание', 'хогвартс'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Заговоры

Базовые и продвинутые заклинания повседневной магии: {{link:lumos}}, {{link:accio}}, защитные чары.

{{link:hermione}} — лучшая ученица курса, часто помогает {{link:ron}} с домашними заданиями.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'harry',
                    title: 'Гарри Поттер',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'гриффиндор', 'дружба', 'пророчество'],
                    isFavorite: true,
                    updatedAtOffset: '-3 days',
                    content: <<<'MD'
## Гарри Поттер

Мальчик, который выжил. Ученик {{link:gryffindor|Гриффиндора}}, друг {{link:hermione}} и {{link:ron}}.

### Ключевые связи

- Наставник: {{link:dumbledore|Альбус Дамблдор}}
- Противник: {{link:voldemort|Волан-де-Морт}}
- Артефакты: {{link:invisibility-cloak}}, {{link:elder-wand}}
- События: {{link:philosophers-stone-event}}, {{link:triwizard}}, {{link:hogwarts-battle}}

Владеет {{link:patronus|патронус}}-ом в форме оленя.
MD,
                    versions: [
                        new DemoVersionDefinition('Гарри Поттер', <<<'MD'
## Гарри

Ученик Хогвартса, факультет Гриффиндор. Известен как «мальчик, который выжил».
MD,
                            '-7 days'),
                        new DemoVersionDefinition('Гарри Поттер', <<<'MD'
## Гарри Поттер

Главный герой. Друзья: Гермиона и Рон. Главный враг — Волан-де-Морт.
MD,
                            '-5 days'),
                        new DemoVersionDefinition('Гарри', 'Мальчик-сирота, живёт у Дурслей. Шрам-молния на лбу.', '-13 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'hermione',
                    title: 'Гермиона Грейнджер',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'гриффиндор', 'учебник'],
                    isFavorite: true,
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Гермиона Грейнджер

Лучшая ученица курса, блестящая в {{link:charms|заговорах}} и {{link:potions|зельеварении}}. Верный друг {{link:harry}} и {{link:ron}}.

> «Книги! И ум! Есть более важные вещи — дружба и храбрость.»

Участвовала в {{link:triwizard}} (формально — нет, но помогала) и {{link:hogwarts-battle}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Hermione', 'Лучшая ученица курса. Друг Гарри.', '-11 days'),
                        new DemoVersionDefinition('Гермиона', <<<'MD'
## Ученица

Отличница по {{link:charms|заговорам}}. Друг {{link:harry|Гарри}} и {{link:ron|Рона}}.
MD,
                            '-7 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'ron',
                    title: 'Рон Уизли',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'гриффиндор', 'квиддич', 'дружба'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Рон Уизли

Шестой ребёнок в семье Уизли, друг {{link:harry}} с первого поезда в {{link:hogwarts-castle|Хогвартс}}.

Любит {{link:quidditch}}, иногда ревнует к успехам друзей, но всегда приходит на помощь в {{link:hogwarts-battle|битве}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'dumbledore',
                    title: 'Альбус Дамблдор',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'хогвартс', 'гриффиндор'],
                    isFavorite: true,
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Альбус Дамблдор

Директор {{link:hogwarts-castle|Хогвартса}}, один из величайших волшебников эпохи. Наставник {{link:harry}}.

Владелец {{link:elder-wand|Старейшей палочки}} (одно из {{link:deathly-hallows|Даров Смерти}}). Противостоял {{link:voldemort}} на протяжении десятилетий.
MD,
                    versions: [
                        new DemoVersionDefinition('Dumbledore', 'Директор Хогвартса.', '-13 days'),
                        new DemoVersionDefinition('Альбус Дамблдор', <<<'MD'
## Директор

Наставник {{link:harry|Гарри}}. Борется с {{link:voldemort|Тёмным Лордом}}.
MD,
                            '-8 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'voldemort',
                    title: 'Волан-де-Морт',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'тёмная-магия', 'событие'],
                    isFavorite: true,
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Волан-де-Морт

Тёмный Лорд, создатель {{link:horcruxes|крестражей}}. Главный антагонист {{link:harry|Гарри}}.

### Связанные события

- {{link:philosophers-stone-event}}
- {{link:chamber-secrets}}
- {{link:hogwarts-battle}}

Использовал {{link:avada|Непростительные}} проклятия. Был учеником {{link:slytherin|Слизерина}}.
MD,
                    versions: [
                        new DemoVersionDefinition('Voldemort', 'Тёмный Лорд. Главный враг Гарри.', '-12 days'),
                        new DemoVersionDefinition('Волан-де-Морт', <<<'MD'
## Тёмный Лорд

Создатель {{link:horcruxes|крестражей}}. Противник {{link:harry|Гарри}}.
MD,
                            '-7 days'),
                    ],
                ),
                new DemoNoteDefinition(
                    key: 'snape',
                    title: 'Северус Снейп',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'тёмная-магия', 'учебник'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Северус Снейп

Профессор {{link:potions|зельеварения}}, бывший слизеринец. Сложная фигура между {{link:voldemort|Тёмным Лордом}} и {{link:dumbledore|Дамблдором}}.

> «После всего этого время? — **Всегда**.»
MD,
                ),
                new DemoNoteDefinition(
                    key: 'mcgonagall',
                    title: 'Минерва Макгонагалл',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'гриффиндор', 'хогвартс'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Минерва Макгонагалл

Заместитель директора, преподаватель превращений. Строгая, но справедливая. Защищала {{link:hogwarts-castle|школу}} в {{link:hogwarts-battle|битве}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'hagrid',
                    title: 'Рубеус Хагрид',
                    folderPath: 'Персонажи',
                    tags: ['персонаж', 'гриффиндор', 'хогвартс'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Рубеус Хагрид

Смотритель ключей и земель {{link:hogwarts-castle|Хогвартса}}. Первым сообщил {{link:harry|Гарри}}, что тот — волшебник.

Любит магических существ, иногда слишком доверчив — см. {{link:chamber-secrets}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'expelliarmus',
                    title: 'Expelliarmus',
                    folderPath: 'Заклинания',
                    tags: ['заклинание', 'гриффиндор', 'магия'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Expelliarmus

Заклинание разоружения — фирменный приём {{link:harry|Гарри}}. Простое, но эффективное против вооружённых противников.

> «Expelliarmus!» — часто спасало героя там, где другие выбирали {{link:avada|смертельные}} проклятия.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'patronus',
                    title: 'Patronus',
                    folderPath: 'Заклинания',
                    tags: ['заклинание', 'гриффиндор', 'магия'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Expecto Patronum

Мощное защитное заклинание против пожирателей. {{link:harry}} создаёт патронус в форме оленя — как его отец.

Изучается на {{link:defense|уроках защиты}}. Требует сильного счастливого воспоминания.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'lumos',
                    title: 'Lumos',
                    folderPath: 'Заклинания',
                    tags: ['заклинание', 'магия'],
                    updatedAtOffset: '-15 days',
                    content: <<<'MD'
## Lumos

Базовое заклинание света на кончике палочки. Одно из первых, что осваивают ученики на {{link:charms|заговорах}}.

Парное заклинание «Нокс» гасит свет.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'accio',
                    title: 'Accio',
                    folderPath: 'Заклинания',
                    tags: ['заклинание'],
                    updatedAtOffset: '-14 days',
                    content: <<<'MD'
## Accio

Заклинание призыва предметов на расстоянии. {{link:harry}} использовал его на {{link:triwizard|Турнире}} для вызова метлы.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'avada',
                    title: 'Avada Kedavra',
                    folderPath: 'Заклинания',
                    tags: ['заклинание', 'тёмная-магия'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Avada Kedavra

Непростительное проклятие смерти. Подпись {{link:voldemort|Тёмного Лорда}}. Нет известного блокирования, кроме жертвенной любви (см. {{link:harry}}).

> Одно из трёх Непростительных проклятий.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'invisibility-cloak',
                    title: 'Мантия-невидимка',
                    folderPath: 'Артефакты',
                    tags: ['артефакт', 'гриффиндор'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Мантия-невидимка

Один из {{link:deathly-hallows|Даров Смерти}}. Передана {{link:harry|Гарри}} — ключ к многим тайнам, включая {{link:philosophers-stone-event}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'philosophers-stone',
                    title: 'Философский камень',
                    folderPath: 'Артефакты',
                    tags: ['артефакт', 'событие'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Философский камень

Даёт бессмертие и превращение металла в золото. Центр {{link:philosophers-stone-event|первого большого конфликта}} с {{link:voldemort}}.

Охранялся в {{link:hogwarts-castle|Хогвартсе}} под руководством {{link:dumbledore}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'elder-wand',
                    title: 'Старейшая палочка',
                    folderPath: 'Артефакты',
                    tags: ['артефакт', 'тёмная-магия', 'смерть'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Старейшая палочка

Самая мощная палочка, часть {{link:deathly-hallows|Даров Смерти}}. Последний хозяин — {{link:harry}}; ранее — {{link:dumbledore}} и {{link:voldemort}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'marauders-map',
                    title: 'Карта мародёров',
                    folderPath: 'Артефакты',
                    tags: ['артефакт', 'гриффиндор'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Карта мародёров

Показывает всех людей в {{link:hogwarts-castle|Хогвартсе}}. Подарена {{link:harry}} через {{link:hagrid|Хагрид}}. Создана отцом Гарри и друзьями.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'deathly-hallows',
                    title: 'Дары Смерти',
                    folderPath: 'Артефакты',
                    tags: ['артефакт', 'хогвартс', 'смерть'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Дары Смерти

Три артефакта: {{link:elder-wand|Старейшая палочка}}, {{link:invisibility-cloak|Мантия-невидимка}} и Камень воскрешения.

{{link:dumbledore}} и {{link:harry}} обсуждали их символику перед {{link:hogwarts-battle|финальной битвой}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'horcruxes',
                    title: 'Крестражи',
                    folderPath: 'Артефакты',
                    tags: ['артефакт', 'тёмная-магия', 'смерть'],
                    updatedAtOffset: '-5 days',
                    content: <<<'MD'
## Крестражи

Объекты, содержащие части души {{link:voldemort|Волан-де-Морта}}. Уничтожение всех — условие его окончательного падения.

Связаны с {{link:chamber-secrets}} и {{link:hogwarts-battle}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'triwizard',
                    title: 'Турнир Трёх волшебников',
                    folderPath: 'События',
                    tags: ['событие', 'хогвартс', 'квиддич'],
                    updatedAtOffset: '-4 days',
                    content: <<<'MD'
## Турнир Трёх волшебников

Соревнование между школами магии в {{link:hogwarts-castle|Хогвартсе}}. {{link:harry}} — неожиданный участник; {{link:hermione}} и {{link:ron}} помогают.

Финал связан с возвращением {{link:voldemort|Тёмного Лорда}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'hogwarts-battle',
                    title: 'Битва при Хогвартсе',
                    folderPath: 'События',
                    tags: ['событие', 'тёмная-магия', 'хогвартс'],
                    updatedAtOffset: '-2 days',
                    content: <<<'MD'
## Битва при Хогвартсе

Финальное сражение против {{link:voldemort|Волан-де-Морта}}. Защитники: {{link:harry}}, {{link:hermione}}, {{link:ron}}, {{link:mcgonagall}}, {{link:hagrid}}.

> «Конец» — только начало новой эры без Тёмного Лорда.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'philosophers-stone-event',
                    title: 'События камня',
                    folderPath: 'События',
                    tags: ['событие', 'гриффиндор', 'дружба'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Охота за камнем

Первый год {{link:harry}} в {{link:hogwarts-castle|Хогвартсе}}. Противостояние с {{link:voldemort|профессором Квирреллом и Тёмным Лордом}} за {{link:philosophers-stone|камень}}.

Друзья: {{link:hermione}}, {{link:ron}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'chamber-secrets',
                    title: 'Тайная комната',
                    folderPath: 'События',
                    tags: ['событие', 'тёмная-магия'],
                    updatedAtOffset: '-12 days',
                    content: <<<'MD'
## Тайная комната

Второй год обучения. Наследник {{link:slytherin|Слизерина}} открывает комнату. {{link:harry}} побеждает василиска с помощью меча Гриффиндор.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'quidditch',
                    title: 'Квиддич',
                    tags: ['квиддич', 'хогвартс', 'гриффиндор'],
                    updatedAtOffset: '-7 days',
                    content: <<<'MD'
## Квиддич

Магический вид спорта на метлах. {{link:harry}} — ловец в команде {{link:gryffindor|Гриффиндора}}; {{link:ron}} позже становится вратарём.

Турнирные матчи — часть жизни {{link:hogwarts-castle|Хогвартса}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'hogwarts-castle',
                    title: 'Хогвартс',
                    tags: ['хогвартс', 'событие', 'школа'],
                    updatedAtOffset: '-6 days',
                    content: <<<'MD'
## Хогвартс

Школа чародейства и волшебства в Шотландии. Четыре факультета, тысячи тайн. Директор — {{link:dumbledore}}.

События: {{link:triwizard}}, {{link:hogwarts-battle}}, {{link:chamber-secrets}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'diagon-alley',
                    title: 'Косой переулок',
                    tags: ['хогвартс', 'событие'],
                    updatedAtOffset: '-11 days',
                    content: <<<'MD'
## Косой переулок

Главная торговая улица магического Лондона. Здесь {{link:harry}} впервые покупает палочку и учебники перед {{link:hogwarts-castle|Хогвартсом}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'ministry',
                    title: 'Министерство магии',
                    tags: ['хогвартс', 'событие'],
                    updatedAtOffset: '-10 days',
                    content: <<<'MD'
## Министерство магии

Правительство британского магического сообщества. Упоминается в {{link:magic-society}} и финале против {{link:voldemort}}.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'azkaban',
                    title: 'Азкабан',
                    tags: ['хогвартс', 'тёмная-магия'],
                    updatedAtOffset: '-16 days',
                    content: <<<'MD'
## Азкабан

Тюрьма для магических преступников. Дементоры охраняют остров. Многие последователи {{link:voldemort|Тёмного Лорда}} содержались здесь.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'sorting-hat',
                    title: 'Распределяющая шляпа',
                    folderPath: 'Хогвартс/Предметы',
                    tags: ['артефакт', 'факультет', 'хогвартс'],
                    updatedAtOffset: '-9 days',
                    content: <<<'MD'
## Распределяющая шляпа

Определяет факультет ученика: {{link:gryffindor}}, {{link:slytherin}}, {{link:ravenclaw}} или {{link:hufflepuff}}.

Спорила насчёт {{link:harry}} — отправила в Гриффиндор.
MD,
                ),
                new DemoNoteDefinition(
                    key: 'potion-recipe',
                    title: 'Рецепт зелья бодрости',
                    folderPath: 'Хогвартс/Предметы',
                    tags: ['учебник', 'заклинание'],
                    updatedAtOffset: '-8 days',
                    content: <<<'MD'
## Рецепт зелья бодрости

```text
1. Нарезать корень асподель
2. Добавить настой аконит
3. Помешивать против часовой стрелки
4. Нагреть 7 минут на слабом огне
```

Из курса {{link:potions|зельеварения}}. {{link:snape|профессор}} требует идеальной точности.
MD,
                ),
            ],
        );
    }
}
