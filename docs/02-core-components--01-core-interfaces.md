[← К оглавлению](../README.md#📖-документация)

# Ядро системы (интерфейсы)

В этом разделе описаны основные интерфейсы библиотеки, их роль и взаимодействие в процессе парсинга. Все интерфейсы находятся в пространстве имен `HtmlDomParser\Contract`.

## Общая структура

Библиотека построена вокруг нескольких ключевых интерфейсов, которые определяют контракты взаимодействия между компонентами:

- **`ParserInterface`** — главный оркестратор, создает документ
- **`DocumentInterface`** — корневой объект, расширяет `NodeInterface`
- **`NodeInterface`** — базовый интерфейс для всех узлов
- **`ElementInterface`** — основной тип узла для тегов, расширяет `NodeInterface`
- **`ErrorElementInterface`** — узел-ошибка, расширяет `ElementInterface`
- **`ElementListInterface`** — коллекция элементов для навигации
- **`NodeContextInterface`** — временный контекст узла в процессе парсинга
- **`ErrorHandlerInterface`** — обработчик ошибок
- **`EventDispatcherInterface`** — диспетчер событий
- **`ModuleInterface`** и **`ModuleManagerInterface`** — система модулей
- **`DataResolverInterface`** — извлечение основного содержимого
- **`InlineCollapserInterface`** — схлопывание строчных элементов
- **`TagContextMapInterface`** — карта правил для тегов
- **`ContextConverterInterface`** — преобразование DOM-узлов в контекст и элементы

## ParserInterface

Главный оркестратор, управляющий всем процессом парсинга от загрузки HTML до возврата готового документа.

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

## NodeInterface

Базовый интерфейс для всех узлов дерева (документ, элементы, текстовые узлы, комментарии).

```php
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

## DocumentInterface

Корневой объект документа, расширяет `NodeInterface`. Содержит всё дерево элементов.

```php
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

## ElementInterface

Основной тип узла для HTML-тегов. Расширяет `NodeInterface` и добавляет семантические методы.

```php
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
     * @return string Например, текст внутри ссылки
     */
    public function getLabel(): string;

    /**
     * Устанавливает текстовую метку.
     *
     * @param string $label
     */
    public function setLabel(string $label): void;

    /**
     * Возвращает массив сущностей форматирования.
     *
     * @return array Массив entities после схлопывания
     */
    public function getEntities(): array;

    /**
     * Добавляет сущность форматирования.
     *
     * @param array $entity
     */
    public function addEntity(array $entity): void;

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

## Примеры использования

### Проверка типа элемента

```php
$element = $document->getChildren()->get(0);

if ($element->isInline()) {
    echo 'Это строчный элемент';
}

// Альтернативный способ через getContextType()
$contextType = $element->getContextType();
if ($contextType === ContextTypeConstantsTrait::INLINE) {
    echo 'Это строчный элемент';
}
```

### Работа с атрибутами

```php
$link = $links->get(0);

// Получение атрибутов
$href = $link->getAttribute('href');
$class = $link->getAttribute('class');

// Проверка наличия
if ($link->hasAttribute('target')) {
    echo 'Ссылка открывается в новом окне';
}

// Установка нового атрибута
$link->setAttribute('rel', 'nofollow');

// Удаление атрибута
$link->removeAttribute('old-attr');
```

## Таблица констант

Библиотека использует трейт `ContextTypeConstantsTrait` для определения типов контекста:

```php
trait ContextTypeConstantsTrait
{
    const INLINE    = 1, // Фразовый элемент (строчный)
          PHRASE    = 2, // Фразовый контейнер
          CONTAINER = 3, // Потоковый контейнер (блочный)
          VOID      = 0, // Пустой элемент (не может иметь детей)
          SKIP      =-1, // Пропустить элемент
          ROOT      = 4; // Корень
}
```

> **Подробнее:** Детальное описание типов контекста и их влияния на обработку узлов рассматривается в разделе [Система контекстов](./03-advanced-architecture--01-context-system.md).

## Связанные разделы

- [Работа с документом](./02-core-components--02-working-with-document.md) — навигация по дереву
- [Модель данных](./02-core-components--03-data-model.md) — Data, Label, Entities
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — типы контекста и их значение
- [Обработка ошибок](./03-advanced-architecture--04-error-handling.md) — ErrorElementInterface