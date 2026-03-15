[← К оглавлению](../README.md#-документация)

# Модель данных элемента: Data, Label, Entities {#data-model}

В этом разделе подробно рассматриваются три ключевых свойства элементов [`ElementInterface`](./04-appendix--02-api-reference.md#elementinterface): основное содержимое (Data), текстовая метка (Label) и карта форматирования (Entities).

## Обзор модели {#overview}

Каждый элемент в итоговом дереве содержит три типа данных, которые формируются в процессе парсинга:

```
Элемент (ElementInterface)
├── Data     — основное смысловое содержимое (лениво извлекается DataResolver)
├── Label    — текстовая метка (объединенный текст после схлопывания)
└── Entities — коллекция сущностей форматирования (если было схлопывание)
```

## Data (основное содержимое) {#data}

**Data** — это смысловое содержимое элемента. Процесс его формирования двухэтапный:

1. **В контексте:** При первом вызове `$context->getData()` значение извлекается из оригинального DOM-узла с помощью [`DataResolverInterface`](./04-appendix--02-api-reference.md#dataresolverinterface) и кэшируется в контексте. Обработчики событий могут модифицировать это значение через `$context->setData()`.
2. **При создании элемента:** Метод [`ContextConverterInterface::contextToElement()`](./04-appendix--02-api-reference.md#contextconverterinterface) копирует текущее значение Data из контекста в создаваемый элемент. Элемент становится самостоятельным объектом и теряет связь с контекстом.

Таким образом, в готовом элементе Data **уже присутствует** и доступна мгновенно. Последующие вызовы `getData()` возвращают сохранённое значение.

### Примеры Data для разных тегов

| Тег | Что возвращает `getData()` | Пример |
| :--- | :--- | :--- |
| `<a>` | Значение атрибута `href` | `'https://example.com'` |
| `<img>` | Значение атрибута `src` | `'/images/logo.png'` |
| `<script>` | Текстовое содержимое | `'alert("Hello");'` |
| `<p>`, `<div>` | Текстовое содержимое | `'Текст параграфа'` |

Подробные правила извлечения Data описаны в разделе [DataResolver](./02-core--03-utilities.md#dataresolver).

> **Примечание:** Модули могут изменять Data через контекст до момента создания элемента. Подробнее в разделе [Событийная модель](./03-events-modules--01-event-system.md).

## Label (текстовая метка) {#label}

**Label** — это текстовая метка элемента, формируемая из его содержимого:

- Для элементов без детей — непосредственно текст узла
- Для элементов с детьми — объединенный текст всех дочерних узлов
- После [схлопывания](./02-core--03-utilities.md#inline-collapser) — единый текст с удаленными тегами

Важно: Label всегда содержит **только текст**, без HTML-тегов. Даже если элемент не содержит текста, `getLabel()` вернет пустую строку.

```php
$element->getLabel(); // "Это жирный и курсивный текст"
```

## Entities (сущности форматирования) {#entities}

**Entities** появляются только после схлопывания строчных элементов и содержат информацию о форматировании текста. Если элемент не проходил схлопывание, `getEntities()` вернет пустую коллекцию ([`EntityListInterface`](./04-appendix--02-api-reference.md#entitylistinterface)).

Каждая сущность реализует интерфейс [`EntityInterface`](./04-appendix--02-api-reference.md#entityinterface) и предоставляет следующие методы:

- `getType(): string` — тип форматирования (`b`, `i`, `a`, `strong` и т.д.)
- `getStart(): int` — позиция начала в тексте (индекс символа)
- `getEnd(): int` — позиция окончания в тексте (индекс символа, не включая)
- `getAttributes(): array` — ассоциативный массив атрибутов
- `getAttribute(string $name): mixed` — значение конкретного атрибута
- `hasAttribute(string $name): bool` — проверка наличия атрибута
- `toArray(): array` — преобразование в массив для обратной совместимости

### Важные особенности Entities

1. **Появляются только после схлопывания** — если у элемента есть дети (`hasChildren() === true`), значит схлопывание не применялось и `getEntities()` вернет пустую коллекцию.
2. **Плоский список** — сущности не вложены, даже если в HTML были вложенные теги (например, `<b><i>текст</i></b>` создаст две отдельные сущности).
3. **Очистка children** — после успешного схлопывания у элемента не остаётся дочерних элементов. Метод `hasChildren()` возвращает `false`, а `getChildren()` — пустую коллекцию. Вся структура форматирования сохраняется в `entities`.

## Примеры кода {#examples}

### Пример 1: Базовая работа с Data, Label и Entities

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

// Data ссылки (доступно через дочерний элемент, если не было схлопывания)
// Но в данном примере div не схлопывался, поэтому ссылка — отдельный элемент
if ($element->hasChildren()) {
    $link = $element->getChildren()->get(1); // элемент <a>
    echo $link->getData(); // "https://example.com"
}

// Entities — коллекция сущностей форматирования
$entities = $element->getEntities(); // пусто, так как div не схлопывался
```

### Пример 2: Работа с Entities после схлопывания

```php
<?php

$html = '<p>Это <b>жирный</b>, <i>курсивный</i> и <u>подчеркнутый</u> текст</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

echo $p->getLabel(); // "Это жирный, курсивный и подчеркнутый текст"

$entities = $p->getEntities(); // EntityListInterface

foreach ($entities as $entity) {
    $text = substr($p->getLabel(), $entity->getStart(), $entity->getEnd() - $entity->getStart());
    echo $entity->getType() . ': "' . $text . '"' . PHP_EOL;
}
// Вывод:
// b: "жирный"
// i: "курсивный"
// u: "подчеркнутый"
```

### Пример 3: Модификация Data

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

### Пример 4: Элемент без схлопывания

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
var_dump($div->getEntities()->count()); // 0

// Однако у p могут быть entities после схлопывания
$p = $div->getChildren()->get(1);
var_dump($p->hasChildren()); // false (после схлопывания детей нет)
var_dump($p->getEntities()->count()); // 1 (есть сущность для <b>)
```

## Связанные разделы {#see-also}

- [Справочник API: ElementInterface](./04-appendix--02-api-reference.md#elementinterface)
- [Справочник API: EntityInterface](./04-appendix--02-api-reference.md#entityinterface)
- [Справочник API: EntityListInterface](./04-appendix--02-api-reference.md#entitylistinterface)
- [DataResolver](./02-core--03-utilities.md#dataresolver) — извлечение Data
- [InlineCollapser](./02-core--03-utilities.md#inline-collapser) — механизм схлопывания
- [Система контекстов](./02-core--02-context-system.md) — как формируются элементы