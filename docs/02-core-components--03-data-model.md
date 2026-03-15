[← К оглавлению](../README.md#📖-документация)

# Модель данных: Data, Label, Entities

В этом разделе подробно рассматриваются три ключевых свойства элементов HtmlDomParser: основное содержимое (Data), текстовая метка (Label) и карта форматирования (Entities).

## Обзор модели

Каждый элемент в итоговом дереве содержит три типа данных, которые формируются в процессе парсинга:

```
Элемент
├── Data     — основное смысловое содержимое (лениво извлекается DataResolver)
├── Label    — текстовая метка (объединенный текст после схлопывания)
└── Entities — массив сущностей форматирования (если было схлопывание)
```

## API Reference

```php
interface ElementInterface extends NodeInterface
{
    /**
     * Возвращает основное содержимое элемента.
     *
     * @return mixed Для ссылки — URL, для изображения — src, для текста — текст
     */
    public function getData(): mixed;

    /**
     * Устанавливает основное содержимое.
     *
     * @param mixed $value
     */
    public function setData($value): void;

    /**
     * Возвращает текстовую метку элемента.
     *
     * @return string Объединенный текст (например, текст ссылки)
     */
    public function getLabel(): string;

    /**
     * Устанавливает текстовую метку.
     *
     * @param string $label
     */
    public function setLabel(string $label): void;

    /**
     * Возвращает массив сущностей форматирования.
     *
     * @return array Массив entities после схлопывания
     */
    public function getEntities(): array;

    /**
     * Добавляет сущность форматирования.
     *
     * @param array $entity
     */
    public function addEntity(array $entity): void;
}
```

## Детальное описание

### Data (основное содержимое)

**Data** — это смысловое содержимое элемента. Процесс его формирования двухэтапный:

1. **В контексте:** При первом вызове `$context->getData()` значение извлекается из оригинального DOM-узла с помощью `DataResolver` и кэшируется в контексте. Обработчики событий могут модифицировать это значение через `$context->setData()`.
2. **При создании элемента:** Метод `ContextConverterInterface::contextToElement()` копирует текущее значение Data из контекста в создаваемый элемент. Элемент становится самостоятельным объектом и теряет связь с контекстом.

Таким образом, в готовом элементе Data **уже присутствует** и доступна мгновенно. Последующие вызовы `getData()` возвращают сохранённое значение.

Примеры:
- Для `<a href="https://example.com">` Data будет `"https://example.com"`
- Для `<img src="/logo.png">` Data будет `"/logo.png"`
- Для `<p>Текст</p>` Data будет `"Текст"` (аналог Label)

> **Примечание:** Модули могут изменять Data через контекст до момента создания элемента.

### Label (текстовая метка)

**Label** — это текстовая метка элемента, формируемая из его содержимого:

- Для элементов без детей — непосредственно текст
- Для элементов с детьми — объединенный текст всех дочерних узлов
- После схлопывания — единый текст с удаленными тегами

Важно: Label всегда содержит **только текст**, без HTML-тегов.

### Entities (сущности форматирования)

**Entities** появляются только после схлопывания строчных элементов и содержат информацию о форматировании текста. Если элемент не проходил схлопывание (например, блочный элемент с детьми), `getEntities()` вернет пустой массив.

## Структура сущности

Каждая сущность представляет один элемент форматирования и содержит:

| Поле | Тип | Описание | Пример |
| :--- | :--- | :--- | :--- |
| `type` | string | Тип форматирования (b, i, a, strong и т.д.) | `'a'` |
| `start` | int | Позиция начала в тексте (индекс символа) | `7` |
| `end` | int | Позиция окончания в тексте (индекс символа) | `10` |
| `attributes` | array | Атрибуты элемента (для ссылки — href) | `['href' => 'https://example.com']` |

## Примеры кода

### Пример 1: Разбор примера из README

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '<div>Привет, <a href="https://example.com">мир</a>!</div>';
$parser = new Parser($html);
$document = $parser->parse();

$element = $document->getChildren()->get(0);

// Label — объединенный текст
echo $element->getLabel(); // "Привет, мир!"

// Entities — информация о форматировании
$entities = $element->getEntities();
print_r($entities);
```

Вывод:
```
Array
(
    [0] => Array
        (
            [type] => a
            [start] => 7
            [end] => 10
            [attributes] => Array
                (
                    [href] => https://example.com
                )
        )
)
```

### Пример 2: Элемент без схлопывания

```php
<?php

$html = '
    <div>
        <h1>Заголовок</h1>
        <p>Параграф с <b>текстом</b></p>
    </div>
';

$parser = new Parser($html);
$document = $parser->parse();

$div = $document->getChildren()->get(0);

// У div есть дети
var_dump($div->hasChildren()); // true

// Но entities отсутствуют (схлопывание не применялось к div)
var_dump($div->getEntities()); // []

// Однако у p могут быть entities после схлопывания
$p = $div->getChildren()->get(1); // второй ребенок (p)
var_dump($p->hasChildren()); // false (после схлопывания детей нет)
var_dump($p->getEntities()); // массив с сущностью для <b>
```

### Пример 3: Работа с Data

```php
<?php

$html = '<a href="https://google.com">Поисковик</a>';
$parser = new Parser($html);
$document = $parser->parse();

$link = $document->getChildren()->get(0);

// Data уже извлечено при создании элемента
echo $link->getData(); // "https://google.com"

// Принудительная установка своего значения
$link->setData('https://custom.com');
echo $link->getData(); // "https://custom.com"

// Label при этом не меняется
echo $link->getLabel(); // "Поисковик"
```

### Пример 4: Множественные сущности

```php
<?php

$html = '<p>Это <b>жирный</b>, <i>курсивный</i> и <u>подчеркнутый</u> текст</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

echo $p->getLabel(); // "Это жирный, курсивный и подчеркнутый текст"

foreach ($p->getEntities() as $entity) {
    $text = substr($p->getLabel(), $entity['start'], $entity['end'] - $entity['start']);
    echo $entity['type'] . ': "' . $text . '"' . PHP_EOL;
}
// Вывод:
// b: "жирный"
// i: "курсивный"
// u: "подчеркнутый"
```

## Важные замечания

1. **Entities появляются только после схлопывания** — если у элемента есть дети (`hasChildren() === true`), значит схлопывание не применялось и `getEntities()` вернет пустой массив.
2. **Label всегда строка** — даже если элемент не содержит текста, `getLabel()` вернет пустую строку.
3. **Data может быть любого типа** — для большинства элементов это строка, но теоретически может быть и другим типом (например, для скриптов это код).
4. **Сущности не вложены** — схлопывание создает плоский список сущностей, даже если в HTML были вложенные теги (например, `<b><i>текст</i></b>`).

## Связанные разделы

- [Ядро системы (интерфейсы)](./02-core-components--01-core-interfaces.md) — ElementInterface
- [Работа с документом](./02-core-components--02-working-with-document.md) — навигация по дереву
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — как формируются элементы
- [InlineCollapser](./04-utilities--02-inline-collapser.md) — механизм схлопывания
- [DataResolver](./04-utilities--01-data-resolver.md) — извлечение Data