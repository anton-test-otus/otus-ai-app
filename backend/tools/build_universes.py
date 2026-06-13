#!/usr/bin/env python3
"""Generate PotterUniverse.php, WesterosUniverse.php and WitcherUniverse.php with Cyrillic-only Russian words."""

from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'src/DemoSeed/Universe'
SKIP = ("key: '", 'folderPath', 'email:', '{{link:')

# Cyrillic-only Russian names (unicode escapes avoid accidental Latin letters)
NED = '\u041d\u0435\u0434'
ARYA = '\u0410\u0440\u044c\u044f'
SANSA = '\u0421\u0430\u043d\u0441\u0430'
JON = '\u0414\u0436\u043e\u043d'
TYRION = '\u0422\u0438\u0440\u0438\u043e\u043d'
TYWIN = '\u0422\u0430\u0439\u0432\u0438\u043d'
JOFFREY = '\u0414\u0436\u043e\u0444\u0444\u0440\u0438'
MORMONT = '\u041c\u043e\u0440\u043c\u043e\u043d\u0442\u044b'
BAELISH = '\u041c\u0438\u0437\u0438\u043d\u0435\u0446'
WALL = '\u0421\u0442\u0435\u043d\u0430'
WHITE_WALKERS = '\u0431\u0435\u043b\u044b\u0435 \u0445\u043e\u0434\u043e\u043a\u0438'
TOWER_JOY = '\u0420\u0430\u0434\u043e\u0441\u0442\u0438'
STANNIS = '\u0421\u0442\u0430\u043d\u043d\u0438\u0441'
MARGAERY = '\u041c\u0430\u0440\u0433\u0435\u0440\u0438'
DAENERYS = '\u0414\u0435\u0439\u043d\u0435\u0440\u0438\u0441'
CERSEI = '\u0421\u0435\u0440\u0441\u0435\u044f'
JAIME = '\u0414\u0436\u0435\u0439\u043c'
ROBB = '\u0420\u043e\u0431\u0431'
WINTERFELL = '\u0412\u0438\u043d\u0442\u0435\u0440\u0444\u0435\u043b\u043b'
KINGS_LANDING = '\u041a\u043e\u0440\u043e\u043b\u0435\u0432\u0441\u043a\u0430\u044f \u0413\u0430\u0432\u0430\u043d\u044c'
STARK_HOUSE = '\u0421\u0442\u0430\u0440\u043a\u0438'
LANNISTER_HOUSE = '\u041b\u0430\u043d\u043d\u0438\u0441\u0442\u0435\u0440\u044b'
BOLTON_HOUSE = '\u0411\u043e\u043b\u0442\u043e\u043d\u044b'
TYRELL_HOUSE = '\u0422\u0438\u0440\u0435\u043b\u043b\u044b'
MARTELL_HOUSE = '\u041c\u0430\u0440\u0442\u0435\u043b\u043b\u044b'
GERALT = '\u0413\u0435\u0440\u0430\u043b\u044c\u0442'
YENNIFER = '\u0415\u043d\u043d\u0438\u0444\u0435\u0440'
TRISS = '\u0422\u0440\u0438\u0441\u0441'
CIRI = '\u0426\u0438\u0440\u0438'
VESEMIR = '\u0412\u0435\u0441\u0435\u043c\u0438\u0440'
VELEN = '\u0412\u0435\u043b\u0435\u043d'
NOVIGRUD = '\u041d\u043e\u0432\u0438\u0433\u0440\u0443\u0434'
SKELLIGE = '\u0421\u043a\u0435\u043b\u043b\u0438\u0433\u0435'
KAER_MORHEN = '\u041a\u0430\u044d\u0440 \u041c\u043e\u0440\u0445\u0435\u043d'
EMHYR = '\u042d\u043c\u0433\u044b\u0440'


def mixed(text: str) -> dict[str, int]:
    bad: dict[str, int] = {}
    for i, line in enumerate(text.splitlines(), 1):
        if any(s in line for s in SKIP):
            continue
        for word in re.findall(r'[\w\-]+', line):
            if re.search(r'[A-Za-z]', word) and re.search(r'[а-яА-ЯёЁ]', word):
                bad[word] = i
    return bad


def arr(items: list[str]) -> str:
    return '[' + ', '.join(f"'{item}'" for item in items) + ']'


def note(
    key: str,
    title: str,
    body: str,
    *,
    folder: str | None = None,
    tags: list[str] | None = None,
    fav: bool = False,
    offset: str = '-5 days',
    versions: list[tuple[str, str, str]] | None = None,
) -> str:
    tags = tags or ['персонаж']
    lines = [
        '                new DemoNoteDefinition(',
        f"                    key: '{key}',",
        f"                    title: '{title}',",
    ]
    if folder:
        lines.append(f"                    folderPath: '{folder}',")
    lines.append(f'                    tags: {arr(tags)},')
    if fav:
        lines.append('                    isFavorite: true,')
    lines.append(f"                    updatedAtOffset: '{offset}',")
    lines.append("                    content: <<<'MD'")
    lines.append(body)
    lines.append('MD,')
    if versions:
        lines.append('                    versions: [')
        for vtitle, vcontent, voffset in versions:
            if '\n' in vcontent:
                lines.extend([
                    f"                        new DemoVersionDefinition('{vtitle}', <<<'MD'",
                    vcontent,
                    'MD,',
                    f"                            '{voffset}'),",
                ])
            else:
                lines.append(
                    f"                        new DemoVersionDefinition('{vtitle}', '{vcontent}', '{voffset}'),"
                )
        lines.append('                    ],')
    lines.append('                ),')
    return '\n'.join(lines)


def universe(class_name: str, email: str, folders: list[str], tags: list[str], notes: list[str]) -> str:
    folder_lines = ',\n                '.join(f"'{f}'" for f in folders)
    tag_lines = ',\n                '.join(f"'{t}'" for t in tags)
    note_block = '\n'.join(notes)
    return f"""<?php

namespace App\\DemoSeed\\Universe;

use App\\DemoSeed\\DemoNoteDefinition;
use App\\DemoSeed\\DemoUniverseDefinition;
use App\\DemoSeed\\DemoVersionDefinition;

final class {class_name}
{{
    public static function definition(): DemoUniverseDefinition
    {{
        return new DemoUniverseDefinition(
            email: '{email}',
            roles: ['ROLE_USER'],
            folders: [
                {folder_lines},
            ],
            tags: [
                {tag_lines},
            ],
            notes: [
{note_block}
            ],
        );
    }}
}}
"""


