[← К оглавлению](../README.md#-документация)

# Обработка ошибок {#error-handling}

В этом разделе рассматривается централизованная система сбора, классификации и настройки поведения при ошибках парсинга.

## Общая концепция {#overview}

HtmlDomParser предоставляет продвинутую систему обработки ошибок, которая:

- Перехватывает все ошибки в процессе парсинга (отсутствие правила для тега, запрещенный дочерний элемент, проблемы с загрузкой)
- Классифицирует ошибки по уровням серьезности
- Создает специальные **узлы-ошибки**, замещающие проблемные HTML-узлы в дереве
- Позволяет настраивать поведение для каждого уровня ошибок
- Дает возможность анализировать ошибки после парсинга

## Уровни серьезности {#severity-levels}

Библиотека использует трейт `ErrorConstantsTrait` для определения уровней серьезности ошибок. Полный список констант доступен в [Справочнике API](./04-appendix--02-api-reference.md#трейты-констант).

| Уровень | Константа | Описание | Примеры |
| :--- | :--- | :--- | :--- |
| **Notice** | `SEVERITY_NOTICE` | Уведомление, не влияет на результат | Незначительные отклонения от стандарта |
| **Warning** | `SEVERITY_WARNING` | Предупреждение, возможны проблемы | Запрещенный дочерний элемент, устаревший тег |
| **Error** | `SEVERITY_ERROR` | Фатальная ошибка, результат может быть некорректен | Отсутствие правила для тега, ошибка загрузки HTML |

## Компоненты системы ошибок {#components}

### ErrorHandlerInterface {#errorhandlerinterface}

Обработчик ошибок ([`ErrorHandlerInterface`](./04-appendix--02-api-reference.md#errorhandlerinterface)) собирает все ошибки в процессе парсинга и управляет поведением (выброс исключений). Доступ к нему осуществляется через метод [`ParserInterface::getErrorHandler()`](./04-appendix--02-api-reference.md#parserinterface).

```php
$errorHandler = $parser->getErrorHandler();
```

### ErrorElementInterface {#errorelementinterface}

Узел-ошибка ([`ErrorElementInterface`](./04-appendix--02-api-reference.md#errorelementinterface)) расширяет обычный элемент ([`ElementInterface`](./04-appendix--02-api-reference.md#elementinterface)) и добавляет методы для получения информации об ошибке. Такие узлы замещают проблемные HTML-узлы в итоговом дереве и имеют имя `#error`.

### Типы ошибок {#error-types}

| Тип ошибки | Описание | Уровень |
| :--- | :--- | :--- |
| `missingRule` | Отсутствует правило для тега в карте контекстов | `ERROR` |
| `disallowedChild` | Запрещенный дочерний элемент внутри родителя | `WARNING` |
| `loadError` | Ошибка загрузки HTML в DOMDocument | `ERROR` |
| `voidHasChildren` | Void-элемент содержит дочерние узлы | `WARNING` |
| `unknown` | Неизвестная ошибка | `NOTICE` |

## Настройка поведения {#configuration}

### Поведение по умолчанию {#default-behavior}

По умолчанию библиотека:
- Собирает все ошибки в обработчике
- Продолжает парсинг даже при фатальных ошибках
- Замещает проблемные узлы узлами-ошибками
- **Не выбрасывает исключения**

### Настройка исключений {#exception-configuration}

Вы можете настроить, при каких уровнях ошибок выбрасывать исключение [`HtmlDomParserException`](./04-appendix--02-api-reference.md#исключения):

```php
$errorHandler = $parser->getErrorHandler();

// Бросать исключение только при фатальных ошибках
$errorHandler->setThrowOnError(true);

// Бросать исключение при любых ошибках (включая уведомления)
$errorHandler
    ->setThrowOnError(true)
    ->setThrowOnWarning(true)
    ->setThrowOnNotice(true);

// Отключить все исключения (поведение по умолчанию)
$errorHandler
    ->setThrowOnError(false)
    ->setThrowOnWarning(false)
    ->setThrowOnNotice(false);
```

## Примеры кода {#examples}

### Пример 1: Базовая проверка ошибок {#example-basic}

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$html = '<div><unknown>Тег</unknown></div>';
$parser = new Parser($html);
$document = $parser->parse();

$errorHandler = $parser->getErrorHandler();

if ($errorHandler->hasErrors()) {
    echo "Найдено ошибок: " . count($errorHandler->getErrors()) . PHP_EOL;
    
    foreach ($errorHandler->getErrors() as $error) {
        echo "Уровень: " . $error->getSeverity() . PHP_EOL;           // error
        echo "Тип: " . $error->getErrorType() . PHP_EOL;              // missingRule
        echo "Сообщение: " . $error->getLabel() . PHP_EOL;            // "Отсутствует правило для тега unknown"
        
        if ($error->getOriginalAttributes()) {
            echo "Оригинальные атрибуты: " . print_r($error->getOriginalAttributes(), true) . PHP_EOL;
        }
        
        // Получение backtrace для отладки
        $trace = $error->getBacktrace();
        echo "Возник в: " . $trace[0]['file'] . ':' . $trace[0]['line'] . PHP_EOL;
        
        echo "---" . PHP_EOL;
    }
}
```

### Пример 2: Настройка исключений {#example-exceptions}

```php
<?php

use HtmlDomParser\Parser;
use HtmlDomParser\Exception\HtmlDomParserException;

$parser = new Parser($html);
$errorHandler = $parser->getErrorHandler();

// Настраиваем: фатальные ошибки прерывают выполнение
$errorHandler->setThrowOnError(true);

try {
    $document = $parser->parse();
    echo "Парсинг завершен успешно" . PHP_EOL;
} catch (HtmlDomParserException $e) {
    echo "Ошибка парсинга: " . $e->getMessage() . PHP_EOL;
    
    // Можно получить все ошибки, даже если было исключение
    foreach ($errorHandler->getErrors() as $error) {
        echo "- " . $error->getErrorType() . ": " . $error->getLabel() . PHP_EOL;
    }
}
```

### Пример 3: Анализ фатальных ошибок {#example-fatal}

```php
<?php

$parser = new Parser($html);
$document = $parser->parse();

$errorHandler = $parser->getErrorHandler();

if ($errorHandler->hasFatalErrors()) {
    echo "Обнаружены фатальные ошибки!" . PHP_EOL;
    
    $fatalErrors = $errorHandler->getErrorsBySeverity(
        ErrorHandlerInterface::SEVERITY_ERROR
    );
    
    foreach ($fatalErrors as $error) {
        echo "Тип: " . $error->getErrorType() . PHP_EOL;
        echo "Сообщение: " . $error->getLabel() . PHP_EOL;
    }
}
```

### Пример 4: Поиск узлов-ошибок в дереве {#example-find-nodes}

```php
<?php

function findErrorNodes(ElementInterface $element): array
{
    $errors = [];
    
    // Проверяем текущий элемент
    if ($element->getName() === '#error') {
        $errors[] = $element;
    }
    
    // Рекурсивно проверяем детей
    if ($element->hasChildren()) {
        foreach ($element->getChildren() as $child) {
            $errors = array_merge($errors, findErrorNodes($child));
        }
    }
    
    return $errors;
}

$parser = new Parser($html);
$document = $parser->parse();

$errorNodes = findErrorNodes($document->getChildren()->get(0));

foreach ($errorNodes as $errorNode) {
    /** @var ErrorElementInterface $errorNode */
    echo "Найден узел-ошибка: " . $errorNode->getErrorType() . PHP_EOL;
    echo "Содержимое: " . $errorNode->getLabel() . PHP_EOL;
}
```

### Пример 5: Статистика ошибок по типам {#example-statistics}

```php
<?php

$errorHandler = $parser->getErrorHandler();
$allErrors = $errorHandler->getErrors();

// Группировка ошибок по типу
$errorsByType = [];
foreach ($allErrors as $error) {
    $type = $error->getErrorType();
    if (!isset($errorsByType[$type])) {
        $errorsByType[$type] = [];
    }
    $errorsByType[$type][] = $error;
}

// Вывод статистики
foreach ($errorsByType as $type => $errors) {
    echo $type . ': ' . count($errors) . ' ошибок' . PHP_EOL;
}
```

## Интеграция с модулями {#modules-integration}

Модули могут добавлять собственные ошибки через обработчик:

```php
class ValidationModule implements ModuleInterface
{
    private ErrorHandlerInterface $errorHandler;
    
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        // Получение обработчика ошибок (зависит от реализации)
        $this->errorHandler = $errorHandler;
        
        $dispatcher->subscribe('post-node', [$this, 'validateNode']);
    }
    
    public function validateNode(NodeContextInterface $context): NodeContextInterface
    {
        // Пользовательская валидация
        if ($context->getName() === 'img' && !$context->hasAttribute('alt')) {
            // Создание и добавление ошибки
            $error = $this->createError(
                'missingAlt',
                'Изображение не имеет alt-текста',
                ErrorConstantsTrait::SEVERITY_WARNING
            );
            
            $this->errorHandler->addError($error);
        }
        
        return $context;
    }
    
    private function createError(string $type, string $message, string $severity): ErrorElementInterface
    {
        // Создание узла-ошибки (упрощенно)
        // В реальности создается через соответствующий сервис
    }
}
```

## Возможные проблемы {#troubleshooting}

При работе с обработкой ошибок могут возникать типичные сложности: неожиданные исключения, потеря информации об ошибках, сложности с отладкой.

Подробное описание этих проблем и методы их решения вы найдете в разделе:

👉 **[FAQ: Проблемы с обработкой ошибок](./04-appendix--01-faq.md#проблемы-с-обработкой-ошибок)**

## Связанные разделы {#see-also}

- [Справочник API: ErrorHandlerInterface](./04-appendix--02-api-reference.md#errorhandlerinterface)
- [Справочник API: ErrorElementInterface](./04-appendix--02-api-reference.md#errorelementinterface)
- [Справочник API: ErrorConstantsTrait](./04-appendix--02-api-reference.md#трейты-констант)
- [Справочник API: ParserInterface](./04-appendix--02-api-reference.md#parserinterface)
- [Система контекстов](./02-core--02-context-system.md) — как возникают ошибки при обработке узлов
- [Система модулей](./03-events-modules--02-modules.md) — добавление ошибок из модулей