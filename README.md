# HtmlDomParser

**HtmlDomParser** — это библиотека для парсинга HTML на PHP, которая преобразует HTML-строку в структурированное объектное дерево с расширенными возможностями: автоматическое схлопывание inline-контента, семантическое извлечение данных, система событий, гибкая обработка ошибок и модульная архитектура.

## Возможности

- **Рекурсивный обход** HTML-дерева с созданием контекста для каждого узла.
- **DataResolver** — автоматическое определение основного содержимого элемента (URL, текст, значение атрибута).
- **InlineCollapser** — объединение фразовых элементов в единый текст с сохранением сущностей форматирования.
- **Событийная система** — подписка на события обработки узлов (`pre-node`, `post-node`, `pre-inline-collapse`, `post-inline-collapse`).
- **Обработка ошибок** — централизованный сбор ошибок с уровнями `NOTICE`, `WARNING`, `ERROR`, возможность настройки поведения.
- **Модульность** — подключение расширений через Composer (например, поддержка CSS-селекторов, XPath).
- **Гибкая настройка** — конфигурация через параметры парсера и компоненты.

## Требования

- PHP 8.1 или выше
- Расширение `dom` (встроенное)
- Composer (рекомендуется)

## Установка

### Через Composer (локальная разработка)

Если библиотека находится в локальной директории (например, `packages/html-dom-parser`), добавьте в `composer.json` вашего проекта:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/html-dom-parser"
        }
    ],
    "require": {
        "html-dom-parser/core": "*"
    }
}
```

Затем выполните:

```bash
composer update
```

### Ручная установка

Скопируйте содержимое библиотеки в директорию вашего проекта (например, `lib/HtmlDomParser`) и настройте автозагрузку вручную:

```php
spl_autoload_register(function ($class) {
    $prefix = 'HtmlDomParser\\';
    $base_dir = __DIR__ . '/lib/HtmlDomParser/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});
```

**Рекомендуется использовать Composer** для автоматической PSR-4 автозагрузки.

## Быстрый старт

```php
use HtmlDomParser\Core\Parser;

$html = '<div class="content"><p>Hello <strong>world</strong>!</p></div>';
$parser = new Parser($html);
$document = $parser->parse();

$root = $document->getRootElement(); // элемент <div>
$p = $root->getChildren()[0];        // первый потомок — <p>

echo $p->getText(); // "Hello world!"
print_r($p->getEntities()); // сущности форматирования
```

## Документация

Подробная документация по всем компонентам находится в директории `docs/`:

- [Главный документ](docs/index.md) — общее описание, архитектура, список компонентов.
- [ErrorHandler и ErrorElement](docs/error-handling.md) — обработка ошибок.
- [Node, Element, ElementList](docs/node-element-list.md) — классы узлов и коллекций.
- [DataResolver](docs/data-resolver.md) — извлечение данных.
- [NodeContext, NodeContextResolver и TagRules](docs/node-context.md) — контекстная обработка.
- [InlineCollapser](docs/inline-collapser.md) — схлопывание inline-контента.
- [Модули и события](docs/modules-events.md) — расширение функциональности.
- [Автоподключение и Composer](docs/composer-autoload.md) — интеграция с Composer.
- [Примеры использования](docs/examples.md) — практические примеры.
- [История изменений](docs/updates.md) — список версий.

## Лицензия

Библиотека распространяется под лицензией MIT. Подробнее см. файл `LICENSE`.