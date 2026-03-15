[← К оглавлению](../README.md#📖-документация)

# Быстрый старт

В этом разделе вы научитесь базовым операциям с HtmlDomParser: парсить HTML, получать элементы и извлекать из них данные.

## Предварительные требования

- Выполнена [установка библиотеки](./01-getting-started--02-installation.md)
- Базовое понимание HTML и PHP

## Пошаговые инструкции

### 1. Создание парсера

Парсер создается с передачей HTML-строки в конструктор:

```php
use HtmlDomParser\Parser;

$html = '<div class="content">Привет, мир!</div>';
$parser = new Parser($html);
```

### 2. Запуск парсинга

Метод `parse()` запускает процесс и возвращает объект документа:

```php
$document = $parser->parse(); // комментарии не сохраняются
// или с сохранением комментариев:
$document = $parser->parse(true);
```

### 3. Получение элементов

Документ содержит корневые элементы, доступные через `getChildren()`:

```php
$rootElements = $document->getChildren();
$firstElement = $rootElements->get(0); // первый элемент
```

## Примеры кода

### Пример 1: Базовый парсинг

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '<div class="content">Привет, мир!</div>';
$parser = new Parser($html);
$document = $parser->parse();

$element = $document->getChildren()->get(0);
echo 'Имя тега: ' . $element->getName() . PHP_EOL;        // div
echo 'Атрибут class: ' . $element->getAttribute('class') . PHP_EOL; // content
echo 'Текст: ' . $element->getLabel() . PHP_EOL;          // Привет, мир!
```

### Пример 2: Работа со ссылкой и сущностями

Этот пример демонстрирует ключевую особенность библиотеки — схлопывание строчных элементов:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '<div>Привет, <a href="https://example.com">мир</a>!</div>';
$parser = new Parser($html);
$document = $parser->parse();

$element = $document->getChildren()->get(0);

// Текст объединен, тегов внутри нет
echo $element->getLabel(); // "Привет, мир!"

// Информация о форматировании сохранена в entities
$entities = $element->getEntities();
print_r($entities);
// [
//   [
//     'type' => 'a',
//     'start' => 7,
//     'end' => 10,
//     'attributes' => ['href' => 'https://example.com']
//   ]
// ]
```

### Пример 3: Поиск всех ссылок

Используйте фильтрацию для поиска элементов по имени тега:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '
    <div>
        <a href="https://google.com">Google</a>
        <p>Текст</p>
        <a href="https://yahoo.com">Yahoo</a>
    </div>
';

$parser = new Parser($html);
$document = $parser->parse();

// Получаем корневой div
$div = $document->getChildren()->get(0);

// Фильтруем его дочерние элементы, оставляем только ссылки
$links = $div->getChildren()->filter(function($element) {
    return $element->getName() === 'a';
});

foreach ($links as $link) {
    echo $link->getData() . ': ' . $link->getLabel() . PHP_EOL;
}
// Вывод:
// https://google.com: Google
// https://yahoo.com: Yahoo
```

### Пример 4: Сохранение комментариев

По умолчанию HTML-комментарии игнорируются. Чтобы их сохранить, передайте `true` в метод `parse()`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '
    <div>
        <!-- Это важный комментарий -->
        <p>Текст</p>
    </div>
';

// Парсим с сохранением комментариев
$parser = new Parser($html);
$document = $parser->parse(true);

$div = $document->getChildren()->get(0);
$children = $div->getChildren();

foreach ($children as $child) {
    echo 'Тип: ' . $child->getName();
    if ($child->getName() === '#comment') {
        echo ', текст: ' . $child->getLabel();
    }
    echo PHP_EOL;
}
// Вывод:
// Тип: #comment, текст: Это важный комментарий
// Тип: p
```

## Заключение

Вы освоили базовые операции с HtmlDomParser. Теперь вы умеете:

- Создавать парсер и парсить HTML
- Получать элементы и их атрибуты
- Работать с текстом и сущностями форматирования
- Фильтровать элементы по условию
- Сохранять комментарии при необходимости

## Следующие шаги

- [Работа с документом](./02-core-components--02-working-with-document.md) — углубленная навигация по дереву
- [Модель данных](./02-core-components--03-data-model.md) — подробнее о Data, Label и Entities
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — как это работает внутри