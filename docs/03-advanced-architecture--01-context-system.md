[← К оглавлению](../README.md#📖-документация)

# Система контекстов

В этом разделе рассматривается ключевая концепция библиотеки — временный объект контекста, который сопровождает каждый узел в процессе рекурсивного обхода и определяет правила его обработки.

## Что такое контекст? {#what-is-context}

**Контекст узла** (`NodeContextInterface`) — это временный объект, создаваемый для каждого DOM-узла в процессе парсинга. Он хранит:

- Ссылку на оригинальный DOM-узел
- Правила обработки тега из карты тегов
- ContextType родительского контекста
- Уже обработанные дочерние элементы
- Промежуточные результаты вычислений

**Важная особенность:** контекст работает с ленивой выдачей информации. При первом обращении к любому свойству (атрибуты, данные, метка) значение извлекается из оригинального DOM-узла и кэшируется в контексте. При повторных обращениях используется кэшированное значение. Это позволяет обработчикам событий перезаписывать свойства, и новые значения будут использоваться на всех последующих этапах обработки.

> **Важно:** Контекст узла — это временный объект, существующий только в процессе парсинга.
> Для создания конечного элемента (`ElementInterface`) используется `ContextConverterInterface::contextToElement()`.
> Сам контекст не умеет преобразовывать себя в элемент.

## Жизненный цикл узла {#node-lifecycle}

```
DOMNode
    │
    ▼
Создание NodeContext
    │
    ▼
Проверка правил (TagContextMap)
    │
    ▼
Событие `pre-node`
    │
    ▼
Обработка потомков с учетом их контекста (Рекурсия)
    │
    ▼
Проверка всех детей (ContextType == INLINE)
    │
    ├─── true ───┐
    │            |
  false          ▼
    │        Событие `pre-inline-collapse`
    │            |
    │            ▼
    │        inline-схлопывание
    │            |
    │            ▼
    │        Событие `post-inline-collapse`
    │            |
    │◀───────────┘
    │
    ▼
Событие post-node
    │
    ▼
Преобразование NodeContext в Element
```

## Типы контекста {#context-types}

Библиотека использует трейт `ContextTypeConstantsTrait` для определения типов контекста:

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

| Тип | Значение | Описание | Примеры |
| :--- | :---: | :--- | :--- |
| `ROOT` | 4 | Корневой элемент | `<html>`, `<body>` |
| `CONTAINER` | 3 | Потоковый контейнер (блочный) | `<div>`, `<section>`, `<article>` |
| `PHRASE` | 2 | Фразовый контейнер | `<p>`, `<h1>`-`<h6>` |
| `INLINE` | 1 | Строчный элемент, может находиться внутри текста | `<b>`, `<i>`, `<a>`, `<span>` |
| `VOID` | 0 | Пустой элемент, не может иметь детей | `<img>`, `<br>`, `<input>` |
| `SKIP` | -1 | Пропускаемый элемент | Зависит от настроек |

## API Reference (NodeContextInterface) {#api-reference-nodecontextinterface}

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

## ContextConverterInterface {#contextconverterinterface}

Конвертер отвечает за преобразование DOM-узлов в контексты и контекстов в готовые элементы. Именно на этом этапе вызывается DataResolver.

```php
interface ContextConverterInterface
{
    use ContextTypeConstantsTrait;

    /**
     * Создаёт контекст для DOM-узла на основе правил тегов и родительского контекста.
     */
    public function nodeToContext(\DOMNode $node, int $parentContextType): NodeContextInterface;
    
    /**
     * Создаёт объект Element из контекста.
     * В этот момент:
     * - Атрибуты копируются из контекста в элемент
     * - Label копируется из контекста
     * - Вызывается DataResolver для извлечения Data
     */
    public function contextToElement(NodeContextInterface $nodeContext): ElementInterface;
}
```

### Пример преобразования

```php
// Создание контекста
$context = $converter->nodeToContext($domNode, $parentContextType);

// ... обработка детей, события ...

// Преобразование в элемент — здесь вызывается DataResolver
$element = $converter->contextToElement($context);
// Элемент готов и содержит все данные
```

## TagContextMapInterface {#tagcontextmapinterface}

Карта контекстов тегов — это словарь с правилами для всех стандартных HTML-тегов:

```php
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

Пример конфигурации для тега `<p>`:
```php
[
    'thisContextType' => ContextTypeConstantsTrait::PHRASE,
    'childrenContextType' => ContextTypeConstantsTrait::INLINE,
    'allowedTags' => ['a', 'b', 'i', 'span', '#text'], // разрешенные внутри
    'isInline' => false,
    'isRawText' => false,
    'isEscapable' => true
]
```

## Детальное описание {#detailed-description}

### Роль TagContextMap

Карта тегов определяет три ключевых аспекта обработки:

1. **Тип самого узла** (`thisContextType`) — Определяет элемент классифицируется для родительского элемента
2. **Тип для дочерних узлов** (`childrenContextType`) — определяет поведение потомков
3. **Разрешенные теги** (`allowedTags`) — используется для валидации структуры HTML

### Наследование контекста

При создании контекста для дочернего узла передается `childrenContextType` родителя. Это позволяет реализовать правильную вложенность:

- Внутри блочного элемента (`CONTAINER`) могут быть другие блочные или строчные элементы
- Внутри строчного элемента (`INLINE`) могут быть только строчные элементы
- Внутри фразового контейнера (`PHRASE`) — только строчные
- Внутри пустого элемента (`VOID`) — не может быть элементов, выполнить inline-схлопывание (принудительно)

### Проверка валидации

Метод `getAllowedTags()` возвращает список тегов, разрешенных внутри текущего узла. Если дочерний тег не входит в этот список, создается узел-ошибка.

## Примеры кода {#code-examples}

### Пример 1: Получение типа контекста элемента

```php
<?php

$html = '<div><p>Текст</p></div>';
$parser = new Parser($html);
$document = $parser->parse();

$div = $document->getChildren()->get(0);
$p = $div->getChildren()->get(0);

// Проверка через специализированные методы
echo 'div isInline? ' . ($div->isInline() ? 'да' : 'нет') . PHP_EOL;    // нет
echo 'p isInline? ' . ($p->isInline() ? 'да' : 'нет') . PHP_EOL;        // нет (p - phrase)

// Получение числового значения типа
$divType = $div->getContextType(); // 3 (CONTAINER)
$pType = $p->getContextType();     // 2 (PHRASE)
```

### Пример 2: Внутренний пример проверки разрешенных тегов

> **Примечание:** Этот код демонстрирует внутреннюю логику библиотеки и не предназначен для использования в пользовательских проектах.

```php
// Внутри процесса парсинга (упрощенно)
$parentContext = createContext($parentNode, $parentType);
$childNode = getNextChild();

if ($parentContext->isVoid()) {
    // void-элементы не могут иметь детей
    createErrorNode('void element cannot have children');
}

$allowedTags = $parentContext->getAllowedTags();
if (!in_array($childNode->nodeName, $allowedTags)) {
    // Тег не разрешен внутри родителя
    createErrorNode('disallowed child tag');
}
```

## Связанные разделы

- [Ядро системы (интерфейсы)](./02-core-components--01-core-interfaces.md) — ElementInterface и getContextType()
- [Событийная модель](./03-advanced-architecture--02-event-system.md) — изменение контекста через события
- [InlineCollapser](./04-utilities--02-inline-collapser.md) — использование allChildrenIsInline()
- [Обработка ошибок](./03-advanced-architecture--04-error-handling.md) — узлы-ошибки при нарушении правил