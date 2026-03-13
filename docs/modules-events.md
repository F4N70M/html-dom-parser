[← К оглавлению](index.md)

## Модули и события

### Введение

Библиотека предоставляет два взаимосвязанных механизма для расширения функциональности без изменения ядра: **события** и **модули**.  
- **События** позволяют внедрять пользовательскую логику в ключевые моменты обработки узлов.  
- **Модули** группируют такую логику в переиспользуемые пакеты, которые могут быть установлены через Composer и автоматически подключены к парсеру.

---

### 1. События (EventDispatcher)

События возникают в процессе рекурсивного обхода для каждого узла. Модули могут подписываться на них и модифицировать контекст узла (`NodeContext`), влияя на дальнейшую обработку.

#### 1.1 Список событий

| Событие | Момент вызова |
|---------|---------------|
| `pre-node` | после создания контекста узла, до рекурсивной обработки его детей |
| `post-node` | после обработки всех детей и схлопывания, перед созданием элемента |
| `pre-inline-collapse` | перед схлопыванием inline-узлов текущего уровня |
| `post-inline-collapse` | после схлопывания inline-узлов текущего уровня |

Каждое событие передаёт текущий контекст узла (`NodeContext`) и ожидает его же в возвращаемом значении (возможно, модифицированным). Обработчики могут изменять любые свойства контекста: флаги, дочерние элементы, данные и т.д.

#### 1.2 Интерфейс EventDispatcher

```php
namespace HtmlDomParser\Contract;

interface EventDispatcherInterface
{
    /**
     * Регистрирует обработчик для события.
     *
     * @param string   $event   Название события (например, 'pre-node').
     * @param callable $handler Функция-обработчик: function(NodeContext $context): NodeContext.
     * @param int      $priority Приоритет (чем выше, тем раньше вызывается).
     * @throws InvalidEventListenerException При несоответствии сигнатуры.
     */
    public function subscribe(string $event, callable $handler, int $priority = 0): void;

    /**
     * Вызывает все обработчики события.
     *
     * @param string      $event   Название события.
     * @param NodeContext $context Текущий контекст узла.
     * @return NodeContext Модифицированный контекст после всех обработчиков.
     */
    public function dispatch(string $event, NodeContext $context): NodeContext;

    /**
     * Проверяет, есть ли обработчики у события.
     */
    public function hasListeners(string $event): bool;

    /**
     * Удаляет все обработчики события.
     */
    public function clearListeners(string $event): void;
}
```

#### 1.3 Обработчики

Обработчик должен иметь сигнатуру `function (NodeContext $context): NodeContext`. При подписке диспетчер проверяет сигнатуру через Reflection и выбрасывает исключение `InvalidEventListenerException` при несоответствии.

Пример простого обработчика:
```php
$dispatcher->subscribe('pre-node', function(NodeContext $context) {
    if ($context->getNode()->nodeName === 'script') {
        $context->setData('custom', 'string');
    }
    return $context;
}, 10);
```

#### 1.4 Приоритеты

Обработчики с более высоким приоритетом вызываются раньше. Приоритет по умолчанию – 0.

#### 1.5 Использование в парсере

`EventDispatcher` создаётся в конструкторе `Parser` и доступен модулям через метод `initialize()`. Внутри `Parser::processNode()` диспетчер вызывается в соответствующих точках:

```php
$nodeContext = $this->eventDispatcher->dispatch('pre-node', $nodeContext);
// ... обработка детей ...
if ($needCollapse) {
    $nodeContext = $this->eventDispatcher->dispatch('pre-inline-collapse', $nodeContext);
    $collapsed = $this->inlineCollapser->collapse($nodeContext->getChildren());
    $nodeContext->setChildren($collapsed);
    $nodeContext = $this->eventDispatcher->dispatch('post-inline-collapse', $nodeContext);
}
$nodeContext = $this->eventDispatcher->dispatch('post-node', $nodeContext);
```

---

### 2. Модули (ModuleManager)

Модули представляют собой законченные расширения, которые могут добавлять новые возможности (например, поддержку CSS-селекторов, XPath, валидацию). Каждый модуль оформляется как отдельный класс, реализующий интерфейс `ModuleInterface`.

#### 2.1 Интерфейс модуля