def build_westeros() -> list[str]:
    w: list[str] = []
    w.append(note(
        'seal', 'Печать: зима близка',
        f"""## Обзор

Сводная карта. {{{{link:winterfell|{WINTERFELL}}}}} и {{{{link:kings-landing|столица}}}}. {{{{link:stark|{STARK_HOUSE}}}}} и {{{{link:lannister|{LANNISTER_HOUSE}}}}} у {{{{link:iron-throne|Железного трона}}}}.

- **Дома:** {{{{link:stark}}}}, {{{{link:bolton}}}}, {{{{link:tyrell}}}}, {{{{link:martell}}}}
- **Люди:** {{{{link:ned|{NED}}}}}, {{{{link:daenerys|{DAENERYS}}}}}, {{{{link:tyrion|{TYRION}}}}}, {{{{link:jon|{JON}}}}}
- **События:** {{{{link:war-five-kings}}}}, {{{{link:red-wedding}}}}, {{{{link:long-night}}}}

> «Зима близка» — призыв готовиться.

См. {{{{link:maester-council|совет мейстера}}}}.""",
        tags=['дом', 'север', 'совет'], fav=True, offset='-2 hours',
        versions=[
            ('Черновик', 'Список домов.', '-14 days'),
            ('Печать', f'## Дома\n\n{STARK_HOUSE} и {LANNISTER_HOUSE}.', '-8 days'),
            ('Печать: зима близка', '## Обзор\n\nДобавлены войны.', '-4 days'),
        ],
    ))
    w.append(note('maester-council', 'Совет мейстера', f"""## Совет

Решения двора {{{{link:kings-landing|{KINGS_LANDING}}}}}. {{{{link:tyrion|{TYRION}}}}} и {{{{link:baelish|Мизинец}}}} спорят; {{{{link:cersei|{CERSEI}}}}} у {{{{link:iron-throne|трона}}}}.""", tags=['совет', 'интрига'], offset='-6 days'))
    w.append(note('houses-table', 'Дома и девизы', f"""## Дома и девизы

- {{{{link:stark|{STARK_HOUSE}}}}} — «Зима близка», Север
- {{{{link:lannister|{LANNISTER_HOUSE}}}}} — «Слушай мой рёв», Запад
- {{{{link:tyrell|{TYRELL_HOUSE}}}}} — «Растём сильнее», Юг
- {{{{link:martell|{MARTELL_HOUSE}}}}} — «Несгибаемые», Дорн""", folder='Дома/Север', tags=['дом', 'север', 'юг'], offset='-12 days'))
    w.append(note('nights-oath', 'Клятва ночной стражи', """## Клятва

```text
Ночь сгустилась, и теперь моя вахта началась.
Я не возьму жену, не получу землю, не оставлю наследника.
Буду носить чёрное до конца моих дней...
```

У {{link:jon|""" + JON + """}} на {{link:wall|Стене}}.""", tags=['ночной-дозор', 'север'], offset='-10 days'))
    w.append(note('stark', 'Дом Старков', f"""## {STARK_HOUSE}

Древний дом {{{{link:winterfell|{WINTERFELL}}}}}. Глава — {{{{link:ned|{NED}}}}}; дети: {{{{link:arya|{ARYA}}}}}, {{{{link:sansa|{SANSA}}}}}, {{{{link:jon|{JON}}}}}.

Союзники — {{{{link:mormont|{MORMONT}}}}}. Враги — {{{{link:bolton|{BOLTON_HOUSE}}}}} после {{{{link:red-wedding|Красной свадьбы}}}}.""", folder='Дома/Север', tags=['дом', 'север'], offset='-9 days'))
    w.append(note('bolton', f'Дом {BOLTON_HOUSE}', f"""## {BOLTON_HOUSE}

Дом, известный жестокостью. Конфликт со {{{{link:stark|{STARK_HOUSE}}}}} ведёт к {{{{link:red-wedding|Красной свадьбе}}}}.""", folder='Дома/Север', tags=['дом', 'север', 'интрига'], offset='-11 days'))
    w.append(note('mormont', f'Дом {MORMONT}', f"""## {MORMONT}

Верные союзники {{{{link:stark|{STARK_HOUSE}}}}}. Поддержали {{{{link:jon|{JON}}}}} на {{{{link:wall|{WALL}}}}}.""", folder='Дома/Север', tags=['дом', 'север'], offset='-13 days'))
    w.append(note('lannister', f'Дом {LANNISTER_HOUSE}', f"""## {LANNISTER_HOUSE}

Богатейший дом. {{{{link:tywin|{TYWIN}}}}} — глава; дети: {{{{link:cersei|{CERSEI}}}}}, {{{{link:jaime|{JAIME}}}}}, {{{{link:tyrion|{TYRION}}}}}.

Держат {{{{link:iron-throne|трон}}}} через {{{{link:joffrey|{JOFFREY}}}}}. Центр {{{{link:war-five-kings|войны}}}}.""", folder='Дома/Юг', tags=['дом', 'юг', 'интрига'], offset='-8 days'))
    w.append(note('tyrell', f'Дом {TYRELL_HOUSE}', f'## {TYRELL_HOUSE}\n\nБогатые земли юга. Союзы через {{{{link:margaery|{MARGAERY}}}}} и {{{{link:purple-wedding|фиолетовую свадьбу}}}}.', folder='Дома/Юг', tags=['дом', 'юг'], offset='-9 days'))
    w.append(note('martell', f'Дом {MARTELL_HOUSE}', f'## {MARTELL_HOUSE}\n\nДорн. Связь с {{{{link:daenerys|{DAENERYS}}}}} и {{{{link:robert-rebellion|восстанием}}}}.', folder='Дома/Юг', tags=['дом', 'юг', 'интрига'], offset='-10 days'))
    w.append(note('baratheon', 'Дом Баратеонов', f'## Баратеоны\n\nПосле {{{{link:robert-rebellion|восстания}}}} — короли. Ветви: {{{{link:stannis|{STANNIS}}}}}, {{{{link:renly|Renly}}}}.', folder='Дома/Юг', tags=['дом', 'война'], offset='-7 days'))
    w.append(note('ned', 'Ned Stark', f'## {NED}\n\nLord {{{{link:winterfell|{WINTERFELL}}}}}. Честь и долг. Жертва интриг в {{{{link:kings-landing|{KINGS_LANDING}}}}}.\n\nСемья: {{{{link:arya|{ARYA}}}}}, {{{{link:sansa|{SANSA}}}}}, {{{{link:jon|{JON}}}}}.', folder='Персонажи', tags=['персонаж', 'север'], fav=True, offset='-4 days', versions=[('Ned', 'Lord Севера.', '-12 days'), ('Ned Stark', 'Hand короля — расследование.', '-7 days'), ('Ned Stark', 'Трагический финал в столице.', '-5 days')]))
    w.append(note('arya', 'Arya Stark', f'## {ARYA}\n\nДочь {{{{link:ned|{NED}}}}}. Путь мести после {{{{link:red-wedding|Красной свадьбы}}}}. Список имён.', folder='Персонажи', tags=['персонаж', 'север', 'интрига'], offset='-5 days'))
    w.append(note('tyrion', 'Tyrion Lannister', f'## {TYRION}\n\n«Я пью и знаю вещи». Сын {{{{link:tywin|{TYWIN}}}}}. Hand короля, затем советник {{{{link:daenerys|{DAENERYS}}}}}.', folder='Персонажи', tags=['персонаж', 'юг', 'совет'], fav=True, offset='-3 days'))
    w.append(note('daenerys', 'Daenerys Targaryen', f'## {DAENERYS}\n\n«Мать драконов». {{{{link:drogo|Drogo}}}}, затем {{{{link:dragons|драконы}}}} — путь к {{{{link:iron-throne|трону}}}}.', folder='Персонажи', tags=['персонаж', 'драконы', 'война'], fav=True, offset='-2 days'))
    w.append(note('jon', 'Jon Snow', f'## {JON}\n\nBastard {{{{link:stark|{STARK_HOUSE}}}}}. Командор {{{{link:wall|{WALL}}}}}. Против {{{{{{link:white-walkers|{WHITE_WALKERS}}}}}}} в {{{{link:long-night|Длинной ночи}}}}.', folder='Персонажи', tags=['персонаж', 'север', 'ночной-дозор'], offset='-3 days'))
    w.append(note('cersei', 'Cersei Lannister', f'## {CERSEI}\n\nRegent. Конфликт с {{{{link:tyrion|{TYRION}}}}} и {{{{link:tyrell|{TYRELL_HOUSE}}}}}.', folder='Персонажи', tags=['персонаж', 'юг', 'интрига'], offset='-6 days'))
    w.append(note('jaime', 'Jaime Lannister', f'## {JAIME}\n\nKingsguard. Брат {{{{link:cersei|{CERSEI}}}}}. Путь с {{{{link:brienne|Brienne}}}}.', folder='Персонажи', tags=['персонаж', 'юг', 'война'], offset='-7 days'))
    w.append(note('sansa', 'Sansa Stark', f'## {SANSA}\n\nSurvivor интриг. Уроки от {{{{link:baelish|{BAELISH}}}}}. Защита {{{{link:winterfell|{WINTERFELL}}}}}.', folder='Персонажи', tags=['персонаж', 'север', 'интрига'], offset='-6 days'))
    w.append(note('baelish', 'Petyr Baelish', f'## {BAELISH}\n\nArchitect {{{{link:red-wedding|Красной свадьбы}}}}. Спор с {{{{link:tyrion|{TYRION}}}}} в {{{{link:maester-council|совете}}}}.', folder='Персонажи', tags=['персонаж', 'интрига'], offset='-8 days'))
    w.append(note('tywin', 'Tywin Lannister', f'## {TYWIN}\n\nPatriarch {{{{link:lannister|{LANNISTER_HOUSE}}}}}. Стратег {{{{link:war-five-kings|войны}}}}.', folder='Персонажи', tags=['персонаж', 'юг', 'война'], offset='-7 days'))
    w.append(note('brienne', 'Brienne of Tarth', '## Brienne\n\nWarrior чести. С {{{{link:jaime|Jaime}}}}.', folder='Персонажи', tags=['персонаж', 'юг'], offset='-14 days'))
    w.append(note('hodor', 'Hodor', f'## Hodor\n\nСлуга {{{{link:stark|{STARK_HOUSE}}}}}. Судьба связана с {{{{link:arya|{ARYA}}}}} и {{{{link:long-night|Длинной ночью}}}}.', folder='Персонажи', tags=['персонаж', 'север'], offset='-15 days'))
    w.append(note('winterfell', 'Winterfell', f'## {WINTERFELL}\n\nSeat {{{{link:stark|{STARK_HOUSE}}}}}. События: {{{{link:red-wedding|последствия войны}}}}, возвращение {{{{link:jon|{JON}}}}}.', folder='Локации', tags=['локация', 'север', 'дом'], offset='-8 days'))
    w.append(note('kings-landing', 'Kings Landing', f'## {KINGS_LANDING}\n\nСтолица. {{{{link:iron-throne|Трон}}}}. Интриги {{{{link:cersei|{CERSEI}}}}}, {{{{link:tyrion|{TYRION}}}}}, {{{{link:ned|{NED}}}}}.', folder='Локации', tags=['локация', 'юг', 'интрига'], offset='-5 days'))
    w.append(note('wall', 'The Wall', f'## {WALL}\n\nЛёд и {{{{link:wildlings|дикари}}}}. {{{{link:jon|{JON}}}}} и {{{{{{link:white-walkers|{WHITE_WALKERS}}}}}}}.', folder='Локации', tags=['локация', 'север', 'ночной-дозор'], offset='-6 days'))
    w.append(note('dragonstone', 'Dragonstone', f'## Dragonstone\n\nBase {{{{link:daenerys|{DAENERYS}}}}} с {{{{link:dragons|драконами}}}}.', folder='Локации', tags=['локация', 'драконы'], offset='-7 days'))
    w.append(note('eyrie', 'The Eyrie', '## Орлиное гнездо\n\nSeat Arryn. {{{{link:baelish|Мизинец}}}}.', folder='Локации', tags=['локация', 'интрига'], offset='-12 days'))
    w.append(note('braavos', 'Braavos', f'## Braavos\n\nFree city. Путь {{{{link:arya|{ARYA}}}}} после {{{{link:red-wedding|Красной свадьбы}}}}.', folder='Локации', tags=['локация', 'интрига'], offset='-13 days'))
    w.append(note('war-five-kings', 'Война Пяти королей', '## Война\n\nБорьба {{{{link:stannis|Stannis}}}}, {{{{link:renly|Renly}}}}, {{{{link:joffrey|Joffrey}}}}, {{{{link:robb|Robb}}}}, {{{{link:balon|Balon}}}}. Перелом — {{{{link:red-wedding|Красная свадьба}}}}.', folder='Войны', tags=['война', 'интрига', 'дом'], fav=True, offset='-4 days', versions=[('Война', 'Список претендентов.', '-11 days'), ('Война Пяти королей', 'Битвы и интриги.', '-6 days')]))
    w.append(note('long-night', 'Long Night', f'## Длинная ночь\n\nБитва с {{{{{{link:white-walkers|{WHITE_WALKERS}}}}}}} у {{{{link:wall|{WALL}}}}}.', folder='Войны', tags=['война', 'ночной-дозор', 'север'], offset='-3 days'))
    w.append(note('robert-rebellion', 'Robert Rebellion', f'## Восстание\n\nСverжение Mad King. {{{{link:ned|{NED}}}}} и Robert. Exile {{{{link:daenerys|{DAENERYS}}}}}.', folder='Войны', tags=['война', 'дом'], offset='-10 days'))
    w.append(note('red-wedding', 'Red Wedding', f'## Красная свадьба\n\nMassacre {{{{link:stark|{STARK_HOUSE}}}}}. {{{{link:bolton|{BOLTON_HOUSE}}}}}, {{{{link:baelish|{BAELISH}}}}}, {{{{link:tywin|{TYWIN}}}}}.', folder='Интриги', tags=['интрига', 'война', 'север'], offset='-5 days'))
    w.append(note('purple-wedding', 'Purple Wedding', f'## Фиолетовая свадьба\n\nPoisoning {{{{link:joffrey|{JOFFREY}}}}}. Обвинения на {{{{link:tyrion|{TYRION}}}}}.', folder='Интриги', tags=['интрига', 'юг'], offset='-6 days'))
    w.append(note('tower-joy', 'Tower of Joy', f'## Башня Радости\n\nSecret о {{{{link:jon|{JON}}}}}. Связь {{{{link:ned|{NED}}}}} и Rhaegar.', folder='Интриги', tags=['интрига', 'север'], offset='-14 days'))
    w.append(note('drogo', 'Khal Drogo', f'## Drogo\n\nПервый муж {{{{link:daenerys|{DAENERYS}}}}}.', tags=['персонаж', 'война'], offset='-9 days'))
    w.append(note('dragons', 'Dragons', f'## Драконы\n\nDrogon, Rhaegal, Viserion — {{{{link:daenerys|{DAENERYS}}}}} в {{{{link:war-five-kings|войне}}}}.', tags=['драконы', 'война'], offset='-4 days'))
    w.append(note('white-walkers', 'White Walkers', f'## Белыеходоки\n\nEnemy за {{{{link:wall|{WALL}}}}}. Цель {{{{link:long-night|Длинной ночи}}}}.', tags=['север', 'война', 'ночной-дозор'], offset='-5 days'))
    w.append(note('wildlings', 'Wildlings', f'## Дикари\n\nЗа {{{{link:wall|{WALL}}}}}. {{{{link:jon|{JON}}}}} объединил с Night Watch.', tags=['север', 'ночной-дозор'], offset='-11 days'))
    w.append(note('iron-throne', 'Iron Throne', f'## Железный трон\n\nClaimants: {{{{link:daenerys|{DAENERYS}}}}}, {{{{link:cersei|{CERSEI}}}}}, {{{{link:stannis|Stannis}}}}, {{{{link:jon|{JON}}}}}.', tags=['локация', 'интрига', 'война'], offset='-4 days'))
    w.append(note('stannis', 'Stannis Baratheon', '## Stannis\n\nClaimant. {{{{link:melisandre|Melisandre}}}}.', tags=['персонаж', 'война', 'драконы'], offset='-8 days'))
    w.append(note('melisandre', 'Melisandre', '## Melisandre\n\nRed Priestess. С {{{{link:stannis|Stannis}}}} и {{{{link:jon|Jon}}}}.', tags=['персонаж', 'интрига', 'драконы'], offset='-9 days'))
    w.append(note('joffrey', 'Joffrey Baratheon', f'## {JOFFREY}\n\nCruel king. Fall на {{{{link:purple-wedding|фиолетовой свадьбе}}}}.', tags=['персонаж', 'интрига', 'юг'], offset='-7 days'))
    w.append(note('renly', 'Renly Baratheon', '## Renly\n\nClaimant. Союз с {{{{link:tyrell|Tyrell}}}}.', tags=['персонаж', 'война'], offset='-13 days'))
    w.append(note('robb', 'Robb Stark', f'## {ROBB}\n\nKing in the North. Погиб на {{{{link:red-wedding|Красной свадьбе}}}}.', tags=['персонаж', 'север', 'война'], offset='-6 days'))
    w.append(note('balon', 'Balon Greyjoy', '## Balon\n\nIron Islands claimant.', tags=['персонаж', 'война'], offset='-14 days'))
    w.append(note('margaery', 'Margaery Tyrell', f'## Margaery\n\nQueen. Брак с {{{{link:renly|Renly}}}} и {{{{link:joffrey|{JOFFREY}}}}}.', tags=['персонаж', 'юг', 'интрига'], offset='-10 days'))
    return w


