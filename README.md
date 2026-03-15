# HtmlDomParser

[![Latest Version](https://img.shields.io/packagist/v/f4n70m/html-dom-parser)](https://packagist.org/packages/f4n70m/html-dom-parser)
[![Total Downloads](https://img.shields.io/packagist/dt/f4n70m/html-dom-parser)](https://packagist.org/packages/f4n70m/html-dom-parser)
[![License](https://img.shields.io/packagist/l/f4n70m/html-dom-parser)](LICENSE)
![PHP](https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white)

**HtmlDomParser** — это PHP-библиотека для продвинутого парсинга HTML, которая преобразует исходный код в удобное семантически-обогащенное объектное дерево.

## ✨ Особенности

- **🧠 Контекстная обработка узлов** — каждый DOM-узел обрабатывается с учётом его типа (строчный, блочный, пустой) и правил вложенности.
- **🔗 Схлопывание строчного контента** — объединяет последовательности строчных элементов (текст, выделения, ссылки) в один элемент с единым текстом и картой форматирования (entities).
- **🧩 Расширяемость через события и модули** — в ключевых точках обработки генерируются события, позволяющие модифицировать контекст. Модули оформляются как Composer-пакеты и автоматически подключаются.
- **⚠️ Продвинутая обработка ошибок** — централизованный сбор, классификация ошибок (notice/warning/error) и настройка поведения (исключения или продолжение работы).
- **📦 Чистая объектная модель** — удобная навигация по дереву, фильтрация, сортировка, работа с атрибутами, данными и форматированием.

## 📦 Установка

```bash
composer require f4n70m/html-dom-parser
```

Требования:
- PHP 7.4 или 8.0+
- Расширение `ext-dom`

## 🚀 Быстрый старт

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '<div>Привет, <a href="https://example.com">мир</a>!</div>';
$parser = new Parser($html);
$document = $parser->parse();

$element = $document->getChildren()->get(0);
echo $element->getLabel(); // "Привет, мир!"
print_r($element->getEntities());
// [
//   ['type' => 'a', 'start' => 7, 'end' => 10, 'attributes' => ['href' => 'https://example.com']]
// ]
```

## 📖 Документация

Вся документация находится в папке [`docs/`](docs/). Рекомендуется начать с введения и быстрого старта.

### Начало работы
- [Введение](docs/01-getting-started--01-introduction.md)
- [Установка](docs/01-getting-started--02-installation.md)
- [Быстрый старт](docs/01-getting-started--03-quick-start.md)

### Основные компоненты
- [Ядро системы (интерфейсы)](docs/02-core-components--01-core-interfaces.md)
- [Работа с документом и навигация](docs/02-core-components--02-working-with-document.md)
- [Модель данных: Data, Label, Entities](docs/02-core-components--03-data-model.md)

### Продвинутая архитектура
- [Система контекстов](docs/03-advanced-architecture--01-context-system.md)
- [Событийная модель](docs/03-advanced-architecture--02-event-system.md)
- [Система модулей](docs/03-advanced-architecture--03-modules.md)
- [Обработка ошибок](docs/03-advanced-architecture--04-error-handling.md)

### Утилиты и сервисы
- [DataResolver – извлечение Data](docs/04-utilities--01-data-resolver.md)
- [InlineCollapser – схлопывание строчных элементов](docs/04-utilities--02-inline-collapser.md)

### Приложения
- [FAQ и решение проблем](docs/05-appendix--01-faq-troubleshooting.md)
- [Справочник API](docs/05-appendix--02-api-reference.md)

## 🔧 Примеры использования

### Получение всех ссылок

```php
$links = $document->getChildren()->filter(fn($el) => $el->getName() === 'a');
foreach ($links as $link) {
    echo $link->getData() . ': ' . $link->getLabel() . "\n";
}
```

### Работа с сущностями форматирования

```php
$text = $element->getLabel(); // "жирный и курсивный текст"
$entities = $element->getEntities(); 
// [
//   ['type' => 'b', 'start' => 0, 'end' => 6],
//   ['type' => 'i', 'start' => 10, 'end' => 18]
// ]
```

## 🧩 Модули

Библиотека поддерживает модули, которые автоматически обнаруживаются через Composer. Для создания своего модуля реализуйте `ModuleInterface` и добавьте секцию `extra.modules` в `composer.json`. Подробнее в разделе [Система модулей](docs/03-advanced-architecture--03-modules.md).

## ⚠️ Обработка ошибок

Обработчик ошибок (`ErrorHandlerInterface`) позволяет собирать, классифицировать и реагировать на ошибки парсинга. Например:

```php
$errorHandler = $parser->getErrorHandler();
$errorHandler->setThrowOnError(true); // бросать исключение при фатальных ошибках

if ($errorHandler->hasErrors()) {
    foreach ($errorHandler->getErrors() as $error) {
        echo $error->getSeverity() . ': ' . $error->getErrorType() . "\n";
    }
}
```

Подробнее в разделе [Обработка ошибок](docs/03-advanced-architecture--04-error-handling.md).

## 🤝 Участие в разработке

1. Форкните репозиторий.
2. Создайте ветку для фичи (`git checkout -b feature/amazing-feature`).
3. Зафиксируйте изменения (`git commit -m 'Add amazing feature'`).
4. Отправьте изменения в форк (`git push origin feature/amazing-feature`).
5. Откройте Pull Request.

## 📄 Лицензия

MIT License. Смотрите файл [LICENSE](LICENSE).

## 👥 Авторы

- Ваше Имя – [@yourusername](https://github.com/yourusername)
- [Список всех контрибьюторов](https://github.com/yourvendor/html-dom-parser/contributors)