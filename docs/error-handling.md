[← К оглавлению](index.md)

## ErrorHandler и ErrorElement

### Назначение
Обеспечивают централизованный сбор ошибок парсинга и их представление в результирующем дереве документа. Позволяют гибко настраивать реакцию на различные типы проблем (логирование, игнорирование, прерывание).

### Компоненты

#### `ErrorHandler`
Класс `HtmlDomParser\Core\ErrorHandler` отвечает за:
- хранение всех ошибок, возникших в процессе парсинга;
- управление поведением при ошибках разных уровней (исключение или продолжение);
- предоставление интерфейса для анализа ошибок.

##### Свойства и настройки
- `$errors` – массив объектов `HtmlDomParser\Core\Node\ErrorElement`.
- Флаги `throwOnError`, `throwOnWarning`, `throwOnNotice` (или единый метод `setThrowOnLevel`).

##### Основные методы
- `addError(HtmlDomParser\Core\Node\ErrorElement $error): void` – добавляет ошибку. Если уровень соответствует настроенному `throwOn...`, выбрасывает исключение `HtmlDomParserException`.
- `getErrors(): array` – возвращает все ошибки.
- `getErrorsBySeverity(string $severity): array` – фильтрует ошибки по уровню.
- `hasErrors(): bool` – проверяет наличие любых ошибок.
- `hasFatalErrors(): bool` – проверяет наличие ошибок уровня `ERROR`.
- `setThrowOnError(bool $throw): self`, `setThrowOnWarning(bool $throw): self`, `setThrowOnNotice(bool $throw): self` – настройка поведения.

По умолчанию исключение выбрасывается только для уровня `ERROR`.

#### `ErrorElement`
Класс `HtmlDomParser\Core\Node\ErrorElement` наследует `HtmlDomParser\Core\Node\Element` и представляет проблемный узел в дереве документа.

##### Структура
- `$name` – всегда `"error"`.
- `$label` – человекочитаемое описание ошибки.
- `$data` – массив `[severity, errorType]` (например, `['notice', 'missingRule']`).
- `$children` – ErrorElement не может содержать дочерних узлов; его коллекция $children всегда пуста.
<!-- - `$children` – backtrace в стандартном PHP-формате (результат `debug_backtrace`). -->
- `$attributes` – оригинальные атрибуты тега, вызвавшего ошибку (если применимо).
Узел-ошибка всегда является листовым и не содержит дочерних элементов. Свойство $children (унаследованное от Element) представляет собой пустой ElementList.


##### Константы уровней
- `SEVERITY_NOTICE`
- `SEVERITY_WARNING`
- `SEVERITY_ERROR`

##### Методы доступа
- `getSeverity(): string`
- `getErrorType(): string`
- `getBacktrace(): array`
- `getChildNodes(): array` (всегда пустой массив)
- `hasChildNodes(): bool` (всегда `false`)

##### Фабричные методы
`HtmlDomParser\Core\Node\ErrorElement` предоставляет статические методы для создания типовых ошибок:
- `missingRule(\DOMNode $node, array $attributes): self` (`NOTICE`)
- `disallowedChild(\DOMNode $child, NodeContext $context): self` (`WARNING`)
- `recursionLimitExceeded(\DOMNode $node): self` (`ERROR`, с backtrace)
- `invalidCallback(\DOMNode $node, \Throwable $e): self` (`WARNING`/`ERROR`)
- `missingAttribute(\DOMNode $node, string $attrName): self` (`NOTICE`)
- `dataResolutionFailed(\DOMNode $node): self` (`WARNING`)
- `libxmlWarning(\LibXMLError $xmlError): self` (`NOTICE`)
- `htmlLoadFailed(string $html): self` (`ERROR`)
- `unknownError(\Throwable $e): self` (`ERROR`)

### Интеграция с парсером
- `ErrorHandler` создаётся в конструкторе `Parser` и доступен через `getErrorHandler()`.
- При возникновении проблемы в любом компоненте (`loadHTML`, `processNode`, `DataResolver`, `InlineCollapser`) создаётся соответствующий `ErrorElement`, который:
  - добавляется в `ErrorHandler` через `addError()`;
  - возвращается из `processNode()` (или иного места) и замещает проблемный узел в дереве.
- Если уровень ошибки требует исключения, `ErrorHandler` выбрасывает `HtmlDomParserException`, содержащее ссылку на `ErrorElement`.

### Примеры использования

#### Настройка поведения перед парсингом
```php
$parser = new Parser($html);
$handler = $parser->getErrorHandler();
$handler->setThrowOnWarning(true);   // прерываться при WARNING
$handler->setThrowOnNotice(false);   // игнорировать NOTICE (только сбор)
```

#### Анализ ошибок после парсинга
```php
$document = $parser->parse(); // может выбросить исключение, если throwOnError = true

if ($handler->hasErrors()) {
    foreach ($handler->getErrorsBySeverity(HtmlDomParser\Core\Node\ErrorElement::SEVERITY_WARNING) as $warning) {
        echo "Warning: " . $warning->getLabel() . "\n";
        // можно также получить backtrace, тип, оригинальные атрибуты
    }
}
```

#### Получение `ErrorElement` из дерева
```php
$root = $document->getRootElement();
if ($root instanceof HtmlDomParser\Core\Node\ErrorElement) {
    // корневой элемент оказался ошибкой (например, пустой HTML)
}
```

### Источники ошибок и их уровни

| Компонент | Ситуация | Уровень |
|-----------|----------|---------|
| Загрузка HTML | пустая строка | `ERROR` |
| | критический сбой `DOMDocument::loadHTML` | `ERROR` |
| | ошибка libxml | `NOTICE` |
| Рекурсивный обход | отсутствие правила для тега | `NOTICE` |
| | запрещённый дочерний элемент | `WARNING` |
| | превышение глубины рекурсии | `ERROR` |
| | ошибка в пользовательском callback модуля | `WARNING`/`ERROR` |
| DataResolver | отсутствие обязательного атрибута | `NOTICE` |
| | невозможность определить `data` | `WARNING` |
| InlineCollapser | проблема с сущностями при схлопывании | `WARNING` |