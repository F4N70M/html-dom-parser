[← К оглавлению](../README.md#-документация)

# Модель данных элемента: Data, Label, Fragments {#data-model}

В этом разделе подробно рассматриваются три ключевых свойства элементов [`ElementInterface`](./04-appendix--02-api-reference.md#elementinterface): основное содержимое (Data), текстовая метка (Label) и коллекция фрагментов форматированного текста (Fragments).

## Обзор модели {#overview}

Каждый элемент в итоговом дереве содержит три типа данных, которые формируются в процессе парсинга:

```
Элемент (ElementInterface)
├── Data      — основное смысловое содержимое (лениво извлекается ContextDataResolver)
├── Label     — текстовая метка (объединенный текст после схлопывания)
└── Fragments — коллекция фрагментов форматирования (если было схлопывание)
```

## Data (основное содержимое) {#data}

**Data** — это смысловое содержимое элемента. Процесс его формирования двухэтапный:

1. **В контексте:** При первом вызове `$context->getData()` значение извлекается из оригинального DOM-узла с помощью [`ContextDataResolverInterface`](./04-appendix--02-api-reference.md#contextdataresolverinterface) и кэшируется в контексте. Обработчики событий могут модифицировать это значение через `$context->setData()`.
2. **При создании элемента:** Метод [`ContextConverterInterface::contextToElement()`](./04-appendix--02-api-reference.md#contextconverterinterface) копирует текущее значение Data из контекста в создаваемый элемент. Элемент становится самостоятельным объектом и теряет связь с контекстом.

Таким образом, в готовом элементе Data **уже присутствует** и доступна мгновенно. Последующие вызовы `getData()` возвращают сохранённое значение.

### Примеры Data для разных тегов

| Тег | Что возвращает `getData()` | Пример |
| :--- | :--- | :--- |
| `<a>` | Значение атрибута `href` | `'https://example.com'` |
| `<img>` | Значение атрибута `src` | `'/images/logo.png'` |
| `<script>` | Текстовое содержимое | `'alert("Hello");'` |
| `<p>`, `<div>` | Текстовое содержимое | `'Текст параграфа'` |

Подробные правила извлечения Data описаны в разделе [ContextDataResolver](./02-core--03-utilities.md#contextdataresolver).

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

## Fragments (фрагменты форматирования) {#fragments}

**Fragments** появляются только после схлопывания строчных элементов и содержат информацию о форматировании текста. Если элемент не проходил схлопывание, `getFragments()` вернет пустую коллекцию ([`RichTextFragmentListInterface`](./04-appendix--02-api-reference.md#richtextfragmentlistinterface)).

Каждый фрагмент реализует интерфейс [`RichTextFragmentInterface`](./04-appendix--02-api-reference.md#richtextfragmentinterface) и предоставляет следующие методы:

- `getType(): string` — тип форматирования (`b`, `i`, `a`, `strong` и т.д.)
- `getStart(): int` — позиция начала в тексте (индекс символа)
- `getEnd(): int` — позиция окончания в тексте (индекс символа, не включая)
- `getAttributes(): array` — ассоциативный массив атрибутов
- `getAttribute(string $name): mixed` — значение конкретного атрибута
- `hasAttribute(string $name): bool` — проверка наличия атрибута
- `toArray(): array` — преобразование в массив для обратной совместимости

### Важные особенности Fragments

1. **Появляются только после схлопывания** — если у элемента есть дети (`hasChildren() === true`), значит схлопывание не применялось и `getFragments()` вернет пустую коллекцию.
2. **Плоский список** — фрагменты не вложены, даже если в HTML были вложенные теги (например, `<b><i>текст</i></b>` создаст два отдельных фрагмента).
3. **Очистка children** — после успешного схлопывания у элемента не остаётся дочерних элементов. Метод `hasChildren()` возвращает `false`, а `getChildren()` — пустую коллекцию. Вся структура форматирования сохраняется в `fragments`.

## Примеры кода {#examples}

### Пример 1: Базовая работа с Data, Label и Fragments

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

// Fragments — коллекция фрагментов форматирования
$fragments = $element->getFragments(); // пусто, так как div не схлопывался
```

### Пример 2: Работа с Fragments после схлопывания

```php
<?php

$html = '<p>Это <b>жирный</b>, <i>курсивный</i> и <u>подчеркнутый</u> текст</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

echo $p->getLabel(); // "Это жирный, курсивный и подчеркнутый текст"

$fragments = $p->getFragments(); // RichTextFragmentListInterface

foreach ($fragments as $fragment) {
    $text = substr($p->getLabel(), $fragment->getStart(), $fragment->getEnd() - $fragment->getStart());
    echo $fragment->getType() . ': "' . $text . '"' . PHP_EOL;
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

// Но fragments отсутствуют (схлопывание не применялось к div)
var_dump($div->getFragments()->count()); // 0

// Однако у p могут быть fragments после схлопывания
$p = $div->getChildren()->get(1);
var_dump($p->hasChildren()); // false (после схлопывания детей нет)
var_dump($p->getFragments()->count()); // 1 (есть фрагмент для <b>)
```

## Связанные разделы {#see-also}

- [Справочник API: ElementInterface](./04-appendix--02-api-reference.md#elementinterface)
- [Справочник API: RichTextFragmentInterface](./04-appendix--02-api-reference.md#richtextfragmentinterface)
- [Справочник API: RichTextFragmentListInterface](./04-appendix--02-api-reference.md#richtextfragmentlistinterface)
- [ContextDataResolver](./02-core--03-utilities.md#contextdataresolver) — извлечение Data
- [InlineCollapser](./02-core--03-utilities.md#inline-collapser) — механизм схлопывания
- [Система контекстов](./02-core--02-context-system.md) — как формируются элементы