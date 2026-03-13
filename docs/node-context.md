[← К оглавлению](index.md)

## **NodeContext, NodeContextResolver и TagRules**

---

### 1. Введение

В процессе рекурсивного обхода HTML-дерева каждый узел нуждается в сопроводительной информации, определяющей, как его обрабатывать, какие дочерние узлы разрешены, является ли он строчным (inline) и т.д. Эту роль выполняет **контекст узла** (`NodeContext`). Контекст создаётся с помощью **резолвера контекста** (`NodeContextResolver`), который применяет правила, описанные в конфигурационном файле **TagRules**.

Объединённое описание этих трёх компонентов позволяет понять, как формируется состояние узла на всём пути его обработки.

---

### 2. Компоненты

#### 2.1 TagRules – правила обработки тегов

Файл `Config/TagRules.php` содержит ассоциативный массив, где ключом является имя тега (например, `'div'`, `'a'`), а значением — массив с параметрами обработки.

**Структура правил:**

| Поле | Тип | Описание |
|------|-----|----------|
| `includeType` | `string` | Режим включения узла: `INCLUDE` (включить), `SKIP` (пропустить), `ERROR` (сгенерировать ошибку). |
| `childrenType` | `string` | Тип обработки дочерних узлов: `INLINE`, `BLOCK`, `MIXED`, `RAW_TEXT`, `EMPTY`. |
| `allowedTags` | `array` | Список имён тегов, разрешённых в качестве прямых потомков (если применимо). |
| `isVoid` | `bool` | Является ли тег самозакрывающимся (например, `img`, `br`). |
| `isInline` | `bool` | Является ли тег строчным (inline) по умолчанию. |
| `isRawText` | `bool` | Следует ли обрабатывать содержимое как сырой текст (без парсинга HTML). |
| `isEscapable` | `bool` | Нужно ли преобразовывать HTML-сущности в тексте (обычно `true`). |

**Пример (`Config/TagRules.php`):**

```php
return [
    'div' => [
        'includeType'  => 'INCLUDE',
        'childrenType' => 'BLOCK',
        'allowedTags'  => [], // любые теги разрешены, если массив пуст
        'isInline'     => false,
        'isRawText'    => false,
        'isEscapable'  => true,
    ],
    'a' => [
        'includeType'  => 'INCLUDE',
        'childrenType' => 'INLINE',
        'allowedTags'  => ['span', 'strong', 'em', '#text'],
        'isInline'     => true,
        'isRawText'    => false,
        'isEscapable'  => true,
    ],
    'script' => [
        'includeType'  => 'INCLUDE',
        'childrenType' => 'RAW_TEXT',
        'allowedTags'  => [],
        'isInline'     => false,
        'isRawText'    => true,
        'isEscapable'  => false,
    ],
    // ... другие теги
];
```

#### 2.2 NodeContext – контекст узла

**Пространство имён:** `HtmlDomParser\Core\Context\NodeContext`  
Реализует интерфейс `NodeContextInterface`.

**Свойства (все защищены, доступ через геттеры/сеттеры):**

| Свойство | Тип | Описание |
|----------|-----|----------|
| `$node` | `\DOMNode` | Оригинальный узел DOM. |
| `$parent` | `?NodeContext` | Родительский контекст. |
| `$children` | `ElementList` | Уже обработанные дочерние элементы (после рекурсии). |
| `$tagRules` | `array` | Правила для текущего тега (из TagRules). |
| `$includeType` | `string` | Унаследованный или определённый режим включения. |
| `$childrenType` | `string` | Тип обработки детей. |
| `$allowedTags` | `array` | Список разрешённых тегов для детей. |
| `$isVoid` | `bool` | Флаг самозакрывающегося тега. |
| `$isInline` | `bool` | Является ли узел строчным. |
| `$isRawText` | `bool` | Флаг сырого текста. |
| `$isEscapable` | `bool` | Флаг преобразования сущностей. |
| `$data` | `mixed` | Кэшированное значение `data` (лениво резолвится через `DataResolver`). |
| `$allChildrenIsInline` | `?bool` | Кэшированный результат проверки, все ли дети строчные. |

**Основные методы:**

| Метод | Описание |
|-------|----------|
| `getNode(): \DOMNode` | Возвращает исходный DOM-узел. |
| `getParent(): ?NodeContext` | Возвращает родительский контекст. |
| `setParent(?NodeContext $parent): void` | Устанавливает родителя. |
| `getChildren(): ElementList` | Возвращает коллекцию обработанных детей. |
| `setChildren(ElementList $children): void` | Устанавливает коллекцию детей. |
| `getData(): mixed` | Возвращает `data` (если не закэшировано – запрашивает у `DataResolver`). |
| `setData($data): void` | Принудительно устанавливает `data`. |
| `isVoid(): bool` | Проверяет, является ли узел void. |
| `isInline(): bool` | Проверяет, строчный ли узел. |
| `isRawText(): bool` | Проверяет, нужно ли обрабатывать как сырой текст. |
| `isEscapable(): bool` | Проверяет, нужно ли эскейпить сущности. |
| `getAllowedTags(): array` | Возвращает список разрешённых дочерних тегов. |
| `allChildrenIsInline(): bool` | Возвращает `true`, если все текущие дети строчные (после обработки). Результат кэшируется. |
| `toElement(): Element` | Создаёт объект `Element` (или наследника) на основе контекста. |

#### 2.3 NodeContextResolver – создание контекста

**Пространство имён:** `HtmlDomParser\Core\Context\NodeContextResolver`  
Реализует интерфейс `NodeContextResolverInterface`.

**Зависимости:**
- Массив правил `$tagRules` (обычно загружается из `Config/TagRules.php`).
- Экземпляр `DataResolver` (для ленивого резолвления `data`).

**Основной метод:**
```php
public function resolve(\DOMNode $node, ?NodeContext $parentContext = null): NodeContext
```
Алгоритм:
1. Если узел является текстовым (`#text`) – создаёт контекст с особыми правилами (inline, разрешён всегда).
2. Если для имени тега (`$node->nodeName`) есть правила в `$tagRules`, они используются.
3. Если правил нет – генерируется ошибка `NOTICE` через `ErrorHandler`, и применяются правила по умолчанию (INCLUDE, childrenType = MIXED, allowedTags = []).
4. Наследование от родительского контекста:
   - Если родитель задаёт `allowedTags` и текущий тег не входит в список – создаётся контекст с `includeType = SKIP` или `ERROR` (в зависимости от настройки).
5. Создаётся новый экземпляр `NodeContext` с заполненными полями.
6. В контекст сохраняется ссылка на родителя (для обратной связи).

---

### 3. Взаимодействие компонентов

1. **Парсер** (`Parser`) получает DOM-узел и вызывает `NodeContextResolver::resolve($node, $parentContext)`.
2. Резолвер определяет правила на основе `TagRules` и родителя, создаёт контекст.
3. В процессе обработки узла парсер может:
   - Получать/устанавливать детей через `$context->setChildren()`.
   - Запрашивать `$context->getData()` – при первом вызове будет вызван `DataResolver`.
   - Проверять `$context->allChildrenIsInline()` перед схлопыванием.
4. После завершения обработки парсер вызывает `$context->toElement()` для получения конечного элемента дерева.