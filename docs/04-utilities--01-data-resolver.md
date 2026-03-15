[← К оглавлению](../README.md#📖-документация)

# DataResolver — извлечение основного содержимого

В этом разделе рассматривается сервис `DataResolverInterface`, отвечающий за извлечение основного смыслового содержимого (Data) из DOM-узла.

## Общая концепция

**DataResolver** — это внутренний сервис библиотеки, который определяет, что считать основным содержимым для каждого типа узла. Он вызывается в процессе парсинга, и полученное значение сохраняется в элементе.

Основная задача DataResolver — ответить на вопрос: **что является смысловым содержимым этого узла?**

Для разных тегов ответ может быть разным:
- Для ссылки (`<a>`) — это атрибут `href`
- Для изображения (`<img>`) — это атрибут `src`
- Для скрипта (`<script>`) — это его текст (код)
- Для параграфа (`<p>`) — это его текст (как и Label)

## API Reference

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

## Правила извлечения

Для каждого типа тега определено свое правило, что считать основным содержимым:

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
| *другие теги* | Текстовое содержимое | `'...'` |

## Когда вызывается DataResolver

DataResolver может быть вызван в два разных момента:

1. **В контексте:** при первом обращении к `$context->getData()` внутри обработчиков событий или других операциях, когда данные запрашиваются до создания элемента. Результат кэшируется в контексте и может быть изменён через `$context->setData()`.
2. **При создании элемента:** если данные не были запрошены ранее, метод `ContextConverterInterface::contextToElement()` вызывает `DataResolver`, чтобы получить значение для сохранения в элементе.

В любом случае, после завершения парсинга все элементы уже содержат свои Data, и повторных вызовов `DataResolver` не происходит. Разработчик обычно не вызывает `DataResolver` напрямую — результат его работы доступен через метод `getData()` у элемента:
```php
$element = $document->getChildren()->get(0);
$data = $element->getData(); // Здесь уже результат работы DataResolver
```

## Примеры использования

### Пример 1: Получение Data для разных элементов

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

### Пример 2: Отличие Data от Label

```php
<?php

$html = '<a href="https://google.com">Перейти на Google</a>';
$parser = new Parser($html);
$document = $parser->parse();

$link = $document->getChildren()->get(0);

echo 'Label: ' . $link->getLabel() . PHP_EOL; // "Перейти на Google"
echo 'Data: ' . $link->getData() . PHP_EOL;   // "https://google.com"
```

### Пример 3: Обработка отсутствующих атрибутов

```php
<?php

$html = '<a>Ссылка без href</a>';
$parser = new Parser($html);
$document = $parser->parse();

$link = $document->getChildren()->get(0);
echo 'Data: "' . $link->getData() . '"' . PHP_EOL; // Data: ""
echo 'Label: ' . $link->getLabel() . PHP_EOL;      // Label: Ссылка без href
```

### Пример 4: Принудительное изменение Data

```php
<?php

$html = '<a href="https://google.com">Google</a>';
$parser = new Parser($html);
$document = $parser->parse();

$link = $document->getChildren()->get(0);
echo $link->getData(); // "https://google.com"

// Принудительно меняем Data
$link->setData('https://custom.com');
echo $link->getData(); // "https://custom.com"

// Label при этом не меняется
echo $link->getLabel(); // "Google"
```

## Расширение DataResolver

### Создание собственного DataResolver

Вы можете создать свою реализацию для нестандартных правил извлечения:

```php
<?php

use HtmlDomParser\Contract\DataResolverInterface;

class CustomDataResolver implements DataResolverInterface
{
    public function resolve(\DOMNode $node): mixed
    {
        // Своя логика для определенных тегов
        if ($node->nodeName === 'custom-tag') {
            return $node->getAttribute('data-content');
        }
        
        // Для остальных — стандартное поведение
        return $this->defaultResolve($node);
    }
    
    private function defaultResolve(\DOMNode $node): mixed
    {
        // Стандартная логика или вызов родительского резолвера
        if ($node->nodeName === 'a') {
            return $node->getAttribute('href');
        }
        return $node->textContent;
    }
}
```

### Интеграция через модули

Модули могут модифицировать Data через контекст до создания элемента:

```php
class SeoModule implements ModuleInterface
{
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe('post-node', [$this, 'onPostNode']);
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        // Нормализуем URL в ссылках
        if ($context->getName() === 'a') {
            $url = $context->getData();
            $context->setData($this->normalizeUrl($url));
        }
        
        return $context;
    }
    
    private function normalizeUrl(string $url): string
    {
        return strtolower(trim($url));
    }
}
```

## Возможные проблемы

При работе с DataResolver могут возникать типичные сложности: неожиданные значения Data, пустые строки вместо null, проблемы с кодировкой.

Подробное описание этих проблем и методы их решения вы найдете в разделе:

👉 **[FAQ: Проблемы с DataResolver](./05-appendix--01-faq-troubleshooting.md#проблемы-с-dataresolver)**

## Связанные разделы

- [Модель данных](./02-core-components--03-data-model.md) — Data, Label, Entities
- [Событийная модель](./03-advanced-architecture--02-event-system.md) — модификация данных через события
- [FAQ и решение проблем](./05-appendix--01-faq-troubleshooting.md) — подробное решение проблем