def build_witcher() -> list[str]:
    z: list[str] = []
    z.append(note('bestiary', 'Записи бестиария', f"""## Бестиарий

Сводка чудовищ и контрактов. {{{{link:geralt|{GERALT}}}}}, {{{{link:vesemir|{VESEMIR}}}}}, {{{{link:velen|{VELEN}}}}}, {{{{link:novigrad|{NOVIGRUD}}}}}.

- **Чудовища:** {{{{link:griffin}}}}, {{{{link:striga}}}}, {{{{link:leshen}}}}, {{{{link:drowner}}}}
- **Люди:** {{{{link:yennefer|{YENNIFER}}}}}, {{{{link:triss|{TRISS}}}}}, {{{{link:ciri|{CIRI}}}}}
- **Квесты:** {{{{link:griffin-hunt}}}}, {{{{link:family-affairs}}}}, {{{{link:baron-quest}}}}

> Контракт — закон.

См. {{{{link:law-of-surprise|право неожиданности}}}}.""", tags=['монстрология', 'ведьмаки', 'легенда'], fav=True, offset='-2 hours', versions=[
        ('Бестиарий', 'Оглавление разделов.', '-14 days'),
        ('Записи бестиария', '## Черти\n\nДобавлены типы чудовищ.', '-8 days'),
        ('Записи бестиария', '## Полный бестиарий\n\nСсылки на квесты и знаки.', '-4 days'),
    ]))
    z.append(note('law-of-surprise', 'Закон неожиданности', f"""## Право неожиданности

Если ведьмак спасает жизнь, может потребовать «то, что уже есть, но не ожидается». Связано с {{{{link:ciri|{CIRI}}}}} и {{{{link:geralt|{GERALT}}}}}.

См. {{{{link:bestiary|бестиарий}}}}.""", tags=['легенда', 'ведьмак'], fav=True, offset='-5 days', versions=[
        ('Закон', 'Краткое определение.', '-12 days'),
        ('Закон неожиданности', f'## Примеры\n\nСвязь с {CIRI}.', '-7 days'),
    ]))
    z.append(note('signs-table', 'Знаки и эффекты', """## Знаки и эффекты

- {{link:cat-sign}} — ускорение
- {{link:quen-sign}} — щит
- {{link:aard-sign}} — толчок
- {{link:igni-sign}} — огонь

Подробнее — {{link:alchemy|алхимия}}.""", folder='Алхимия и знаки', tags=['знак', 'алхимия'], offset='-11 days'))
    z.append(note('contract-template', 'Шаблон контракта', f"""## Шаблон

```text
Заказчик: ___
Цель: ___
Награда: ___
Срок: ___
Подпись ведьмака: ___
```

Использует {GERALT} в {VELEN}.""", tags=['контракт', 'ведьмак'], offset='-10 days'))
    z.append(note('geralt', 'Geralt of Rivia', f'## {GERALT}\n\nВедьмак из {{{{link:kaer-morhen|{KAER_MORHEN}}}}}. Знак {{{{link:cat-sign|Кошачий}}}}. Связь с {{{{link:ciri|{CIRI}}}}} и {{{{link:yennefer|{YENNIFER}}}}}.', folder='Ведьмаки', tags=['ведьмак', 'ведьмаки'], fav=True, offset='-3 days', versions=[('Geralt', 'Контрактник.', '-13 days'), ('Geralt of Rivia', '## Путь\n\nКвесты и знаки.', '-7 days')]))
    z.append(note('yennefer', 'Yennefer', f'## {YENNIFER}\n\nЧародейка. Связь с {{{{link:geralt|{GERALT}}}}} и {{{{link:ciri|{CIRI}}}}}. {{{{link:novigrad|{NOVIGRUD}}}}}.', folder='Ведьмаки', tags=['ведьмак', 'легенда'], fav=True, offset='-4 days'))
    z.append(note('triss', 'Triss Merigold', f'## {TRISS}\n\nЧародейка. Союзница {{{{link:geralt|{GERALT}}}}} в {{{{link:novigrad|{NOVIGRUD}}}}}.', folder='Ведьмаки', tags=['ведьмак', 'легенда'], offset='-6 days'))
    z.append(note('ciri', 'Ciri', f'## {CIRI}\n\nПепельные волосы. {{{{link:law-of-surprise|Закон неожиданности}}}}. Путь с {{{{link:geralt|{GERALT}}}}}.', folder='Ведьмаки', tags=['легенда', 'ведьмаки'], fav=True, offset='-3 days'))
    z.append(note('vesemir', 'Vesemir', f'## {VESEMIR}\n\nСтарший ведьмак {{{{link:kaer-morhen|{KAER_MORHEN}}}}}. Наставник {{{{link:geralt|{GERALT}}}}}.', folder='Ведьмаки', tags=['ведьмак', 'ведьмаки'], fav=True, offset='-7 days'))
    z.append(note('lambert', 'Lambert', f'## Lambert\n\nВедьмак. Товарищ {{{{link:geralt|{GERALT}}}}} из {{{{link:kaer-morhen|{KAER_MORHEN}}}}}.', folder='Ведьмаки', tags=['ведьмак', 'ведьмаки'], offset='-12 days'))
    z.append(note('eskell', 'Eskel', f'## Eskel\n\nВедьмак. {{{{link:kaer-morhen|{KAER_MORHEN}}}}}.', folder='Ведьмаки', tags=['ведьмак', 'ведьмаки'], offset='-13 days'))
    z.append(note('griffin', 'Griffin', f'## Грифон\n\nКонтракт в Белом Саду. Квест {{{{link:griffin-hunt|охота}}}}. {{{{link:geralt|{GERALT}}}}}.', folder='Чудовища', tags=['чудовище', 'контракт'], offset='-8 days'))
    z.append(note('striga', 'Striga', f'## Стрига\n\nПроклятие в {{{{link:velen|{VELEN}}}}}. Ранний контракт {{{{link:geralt|{GERALT}}}}}.', folder='Чудовища', tags=['чудовище', 'контракт', 'легенда'], offset='-9 days'))
    z.append(note('leshen', 'Leshen', f'## Леший\n\nЛесной дух. {{{{link:velen|{VELEN}}}}}. Опасен для деревень.', folder='Чудовища', tags=['чудовище', 'легенда'], offset='-10 days'))
    z.append(note('werewolf', 'Werewolf', f'## Оборотень\n\nВстреча в {VELEN}. Слабость к серебру.', folder='Чудовища', tags=['чудовище'], offset='-11 days'))
    z.append(note('drowner', 'Drowner', f'## Утопец\n\nУ воды в {VELEN} и {NOVIGRUD}.', folder='Чудовища', tags=['чудовище', 'монстрология'], offset='-14 days'))
    z.append(note('ghoul', 'Ghoul', f'## Упырь\n\nКладбища {VELEN}. Слаб к огню.', folder='Чудовища', tags=['чудовище'], offset='-15 days'))
    z.append(note('novigrad', 'Novigrad', f'## {NOVIGRUD}\n\nГород. {{{{link:yennefer|{YENNIFER}}}}}, {{{{link:triss|{TRISS}}}}}, {{{{link:dandelion|Лютик}}}}.', folder='Локации/Королевства', tags=['локация', 'ведьмаки'], offset='-5 days'))
    z.append(note('velen', 'Velen', f'## {VELEN}\n\nЗемля никому не принадлежащая. {{{{link:baron-quest|Кровавый барон}}}}. Много стриг и леших.', folder='Локации/Королевства', tags=['локация', 'квест'], offset='-6 days'))
    z.append(note('skellige', 'Skellige', f'## {SKELLIGE}\n\nОстрова. Квесты и монстры моря.', folder='Локации/Королевства', tags=['локация', 'легенда'], offset='-7 days'))
    z.append(note('kaer-morhen', 'Kaer Morhen', f'## {KAER_MORHEN}\n\nКрепость ведьмаков. {{{{link:vesemir|{VESEMIR}}}}} и {{{{link:geralt|{GERALT}}}}}.', folder='Локации/Королевства', tags=['локация', 'ведьмаки'], offset='-8 days'))
    z.append(note('white-orchard', 'White Orchard', f'## Белый Сад\n\nНачало пути {GERALT}. {{{{link:griffin|Грифон}}}}.', folder='Локации/Королевства', tags=['локация', 'квест'], offset='-9 days'))
    z.append(note('cat-sign', 'Cat Sign', f'## Кошачий\n\nЗнак скорости. {GERALT} использует часто.', folder='Алхимия и знаки', tags=['знак', 'алхимия'], offset='-6 days'))
    z.append(note('quen-sign', 'Quen Sign', f'## Кваен\n\nЗащитный знак. {GERALT}.', folder='Алхимия и знаки', tags=['знак', 'алхимия'], offset='-6 days'))
    z.append(note('aard-sign', 'Aard Sign', '## Аард\n\nТолчок. Против утопцев.', folder='Алхимия и знаки', tags=['знак'], offset='-7 days'))
    z.append(note('igni-sign', 'Igni Sign', '## Игни\n\nОгненный знак. Против упырей.', folder='Алхимия и знаки', tags=['знак', 'алхимия'], offset='-7 days'))
    z.append(note('alchemy', 'Алхимия', '## Алхимия\n\nМасла и зелья. Ласточка. Знаки.', folder='Алхимия и знаки', tags=['алхимия', 'ведьмак'], offset='-8 days'))
    z.append(note('swallow-potion', 'Swallow Potion', '## Ласточка\n\nЗелье регенерации. Алхимия.', folder='Алхимия и знаки', tags=['алхимия'], offset='-9 days'))
    z.append(note('griffin-hunt', 'Охота на грифона', f'## Охота\n\nПервый крупный контракт {{{{link:geralt|{GERALT}}}}} у Белого Сада. Цель — {{{{link:griffin|грифон}}}}.', folder='Квесты', tags=['квест', 'чудовище', 'контракт'], offset='-4 days'))
    z.append(note('family-affairs', 'Семейное дело', f'## Семья\n\nКвест в {{{{link:velen|{VELEN}}}}}. Связь с {{{{link:baron-quest|бароном}}}} и {{{{link:ciri|{CIRI}}}}}.', folder='Квесты', tags=['квест', 'легенда'], offset='-5 days'))
    z.append(note('baron-quest', 'Bloody Baron', f'## Кровавый барон\n\nЦентр {{{{link:velen|{VELEN}}}}}. {{{{link:family-affairs|семейное дело}}}}.', folder='Квесты', tags=['квест', 'локация'], offset='-6 days'))
    z.append(note('dandelion', 'Dandelion', f'## Лютик\n\nБард. Друг {{{{link:geralt|{GERALT}}}}} в {{{{link:novigrad|{NOVIGRUD}}}}}.', tags=['легенда', 'ведьмаки'], offset='-8 days'))
    z.append(note('emhyr', 'Emhyr', f'## {EMHYR}\n\nИмператор Нильфгаарда. Связь с {CIRI}.', tags=['легенда'], offset='-10 days'))
    z.append(note('regis', 'Regis', f'## Regis\n\nВампир. Помогает {{{{link:geralt|{GERALT}}}}} в пути.', tags=['легенда', 'чудовище'], offset='-11 days'))
    z.append(note('troll', 'Troll', f'## Тролль\n\nПод мостом в {VELEN}. Комический персонаж.', tags=['чудовище'], offset='-16 days'))
    z.append(note('werewolf-hunt', 'Охота на оборотня', f'## Контракт\n\n{{{{link:geralt|{GERALT}}}}} и {{{{link:werewolf|оборотень}}}} в {{{{link:velen|{VELEN}}}}}.', folder='Квесты', tags=['квест', 'чудовище', 'контракт'], offset='-7 days'))
    z.append(note('ciri-quest', 'Поиск Ciri', f'## Поиск\n\n{{{{link:geralt|{GERALT}}}}} ищет {{{{link:ciri|{CIRI}}}}} через {{{{link:velen|{VELEN}}}}} и {{{{link:novigrad|{NOVIGRUD}}}}}.', folder='Квесты', tags=['квест', 'легенда'], offset='-3 days'))
    z.append(note('trial-mountains', 'Испытание гор', f'## Горы\n\nПуть юного {{{{link:geralt|{GERALT}}}}} из {{{{link:kaer-morhen|{KAER_MORHEN}}}}}.', tags=['ведьмаки', 'легенда'], offset='-13 days'))
    z.append(note('wolf-school', 'Школа Волка', f'## Школа\n\n{{{{link:kaer-morhen|{KAER_MORHEN}}}}}. {{{{link:vesemir|{VESEMIR}}}}}.', tags=['ведьмаки', 'легенда'], offset='-12 days'))
    return z



