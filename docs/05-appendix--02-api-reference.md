[← К оглавлению](../README.md#📖-документация)

# Справочник API

Краткий справочник всех интерфейсов и методов для быстрого поиска. Подробное описание каждого интерфейса можно найти в соответствующих разделах документации.

## Содержание

- [ParserInterface](#parserinterface)
- [NodeInterface](#nodeinterface)
- [DocumentInterface](#documentinterface)
- [ElementInterface](#elementinterface)
- [ElementListInterface](#elementlistinterface)
- [NodeContextInterface](#nodecontextinterface)
- [ContextConverterInterface](#contextconverterinterface)
- [TagContextMapInterface](#tagcontextmapinterface)
- [ErrorHandlerInterface](#errorhandlerinterface)
- [ErrorElementInterface](#errorelementinterface)
- [EventDispatcherInterface](#eventdispatcherinterface)
- [ModuleInterface](#moduleinterface)
- [ModuleManagerInterface](#modulemanagerinterface)
- [DataResolverInterface](#dataresolverinterface)
- [InlineCollapserInterface](#inlinecollapserinterface)
- [Трейты констант](#трейты-констант)

---

## ParserInterface

Главный оркестратор, управляющий процессом парсинга.

```php
namespace HtmlDomParser\Contract;

interface ParserInterface
{
    public function __construct(string $html);
    public function parse(bool $keepComments = false): DocumentInterface;
    public function getErrorHandler(): ErrorHandlerInterface;
    public function getModuleManager(): ModuleManagerInterface;
}
```

**Подробнее:** [Ядро системы](./02-core-components--01-core-interfaces.md#parserinterface)

---

## NodeInterface

Базовый интерфейс для всех узлов дерева.

```php
namespace HtmlDomParser\Contract;

interface NodeInterface
{
    public function getName(): string;
    public function getAttributes(): array;
    public function getAttribute(string $name): mixed;
    public function hasAttribute(string $name): bool;
    public function setAttribute(string $name, $value): void;
    public function removeAttribute(string $name): void;
}
```

**Подробнее:** [Ядро системы](./02-core-components--01-core-interfaces.md#nodeinterface)

---

## DocumentInterface

Корневой объект документа.

```php
namespace HtmlDomParser\Contract;

interface DocumentInterface extends NodeInterface
{
    public function getChildren(): ElementListInterface;
}
```

**Подробнее:** [Ядро системы](./02-core-components--01-core-interfaces.md#documentinterface)

---

## ElementInterface

Основной тип узла для HTML-тегов.

```php
namespace HtmlDomParser\Contract;

interface ElementInterface extends NodeInterface
{
    public function getData(): mixed;
    public function setData($value): void;
    public function getLabel(): string;
    public function setLabel(string $label): void;
    public function getEntities(): array;
    public function addEntity(array $entity): void;
    public function isInline(): bool;
    public function getChildren(): ElementListInterface;
    public function hasChildren(): bool;
    public function getContextType(): int;
}
```

**Подробнее:** 
- [Ядро системы](./02-core-components--01-core-interfaces.md#elementinterface)
- [Модель данных](./02-core-components--03-data-model.md)

---

## ElementListInterface

Коллекция элементов для навигации по дереву.

```php
namespace HtmlDomParser\Contract;

interface ElementListInterface extends \IteratorAggregate, \Countable
{
    public function push(ElementInterface $element): void;
    public function get(int $index): ?ElementInterface;
    public function count(): int;
    public function getIterator(): \ArrayIterator;
    public function toArray(): array;
    public function toJson(): string;
    public function filter(callable $callback): ElementListInterface;
    public function map(callable $callback): array;
}
```

**Подробнее:** [Работа с документом](./02-core-components--02-working-with-document.md)

---

## NodeContextInterface

Временный контекст узла в процессе парсинга.

```php
namespace HtmlDomParser\Contract;

use DOMNode;

interface NodeContextInterface
{
    public function __construct(DOMNode $domNode, int $parentContextType);
    public function getNode(): DOMNode;
    public function getName(): string;
    public function getParent(): ?NodeContextInterface;
    public function getChildren(): ElementListInterface;
    public function setChildren(ElementListInterface $children): void;
    public function getData(): mixed;
    public function setData($data): void;
    public function isVoid(): bool;
    public function isInclude(): bool;
    public function isContainer(): bool;
    public function isPhrase(): bool;
    public function isInline(): bool;
    public function isTransparent(): bool;
    public function isRawText(): bool;
    public function isEscapable(): bool;
    public function getAllowedTags(): mixed;
    public function allChildrenIsInline(): bool;
}
```

> **Примечание:** Создание элемента из контекста выполняется через `ContextConverterInterface::contextToElement()`, а не через метод контекста.

**Подробнее:** [Система контекстов](./03-advanced-architecture--01-context-system.md)

---

## ContextConverterInterface

Преобразование DOM-узлов в контексты и контекстов в элементы.

```php
namespace HtmlDomParser\Contract;

interface ContextConverterInterface
{
    use ContextTypeConstantsTrait;
    
    public function nodeToContext(\DOMNode $node, int $parentContextType): NodeContextInterface;
    public function contextToElement(NodeContextInterface $nodeContext): ElementInterface;
}
```

**Подробнее:** [Система контекстов](./03-advanced-architecture--01-context-system.md#contextconverterinterface)

---

## TagContextMapInterface

Карта правил для HTML-тегов.

```php
namespace HtmlDomParser\Contract;

interface TagContextMapInterface
{
    use ContextTypeConstantsTrait;
    
    public function get(string $tag): array;
    public function has(string $tag): bool;
}
```

**Возвращаемый массив:**
```php
[
    'thisContextType' => int,      // тип текущего узла
    'childrenContextType' => int,  // тип для дочерних узлов
    'allowedTags' => array,        // разрешенные теги-потомки
    'isInline' => bool,
    'isRawText' => bool,
    'isEscapable' => bool
]
```

**Подробнее:** [Система контекстов](./03-advanced-architecture--01-context-system.md#tagcontextmapinterface)

---

## ErrorHandlerInterface

Обработчик ошибок парсинга.

```php
namespace HtmlDomParser\Contract;

interface ErrorHandlerInterface
{
    use ErrorConstantsTrait;
    
    public function addError(ErrorElementInterface $error): void;
    public function getErrors(): array;
    public function getErrorsBySeverity(string $severity): array;
    public function hasErrors(): bool;
    public function hasFatalErrors(): bool;
    public function setThrowOnError(bool $throw): self;
    public function setThrowOnWarning(bool $throw): self;
    public function setThrowOnNotice(bool $throw): self;
}
```

**Подробнее:** [Обработка ошибок](./03-advanced-architecture--04-error-handling.md)

---

## ErrorElementInterface

Узел-ошибка, расширяет обычный элемент.

```php
namespace HtmlDomParser\Contract;

interface ErrorElementInterface extends ElementInterface
{
    use ErrorConstantsTrait;
    
    public function getSeverity(): string;
    public function getErrorType(): string;
    public function getBacktrace(): array;
    public function getOriginalAttributes(): array;
    public function isFatal(): bool;
}
```

**Подробнее:** [Обработка ошибок](./03-advanced-architecture--04-error-handling.md#errorelementinterface)

---

## EventDispatcherInterface

Диспетчер событий для модульной системы.

```php
namespace HtmlDomParser\Contract;

interface EventDispatcherInterface
{
    public function subscribe(string $event, callable $handler, int $priority = 0): void;
    public function dispatch(string $event, NodeContextInterface $context): NodeContextInterface;
    public function hasListeners(string $event): bool;
    public function clearListeners(string $event): void;
}
```

**Подробнее:** [Событийная модель](./03-advanced-architecture--02-event-system.md)

---

## ModuleInterface

Базовый интерфейс для всех модулей.

```php
namespace HtmlDomParser\Contract;

interface ModuleInterface
{
    public function getName(): string;
    public function getDependencies(): array;
    public function supportsCoreVersion(string $version): bool;
    public function initialize(EventDispatcherInterface $dispatcher): void;
}
```

**Подробнее:** [Система модулей](./03-advanced-architecture--03-modules.md#moduleinterface)

---

## ModuleManagerInterface

Менеджер для обнаружения и загрузки модулей.

```php
namespace HtmlDomParser\Contract;

interface ModuleManagerInterface
{
    public function discover(): array;
    public function loadModules(): void;
    public function getModule(string $name): ?ModuleInterface;
    public function hasModule(string $name): bool;
    public function getLoadedModules(): array;
    public function registerModule(ModuleInterface $module): void;
}
```

**Подробнее:** [Система модулей](./03-advanced-architecture--03-modules.md#modulemanagerinterface)

---

## DataResolverInterface

Извлечение основного содержимого из DOM-узла.

```php
namespace HtmlDomParser\Contract;

interface DataResolverInterface
{
    public function resolve(\DOMNode $node): mixed;
}
```

**Подробнее:** [DataResolver](./04-utilities--01-data-resolver.md)

---

## InlineCollapserInterface

Схлопывание строчных элементов.

```php
namespace HtmlDomParser\Contract;

interface InlineCollapserInterface
{
    public function collapse(NodeContextInterface $context, array $options = []): NodeContextInterface;
}
```

**Подробнее:** [InlineCollapser](./04-utilities--02-inline-collapser.md)

---

## Трейты констант

### ContextTypeConstantsTrait

```php
trait ContextTypeConstantsTrait
{
    const INLINE    = 1; // Фразовый элемент (строчный)
    const PHRASE    = 2; // Фразовый контейнер
    const CONTAINER = 3; // Потоковый контейнер (блочный)
    const VOID      = 0; // Пустой элемент
    const SKIP      = -1; // Пропустить элемент
    const ROOT      = 4; // Корень
}
```

### ErrorConstantsTrait

```php
trait ErrorConstantsTrait
{
    const SEVERITY_NOTICE  = 'notice';  // Уведомление
    const SEVERITY_WARNING = 'warning'; // Предупреждение
    const SEVERITY_ERROR   = 'error';   // Фатальная ошибка
}
```

---

## Связанные разделы

- [Введение](./01-getting-started--01-introduction.md)
- [Ядро системы](./02-core-components--01-core-interfaces.md)
- [Система контекстов](./03-advanced-architecture--01-context-system.md)
- [Обработка ошибок](./03-advanced-architecture--04-error-handling.md)
- [Событийная модель](./03-advanced-architecture--02-event-system.md)
- [Система модулей](./03-advanced-architecture--03-modules.md)
- [DataResolver](./04-utilities--01-data-resolver.md)
- [InlineCollapser](./04-utilities--02-inline-collapser.md)