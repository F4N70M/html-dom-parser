[← К оглавлению](../README.md#📖-документация)

# Работа с документом и навигация по дереву

В этом разделе рассмотрен `ElementListInterface` — основной инструмент для навигации, фильтрации и преобразования коллекций элементов.

## ElementListInterface

Коллекция элементов реализует стандартные PHP-интерфейсы `IteratorAggregate` и `Countable`, что позволяет работать с ней как с обычным массивом.

```php
namespace HtmlDomParser\Contract;

interface ElementListInterface extends \IteratorAggregate, \Countable
{
    /**
     * Добавляет элемент в конец списка.
     *
     * @param ElementInterface $element
     */
    public function push(ElementInterface $element): void;

    /**
     * Возвращает элемент по индексу.
     *
     * @param int $index
     * @return ElementInterface|null
     */
    public function get(int $index): ?ElementInterface;

    /**
     * Возвращает количество элементов в списке.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Возвращает итератор для перебора элементов.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator;

    /**
     * Преобразует коллекцию в массив элементов.
     *
     * @return ElementInterface[]
     */
    public function toArray(): array;

    /**
     * Преобразует коллекцию в JSON-строку.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Возвращает новую коллекцию, отфильтрованную по callback.
     *
     * @param callable $callback function(ElementInterface $element): bool
     * @return ElementListInterface
     */
    public function filter(callable $callback): ElementListInterface;

    /**
     * Применяет callback к каждому элементу и возвращает массив результатов.
     *
     * @param callable $callback function(ElementInterface $element): mixed
     * @return array
     */
    public function map(callable $callback): array;
}
```

## Пошаговые инструкции

### Получение корневых элементов

После парсинга документа корневые элементы доступны через `getChildren()`:

```php
$document = $parser->parse();
$rootElements = $document->getChildren(); // ElementListInterface
```

### Доступ к элементу по индексу

```php
$firstElement = $rootElements->get(0); // первый элемент
$secondElement = $rootElements->get(1); // второй элемент
$notExists = $rootElements->get(100); // null
```

### Обход всех элементов через foreach

Благодаря реализации `IteratorAggregate`, коллекцию можно перебирать в цикле:

```php
foreach ($rootElements as $index => $element) {
    echo $index . ': ' . $element->getName() . PHP_EOL;
}
```

## Примеры кода

### Фильтрация: получение всех параграфов

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '
    <div>
        <p>Первый параграф</p>
        <span>Спан</span>
        <p>Второй параграф</p>
        <div>Див</div>
    </div>
';

$parser = new Parser($html);
$document = $parser->parse();

$div = $document->getChildren()->get(0);
$paragraphs = $div->getChildren()->filter(function($element) {
    return $element->getName() === 'p';
});

echo 'Найдено параграфов: ' . $paragraphs->count() . PHP_EOL; // 2

foreach ($paragraphs as $p) {
    echo '- ' . $p->getLabel() . PHP_EOL;
}
// Вывод:
// - Первый параграф
// - Второй параграф
```

### Трансформация: извлечение текста из всех заголовков

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '
    <body>
        <h1>Заголовок 1</h1>
        <p>Текст</p>
        <h2>Заголовок 2</h2>
        <p>Еще текст</p>
        <h3>Заголовок 3</h3>
    </body>
';

$parser = new Parser($html);
$document = $parser->parse();

$body = $document->getChildren()->get(0);

// Фильтруем заголовки
$headings = $body->getChildren()->filter(function($element) {
    return in_array($element->getName(), ['h1', 'h2', 'h3']);
});

// Извлекаем текст из каждого заголовка
$headingTexts = $headings->map(function($element) {
    return $element->getLabel();
});

print_r($headingTexts);
// Вывод:
// Array
// (
//     [0] => Заголовок 1
//     [1] => Заголовок 2
//     [2] => Заголовок 3
// )
```

### Экспорт: преобразование в массив или JSON

```php
// Преобразование в массив
$array = $div->getChildren()->toArray();
// [ElementInterface, ElementInterface, ...]

// Преобразование в JSON
$json = $div->getChildren()->toJson();
// '[{"name":"p","label":"Первый параграф",...}, ...]'

echo $json;
```

### Рекурсивный обход дерева

Для обхода всего дерева (не только прямых потомков) используйте рекурсию:

```php
function traverse(ElementInterface $element, int $depth = 0) {
    echo str_repeat('  ', $depth) . '- ' . $element->getName();
    
    if ($element->getLabel()) {
        echo ': ' . $element->getLabel();
    }
    echo PHP_EOL;
    
    if ($element->hasChildren()) {
        foreach ($element->getChildren() as $child) {
            traverse($child, $depth + 1);
        }
    }
}

$root = $document->getChildren()->get(0);
traverse($root);
```

Пример вывода для сложной структуры:
```
- div
  - h1: Главный заголовок
  - p: Текст параграфа
  - ul
    - li: Элемент списка 1
    - li: Элемент списка 2
```

## Связанные разделы

- [Ядро системы (интерфейсы)](./02-core-components--01-core-interfaces.md) — базовые интерфейсы
- [Модель данных](./02-core-components--03-data-model.md) — что содержат элементы
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — как формируется дерево