def build_potter() -> list[str]:
    p: list[str] = []

    p.append(note('guide', 'Краткий путеводитель', '''## Карта знаний

Добро пожаловать в личную энциклопедию магического мира. Начните с {{link:hogwarts-castle|школы}}, затем изучите {{link:gryffindor}}, {{link:slytherin}}, {{link:ravenclaw}} и {{link:hufflepuff}}.

### Ключевые темы

- **Персонажи:** {{link:harry}}, {{link:hermione}}, {{link:dumbledore}}, {{link:voldemort}}
- **События:** {{link:triwizard}}, {{link:hogwarts-battle}}, {{link:philosophers-stone-event}}
- **Артефакты:** {{link:invisibility-cloak}}, {{link:elder-wand}}, {{link:deathly-hallows}}

> «Счастье можно найти даже в самые тёмные времена, если не забывать обращаться к свету.» — {{link:dumbledore|директор}}

Справочник по заклинаниям: {{link:expelliarmus}}, {{link:patronus}}, {{link:lumos}}. Подробнее о сообществе — {{link:magic-society}}.''',
        tags=['хогвартс', 'учебник'], fav=True, offset='-2 hours', versions=[('Черновик путеводителя', '''## Черновик

Список тем для будущих заметок: факультеты, заклинания, артефакты.''', '-14 days'), ('Путеводитель, версия 2', '''## Путеводитель

Добавлены факультеты: Гриффиндор, Слизерин, Когтевран, Пуффендуй.''', '-9 days'), ('Краткий путеводитель', '''## Карта знаний

Добро пожаловать. Начните с заметок о Хогвартсе и главных персонажах.''', '-4 days')]))

    p.append(note('magic-society', 'Магическое сообщество', '''## Обзор

Британское магическое сообщество сосредоточено вокруг {{link:ministry}}, торговой улицы {{link:diagon-alley}} и школы {{link:hogwarts-castle|Хогвартс}}.

### Структура

1. Министерство магии — законы и безопасность
2. Школа — образование молодых волшебников
3. Тайное общество — защита от {{link:voldemort|Тёмного Лорда}}

Связанные события: {{link:philosophers-stone-event}}, {{link:chamber-secrets}}.''',
        tags=['хогвартс', 'событие'], fav=True, offset='-5 days', versions=[('Магическое сообщество', '''## Общество

Министерство контролирует законы. Хогвартс готовит новое поколение волшебников.''', '-8 days'), ('Магическое сообщество', 'Краткая заметка о министерстве и школе.', '-12 days')]))

    p.append(note('house-table', 'Факультеты Хогвартса', '''## Сравнение факультетов

- {{link:gryffindor}} — «Доблесть отважных», храбрость
- {{link:slytherin}} — «Не касайся слизняка», амбиции
- {{link:ravenclaw}} — «Ум превыше всего», мудрость
- {{link:hufflepuff}} — «Справедливость прежде всего», трудолюбие

Подробнее о распределении — {{link:sorting-hat}}.''',
        folder='Хогвартс/Факультеты', tags=['факультет', 'хогвартс', 'учебник'], offset='-11 days'))

    p.append(note('gryffindor', 'Гриффиндор', '''## Гриффиндор

Факультет храбрости и благородства. Главные представители: {{link:harry}}, {{link:hermione}}, {{link:ron}}, {{link:dumbledore|Альбус}}.

> Девиз: «Доблесть отважных».

Связанные события: {{link:triwizard}}, {{link:hogwarts-battle}}.''',
        folder='Хогвартс/Факультеты', tags=['факультет', 'гриффиндор', 'хогвартс'], offset='-10 days'))

    p.append(note('slytherin', 'Слизерин', '''## Слизерин

Факультет амбиций и хитрости. Известные выпускники включают {{link:voldemort|Тома Реддла}} и {{link:snape|Северуса}}.

Часто ассоциируется с {{link:horcruxes|крестражами}} и тёмной магией, хотя не каждый ученик следует этому пути.''',
        folder='Хогвартс/Факультеты', tags=['факультет', 'хогвартс', 'тёмная-магия'], offset='-10 days'))

    p.append(note('ravenclaw', 'Когтевран', '''## Когтевран

Факультет мудрости и остроумия. Ценит знания, логику и творческое мышление.

{{link:hermione}} могла бы процветать здесь, но распределяющая шляпа отправила её в {{link:gryffindor|Гриффиндор}}.''',
        folder='Хогвартс/Факультеты', tags=['факультет', 'хогвартс', 'учебник'], offset='-9 days'))

    p.append(note('hufflepuff', 'Пуффендуй', '''## Пуффендуй

Факультет трудолюбия, терпения и честности. Часто недооценивают, но именно здесь воспитывают надёжных союзников.

> «Справедливость прежде всего» — девиз, который редко звучит громко, но всегда держит слово.''',
        folder='Хогвартс/Факультеты', tags=['факультет', 'хогвартс'], offset='-9 days'))

    p.append(note('potions', 'Зельеварение', '''## Предмет

Изучение приготовления магических зелий. Преподаёт {{link:snape|Северус Снейп}} — строгий, но блестящий мастер.

Связано с {{link:potion-recipe|рецептами}} и {{link:philosophers-stone|Философским камнем}}.''',
        folder='Хогвартс/Предметы', tags=['учебник', 'хогвартс'], offset='-8 days'))

    p.append(note('defense', 'Защита от тёмных искусств', '''## Защита от тёмных искусств

Практический предмет против проклятий и опасных существ. {{link:harry}} и {{link:hermione}} особенно сильны в применении {{link:patronus|патронус}} и {{link:expelliarmus}}.

Преподаватели менялись, но уроки всегда актуальны перед {{link:hogwarts-battle|битвой}}.''',
        folder='Хогвартс/Предметы', tags=['учебник', 'хогвартс', 'заклинание'], offset='-8 days'))

    p.append(note('charms', 'Заговоры', '''## Заговоры

Базовые и продвинутые заклинания повседневной магии: {{link:lumos}}, {{link:accio}}, защитные чары.

{{link:hermione}} — лучшая ученица курса, часто помогает {{link:ron}} с домашними заданиями.''',
        folder='Хогвартс/Предметы', tags=['учебник', 'заклинание', 'хогвартс'], offset='-7 days'))

    p.append(note('harry', 'Гарри Поттер', '''## Гарри Поттер

Мальчик, который выжил. Ученик {{link:gryffindor|Гриффиндора}}, друг {{link:hermione}} и {{link:ron}}.

### Ключевые связи

- Наставник: {{link:dumbledore|Альбус Дамблдор}}
- Противник: {{link:voldemort|Волан-де-Морт}}
- Артефакты: {{link:invisibility-cloak}}, {{link:elder-wand}}
- События: {{link:philosophers-stone-event}}, {{link:triwizard}}, {{link:hogwarts-battle}}

Владеет {{link:patronus|патронус}}-ом в форме оленя.''',
        folder='Персонажи', tags=['персонаж', 'гриффиндор'], fav=True, offset='-3 days', versions=[('Гарри Поттер', '''## Гарри

Ученик Хогвартса, факультет Гриффиндор. Известен как «мальчик, который выжил».''', '-7 days'), ('Гарри Поттер', '''## Гарри Поттер

Главный герой. Друзья: Гермиона и Рон. Главный враг — Волан-де-Морт.''', '-5 days'), ('Гарри', 'Мальчик-сирота, живёт у Дурслей. Шрам-молния на лбу.', '-13 days')]))

    p.append(note('hermione', 'Гермиона Грейнджер', '''## Гермиона Грейнджер

Лучшая ученица курса, блестящая в {{link:charms|заговорах}} и {{link:potions|зельеварении}}. Верный друг {{link:harry}} и {{link:ron}}.

> «Книги! И ум! Есть более важные вещи — дружба и храбрость.»

Участвовала в {{link:triwizard}} (формально — нет, но помогала) и {{link:hogwarts-battle}}.''',
        folder='Персонажи', tags=['персонаж', 'гриффиндор', 'учебник'], fav=True, offset='-4 days'))

    p.append(note('ron', 'Рон Уизли', '''## Рон Уизли

Шестой ребёнок в семье Уизли, друг {{link:harry}} с первого поезда в {{link:hogwarts-castle|Хогвартс}}.

Любит {{link:quidditch}}, иногда ревнует к успехам друзей, но всегда приходит на помощь в {{link:hogwarts-battle|битве}}.''',
        folder='Персонажи', tags=['персонаж', 'гриффиндор', 'квиддич'], offset='-6 days'))

    p.append(note('dumbledore', 'Альбус Дамблдор', '''## Альбус Дамблдор

Директор {{link:hogwarts-castle|Хогвартса}}, один из величайших волшебников эпохи. Наставник {{link:harry}}.

Владелец {{link:elder-wand|Старейшей палочки}} (одно из {{link:deathly-hallows|Даров Смерти}}). Противостоял {{link:voldemort}} на протяжении десятилетий.''',
        folder='Персонажи', tags=['персонаж', 'хогвартс', 'гриффиндор'], fav=True, offset='-5 days'))

    p.append(note('voldemort', 'Волан-де-Морт', '''## Волан-де-Морт

Тёмный Лорд, создатель {{link:horcruxes|крестражей}}. Главный антагонист {{link:harry|Гарри}}.

### Связанные события

- {{link:philosophers-stone-event}}
- {{link:chamber-secrets}}
- {{link:hogwarts-battle}}

Использовал {{link:avada|Непростительные}} проклятия. Был учеником {{link:slytherin|Слизерина}}.''',
        folder='Персонажи', tags=['персонаж', 'тёмная-магия', 'событие'], fav=True, offset='-4 days'))

    p.append(note('snape', 'Северус Снейп', '''## Северус Снейп

Профессор {{link:potions|зельеварения}}, бывший слизеринец. Сложная фигура между {{link:voldemort|Тёмным Лордом}} и {{link:dumbledore|Дамблдором}}.

> «После всего этого время? — **Всегда**.»''',
        folder='Персонажи', tags=['персонаж', 'тёмная-магия', 'учебник'], offset='-7 days'))

    p.append(note('mcgonagall', 'Минерва Макгонагалл', '''## Минерва Макгонагалл

Заместитель директора, преподаватель превращений. Строгая, но справедливая. Защищала {{link:hogwarts-castle|школу}} в {{link:hogwarts-battle|битве}}.''',
        folder='Персонажи', tags=['персонаж', 'гриффиндор', 'хогвартс'], offset='-8 days'))

    p.append(note('hagrid', 'Рубеус Хагрид', '''## Рубеус Хагрид

Смотритель ключей и земель {{link:hogwarts-castle|Хогвартса}}. Первым сообщил {{link:harry|Гарри}}, что тот — волшебник.

Любит магических существ, иногда слишком доверчив — см. {{link:chamber-secrets}}.''',
        folder='Персонажи', tags=['персонаж', 'гриффиндор', 'хогвартс'], offset='-9 days'))

    p.append(note('expelliarmus', 'Expelliarmus', '''## Expelliarmus

Заклинание разоружения — фирменный приём {{link:harry|Гарри}}. Простое, но эффективное против вооружённых противников.

> «Expelliarmus!» — часто спасало героя там, где другие выбирали {{link:avada|смертельные}} проклятия.''',
        folder='Заклинания', tags=['заклинание', 'гриффиндор'], offset='-6 days'))

    p.append(note('patronus', 'Patronus', '''## Expecto Patronum

Мощное защитное заклинание против пожирателей. {{link:harry}} создаёт патронус в форме оленя — как его отец.

Изучается на {{link:defense|уроках защиты}}. Требует сильного счастливого воспоминания.''',
        folder='Заклинания', tags=['заклинание', 'гриффиндор'], offset='-6 days'))

    p.append(note('lumos', 'Lumos', '''## Lumos

Базовое заклинание света на кончике палочки. Одно из первых, что осваивают ученики на {{link:charms|заговорах}}.

Парное заклинание «Нокс» гасит свет.''',
        folder='Заклинания', tags=['заклинание'], offset='-15 days'))

    p.append(note('accio', 'Accio', '''## Accio

Заклинание призыва предметов на расстоянии. {{link:harry}} использовал его на {{link:triwizard|Турнире}} для вызова метлы.''',
        folder='Заклинания', tags=['заклинание'], offset='-14 days'))

    p.append(note('avada', 'Avada Kedavra', '''## Avada Kedavra

Непростительное проклятие смерти. Подпись {{link:voldemort|Тёмного Лорда}}. Нет известного блокирования, кроме жертвенной любви (см. {{link:harry}}).

> Одно из трёх Непростительных проклятий.''',
        folder='Заклинания', tags=['заклинание', 'тёмная-магия'], offset='-5 days'))

    p.append(note('invisibility-cloak', 'Мантия-невидимка', '''## Мантия-невидимка

Один из {{link:deathly-hallows|Даров Смерти}}. Передана {{link:harry|Гарри}} — ключ к многим тайнам, включая {{link:philosophers-stone-event}}.''',
        folder='Артефакты', tags=['артефакт', 'гриффиндор'], offset='-5 days'))

    p.append(note('philosophers-stone', 'Философский камень', '''## Философский камень

Даёт бессмертие и превращение металла в золото. Центр {{link:philosophers-stone-event|первого большого конфликта}} с {{link:voldemort}}.

Охранялся в {{link:hogwarts-castle|Хогвартсе}} под руководством {{link:dumbledore}}.''',
        folder='Артефакты', tags=['артефакт', 'событие'], offset='-6 days'))

    p.append(note('elder-wand', 'Старейшая палочка', '''## Старейшая палочка

Самая мощная палочка, часть {{link:deathly-hallows|Даров Смерти}}. Последний хозяин — {{link:harry}}; ранее — {{link:dumbledore}} и {{link:voldemort}}.''',
        folder='Артефакты', tags=['артефакт', 'тёмная-магия'], offset='-5 days'))

    p.append(note('marauders-map', 'Карта мародёров', '''## Карта мародёров

Показывает всех людей в {{link:hogwarts-castle|Хогвартсе}}. Подарена {{link:harry}} через {{link:hagrid|Хагрид}}. Создана отцом Гарри и друзьями.''',
        folder='Артефакты', tags=['артефакт', 'гриффиндор'], offset='-7 days'))

    p.append(note('deathly-hallows', 'Дары Смерти', '''## Дары Смерти

Три артефакта: {{link:elder-wand|Старейшая палочка}}, {{link:invisibility-cloak|Мантия-невидимка}} и Камень воскрешения.

{{link:dumbledore}} и {{link:harry}} обсуждали их символику перед {{link:hogwarts-battle|финальной битвой}}.''',
        folder='Артефакты', tags=['артефакт', 'хогвартс'], offset='-6 days'))

    p.append(note('horcruxes', 'Крестражи', '''## Крестражи

Объекты, содержащие части души {{link:voldemort|Волан-де-Морта}}. Уничтожение всех — условие его окончательного падения.

Связаны с {{link:chamber-secrets}} и {{link:hogwarts-battle}}.''',
        folder='Артефакты', tags=['артефакт', 'тёмная-магия'], offset='-5 days'))

    p.append(note('triwizard', 'Турнир Трёх волшебников', '''## Турнир Трёх волшебников

Соревнование между школами магии в {{link:hogwarts-castle|Хогвартсе}}. {{link:harry}} — неожиданный участник; {{link:hermione}} и {{link:ron}} помогают.

Финал связан с возвращением {{link:voldemort|Тёмного Лорда}}.''',
        folder='События', tags=['событие', 'хогвартс', 'квиддич'], offset='-4 days'))

    p.append(note('hogwarts-battle', 'Битва при Хогвартсе', '''## Битва при Хогвартсе

Финальное сражение против {{link:voldemort|Волан-де-Морта}}. Защитники: {{link:harry}}, {{link:hermione}}, {{link:ron}}, {{link:mcgonagall}}, {{link:hagrid}}.

> «Конец» — только начало новой эры без Тёмного Лорда.''',
        folder='События', tags=['событие', 'тёмная-магия', 'хогвартс'], offset='-2 days'))

    p.append(note('philosophers-stone-event', 'События камня', '''## Охота за камнем

Первый год {{link:harry}} в {{link:hogwarts-castle|Хогвартсе}}. Противостояние с {{link:voldemort|профессором Квирреллом и Тёмным Лордом}} за {{link:philosophers-stone|камень}}.

Друзья: {{link:hermione}}, {{link:ron}}.''',
        folder='События', tags=['событие', 'гриффиндор'], offset='-8 days'))

    p.append(note('chamber-secrets', 'Тайная комната', '''## Тайная комната

Второй год обучения. Наследник {{link:slytherin|Слизерина}} открывает комнату. {{link:harry}} побеждает василиска с помощью меча Гриффиндор.''',
        folder='События', tags=['событие', 'тёмная-магия'], offset='-12 days'))

    p.append(note('quidditch', 'Квиддич', '''## Квиддич

Магический вид спорта на метлах. {{link:harry}} — ловец в команде {{link:gryffindor|Гриффиндора}}; {{link:ron}} позже становится вратарём.

Турнирные матчи — часть жизни {{link:hogwarts-castle|Хогвартса}}.''',
        tags=['квиддич', 'хогвартс', 'гриффиндор'], offset='-7 days'))

    p.append(note('hogwarts-castle', 'Хогвартс', '''## Хогвартс

Школа чародейства и волшебства в Шотландии. Четыре факультета, тысячи тайн. Директор — {{link:dumbledore}}.

События: {{link:triwizard}}, {{link:hogwarts-battle}}, {{link:chamber-secrets}}.''',
        tags=['хогвартс', 'событие'], offset='-6 days'))

    p.append(note('diagon-alley', 'Косой переулок', '''## Косой переулок

Главная торговая улица магического Лондона. Здесь {{link:harry}} впервые покупает палочку и учебники перед {{link:hogwarts-castle|Хогвартсом}}.''',
        tags=['хогвартс', 'событие'], offset='-11 days'))

    p.append(note('ministry', 'Министерство магии', '''## Министерство магии

Правительство британского магического сообщества. Упоминается в {{link:magic-society}} и финале против {{link:voldemort}}.''',
        tags=['хогвартс', 'событие'], offset='-10 days'))

    p.append(note('azkaban', 'Азкабан', '''## Азкабан

Тюрьма для магических преступников. Дементоры охраняют остров. Многие последователи {{link:voldemort|Тёмного Лорда}} содержались здесь.''',
        tags=['хогвартс', 'тёмная-магия'], offset='-16 days'))

    p.append(note('sorting-hat', 'Распределяющая шляпа', '''## Распределяющая шляпа

Определяет факультет ученика: {{link:gryffindor}}, {{link:slytherin}}, {{link:ravenclaw}} или {{link:hufflepuff}}.

Спорила насчёт {{link:harry}} — отправила в Гриффиндор.''',
        folder='Хогвартс/Предметы', tags=['артефакт', 'факультет', 'хогвартс'], offset='-9 days'))

    p.append(note('potion-recipe', 'Рецепт зелья бодрости', '''## Рецепт зелья бодрости

```text
1. Нарезать корень асподель
2. Добавить настой аконит
3. Помешивать против часовой стрелки
4. Нагреть 7 минут на слабом огне
```

Из курса {{link:potions|зельеварения}}. {{link:snape|профессор}} требует идеальной точности.''',
        folder='Хогвартс/Предметы', tags=['учебник', 'заклинание'], offset='-8 days'))

    return p



