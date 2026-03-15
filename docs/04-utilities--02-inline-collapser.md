[← К оглавлению](../README.md#📖-документация)

# InlineCollapser — схлопывание строчных элементов

В этом разделе рассматривается механизм объединения последовательности строчных элементов в один элемент с единым текстом и картой форматирования (entities).

## Общая концепция

**InlineCollapser** — это сервис, который преобразует набор мелких строчных элементов в один крупный элемент с единым текстом и массивом сущностей форматирования.

### Зачем нужно схлопывание?

Рассмотрим типичный HTML-фрагмент:

```html
<p>Это <b>жирный</b> и <i>курсивный</b> текст</p>
```

Без схлопывания вы получите дерево из 5 элементов:
- Текстовый узел "Это "
- Элемент `<b>` с текстом "жирный"
- Текстовый узел " и "
- Элемент `<i>` с текстом "курсивный"
- Текстовый узел " текст"

Со схлопыванием вы получаете **один элемент** `<p>` с:
- Единым текстом: "Это жирный и курсивный текст"
- Массивом entities, описывающим форматирование

Это делает навигацию по тексту и анализ форматирования значительно удобнее.

## API Reference

```php
namespace HtmlDomParser\Contract;

interface InlineCollapserInterface
{
    /**
     * Выполняет схлопывание последовательности inline-элементов в один или несколько элементов
     * с объединённым текстом и массивом сущностей форматирования.
     *
     * @param NodeContextInterface $context Контекст узла
     * @param array $options Ассоциативный массив опций схлопывания (на будущее)
     * @return NodeContextInterface Модифицированный контекст с результатом схлопывания
     */
    public function collapse(NodeContextInterface $context, array $options = []): NodeContextInterface;
}
```

## Условия запуска схлопывания

Схлопывание запускается автоматически, если выполняется условие:

```php
if ($context->allChildrenIsInline()) {
    // Все дочерние элементы являются строчными — можно схлопывать
    $context = $inlineCollapser->collapse($context);
}
```

Метод `allChildrenIsInline()` проверяет, что **все** прямые потомки узла являются строчными элементами (включая текстовые узлы).

## Алгоритм работы

```
Исходные дети: [текст, <b>, текст, <i>, текст]
         │
         ▼
   Шаг 1: Сбор текста
   ─────────────────
   - Из каждого элемента извлекается Label
   - Тексты собираются в порядке следования
   
         ▼
   Шаг 2: Склейка с отслеживанием позиций
   ─────────────────
   - Тексты объединяются в одну строку
   - Для каждого элемента запоминается:
     * start — позиция начала в итоговом тексте
     * end — позиция окончания
     * type — тип элемента (b, i, a и т.д.)
     * attributes — атрибуты элемента
   
         ▼
   Шаг 3: Создание сущностей
   ─────────────────
   - Для каждого не-текстового элемента создается entity
   - Текстовые узлы не создают entities, но влияют на позиции
   
         ▼
   Шаг 4: Замена детей
   ─────────────────
   - Все дочерние элементы удаляются
   - Создается один новый элемент с объединенным текстом
   - Массив entities сохраняется в новом элементе
   
         ▼
   Результат: [один элемент с текстом и entities]
```

## Структура сущности

Каждая сущность в массиве `entities` содержит:

| Поле | Тип | Описание | Пример |
| :--- | :--- | :--- | :--- |
| `type` | string | Тип форматирования | `'b'`, `'i'`, `'a'` |
| `start` | int | Позиция начала в тексте (индекс символа) | `4` |
| `end` | int | Позиция окончания в тексте | `10` |
| `attributes` | array | Атрибуты элемента | `['href' => 'https://...']` |

## Примеры работы

### Пример 1: Базовое схлопывание

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '<p>Это <b>жирный</b> и <i>курсивный</i> текст</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

// После схлопывания у p нет детей
var_dump($p->hasChildren()); // false

// Но есть единый текст
echo $p->getLabel(); // "Это жирный и курсивный текст"

// И массив сущностей форматирования
$entities = $p->getEntities();
print_r($entities);
```

Вывод:
```
Array
(
    [0] => Array
        (
            [type] => b
            [start] => 4
            [end] => 10
            [attributes] => Array()
        )
    [1] => Array
        (
            [type] => i
            [start] => 13
            [end] => 22
            [attributes] => Array()
        )
)
```

### Пример 2: Ссылки внутри текста

```php
<?php

$html = '<p>Читайте на <a href="https://google.com">Google</a> и <a href="https://yahoo.com">Yahoo</a></p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

echo $p->getLabel(); // "Читайте на Google и Yahoo"

foreach ($p->getEntities() as $entity) {
    $text = substr($p->getLabel(), $entity['start'], $entity['end'] - $entity['start']);
    echo $entity['type'] . ': ' . $text . ' -> ' . $entity['attributes']['href'] . PHP_EOL;
}
// Вывод:
// a: Google -> https://google.com
// a: Yahoo -> https://yahoo.com
```

### Пример 3: Вложенные строчные элементы

```php
<?php

$html = '<p>Это <b>жирный <i>и курсивный</i></b> текст</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

echo $p->getLabel(); // "Это жирный и курсивный текст"

// Вложенность схлопывается в плоский список
foreach ($p->getEntities() as $entity) {
    echo $entity['type'] . ': ' . $entity['start'] . '-' . $entity['end'] . PHP_EOL;
}
// Вывод:
// b: 4-20
// i: 11-20
```

> **Важно:** При вложенных тегах создаются отдельные сущности для каждого уровня. Они могут перекрываться.

### Пример 4: Смешанный контент

```php
<?php

$html = '<p><b>Важно:</b> не забудьте <i>сохранить</i> файл!</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

echo $p->getLabel(); // "Важно: не забудьте сохранить файл!"

foreach ($p->getEntities() as $entity) {
    $text = substr($p->getLabel(), $entity['start'], $entity['end'] - $entity['start']);
    echo $entity['type'] . ': "' . $text . '"' . PHP_EOL;
}
// Вывод:
// b: "Важно:"
// i: "сохранить"
```

### Пример 5: Когда схлопывание не применяется

```php
<?php

$html = '
    <div>
        <p>Текст</p>
        <p>Еще <b>текст</b></p>
    </div>
';

$parser = new Parser($html);
$document = $parser->parse();

$div = $document->getChildren()->get(0);

// У div дети не являются всеми строчными (p — блочный)
var_dump($div->allChildrenIsInline()); // false (гипотетический метод)

// Поэтому у div есть дети
var_dump($div->hasChildren()); // true
var_dump($div->getEntities()); // [] — entities нет

// А вот у второго p внутри — строчные дети, там схлопывание сработает
$secondP = $div->getChildren()->get(1);
var_dump($secondP->hasChildren()); // false
var_dump($secondP->getEntities()); // массив с entity для <b>
```

## Настройка схлопывания

### Опции (будущее расширение)

Интерфейс `InlineCollapserInterface::collapse()` принимает массив опций, который может быть использован в будущем для настройки поведения:

```php
// Гипотетический пример
$context = $collapser->collapse($context, [
    'preserve_whitespace' => true,
    'max_elements' => 100,
    'exclude_tags' => ['script', 'style']
]);
```

### Отключение схлопывания через события

Модули могут отключить схлопывание для определенных узлов через событие `pre-inline-collapse`:

```php
class NoCollapseModule implements ModuleInterface
{
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe('pre-inline-collapse', [$this, 'onPreInlineCollapse']);
    }
    
    public function onPreInlineCollapse(NodeContextInterface $context): NodeContextInterface
    {
        // Не схлопывать содержимое <pre>
        if ($context->getName() === 'pre') {
            // Модифицируем контекст так, чтобы allChildrenIsInline вернул false
            // или устанавливаем специальный флаг
        }
        
        return $context;
    }
}
```

## Производительность

Схлопывание выполняется однократно для каждого узла, где все дети являются строчными. Это оптимально, так как:

- Количество таких узлов ограничено структурой HTML
- Алгоритм имеет сложность O(n) по количеству дочерних элементов
- Результат кэшируется в готовом элементе

Для больших документов с тысячами строчных элементов схлопывание может потребовать дополнительной памяти, но в целом оно эффективнее, чем работа с глубоко вложенной структурой.

## Возможные проблемы

При работе со схлопыванием могут возникать типичные сложности: неожиданные результаты при вложенных тегах, проблемы с пробелами, потеря атрибутов.

Подробное описание этих проблем и методы их решения вы найдете в разделе:

👉 **[FAQ: Проблемы с InlineCollapser](./05-appendix--01-faq-troubleshooting.md#проблемы-с-inlinecollapser)**

## Связанные разделы

- [Модель данных](./02-core-components--03-data-model.md) — Entities в модели данных
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — метод allChildrenIsInline()
- [Событийная модель](./03-advanced-architecture--02-event-system.md) — события pre-inline-collapse/post-inline-collapse
- [FAQ и решение проблем](./05-appendix--01-faq-troubleshooting.md) — подробное решение проблем