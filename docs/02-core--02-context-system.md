[← К оглавлению](../README.md#-документация)

# Система контекстов {#context-system}

В этом разделе рассматривается ключевая концепция библиотеки — временный объект контекста, который сопровождает каждый узел в процессе рекурсивного обхода и определяет правила его обработки.

## Что такое контекст? {#what-is-context}

**Контекст узла** ([`NodeContextInterface`](./04-appendix--02-api-reference.md#nodecontextinterface)) — это временный объект, создаваемый для каждого DOM-узла в процессе парсинга. Он хранит:

- Ссылку на оригинальный DOM-узел
- Правила обработки тега из карты тегов
- Тип контекста родительского узла
- Уже обработанные дочерние элементы
- Промежуточные результаты вычислений

**Важная особенность:** контекст работает с ленивой выдачей информации. При первом обращении к любому свойству (атрибуты, данные, метка) значение извлекается из оригинального DOM-узла и кэшируется в контексте. При повторных обращениях используется кэшированное значение. Это позволяет обработчикам событий перезаписывать свойства, и новые значения будут использоваться на всех последующих этапах обработки.

> **Важно:** Контекст узла — это временный объект, существующий только в процессе парсинга.
> Для создания конечного элемента ([`ElementInterface`](./04-appendix--02-api-reference.md#elementinterface)) используется [`ContextConverterInterface::contextToElement()`](./04-appendix--02-api-reference.md#contextconverterinterface).
> Сам контекст не умеет преобразовывать себя в элемент.

## Жизненный цикл узла {#node-lifecycle}

```
DOMNode
    │
    ▼
Создание NodeContext (ContextConverterInterface::nodeToContext)
    │
    ▼
Получение правил из TagContextMapInterface
    │
    ▼
Событие `pre-node` (см. [Событийная модель](./03-events-modules--01-event-system.md))
    │
    ▼
Обработка потомков с учетом их контекста (Рекурсия)
    │
    ▼
Проверка: все ли дети являются строчными? (allChildrenIsInline())
    │
    ├─── true ───────────────────────────────────────┐
    │                                                │
  false                                              ▼
    │                                       Событие `pre-inline-collapse`
    │                                                │
    ▼                                                ▼
    │                                       Inline-схлопывание (InlineCollapserInterface)
Событие `post-node`                                  │
    │                                                ▼
    │                                       Событие `post-inline-collapse`
    │                                                │
    │◀───────────────────────────────────────────────┘
    │
    ▼
Преобразование NodeContext в Element (ContextConverterInterface::contextToElement)
    │
    ▼
Готовый элемент добавлен в дерево
```

Подробнее о событиях читайте в разделе [Событийная модель](./03-events-modules--01-event-system.md), о схлопывании — в разделе [InlineCollapser](./02-core--03-utilities.md#inline-collapser).

## Типы контекста {#context-types}

Библиотека использует трейт `ContextTypeConstantsTrait` для определения типов контекста. Полный список констант доступен в [Справочнике API](./04-appendix--02-api-reference.md#трейты-констант).

| Тип | Константа | Описание | Примеры тегов |
| :--- | :---: | :--- | :--- |
| **Корень** | `ROOT` (4) | Корневой элемент документа | `<html>`, `<body>` |
| **Блочный** | `CONTAINER` (3) | Потоковый контейнер | `<div>`, `<section>`, `<article>` |
| **Фразовый** | `PHRASE` (2) | Фразовый контейнер | `<p>`, `<h1>`-`<h6>` |
| **Строчный** | `INLINE` (1) | Строчный элемент внутри текста | `<b>`, `<i>`, `<a>`, `<span>` |
| **Пустой** | `VOID` (0) | Не может иметь детей | `<img>`, `<br>`, `<input>` |
| **Пропуск** | `SKIP` (-1) | Пропускается при парсинге | Зависит от настроек |

## Компоненты системы контекстов {#components}

### NodeContextInterface {#nodecontextinterface}

Временный контекст узла предоставляет методы для доступа к DOM-узлу, его свойствам и правилам обработки. Полный список методов см. в [Справочнике API](./04-appendix--02-api-reference.md#nodecontextinterface).

Ключевые возможности:
- Ленивое извлечение данных (`getData()`, работа с атрибутами)
- Проверка типа контекста (`isInline()`, `isContainer()`, `isVoid()` и др.)
- Управление дочерними элементами (`getChildren()`, `setChildren()`)
- Валидация структуры (`getAllowedTags()`, `allChildrenIsInline()`)

### TagContextMapInterface {#tagcontextmapinterface}

Карта контекстов тегов — это словарь с правилами для всех стандартных HTML-тегов. Детальное описание интерфейса доступно в [Справочнике API](./04-appendix--02-api-reference.md#tagcontextmapinterface).

Для каждого тега карта определяет:
- **`thisContextType`** — тип самого узла
- **`childrenContextType`** — тип, который будет передан дочерним узлам
- **`allowedTags`** — список разрешенных тегов-потомков
- Флаги `isInline`, `isRawText`, `isEscapable`

Пример конфигурации для тега `<p>`:
```php
[
    'thisContextType' => ContextTypeConstantsTrait::PHRASE,      // сам тег - фразовый
    'childrenContextType' => ContextTypeConstantsTrait::INLINE,  // дети будут строчными
    'allowedTags' => ['a', 'b', 'i', 'span', '#text'],           // разрешенные потомки
    'isInline' => false,
    'isRawText' => false,
    'isEscapable' => true
]
```

### ContextConverterInterface {#contextconverterinterface}

Конвертер отвечает за преобразование DOM-узлов в контексты и контекстов в готовые элементы. Полное описание методов см. в [Справочнике API](./04-appendix--02-api-reference.md#contextconverterinterface).

**Основные методы:**
- `nodeToContext(DOMNode $node, int $parentContextType): NodeContextInterface` — создаёт контекст для DOM-узла
- `contextToElement(NodeContextInterface $nodeContext): ElementInterface` — создаёт элемент из контекста (на этом этапе вызывается [`DataResolverInterface`](./04-appendix--02-api-reference.md#dataresolverinterface))

```php
// Пример преобразования (внутренняя логика библиотеки)
$context = $converter->nodeToContext($domNode, $parentContextType);
// ... обработка детей, события ...
$element = $converter->contextToElement($context); // здесь вызывается DataResolver
```

## Принципы работы {#how-it-works}

### Наследование контекста {#context-inheritance}

При создании контекста для дочернего узла передается `childrenContextType` родителя. Это обеспечивает правильную вложенность элементов:

- Внутри блочного элемента (`CONTAINER`) могут быть другие блочные или строчные элементы
- Внутри строчного элемента (`INLINE`) могут быть только строчные элементы
- Внутри фразового контейнера (`PHRASE`) — только строчные
- Внутри пустого элемента (`VOID`) — детей быть не может

### Валидация структуры {#validation}

Метод `getAllowedTags()` возвращает список тегов, разрешенных внутри текущего узла. Если дочерний тег не входит в этот список, создается узел-ошибка ([`ErrorElementInterface`](./04-appendix--02-api-reference.md#errorelementinterface)). Подробнее в разделе [Обработка ошибок](./02-core--04-error-handling.md).

## Примеры кода {#examples}

### Пример 1: Получение типа контекста элемента

```php
<?php

$html = '<div><p>Текст</p></div>';
$parser = new Parser($html);
$document = $parser->parse();

$div = $document->getChildren()->get(0);
$p = $div->getChildren()->get(0);

// Проверка через специализированные методы ElementInterface
echo 'div isInline? ' . ($div->isInline() ? 'да' : 'нет') . PHP_EOL;    // нет
echo 'p isInline? ' . ($p->isInline() ? 'да' : 'нет') . PHP_EOL;        // нет (p - phrase)

// Получение числового значения типа
$divType = $div->getContextType(); // 3 (CONTAINER)
$pType = $p->getContextType();     // 2 (PHRASE)
```

### Пример 2: Проверка разрешенных тегов (внутренняя логика)

> **Примечание:** Этот код демонстрирует внутреннюю логику библиотеки и не предназначен для использования в пользовательских проектах.

```php
// Упрощенная демонстрация внутренней валидации
$parentContext = $converter->nodeToContext($parentNode, $parentType);
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

### Пример 3: Модификация контекста через события

```php
<?php

use HtmlDomParser\Contract\NodeContextInterface;

// Подписка на событие pre-node для изменения атрибутов
$dispatcher->subscribe('pre-node', function(NodeContextInterface $context) {
    if ($context->getName() === 'div') {
        // Добавляем класс ко всем div-элементам
        $class = $context->getNode()->getAttribute('class') ?? '';
        $context->setAttribute('class', trim($class . ' contextualized'));
    }
    return $context;
});
```

Подробнее о работе с событиями в разделе [Событийная модель](./03-events-modules--01-event-system.md).

## Связанные разделы {#see-also}

- [Справочник API: NodeContextInterface](./04-appendix--02-api-reference.md#nodecontextinterface)
- [Справочник API: ContextConverterInterface](./04-appendix--02-api-reference.md#contextconverterinterface)
- [Справочник API: TagContextMapInterface](./04-appendix--02-api-reference.md#tagcontextmapinterface)
- [Справочник API: ContextTypeConstantsTrait](./04-appendix--02-api-reference.md#трейты-констант)
- [Событийная модель](./03-events-modules--01-event-system.md) — изменение контекста через события
- [InlineCollapser](./02-core--03-utilities.md#inline-collapser) — использование `allChildrenIsInline()`
- [Обработка ошибок](./02-core--04-error-handling.md) — узлы-ошибки при нарушении правил
- [Модель данных](./02-core--01-data-model.md) — как Data извлекается из контекста