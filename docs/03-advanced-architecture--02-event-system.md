[← К оглавлению](../README.md#📖-документация)

# Событийная модель

В этом разделе рассматривается событийная система библиотеки, которая позволяет расширять функциональность парсера, вмешиваясь в процесс обработки узлов на ключевых этапах.

## Общая концепция

Событийная модель построена вокруг диспетчера событий (`EventDispatcherInterface`), который оповещает все подписанные модули и обработчики в ключевые моменты обработки каждого узла. Это позволяет:

- Модифицировать контекст узла до и после обработки детей
- Влиять на процесс схлопывания строчных элементов
- Добавлять собственную логику валидации
- Обогащать узлы дополнительной информацией

### Работа с контекстом через события

Объект контекста (`NodeContextInterface`), передаваемый в обработчики событий, реализует механизм **ленивой выдачи информации** с кэшированием:

- Если свойство контекста еще не заполнено, оно автоматически извлекается из оригинального DOM-узла при первом обращении
- После извлечения значение кэшируется в контексте для последующих вызовов
- Обработчики событий могут **перезаписывать** эти свойства, и перезаписанные значения будут использоваться на всех следующих этапах обработки

Этот механизм позволяет эффективно модифицировать данные узла без повторного обращения к оригинальному DOM-дереву:

```php
// Пример: в обработчике pre-node перезаписываем атрибут
$dispatcher->subscribe('pre-node', function(NodeContextInterface $context) {
    // Первое обращение — значение извлекается из DOMNode и кэшируется
    $oldClass = $context->getAttribute('class');
    // Перезаписываем — теперь везде будет использоваться новое значение
    $context->setAttribute('class', $oldClass . ' processed');
    // Возвращаем обновленный контекст
    return $context;
});
```

Таким образом, события дают полный контроль над данными узла на всех этапах его жизненного цикла.

## Поток событий в жизненном цикле узла

```
Начало обработки узла
         │
         ▼
    pre-node ◄──────────────────┐
         │                      │
         ▼                      │
Обработка детей                 │
         │                      │
         ▼                      │
   pre-inline-collapse ◄────────┤
         │                      │ Подписка
         ▼                      │ модулей
   Схлопывание (если нужно)     │
         │                      │
         ▼                      │
   post-inline-collapse ◄───────┤
         │                      │
         ▼                      │
   post-node ◄──────────────────┘
         │
         ▼
Конец обработки узла
```

## Список событий

| Событие | Момент вызова | Типичное использование |
| :--- | :--- | :--- |
| `pre-node` | Сразу после создания контекста, до обработки детей | Модификация атрибутов, изменение типа контекста, добавление метаданных |
| `post-node` | После обработки всех детей и схлопывания, перед преобразованием в элемент | Финальная модификация готового элемента, пост-обработка |
| `pre-inline-collapse` | Перед запуском механизма схлопывания (если `allChildrenIsInline() === true`) | Изменение логики схлопывания, отключение схлопывания для определенных случаев |
| `post-inline-collapse` | После схлопывания, но до `post-node` | Модификация полученных entities, корректировка объединенного текста |

## API Reference

### EventDispatcherInterface

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

## Детальное описание

### Сигнатура обработчика

Каждый обработчик события должен соответствовать строгой сигнатуре:

```php
function(NodeContextInterface $context): NodeContextInterface
```

**Важные правила:**
1. Обработчик **обязан вернуть** модифицированный контекст
2. Контекст можно изменять (атрибуты, данные, метку)
3. Нельзя заменить контекст другим объектом — только модифицировать существующий
4. Исключения в обработчике прерывают выполнение цепочки

### Приоритеты обработчиков

Обработчики с более высоким приоритетом вызываются раньше:

```php
// Этот обработчик выполнится первым (приоритет 100)
$dispatcher->subscribe('pre-node', $handler1, 100);

// Этот — вторым (приоритет 50)
$dispatcher->subscribe('pre-node', $handler2, 50);

// Этот — последним (приоритет 0 по умолчанию)
$dispatcher->subscribe('pre-node', $handler3);
```

Если несколько обработчиков имеют одинаковый приоритет, порядок вызова не гарантируется.

## Примеры кода

### Пример 1: Добавление атрибута всем div-элементам

```php
<?php

use HtmlDomParser\Parser;
use HtmlDomParser\Contract\NodeContextInterface;

// Создаем парсер
$parser = new Parser($html);
$dispatcher = $parser->getEventDispatcher(); // гипотетический метод получения диспетчера

// Подписываемся на событие pre-node
$dispatcher->subscribe('pre-node', function(NodeContextInterface $context) {
    // Добавляем атрибут всем div-ам
    if ($context->getName() === 'div') {
        $attributes = $context->getNode()->attributes;
        // Работаем через DOMNode, так как контекст еще не стал элементом
        // В реальности потребуется более сложная логика
        $context->setAttribute('data-parsed', date('Y-m-d H:i:s'));
    }
    
    return $context;
}, 10);

// Запускаем парсинг
$document = $parser->parse();
```

### Пример 2: Отключение схлопывания для определенных элементов

```php
<?php

$dispatcher->subscribe('pre-inline-collapse', function(NodeContextInterface $context) {
    $tag = $context->getName();
    
    // Не схлопывать содержимое внутри тегов <pre> и <code>
    if (in_array($tag, ['pre', 'code'])) {
        // Возвращаем контекст без изменений, но схлопывание не запустится?
        // В реальности нужно изменить условие allChildrenIsInline()
        // или установить специальный флаг в контексте
    }
    
    return $context;
});
```

> **Примечание:** Для полного контроля над схлопыванием требуется более глубокая модификация контекста, включая изменение его свойств.

### Пример 3: Логирование процесса парсинга

```php
<?php

$logger = new Logger();

// Подписываемся на несколько событий для отслеживания прогресса
$dispatcher->subscribe('pre-node', function(NodeContextInterface $context) use ($logger) {
    $logger->debug('Начало обработки узла: ' . $context->getName());
    return $context;
});

$dispatcher->subscribe('post-node', function(NodeContextInterface $context) use ($logger) {
    $logger->debug('Завершение обработки узла: ' . $context->getName());
    return $context;
});

$dispatcher->subscribe('pre-inline-collapse', function(NodeContextInterface $context) use ($logger) {
    $logger->info('Схлопывание для узла: ' . $context->getName());
    return $context;
});
```

### Пример 4: Валидация структуры HTML

```php
<?php

$dispatcher->subscribe('post-node', function(NodeContextInterface $context) {
    static $validationErrors = [];
    
    // Проверяем, есть ли у элемента неразрешенные дети
    // (это уже проверяется библиотекой, но мы можем добавить свою логику)
    
    if ($context->getName() === 'ul') {
        foreach ($context->getChildren() as $child) {
            if ($child->getName() !== 'li') {
                $validationErrors[] = 'В <ul> найден неразрешенный тег: ' . $child->getName();
            }
        }
    }
    
    return $context;
});
```

### Пример 5: Модуль для подсчета вложенности

```php
<?php

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
        $dispatcher->subscribe('pre-node', [$this, 'onPreNode']);
        $dispatcher->subscribe('post-node', [$this, 'onPostNode']);
    }
    
    public function onPreNode(NodeContextInterface $context): NodeContextInterface
    {
        $parent = $context->getParent();
        $currentDepth = $parent ? ($this->depth[spl_object_id($parent)] + 1) : 0;
        $this->depth[spl_object_id($context)] = $currentDepth;
        
        // Добавляем глубину как атрибут (позже станет атрибутом элемента)
        $context->setAttribute('data-depth', $currentDepth);
        
        return $context;
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        // Очищаем временные данные
        unset($this->depth[spl_object_id($context)]);
        return $context;
    }
}
```

### Пример 6: Модификация Data через контекст

```php
<?php

$dispatcher->subscribe('post-node', function(NodeContextInterface $context) {
    // Модифицируем Data для всех ссылок
    if ($context->getName() === 'a') {
        $originalUrl = $context->getData(); // Получаем原始льное значение
        $context->setData('https://proxy.com/?url=' . urlencode($originalUrl));
    }
    
    return $context;
});

// После парсинга все ссылки будут иметь модифицированный Data
$document = $parser->parse();
$link = $document->getChildren()->get(0);
echo $link->getData(); // https://proxy.com/?url=https://original.com
```

## Создание собственных событий

Модули могут генерировать собственные события, хотя это требует доступа к диспетчеру:

```php
class CustomModule implements ModuleInterface
{
    private EventDispatcherInterface $dispatcher;
    
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
        
        // Подписываемся на системные события
        $dispatcher->subscribe('post-node', [$this, 'onPostNode']);
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        // Генерируем пользовательское событие
        if ($context->getName() === 'article') {
            // Создаем копию контекста или специальный объект для события
            $this->dispatcher->dispatch('custom.article.processed', $context);
        }
        
        return $context;
    }
}
```

## Рекомендации по использованию

1. **Не изменяйте структуру дерева в обработчиках** — добавление/удаление узлов лучше делать через стандартные механизмы
2. **Будьте осторожны с производительностью** — обработчики вызываются для каждого узла
3. **Используйте приоритеты** для управления порядком выполнения
4. **Всегда возвращайте контекст** — даже если не вносили изменений
5. **Не полагайтесь на порядок вызова** обработчиков с одинаковым приоритетом

## Связанные разделы

- [Система модулей](./03-advanced-architecture--03-modules.md) — модули как основной способ подписки на события
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — что можно модифицировать в контексте
- [InlineCollapser](./04-utilities--02-inline-collapser.md) — события pre-inline-collapse/post-inline-collapse
- [Ядро системы](./02-core-components--01-core-interfaces.md) — базовые интерфейсы