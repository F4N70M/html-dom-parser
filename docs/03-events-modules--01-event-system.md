[← К оглавлению](../README.md#-документация)

# Событийная модель {#event-system}

В этом разделе рассматривается событийная система библиотеки, которая позволяет расширять функциональность парсера, вмешиваясь в процесс обработки узлов на ключевых этапах.

## Общая концепция {#overview}

Событийная модель построена вокруг диспетчера событий ([`EventDispatcherInterface`](./04-appendix--02-api-reference.md#eventdispatcherinterface)), который оповещает все подписанные модули и обработчики в ключевые моменты обработки каждого узла. Это позволяет:

- Модифицировать контекст узла до и после обработки детей
- Влиять на процесс схлопывания строчных элементов
- Добавлять собственную логику валидации
- Обогащать узлы дополнительной информацией

### Работа с контекстом через события {#context-modification}

Объект контекста ([`NodeContextInterface`](./04-appendix--02-api-reference.md#nodecontextinterface)), передаваемый в обработчики событий, реализует механизм **ленивой выдачи информации** с кэшированием:

- Если свойство контекста еще не заполнено, оно автоматически извлекается из оригинального DOM-узла при первом обращении
- После извлечения значение кэшируется в контексте для последующих вызовов
- Обработчики событий могут **перезаписывать** эти свойства, и перезаписанные значения будут использоваться на всех следующих этапах обработки

Этот механизм позволяет эффективно модифицировать данные узла без повторного обращения к оригинальному DOM-дереву:

```php
// Пример: в обработчике pre-node перезаписываем атрибут
$dispatcher->subscribe(EventConstant::PRE_NODE, function(NodeContextInterface $context) {
    // Первое обращение — значение извлекается из DOMNode и кэшируется
    $oldClass = $context->getAttribute('class');
    // Перезаписываем — теперь везде будет использоваться новое значение
    $context->setAttribute('class', $oldClass . ' processed');
    // Возвращаем обновленный контекст
    return $context;
});
```

Таким образом, события дают полный контроль над данными узла на всех этапах его жизненного цикла.

## Поток событий в жизненном цикле узла {#event-flow}

```
Начало обработки узла
         │
         ▼
    PRE_NODE ◄──────────────────┐
         │                      │
         ▼                      │
Обработка детей (рекурсивно)    │
         │                      │
         ▼                      │
   PRE_INLINE_COLLAPSE ◄────────┤
         │                      │ Подписка
         ▼                      │ модулей
   Схлопывание (если нужно)     │
         │                      │
         ▼                      │
   POST_INLINE_COLLAPSE ◄───────┤
         │                      │
         ▼                      │
   POST_NODE ◄──────────────────┘
         │
         ▼
Конец обработки узла
```

Подробнее о жизненном цикле узла читайте в разделе [Система контекстов](./02-core--02-context-system.md#node-lifecycle).

## Список событий {#event-list}

Библиотека использует класс `EventConstant` для определения констант событий. Полный список доступен в [Справочнике API](./04-appendix--02-api-reference.md#eventconstant).

| Событие | Константа | Момент вызова | Типичное использование |
| :--- | :--- | :--- | :--- |
| `pre-node` | `PRE_NODE` | Сразу после создания контекста, до обработки детей | Модификация атрибутов, изменение типа контекста, добавление метаданных |
| `post-node` | `POST_NODE` | После обработки всех детей и схлопывания, перед преобразованием в элемент | Финальная модификация готового элемента, пост-обработка |
| `pre-inline-collapse` | `PRE_INLINE_COLLAPSE` | Перед запуском механизма схлопывания (если `allChildrenIsInline() === true`) | Изменение логики схлопывания, отключение схлопывания для определенных случаев |
| `post-inline-collapse` | `POST_INLINE_COLLAPSE` | После схлопывания, но до `post-node` | Модификация полученных фрагментов, корректировка объединенного текста |

## API Reference {#api-reference}

### EventDispatcherInterface {#eventdispatcherinterface}

```php
namespace HtmlDomParser\Contract;

interface EventDispatcherInterface
{
    /**
     * Регистрирует обработчик для события.
     *
     * @param string $event Название события (одна из констант EventConstant)
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

Полное описание интерфейса доступно в [Справочнике API](./04-appendix--02-api-reference.md#eventdispatcherinterface).

## Детальное описание {#details}

### Сигнатура обработчика {#handler-signature}

Каждый обработчик события должен соответствовать строгой сигнатуре:

```php
function(NodeContextInterface $context): NodeContextInterface
```

**Важные правила:**
1. Обработчик **обязан вернуть** модифицированный контекст
2. Контекст можно изменять (атрибуты, данные, метку)
3. Нельзя заменить контекст другим объектом — только модифицировать существующий
4. Исключения в обработчике прерывают выполнение цепочки

### Приоритеты обработчиков {#priorities}

Обработчики с более высоким приоритетом вызываются раньше:

```php
// Этот обработчик выполнится первым (приоритет 100)
$dispatcher->subscribe(EventConstant::PRE_NODE, $handler1, 100);

// Этот — вторым (приоритет 50)
$dispatcher->subscribe(EventConstant::PRE_NODE, $handler2, 50);

// Этот — последним (приоритет 0 по умолчанию)
$dispatcher->subscribe(EventConstant::PRE_NODE, $handler3);
```

Если несколько обработчиков имеют одинаковый приоритет, порядок вызова не гарантируется.

### Получение диспетчера событий {#getting-dispatcher}

Диспетчер событий доступен через менеджер модулей или внедряется в модули при инициализации:

```php
// Внутри модуля
public function initialize(EventDispatcherInterface $dispatcher): void
{
    $this->dispatcher = $dispatcher;
}
```

## Примеры кода {#examples}

### Пример 1: Добавление атрибута всем div-элементам {#example-add-attribute}

```php
<?php

use HtmlDomParser\Parser;
use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Core\Event\EventConstant;

$parser = new Parser($html);
// Получение диспетчера (зависит от реализации)
$dispatcher = $parser->getModuleManager()->getEventDispatcher();

$dispatcher->subscribe(EventConstant::PRE_NODE, function(NodeContextInterface $context) {
    if ($context->getName() === 'div') {
        // Добавляем атрибут с временной меткой
        $context->setAttribute('data-parsed', date('Y-m-d H:i:s'));
    }
    return $context;
}, 10);

$document = $parser->parse();
```

### Пример 2: Отключение схлопывания для определенных элементов {#example-disable-collapse}

```php
<?php

use HtmlDomParser\Core\Event\EventConstant;

$dispatcher->subscribe(EventConstant::PRE_INLINE_COLLAPSE, function(NodeContextInterface $context) {
    $tag = $context->getName();
    
    // Не схлопывать содержимое внутри тегов <pre> и <code>
    if (in_array($tag, ['pre', 'code'])) {
        // Модифицируем контекст, чтобы allChildrenIsInline() вернул false
        // Например, можно изменить тип контекста или установить флаг
        // $context->setSomeFlag(false);
    }
    
    return $context;
});
```

> **Примечание:** Для полного контроля над схлопыванием требуется более глубокая модификация контекста. Подробнее в разделе [InlineCollapser](./02-core--03-utilities.md#inline-collapser).

### Пример 3: Логирование процесса парсинга {#example-logging}

```php
<?php

use HtmlDomParser\Core\Event\EventConstant;

$logger = new Logger();

// Подписываемся на несколько событий для отслеживания прогресса
$dispatcher->subscribe(EventConstant::PRE_NODE, function(NodeContextInterface $context) use ($logger) {
    $logger->debug('Начало обработки узла: ' . $context->getName());
    return $context;
});

$dispatcher->subscribe(EventConstant::POST_NODE, function(NodeContextInterface $context) use ($logger) {
    $logger->debug('Завершение обработки узла: ' . $context->getName());
    return $context;
});

$dispatcher->subscribe(EventConstant::PRE_INLINE_COLLAPSE, function(NodeContextInterface $context) use ($logger) {
    $logger->info('Схлопывание для узла: ' . $context->getName());
    return $context;
});
```

### Пример 4: Валидация структуры HTML {#example-validation}

```php
<?php

use HtmlDomParser\Core\Event\EventConstant;

$dispatcher->subscribe(EventConstant::POST_NODE, function(NodeContextInterface $context) {
    static $validationErrors = [];
    
    if ($context->getName() === 'ul') {
        foreach ($context->getChildren() as $child) {
            if ($child->getName() !== 'li') {
                $validationErrors[] = 'В <ul> найден неразрешенный тег: ' . $child->getName();
            }
        }
    }
    
    // Здесь можно добавить ошибки в обработчик ошибок
    // См. раздел [Обработка ошибок](./02-core--04-error-handling.md)
    
    return $context;
});
```

### Пример 5: Модуль для подсчета вложенности {#example-nesting}

```php
<?php

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Core\Event\EventConstant;

class NestingCounterModule implements ModuleInterface
{
    private array $depth = [];
    
    public function getName(): string
    {
        return 'nesting_counter';
    }
    
    public function getDependencies(): array
    {
        return [];
    }
    
    public function supportsCoreVersion(string $version): bool
    {
        return true;
    }
    
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe(EventConstant::PRE_NODE, [$this, 'onPreNode']);
        $dispatcher->subscribe(EventConstant::POST_NODE, [$this, 'onPostNode']);
    }
    
    public function onPreNode(NodeContextInterface $context): NodeContextInterface
    {
        $parent = $context->getParent();
        $currentDepth = $parent ? ($this->depth[spl_object_id($parent)] + 1) : 0;
        $this->depth[spl_object_id($context)] = $currentDepth;
        
        // Добавляем глубину как атрибут
        $context->setAttribute('data-depth', $currentDepth);
        
        return $context;
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        unset($this->depth[spl_object_id($context)]);
        return $context;
    }
}
```

### Пример 6: Модификация Data через контекст {#example-modify-data}

```php
<?php

use HtmlDomParser\Core\Event\EventConstant;

$dispatcher->subscribe(EventConstant::POST_NODE, function(NodeContextInterface $context) {
    // Модифицируем Data для всех ссылок
    if ($context->getName() === 'a') {
        $originalUrl = $context->getData();
        $context->setData('https://proxy.com/?url=' . urlencode($originalUrl));
    }
    return $context;
});

$document = $parser->parse();
$link = $document->getChildren()->get(0);
echo $link->getData(); // https://proxy.com/?url=https://original.com
```

## Создание собственных событий {#custom-events}

Модули могут генерировать собственные события:

```php
use HtmlDomParser\Core\Event\EventConstant;

class CustomModule implements ModuleInterface
{
    private EventDispatcherInterface $dispatcher;
    
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
        $dispatcher->subscribe(EventConstant::POST_NODE, [$this, 'onPostNode']);
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        if ($context->getName() === 'article') {
            // Генерируем пользовательское событие
            $this->dispatcher->dispatch('custom.article.processed', $context);
        }
        return $context;
    }
}
```

## Рекомендации по использованию {#best-practices}

1. **Не изменяйте структуру дерева в обработчиках** — добавление/удаление узлов лучше делать через стандартные механизмы
2. **Будьте осторожны с производительностью** — обработчики вызываются для каждого узла
3. **Используйте приоритеты** для управления порядком выполнения
4. **Всегда возвращайте контекст** — даже если не вносили изменений
5. **Не полагайтесь на порядок вызова** обработчиков с одинаковым приоритетом

## Связанные разделы {#see-also}

- [Справочник API: EventDispatcherInterface](./04-appendix--02-api-reference.md#eventdispatcherinterface)
- [Справочник API: NodeContextInterface](./04-appendix--02-api-reference.md#nodecontextinterface)
- [Справочник API: EventConstant](./04-appendix--02-api-reference.md#eventconstant)
- [Система модулей](./03-events-modules--02-modules.md) — модули как основной способ подписки на события
- [Система контекстов](./02-core--02-context-system.md) — что можно модифицировать в контексте
- [InlineCollapser](./02-core--03-utilities.md#inline-collapser) — события pre-inline-collapse/post-inline-collapse
- [Модель данных](./02-core--01-data-model.md) — модификация Data через события