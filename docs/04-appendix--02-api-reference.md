[← К оглавлению](../README.md#-документация)

# Справочник API {#api-reference}

Краткий справочник всех интерфейсов и методов для быстрого поиска. Все интерфейсы находятся в пространстве имен `HtmlDomParser\Contract`. Подробное описание каждого интерфейса можно найти в соответствующих разделах документации.

## Содержание {#contents}

- [ParserInterface](#parserinterface)
- [NodeInterface](#nodeinterface)
- [DocumentInterface](#documentinterface)
- [ElementInterface](#elementinterface)
- [EntityInterface](#entityinterface)
- [ParserListInterface](#parserlistinterface)
- [ElementListInterface](#elementlistinterface)
- [EntityListInterface](#entitylistinterface)
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
- [Исключения](#исключения)

---

## ParserInterface {#parserinterface}

Главный оркестратор, управляющий процессом парсинга от загрузки HTML до возврата готового документа.

```php
namespace HtmlDomParser\Contract;

interface ParserInterface
{
    /**
     * Конструктор принимает HTML-строку.
     *
     * @param string $html Исходная HTML-строка.
     */
    public function __construct(string $html);

    /**
     * Запускает процесс парсинга и возвращает объект DocumentInterface.
     *
     * @param bool $keepComments Сохранять ли комментарии (по умолчанию false).
     * @return DocumentInterface
     * @throws HtmlDomParserException При фатальной ошибке, если включено соответствующее поведение.
     */
    public function parse(bool $keepComments = false): DocumentInterface;

    /**
     * Возвращает обработчик ошибок для настройки поведения и анализа.
     *
     * @return ErrorHandlerInterface
     */
    public function getErrorHandler(): ErrorHandlerInterface;

    /**
     * Возвращает менеджер модулей для доступа к загруженным модулям.
     *
     * @return ModuleManagerInterface
     */
    public function getModuleManager(): ModuleManagerInterface;
}
```

**Подробнее:** [Введение](./01-general-information--01-introduction.md)

---

## NodeInterface {#nodeinterface}

Базовый интерфейс для всех узлов дерева (документ, элементы, текстовые узлы, комментарии).

```php
namespace HtmlDomParser\Contract;

interface NodeInterface
{
    /**
     * Возвращает имя узла.
     *
     * @return string Например, 'div', '#text', '#document', '#comment'
     */
    public function getName(): string;

    /**
     * Возвращает все атрибуты узла.
     *
     * @return array Ассоциативный массив ['атрибут' => 'значение']
     */
    public function getAttributes(): array;

    /**
     * Возвращает значение атрибута по имени.
     *
     * @param string $name
     * @return mixed Значение атрибута или null, если атрибут не найден
     */
    public function getAttribute(string $name): mixed;

    /**
     * Проверяет наличие атрибута.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Устанавливает атрибут.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute(string $name, $value): void;

    /**
     * Удаляет атрибут.
     *
     * @param string $name
     */
    public function removeAttribute(string $name): void;
}
```

---

## DocumentInterface {#documentinterface}

Корневой объект документа, расширяет `NodeInterface`. Содержит всё дерево элементов.

```php
namespace HtmlDomParser\Contract;

interface DocumentInterface extends NodeInterface
{
    /**
     * Возвращает коллекцию дочерних элементов (корневые узлы документа).
     *
     * @return ElementListInterface
     */
    public function getChildren(): ElementListInterface;
}
```

> **Примечание:** Имя узла документа — `#document`, атрибуты отсутствуют.

---

## ElementInterface {#elementinterface}

Основной тип узла для HTML-тегов. Расширяет `NodeInterface` и добавляет семантические методы.

```php
namespace HtmlDomParser\Contract;

interface ElementInterface extends NodeInterface
{
    /**
     * Возвращает основное содержимое элемента.
     *
     * @return mixed Для ссылки — URL, для изображения — src, для текста — текст
     */
    public function getData(): mixed;

    /**
     * Устанавливает основное содержимое.
     *
     * @param mixed $value
     */
    public function setData($value): void;

    /**
     * Возвращает текстовую метку элемента.
     *
     * @return string Объединенный текст (например, текст ссылки)
     */
    public function getLabel(): string;

    /**
     * Устанавливает текстовую метку.
     *
     * @param string $label
     */
    public function setLabel(string $label): void;

    /**
     * Возвращает коллекцию сущностей форматирования.
     *
     * @return EntityListInterface
     */
    public function getEntities(): EntityListInterface;

    /**
     * Добавляет сущность форматирования.
     *
     * @param EntityInterface $entity
     */
    public function addEntity(EntityInterface $entity): void;

    /**
     * Проверяет, является ли элемент строчным (inline).
     *
     * @return bool
     */
    public function isInline(): bool;

    /**
     * Возвращает коллекцию дочерних элементов.
     *
     * @return ElementListInterface
     */
    public function getChildren(): ElementListInterface;

    /**
     * Проверяет наличие дочерних элементов.
     *
     * @return bool
     */
    public function hasChildren(): bool;

    /**
     * Возвращает тип контекста элемента.
     *
     * @return int Одна из констант ContextTypeConstantsTrait
     */
    public function getContextType(): int;
}
```

**Подробнее:** [Модель данных](./02-core--01-data-model.md)

---

## EntityInterface {#entityinterface}

Интерфейс сущности форматирования, представляющей часть текста, оформленную определённым тегом. Сущности создаются в процессе схлопывания строчных элементов.

```php
namespace HtmlDomParser\Contract;

interface EntityInterface
{
    /**
     * Возвращает тип форматирования (имя тега).
     *
     * @return string Например, 'b', 'i', 'a', 'strong'
     */
    public function getType(): string;

    /**
     * Возвращает позицию начала действия форматирования в тексте (индекс символа).
     *
     * @return int
     */
    public function getStart(): int;

    /**
     * Возвращает позицию окончания действия форматирования в тексте (индекс символа, не включая).
     *
     * @return int
     */
    public function getEnd(): int;

    /**
     * Возвращает все атрибуты, связанные с сущностью.
     *
     * @return array Ассоциативный массив вида ['атрибут' => 'значение']
     */
    public function getAttributes(): array;

    /**
     * Возвращает значение конкретного атрибута.
     *
     * @param string $name Имя атрибута
     * @return mixed Значение атрибута или null, если атрибут отсутствует
     */
    public function getAttribute(string $name): mixed;

    /**
     * Проверяет наличие атрибута.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Преобразует сущность в массив для обратной совместимости или сериализации.
     *
     * @return array Структура: ['type' => string, 'start' => int, 'end' => int, 'attributes' => array]
     */
    public function toArray(): array;
}
```

**Подробнее:** [Модель данных: Entities](./02-core--01-data-model.md#entities)

---

## ParserListInterface {#parserlistinterface}

Базовый интерфейс для всех коллекций библиотеки. Предоставляет единообразные методы для работы с коллекциями независимо от типа хранящихся объектов.

```php
namespace HtmlDomParser\Contract;

interface ParserListInterface extends \IteratorAggregate, \Countable
{
    /**
     * Возвращает количество элементов в коллекции.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Возвращает итератор для перебора элементов коллекции.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator;

    /**
     * Преобразует коллекцию в массив.
     *
     * @return array Массив хранящихся объектов (ElementInterface[], EntityInterface[] и т.д.)
     */
    public function toArray(): array;

    /**
     * Преобразует коллекцию в JSON-строку.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Применяет callback к каждому элементу коллекции и возвращает массив результатов.
     *
     * @param callable $callback function(объект_коллекции): mixed
     * @return array
     */
    public function map(callable $callback): array;
}
```

---

## ElementListInterface {#elementlistinterface}

Коллекция элементов для навигации по дереву. Наследует `ParserListInterface`.

```php
namespace HtmlDomParser\Contract;

interface ElementListInterface extends ParserListInterface
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
     * Возвращает новую коллекцию, отфильтрованную по callback.
     *
     * @param callable $callback function(ElementInterface $element): bool
     * @return ElementListInterface
     */
    public function filter(callable $callback): ElementListInterface;
}
```

---

## EntityListInterface {#entitylistinterface}

Коллекция сущностей форматирования. Наследует `ParserListInterface`.

```php
namespace HtmlDomParser\Contract;

interface EntityListInterface extends ParserListInterface
{
    /**
     * Добавляет сущность в конец списка.
     *
     * @param EntityInterface $entity
     */
    public function push(EntityInterface $entity): void;

    /**
     * Возвращает сущность по индексу.
     *
     * @param int $index
     * @return EntityInterface|null
     */
    public function get(int $index): ?EntityInterface;

    /**
     * Возвращает новую коллекцию, отфильтрованную по callback.
     *
     * @param callable $callback function(EntityInterface $entity): bool
     * @return EntityListInterface
     */
    public function filter(callable $callback): EntityListInterface;
}
```

---

## NodeContextInterface {#nodecontextinterface}

Временный контекст узла в процессе парсинга. Создается для каждого DOM-узла и хранит правила обработки, промежуточные результаты и ссылку на оригинальный узел.

```php
namespace HtmlDomParser\Contract;

use DOMNode;

interface NodeContextInterface
{
    /**
     * Создает контекст для DOM-узла.
     *
     * @param DOMNode $domNode
     * @param int $parentContextType Тип контекста родителя
     */
    public function __construct(DOMNode $domNode, int $parentContextType);

    /**
     * Возвращает оригинальный DOM-узел.
     *
     * @return DOMNode
     */
    public function getNode(): DOMNode;

    /**
     * Возвращает имя тега.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Возвращает родительский контекст.
     *
     * @return NodeContextInterface|null
     */
    public function getParent(): ?NodeContextInterface;

    /**
     * Возвращает коллекцию обработанных дочерних элементов.
     *
     * @return ElementListInterface
     */
    public function getChildren(): ElementListInterface;

    /**
     * Устанавливает коллекцию дочерних элементов.
     *
     * @param ElementListInterface $children
     */
    public function setChildren(ElementListInterface $children): void;

    /**
     * Возвращает данные узла (лениво извлекая при необходимости).
     *
     * @return mixed
     */
    public function getData(): mixed;

    /**
     * Принудительно устанавливает данные.
     *
     * @param mixed $data
     */
    public function setData($data): void;

    /**
     * Проверяет, является ли узел void (не может иметь детей).
     *
     * @return bool
     */
    public function isVoid(): bool;

    /**
     * Проверяет, будет ли включен элемент в результат.
     *
     * @return bool
     */
    public function isInclude(): bool;

    /**
     * Проверяет, является ли узел потоковым контейнером.
     *
     * @return bool
     */
    public function isContainer(): bool;

    /**
     * Проверяет, является ли узел фразовым контейнером.
     *
     * @return bool
     */
    public function isPhrase(): bool;

    /**
     * Проверяет, является ли узел строчным элементом.
     *
     * @return bool
     */
    public function isInline(): bool;

    /**
     * Проверяет, является ли элемент "прозрачным".
     *
     * @return bool
     */
    public function isTransparent(): bool;

    /**
     * Проверяет, является ли узел "сырым текстом".
     *
     * @return bool
     */
    public function isRawText(): bool;

    /**
     * Проверяет, нужно ли экранировать HTML-сущности.
     *
     * @return bool
     */
    public function isEscapable(): bool;

    /**
     * Возвращает список тегов, разрешенных в качестве прямых потомков.
     *
     * @return mixed
     */
    public function getAllowedTags(): mixed;

    /**
     * Проверяет, все ли дочерние элементы являются строчными.
     *
     * @return bool
     */
    public function allChildrenIsInline(): bool;
}
```

**Подробнее:** [Система контекстов](./02-core--02-context-system.md)

---

## ContextConverterInterface {#contextconverterinterface}

Преобразование DOM-узлов в контексты и контекстов в готовые элементы.

```php
namespace HtmlDomParser\Contract;

interface ContextConverterInterface
{
    use ContextTypeConstantsTrait;

    /**
     * Создаёт контекст для DOM-узла на основе правил тегов и родительского контекста.
     *
     * @param \DOMNode $node
     * @param int $parentContextType
     * @return NodeContextInterface
     */
    public function nodeToContext(\DOMNode $node, int $parentContextType): NodeContextInterface;
    
    /**
     * Создаёт объект Element из контекста.
     * В этот момент:
     * - Атрибуты копируются из контекста в элемент
     * - Label копируется из контекста
     * - Вызывается DataResolver для извлечения Data
     *
     * @param NodeContextInterface $nodeContext
     * @return ElementInterface
     */
    public function contextToElement(NodeContextInterface $nodeContext): ElementInterface;
}
```

**Подробнее:** [Система контекстов](./02-core--02-context-system.md#contextconverterinterface)

---

## TagContextMapInterface {#tagcontextmapinterface}

Карта правил для HTML-тегов. Содержит конфигурации для всех стандартных HTML-тегов.

```php
namespace HtmlDomParser\Contract;

interface TagContextMapInterface
{
    use ContextTypeConstantsTrait;

    /**
     * Возвращает конфигурацию для указанного тега.
     *
     * @param string $tag
     * @return array [
     *   'thisContextType' => int,      // тип текущего узла
     *   'childrenContextType' => int,  // тип для дочерних узлов
     *   'allowedTags' => array,        // разрешенные теги-потомки
     *   'isInline' => bool,
     *   'isRawText' => bool,
     *   'isEscapable' => bool
     * ]
     */
    public function get(string $tag): array;

    /**
     * Проверяет наличие конфигурации для тега.
     *
     * @param string $tag
     * @return bool
     */
    public function has(string $tag): bool;
}
```

**Подробнее:** [Система контекстов](./02-core--02-context-system.md#tagcontextmapinterface)

---

## ErrorHandlerInterface {#errorhandlerinterface}

Обработчик ошибок парсинга. Собирает все ошибки в процессе парсинга и управляет поведением (выброс исключений).

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

**Подробнее:** [Обработка ошибок](./02-core--04-error-handling.md)

---

## ErrorElementInterface {#errorelementinterface}

Узел-ошибка, расширяет обычный элемент (`ElementInterface`). Замещает проблемные HTML-узлы в итоговом дереве.

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

**Подробнее:** [Обработка ошибок](./02-core--04-error-handling.md#errorelementinterface)

---

## EventDispatcherInterface {#eventdispatcherinterface}

Диспетчер событий для модульной системы. Позволяет подписываться на события и модифицировать контекст узлов в ключевые моменты обработки.

```php
namespace HtmlDomParser\Contract;

interface EventDispatcherInterface
{
    /**
     * Регистрирует обработчик для события.
     *
     * @param string $event Название события (например, 'pre-node')
     * @param callable $handler Функция-обработчик: function(NodeContextInterface $context): NodeContextInterface
     * @param int $priority Приоритет (чем выше, тем раньше вызывается). По умолчанию 0
     * @throws InvalidEventListenerException При несоответствии сигнатуры
     */
    public function subscribe(string $event, callable $handler, int $priority = 0): void;

    /**
     * Вызывает все обработчики события.
     *
     * @param string $event Название события
     * @param NodeContextInterface $context Текущий контекст узла
     * @return NodeContextInterface Модифицированный контекст после всех обработчиков
     */
    public function dispatch(string $event, NodeContextInterface $context): NodeContextInterface;

    /**
     * Проверяет, есть ли обработчики у события.
     *
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool;

    /**
     * Удаляет все обработчики события.
     *
     * @param string $event
     */
    public function clearListeners(string $event): void;
}
```

**Подробнее:** [Событийная модель](./03-events-modules--01-event-system.md)

---

## ModuleInterface {#moduleinterface}

Базовый интерфейс для всех модулей. Модули позволяют расширять функциональность библиотеки через подписку на события.

```php
namespace HtmlDomParser\Contract;

interface ModuleInterface
{
    /**
     * Уникальное имя модуля.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Список имён модулей, от которых зависит данный.
     *
     * @return string[]
     */
    public function getDependencies(): array;

    /**
     * Проверяет совместимость с указанной версией ядра.
     *
     * @param string $version
     * @return bool
     */
    public function supportsCoreVersion(string $version): bool;

    /**
     * Инициализирует модуль, подписываясь на события через диспетчер.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function initialize(EventDispatcherInterface $dispatcher): void;
}
```

**Подробнее:** [Система модулей](./03-events-modules--02-modules.md)

---

## ModuleManagerInterface {#modulemanagerinterface}

Менеджер для обнаружения и загрузки модулей.

```php
namespace HtmlDomParser\Contract;

interface ModuleManagerInterface
{
    /**
     * Обнаруживает доступные модули (из composer.json extra.modules).
     *
     * @return array Список информации о модулях
     */
    public function discover(): array;

    /**
     * Загружает и инициализирует все модули с проверкой зависимостей.
     *
     * @throws \RuntimeException При циклических зависимостях или несовместимости
     */
    public function loadModules(): void;

    /**
     * Возвращает экземпляр модуля по имени.
     *
     * @param string $name
     * @return ModuleInterface|null
     */
    public function getModule(string $name): ?ModuleInterface;

    /**
     * Проверяет, загружен ли модуль с указанным именем.
     *
     * @param string $name
     * @return bool
     */
    public function hasModule(string $name): bool;

    /**
     * Возвращает список всех загруженных модулей.
     *
     * @return ModuleInterface[]
     */
    public function getLoadedModules(): array;

    /**
     * Регистрирует модуль вручную (без автоматического обнаружения).
     *
     * @param ModuleInterface $module
     */
    public function registerModule(ModuleInterface $module): void;
}
```

**Подробнее:** [Система модулей](./03-events-modules--02-modules.md#modulemanagerinterface)

---

## DataResolverInterface {#dataresolverinterface}

Извлечение основного содержимого (Data) из DOM-узла.

```php
namespace HtmlDomParser\Contract;

interface DataResolverInterface
{
    /**
     * Извлекает основное содержимое (data) из DOM-узла.
     *
     * @param \DOMNode $node Оригинальный DOM-узел
     * @return mixed Извлеченные данные
     */
    public function resolve(\DOMNode $node): mixed;
}
```

**Подробнее:** [DataResolver](./02-core--03-utilities.md#dataresolver)

---

## InlineCollapserInterface {#inlinecollapserinterface}

Схлопывание строчных элементов в единый текст с коллекцией сущностей форматирования.

```php
namespace HtmlDomParser\Contract;

interface InlineCollapserInterface
{
    /**
     * Выполняет схлопывание последовательности inline-элементов.
     *
     * @param NodeContextInterface $context Контекст узла
     * @param array $options Опции схлопывания (на будущее)
     * @return NodeContextInterface Модифицированный контекст с результатом схлопывания
     */
    public function collapse(NodeContextInterface $context, array $options = []): NodeContextInterface;
}
```

**Подробнее:** [InlineCollapser](./02-core--03-utilities.md#inline-collapser)

---

## Трейты констант {#трейты-констант}

### ContextTypeConstantsTrait {#contexttypeconstantstrait}

```php
trait ContextTypeConstantsTrait
{
    const INLINE    = 1; // Фразовый элемент (строчный)
    const PHRASE    = 2; // Фразовый контейнер
    const CONTAINER = 3; // Потоковый контейнер (блочный)
    const VOID      = 0; // Пустой элемент (не может иметь детей)
    const SKIP      = -1; // Пропустить элемент (не включать в результат)
    const ROOT      = 4; // Корень
}
```

### ErrorConstantsTrait {#errorconstantstrait}

```php
trait ErrorConstantsTrait
{
    const SEVERITY_NOTICE  = 'notice';  // Уведомление, не влияет на результат
    const SEVERITY_WARNING = 'warning'; // Предупреждение, возможны незначительные проблемы
    const SEVERITY_ERROR   = 'error';   // Фатальная ошибка, результат может быть некорректен
}
```

---

## Исключения {#исключения}

### HtmlDomParserException {#htmldomparserexception}

Основное исключение библиотеки, выбрасываемое при ошибках парсинга, если соответствующее поведение включено через `ErrorHandlerInterface`.

```php
namespace HtmlDomParser\Exception;

class HtmlDomParserException extends \Exception
{
    // Специфичные методы отсутствуют, используется стандартный функционал Exception
}
```

### InvalidEventListenerException {#invalideventlistenerexception}

Выбрасывается при попытке подписать обработчик с неверной сигнатурой.

```php
namespace HtmlDomParser\Exception;

class InvalidEventListenerException extends \Exception
{
    // Специфичные методы отсутствуют
}
```

---

## Связанные разделы {#see-also}

- [Введение](./01-general-information--01-introduction.md)
- [Модель данных](./02-core--01-data-model.md)
- [Система контекстов](./02-core--02-context-system.md)
- [Утилиты](./02-core--03-utilities.md)
- [Обработка ошибок](./02-core--04-error-handling.md)
- [Событийная модель](./03-events-modules--01-event-system.md)
- [Система модулей](./03-events-modules--02-modules.md)
- [FAQ](./04-appendix--01-faq.md)