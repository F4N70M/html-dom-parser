[![Latest Version on Packagist](https://img.shields.io/packagist/v/f4n70m/html-dom-parser.svg?style=flat-square)](https://packagist.org/packages/f4n70m/html-dom-parser)
[![PHP Version](https://img.shields.io/packagist/php-v/f4n70m/html-dom-parser.svg?style=flat-square)](https://packagist.org/packages/f4n70m/html-dom-parser)
[![License](https://img.shields.io/github/license/f4n70m/html-dom-parser.svg?style=flat-square)](https://github.com/f4n70m/html-dom-parser/blob/main/LICENSE)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white)

**HtmlDomParser** — это PHP-библиотека для продвинутого парсинга HTML, которая преобразует исходный код в удобное объектное дерево с богатой семантической информацией. Это не просто очередной парсер, а полноценный фреймворк для построения семантически-обогащённого DOM-дерева.

## ✨ Особенности

- **Контекстная обработка узлов** – каждый узел обрабатывается с учётом его роли в документе (блочный, строчный, фразовый и т.д.).
- **Схлопывание строчного контента** – объединение последовательности строчных элементов (текст, выделения, ссылки) в один элемент с единым текстом и сущностями форматирования.
- **Расширяемость через события и модули** – подписывайтесь на ключевые этапы парсинга и модифицируйте результат.
- **Продвинутая обработка ошибок** – классификация ошибок по уровням, узлы-ошибки в дереве, настройка поведения (исключения / сбор).
- **Чистая объектная модель** – удобные интерфейсы для навигации, фильтрации и трансформации дерева.

## 📦 Установка

```bash
composer require f4n70m/html-dom-parser
```

## 🚀 Быстрый старт

```php
require_once 'vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '<div>Привет, <a href="https://example.com">мир</a>!</div>';
$parser = new Parser($html);
$document = $parser->parse();

$element = $document->getChildren()->get(0);
echo $element->getLabel(); // "Привет, мир!"

foreach ($element->getEntities() as $entity) {
    echo $entity->getType() . ': ' . $entity->getStart() . '-' . $entity->getEnd();
}
// a: 7-10
```

## 📚 Документация

### 1. Общая информация
- [**Введение**](docs/01-general-information--01-introduction.md) – обзор концепций и возможностей библиотеки.
- [**Установка и начало работы**](docs/01-general-information--02-installation.md) – системные требования, установка через Composer, проверка.
- [**Быстрый старт**](docs/01-general-information--03-quick-start.md) – первые примеры парсинга и работы с данными.

### 2. Ядро системы
- [**Модель данных элемента (Data, Label, Entities)**](docs/02-core--01-data-model.md) – подробно о трёх ключевых свойствах элементов.
- [**Система контекстов**](docs/02-core--02-context-system.md) – временные объекты контекста, жизненный цикл узла, типы контекста.
- [**Утилиты**](docs/02-core--03-utilities.md) – `DataResolver` (извлечение основного содержимого) и `InlineCollapser` (схлопывание строчных элементов).
- [**Обработка ошибок**](docs/02-core--04-error-handling.md) – уровни ошибок, узлы-ошибки, настройка поведения.

### 3. События и модули
- [**Событийная модель**](docs/03-events-modules--01-event-system.md) – подписка на события, жизненный цикл событий, примеры.
- [**Система модулей**](docs/03-events-modules--02-modules.md) – создание модулей, обнаружение через Composer, зависимости, конфигурация.

### 4. Информация
- [**FAQ и решение проблем**](docs/04-appendix--01-faq.md) – ответы на частые вопросы и типичные сложности.
- [**Справочник API**](docs/04-appendix--02-api-reference.md) – полный перечень всех интерфейсов с методами (единый источник).

## 🧩 Примеры использования

Больше примеров можно найти в документации, особенно в разделах «Быстрый старт», «Модель данных» и «Утилиты». Вот ещё один фрагмент, демонстрирующий фильтрацию элементов:

```php
$links = $div->getChildren()->filter(fn($el) => $el->getName() === 'a');
foreach ($links as $link) {
    echo $link->getAttribute('href') . ': ' . $link->getLabel() . "\n";
}
```

## 🛠 Требования

- PHP 7.4+
- Расширение `ext-dom`
- Composer

## 📄 Лицензия

Этот проект распространяется под лицензией MIT. См. файл [LICENSE](LICENSE).

## 🤝 Сообщество

- Сообщить об ошибке или предложить улучшение: [GitHub Issues](https://github.com/f4n70m/html-dom-parser/issues)
- Пакет на Packagist: [f4n70m/html-dom-parser](https://packagist.org/packages/f4n70m/html-dom-parser)