```php
namespace HtmlDomParser\Contract;

interface ModuleInterface
{
    /**
     * Уникальное имя модуля (используется для идентификации и зависимостей).
     */
    public function getName(): string;

    /**
     * Список имён модулей, от которых зависит данный.
     */
    public function getDependencies(): array;

    /**
     * Проверка совместимости с версией ядра.
     */
    public function supportsCoreVersion(string $version): bool;

    /**
     * Инициализация модуля. Здесь модуль может подписаться на события.
     */
    public function initialize(EventDispatcherInterface $dispatcher): void;
}
```

#### 2.2 ModuleManager

Класс `HtmlDomParser\Core\ModuleManager` отвечает за обнаружение, загрузку и инициализацию модулей. Он автоматически считывает секцию `extra.modules` из `composer.json` основного проекта и подключает указанные классы модулей.

**ModuleManager** использует автозагрузку Composer (PSR-4) для создания экземпляров классов модулей, указанных в секции `extra.modules` composer.json.

**Основные методы:**

- `discover(): array` – собирает информацию о доступных модулях (из composer.json и/или директории Module/).
- `loadModules(): void` – загружает модули с проверкой зависимостей, сортировкой и инициализацией.
- `getModule(string $name): ?ModuleInterface` – возвращает экземпляр модуля по имени.
- `hasModule(string $name): bool` – проверяет наличие модуля.
- `getLoadedModules(): array` – возвращает список всех загруженных модулей.

**Процесс загрузки:**

1. Сбор списка модулей (**ModuleManager** использует автозагрузку Composer PSR-4 для создания экземпляров классов модулей, указанных в секции `extra.modules` composer.json).
2. Проверка циклических зависимостей.
3. Сортировка в порядке, удовлетворяющем зависимостям (топологическая сортировка).
4. Для каждого модуля: создание экземпляра, вызов `supportsCoreVersion()` (если несовместим – исключение или предупреждение), затем `initialize()` с передачей диспетчера событий.

#### 2.3 Интеграция с Parser

Парсер создаёт `ModuleManager` в конструкторе и передаёт себя для инициализации модулей. После загрузки модули доступны через метод `modules()`:

```php
$parser = new Parser($html);
$document = $parser->parse();

if ($parser->modules()->hasModule('css-selector')) {
    $cssModule = $parser->modules()->getModule('css-selector');
    $elements = $cssModule->querySelectorAll($document, '.class'); // прямой вызов публичного метода модуля
}
```

Модули могут расширять базовые классы (`Document`, `Element`) через композицию или динамическое добавление методов, но предпочтительный способ — предоставлять отдельный API через сам модуль (например, `$module->doSomething()`). При этом модули также могут реагировать на события, подписываясь на них в методе `initialize()`.

#### 2.4 Пример простого модуля

```php
namespace HtmlDomParser\Module\Example;

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;

class ExampleModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'example';
    }

    public function getDependencies(): array
    {
        return []; // нет зависимостей
    }

    public function supportsCoreVersion(string $version): bool
    {
        return version_compare($version, '1.0.0', '>=');
    }

    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe('pre-node', [$this, 'onPreNode'], 5);
    }

    public function onPreNode(NodeContext $context): NodeContext
    {
        // логика модуля
        return $context;
    }
}
```

---

### 3. Взаимодействие событий и модулей

Типичный сценарий работы модуля:
1. Модуль регистрируется в `ModuleManager`.
2. В методе `initialize()` модуль подписывается на нужные события через диспетчер, передавая свои методы-обработчики.
3. При наступлении события вызывается обработчик модуля, который модифицирует контекст.
4. После завершения парсинга модуль может предоставлять пользовательские методы для работы с полученным деревом (например, для поиска элементов).

Такая архитектура позволяет полностью изолировать расширения и легко подключать или отключать их без изменения кода ядра.

---

### 4. Автоподключение модулей через Composer

Модули могут быть оформлены как отдельные пакеты Composer. В корневом `composer.json` проекта указывается секция `extra.modules`, где перечисляются классы модулей:

```json
{
    "extra": {
        "modules": {
            "css-selector": "HtmlDomParser\\Module\\CssSelector\\CssSelectorModule",
            "xpath": "HtmlDomParser\\Module\\XPath\\XPathModule"
        }
    }
}
```

`ModuleManager` автоматически читает эту секцию и загружает указанные классы. Зависимости между модулями указываются в их собственных `composer.json` через стандартные `require` и разрешаются Composer'ом.