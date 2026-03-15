[← К оглавлению](../README.md#-документация)

# Утилиты {#utilities}

В этом разделе рассматриваются два ключевых служебных компонента библиотеки: `DataResolver` для извлечения основного содержимого и `InlineCollapser` для схлопывания строчных элементов.

## DataResolver {#dataresolver}

**DataResolver** — это внутренний сервис библиотеки, реализующий [`DataResolverInterface`](./04-appendix--02-api-reference.md#dataresolverinterface), который определяет, что считать основным содержимым (Data) для каждого типа узла.

### Назначение {#dataresolver-purpose}

Основная задача DataResolver — ответить на вопрос: **что является смысловым содержимым этого узла?** Для разных тегов ответ может быть разным:

- Для ссылки (`<a>`) — это атрибут `href`
- Для изображения (`<img>`) — это атрибут `src`
- Для скрипта (`<script>`) — это его текст (код)
- Для параграфа (`<p>`) — это его текст (как и Label)

### API Reference {#dataresolver-api}

```php
namespace HtmlDomParser\Contract;

interface DataResolverInterface
{
    /**
     * Извлекает основное содержимое (data) из DOM-узла.
     *
     * @param \DOMNode $node Оригинальный DOM-узел
     * @return mixed Извлеченные данные
     */
    public function resolve(\DOMNode $node): mixed;
}
```

### Правила извлечения {#dataresolver-rules}

| Тег | Что возвращает `resolve()` | Пример |
| :--- | :--- | :--- |
| `a` | Значение атрибута `href` | `'https://example.com'` |
| `img` | Значение атрибута `src` | `'/images/logo.png'` |
| `script` | Текстовое содержимое | `'alert("Hello");'` |
| `style` | Текстовое содержимое | `'body {color: red;}'` |
| `p`, `div`, `span` | Текстовое содержимое | `'Текст параграфа'` |
| `h1`-`h6` | Текстовое содержимое | `'Заголовок'` |
| `input` | Значение атрибута `value` | `'Submit'` |
| `meta` | Значение атрибута `content` | `'description'` |
| `#text` | Текст узла | `'Просто текст'` |
| `#comment` | Текст комментария | `'Это комментарий'` |
| `#document` | `null` | `null` |

### Когда вызывается DataResolver {#dataresolver-when}

DataResolver может быть вызван в два разных момента:

1. **В контексте:** при первом обращении к `$context->getData()` внутри обработчиков событий. Результат кэшируется в контексте и может быть изменён через `$context->setData()`.
2. **При создании элемента:** метод [`ContextConverterInterface::contextToElement()`](./04-appendix--02-api-reference.md#contextconverterinterface) вызывает `DataResolver`, если данные не были запрошены ранее.

Разработчик обычно не вызывает `DataResolver` напрямую — результат его работы доступен через метод `getData()` у элемента:

```php
$element = $document->getChildren()->get(0);
$data = $element->getData(); // Здесь уже результат работы DataResolver
```

### Примеры использования DataResolver {#dataresolver-examples}

#### Получение Data для разных элементов

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '
    <a href="https://google.com">Google</a>
    <img src="/logo.png">
    <script>console.log("Hello");</script>
    <p>Простой текст</p>
';

$parser = new Parser($html);
$document = $parser->parse();

foreach ($document->getChildren() as $element) {
    echo $element->getName() . ': ' . $element->getData() . PHP_EOL;
}
// Вывод:
// a: https://google.com
// img: /logo.png
// script: console.log("Hello");
// p: Простой текст
```

#### Отличие Data от Label

```php
<?php

$html = '<a href="https://google.com">Перейти на Google</a>';
$parser = new Parser($html);
$document = $parser->parse();

$link = $document->getChildren()->get(0);

echo 'Label: ' . $link->getLabel() . PHP_EOL; // "Перейти на Google"
echo 'Data: ' . $link->getData() . PHP_EOL;   // "https://google.com"
```

#### Модификация Data через модуль

```php
class SeoModule implements ModuleInterface
{
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe('post-node', [$this, 'onPostNode']);
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        if ($context->getName() === 'a') {
            $url = $context->getData();
            $context->setData(strtolower(trim($url))); // нормализация URL
        }
        return $context;
    }
}
```

### Связанные разделы {#dataresolver-see-also}

- [Модель данных: Data](./02-core--01-data-model.md#data)
- [Справочник API: DataResolverInterface](./04-appendix--02-api-reference.md#dataresolverinterface)
- [Событийная модель](./03-events-modules--01-event-system.md)
- [FAQ: Проблемы с DataResolver](./04-appendix--01-faq.md#проблемы-с-dataresolver)

---

## InlineCollapser {#inline-collapser}

**InlineCollapser** — это сервис, реализующий [`InlineCollapserInterface`](./04-appendix--02-api-reference.md#inlinecollapserinterface), который преобразует набор мелких строчных элементов в один крупный элемент с единым текстом и коллекцией сущностей форматирования.

### Зачем нужно схлопывание? {#collapser-purpose}

Без схлопывания фрагмент `<p>Это <b>жирный</b> текст</p>` превратится в дерево из 3 элементов. Со схлопыванием вы получаете **один элемент** с единым текстом и сущностями, описывающими форматирование, что делает навигацию и анализ значительно удобнее.

### API Reference {#collapser-api}

```php
namespace HtmlDomParser\Contract;

interface InlineCollapserInterface
{
    /**
     * Выполняет схлопывание последовательности inline-элементов.
     *
     * @param NodeContextInterface $context Контекст узла
     * @param array $options Опции схлопывания (на будущее)
     * @return NodeContextInterface Модифицированный контекст
     */
    public function collapse(NodeContextInterface $context, array $options = []): NodeContextInterface;
}
```

### Условия запуска {#collapser-trigger}

Схлопывание запускается автоматически, если выполняется условие:

```php
if ($context->allChildrenIsInline()) {
    $context = $inlineCollapser->collapse($context);
}
```

Метод `allChildrenIsInline()` проверяет, что **все** прямые потомки узла являются строчными элементами.

### Алгоритм работы {#collapser-algorithm}

```
Исходные дети: [текст, <b>, текст, <i>, текст]
         │
         ▼
   Шаг 1: Сбор текста (извлекаются Label всех потомков)
         │
         ▼
   Шаг 2: Склейка с отслеживанием позиций (start/end для каждого элемента)
         │
         ▼
   Шаг 3: Создание сущностей форматирования (EntityInterface)
         │
         ▼
   Шаг 4: Замена детей на один элемент с объединенным текстом
         │
         ▼
   Результат: [один элемент с текстом и коллекцией entities]
```

### Сущности форматирования {#collapser-entities}

В результате схлопывания создаются объекты, реализующие [`EntityInterface`](./04-appendix--02-api-reference.md#entityinterface). Каждая сущность описывает часть текста, оформленную определённым тегом.

```php
foreach ($element->getEntities() as $entity) {
    echo $entity->getType();           // 'b', 'i', 'a'
    echo $entity->getStart();          // позиция начала в тексте
    echo $entity->getEnd();            // позиция окончания
    echo $entity->getAttribute('href'); // значение атрибута для ссылок
}
```

### Примеры работы InlineCollapser {#collapser-examples}

#### Базовое схлопывание

```php
<?php

$html = '<p>Это <b>жирный</b> и <i>курсивный</i> текст</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

echo $p->getLabel(); // "Это жирный и курсивный текст"

foreach ($p->getEntities() as $entity) {
    echo $entity->getType() . ': ' . $entity->getStart() . '-' . $entity->getEnd();
}
// Вывод:
// b: 4-10
// i: 13-22
```

#### Ссылки внутри текста

```php
<?php

$html = '<p>Читайте на <a href="https://google.com">Google</a></p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

foreach ($p->getEntities() as $entity) {
    $text = substr($p->getLabel(), $entity->getStart(), $entity->getEnd() - $entity->getStart());
    echo $entity->getType() . ': ' . $text . ' -> ' . $entity->getAttribute('href');
}
// Вывод: a: Google -> https://google.com
```

#### Вложенные строчные элементы

```php
<?php

$html = '<p>Это <b>жирный <i>и курсивный</i></b> текст</p>';
$parser = new Parser($html);
$document = $parser->parse();

$p = $document->getChildren()->get(0);

foreach ($p->getEntities() as $entity) {
    echo $entity->getType() . ': ' . $entity->getStart() . '-' . $entity->getEnd() . PHP_EOL;
}
// Вывод:
// b: 4-20
// i: 11-20
```

> **Важно:** При вложенных тегах создаются отдельные сущности для каждого уровня. Они могут перекрываться.

### Настройка схлопывания {#collapser-configuration}

#### Отключение через события

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
            // Модифицируем контекст, чтобы allChildrenIsInline вернул false
        }
        return $context;
    }
}
```

### Производительность {#collapser-performance}

Схлопывание выполняется однократно для каждого узла, где все дети являются строчными. Алгоритм имеет сложность O(n) по количеству дочерних элементов, а результат кэшируется в готовом элементе.

### Возможные проблемы {#collapser-troubleshooting}

При работе со схлопыванием могут возникать типичные сложности: неожиданные результаты при вложенных тегах, проблемы с пробелами, потеря атрибутов. Подробнее в разделе [FAQ](./04-appendix--01-faq.md#проблемы-с-inlinecollapser).

### Связанные разделы {#collapser-see-also}

- [Модель данных: Entities](./02-core--01-data-model.md#entities)
- [Справочник API: InlineCollapserInterface](./04-appendix--02-api-reference.md#inlinecollapserinterface)
- [Справочник API: EntityInterface](./04-appendix--02-api-reference.md#entityinterface)
- [Система контекстов](./02-core--02-context-system.md) — метод `allChildrenIsInline()`
- [Событийная модель](./03-events-modules--01-event-system.md)