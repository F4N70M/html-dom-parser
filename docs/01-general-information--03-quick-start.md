[← К оглавлению](../README.md#-документация)

# Быстрый старт {#quick-start}

В этом разделе вы научитесь базовым операциям с HtmlDomParser: парсить HTML, получать элементы и извлекать из них данные.

## Предварительные требования {#prerequisites}

- Выполнена [установка библиотеки](./01-general-information--02-installation.md)
- Базовое понимание HTML и PHP
- Ознакомление с [основными концепциями](./01-general-information--01-introduction.md)

## Пошаговые инструкции {#step-by-step}

### 1. Создание парсера {#create-parser}

Парзер создается с передачей HTML-строки в конструктор [`ParserInterface`](./04-appendix--02-api-reference.md#parserinterface):

```php
use HtmlDomParser\Parser;

$html = '<div class="content">Привет, мир!</div>';
$parser = new Parser($html);
```

### 2. Запуск парсинга {#run-parsing}

Метод `parse()` запускает процесс и возвращает объект документа ([`DocumentInterface`](./04-appendix--02-api-reference.md#documentinterface)):

```php
$document = $parser->parse(); // комментарии не сохраняются
// или с сохранением комментариев:
$document = $parser->parse(true);
```

### 3. Получение элементов {#get-elements}

Документ содержит корневые элементы, доступные через `getChildren()`, который возвращает [`ElementListInterface`](./04-appendix--02-api-reference.md#elementlistinterface):

```php
$rootElements = $document->getChildren();
$firstElement = $rootElements->get(0); // первый элемент
```

## Примеры кода {#code-examples}

### Пример 1: Базовый парсинг {#example-basic}

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

### Пример 2: Работа со ссылкой и фрагментами {#example-fragments}

Этот пример демонстрирует ключевую особенность библиотеки — [схлопывание строчных элементов](./02-core--03-utilities.md#inline-collapser):

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

// Информация о форматировании сохранена в fragments
$fragments = $element->getFragments(); // RichTextFragmentListInterface
foreach ($fragments as $fragment) {
    echo 'Тип: ' . $fragment->getType() . PHP_EOL;
    echo 'Начало: ' . $fragment->getStart() . PHP_EOL;
    echo 'Конец: ' . $fragment->getEnd() . PHP_EOL;
    echo 'Атрибуты: ' . print_r($fragment->getAttributes(), true) . PHP_EOL;
}
// Или для компактного вывода:
// print_r($fragments->toArray());
```

Подробнее о фрагментах форматирования читайте в разделе [Модель данных элемента](./02-core--01-data-model.md#fragments).

### Пример 3: Поиск всех ссылок {#example-filtering}

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

### Пример 4: Сохранение комментариев {#example-comments}

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

## Заключение {#conclusion}

Вы освоили базовые операции с HtmlDomParser. Теперь вы умеете:

- Создавать парсер и парсить HTML
- Получать элементы и их атрибуты
- Работать с текстом и фрагментами форматирования
- Фильтровать элементы по условию
- Сохранять комментарии при необходимости

## Следующие шаги {#next-steps}

- [Система контекстов](./02-core--02-context-system.md) — как это работает внутри
- [Утилиты](./02-core--03-utilities.md) — ContextDataResolver и InlineCollapser
- [Модель данных элемента](./02-core--01-data-model.md) — подробнее о Data, Label и Fragments

## Связанные разделы {#see-also}

- [Введение](./01-general-information--01-introduction.md)
- [Установка](./01-general-information--02-installation.md)
- [Справочник API](./04-appendix--02-api-reference.md)