def write_file(class_name: str, php: str) -> None:
    bad = mixed(php)
    if bad:
        print(f'{class_name} mixed words:', file=sys.stderr)
        for word, line in bad.items():
            print(f'  {word} (line {line})', file=sys.stderr)
        raise SystemExit(1)
    path = OUT / f'{class_name}.php'
    path.write_text(php, encoding='utf-8')
    print(f'Wrote {path} ({php.count("new DemoNoteDefinition")} notes)')


def main() -> None:
    potter_notes = build_potter()
    write_file('PotterUniverse', universe(
        'PotterUniverse', 'hogwarts@demo.local',
        ['Хогвартс/Факультеты', 'Хогвартс/Предметы', 'Персонажи', 'Заклинания', 'Артефакты', 'События'],
        ['персонаж', 'факультет', 'заклинание', 'артефакт', 'событие', 'хогвартс', 'тёмная-магия', 'квиддич', 'учебник', 'гриффиндор'],
        potter_notes,
    ))
    westeros_notes = build_westeros()
    write_file('WesterosUniverse', universe(
        'WesterosUniverse', 'westeros@demo.local',
        ['Дома/Север', 'Дома/Юг', 'Персонажи', 'Локации', 'Войны', 'Интриги'],
        ['дом', 'персонаж', 'локация', 'война', 'интрига', 'север', 'юг', 'драконы', 'ночной-дозор', 'совет'],
        westeros_notes,
    ))
    witcher_notes = build_witcher()
    write_file('WitcherUniverse', universe(
        'WitcherUniverse', 'witcher@demo.local',
        ['Ведьмаки', 'Чудовища', 'Локации/Королевства', 'Алхимия и знаки', 'Квесты'],
        ['ведьмак', 'чудовище', 'локация', 'квест', 'алхимия', 'знак', 'контракт', 'монстрология', 'ведьмаки', 'легенда'],
        witcher_notes,
    ))


if __name__ == '__main__':
    main()
