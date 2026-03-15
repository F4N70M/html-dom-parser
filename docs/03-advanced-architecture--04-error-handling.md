[← К оглавлению](../README.md#📖-документация)

# Обработка ошибок

В этом разделе рассматривается централизованная система сбора, классификации и настройки поведения при ошибках парсинга.

## Общая концепция

HtmlDomParser предоставляет продвинутую систему обработки ошибок, которая:

- Перехватывает все ошибки в процессе парсинга (отсутствие правила для тега, запрещенный дочерний элемент, проблемы с загрузкой)
- Классифицирует ошибки по уровням серьезности
- Создает специальные **узлы-ошибки**, замещающие проблемные HTML-узлы в дереве
- Позволяет настраивать поведение для каждого уровня ошибок
- Дает возможность анализировать ошибки после парсинга

## Уровни серьезности

Библиотека использует трейт `ErrorConstantsTrait` для определения уровней серьезности ошибок:

```php
trait ErrorConstantsTrait
{
    const SEVERITY_NOTICE  = 'notice';  // Уведомление, не влияет на результат
    const SEVERITY_WARNING = 'warning'; // Предупреждение, возможны незначительные проблемы
    const SEVERITY_ERROR   = 'error';   // Фатальная ошибка, результат может быть некорректен
}
```

| Уровень | Константа | Описание | Примеры |
| :--- | :--- | :--- | :--- |
| **Notice** | `SEVERITY_NOTICE` | Уведомление, не влияет на результат | Незначительные отклонения от стандарта |
| **Warning** | `SEVERITY_WARNING` | Предупреждение, возможны проблемы | Запрещенный дочерний элемент, устаревший тег |
| **Error** | `SEVERITY_ERROR` | Фатальная ошибка, результат может быть некорректен | Отсутствие правила для тега, ошибка загрузки HTML |

## API Reference

### ErrorHandlerInterface

```php
namespace HtmlDomParser\Contract;

interface ErrorHandlerInterface
{
    use ErrorConstantsTrait;

    /**
     * Добавляет ошибку в обработчик.
     *
     * @param ErrorElementInterface $error
     * @throws HtmlDomParserException Если уровень ошибки соответствует настроенному throwOn...
     */
    public function addError(ErrorElementInterface $error): void;

    /**
     * Возвращает все собранные ошибки.
     *
     * @return ErrorElementInterface[]
     */
    public function getErrors(): array;

    /**
     * Возвращает ошибки указанного уровня.
     *
     * @param string $severity Одна из констант SEVERITY_*
     * @return ErrorElementInterface[]
     */
    public function getErrorsBySeverity(string $severity): array;

    /**
     * Проверяет наличие любых ошибок.
     *
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * Проверяет наличие фатальных ошибок (уровня ERROR).
     *
     * @return bool
     */
    public function hasFatalErrors(): bool;

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня ERROR.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnError(bool $throw): self;

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня WARNING.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnWarning(bool $throw): self;

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня NOTICE.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnNotice(bool $throw): self;
}
```

### ErrorElementInterface

Узел-ошибка расширяет обычный элемент (`ElementInterface`) и добавляет методы для получения информации об ошибке:

```php
namespace HtmlDomParser\Contract;

interface ErrorElementInterface extends ElementInterface
{
    use ErrorConstantsTrait;

    /**
     * Возвращает уровень серьезности ошибки.
     *
     * @return string Одна из констант SEVERITY_*
     */
    public function getSeverity(): string;

    /**
     * Возвращает тип ошибки.
     *
     * @return string Например, 'missingRule', 'disallowedChild', 'loadError'
     */
    public function getErrorType(): string;

    /**
     * Возвращает backtrace в стандартном PHP-формате.
     *
     * @return array
     */
    public function getBacktrace(): array;

    /**
     * Возвращает оригинальные атрибуты тега, вызвавшего ошибку.
     *
     * @return array
     */
    public function getOriginalAttributes(): array;

    /**
     * Проверяет, является ли ошибка фатальной (уровня ERROR).
     *
     * @return bool
     */
    public function isFatal(): bool;
}
```

## Типы ошибок

| Тип ошибки | Описание | Уровень |
| :--- | :--- | :--- |
| `missingRule` | Отсутствует правило для тега в карте контекстов | `ERROR` |
| `disallowedChild` | Запрещенный дочерний элемент внутри родителя | `WARNING` |
| `loadError` | Ошибка загрузки HTML в DOMDocument | `ERROR` |
| `voidHasChildren` | Void-элемент содержит дочерние узлы | `WARNING` |
| `unknown` | Неизвестная ошибка | `NOTICE` |

## Настройка поведения

### Поведение по умолчанию

По умолчанию библиотека:
- Собирает все ошибки в обработчике
- Продолжает парсинг даже при фатальных ошибках
- Замещает проблемные узлы узлами-ошибками
- Не выбрасывает исключения

### Настройка исключений

Вы можете настроить, при каких уровнях ошибок выбрасывать исключение:

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

## Примеры кода

### Пример 1: Базовая проверка ошибок

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
        echo "Уровень: " . $error->getSeverity() . PHP_EOL;
        echo "Тип: " . $error->getErrorType() . PHP_EOL;
        echo "Сообщение: " . $error->getLabel() . PHP_EOL;
        
        if ($error->getOriginalAttributes()) {
            echo "Оригинальные атрибуты: " . print_r($error->getOriginalAttributes(), true) . PHP_EOL;
        }
        
        echo "---" . PHP_EOL;
    }
}
```

### Пример 2: Настройка исключений

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

### Пример 3: Анализ фатальных ошибок

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
        
        // Получение backtrace для отладки
        $trace = $error->getBacktrace();
        echo "Возник в: " . $trace[0]['file'] . ':' . $trace[0]['line'] . PHP_EOL;
    }
}
```

### Пример 4: Работа с узлами-ошибками в дереве

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
    // Приводим к интерфейсу ошибки
    $error = $errorNode; // уже реализует ErrorElementInterface
    
    echo "Найден узел-ошибка: " . $error->getErrorType() . PHP_EOL;
    echo "Содержимое: " . $error->getLabel() . PHP_EOL;
}
```

### Пример 5: Фильтрация ошибок по типу

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

## Интеграция с модулями

Модули могут добавлять собственные ошибки через обработчик:

```php
class ValidationModule implements ModuleInterface
{
    private ErrorHandlerInterface $errorHandler;
    
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        // Сохраняем ссылку на обработчик ошибок (должен быть доступен)
        $this->errorHandler = $errorHandler; // как получить — зависит от реализации
        
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
}
```

## Возможные проблемы

При работе с обработкой ошибок могут возникать типичные сложности: неожиданные исключения, потеря информации об ошибках, сложности с отладкой.

Подробное описание этих проблем и методы их решения вы найдете в разделе:

👉 **[FAQ: Проблемы с обработкой ошибок](./05-appendix--01-faq-troubleshooting.md#проблемы-с-обработкой-ошибок)**

## Связанные разделы

- [Ядро системы](./02-core-components--01-core-interfaces.md) — получение ErrorHandler через ParserInterface
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — как возникают ошибки при обработке узлов
- [Система модулей](./03-advanced-architecture--03-modules.md) — добавление ошибок из модулей
- [FAQ и решение проблем](./05-appendix--01-faq-troubleshooting.md) — подробное решение проблем