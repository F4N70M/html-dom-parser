[← К оглавлению](index.md)

## **Примеры использования**

---

### 1. Введение

В этом разделе приведены практические примеры использования библиотеки `HtmlDomParser` для решения типовых задач. Все примеры предполагают, что библиотека установлена и автозагрузка настроена.

---

### 2. Базовый парсинг и навигация

```php
use HtmlDomParser\Core\Parser;

$html = '<div class="content"><p>Hello <strong>world</strong>!</p></div>';
$parser = new Parser($html);
$document = $parser->parse();

$root = $document->getRootElement(); // элемент <div>
$p = $root->getChildren()[0];        // первый потомок — <p>

echo $p->getName();                   // 'p'
echo $p->getText();                    // 'Hello world!' (после схлопывания)
echo $p->getEntities()[0]['type'];    // 'strong'
```

---

### 3. Извлечение данных из ссылок

```php
$html = '<a href="https://example.com">Click <em>here</em></a>';
$parser = new Parser($html);
$document = $parser->parse();
$link = $document->getRootElement(); // <a>

echo $link->getData();   // 'https://example.com' (href)
echo $link->getLabel();  // 'Click here' (текст после схлопывания)
print_r($link->getEntities());
// Массив сущностей: одна сущность 'em' на слово 'here'
```

---

### 4. Обработка ошибок

```php
$parser = new Parser('<div><unknown>test</unknown></div>');
$handler = $parser->getErrorHandler();
$handler->setThrowOnNotice(false); // не прерываться на NOTICE

$document = $parser->parse();

if ($handler->hasErrors()) {
    foreach ($handler->getErrors() as $error) {
        echo $error->getLabel() . "\n"; // "Missing rule for tag 'unknown'"
        if ($error instanceof ErrorElement) {
            echo "Severity: " . $error->getSeverity() . "\n";
        }
    }
}
```

---

### 5. Использование модуля CSS-селектора (гипотетический)

```php
$parser = new Parser($html);
$document = $parser->parse();

$cssModule = $parser->modules()->getModule('css-selector');
if ($cssModule) {
    $elements = $cssModule->querySelectorAll($document, '.article p');
    foreach ($elements as $el) {
        echo $el->getText() . "\n";
    }
}
```

---

### 6. Создание собственного модуля

Допустим,我们需要 модуль, который добавляет атрибут `data-parsed` ко всем элементам.

**Модуль `AttributeMarkerModule.php`:**

```php
namespace MyApp\HtmlParserModule;

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Core\Context\NodeContext;

class AttributeMarkerModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'attribute-marker';
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
        $dispatcher->subscribe('post-node', [$this, 'onPostNode']);
    }

    public function onPostNode(NodeContext $context): NodeContext
    {
        $element = $context->toElement(); // здесь ещё не финализирован?
        // Лучше работать с контекстом, добавляя атрибут при создании элемента.
        // Но для примера используем событие post-node и изменим контекст,
        // добавив флаг, который позже повлияет на создание элемента.
        $context->setAttribute('data-parsed', 'true');
        return $context;
    }
}
```

Затем зарегистрировать модуль в `composer.json` проекта:

```json
{
    "extra": {
        "modules": {
            "attribute-marker": "MyApp\\HtmlParserModule\\AttributeMarkerModule"
        }
    }
}
```

После парсинга все элементы будут иметь атрибут `data-parsed="true"`.

---

### 7. Экспорт дерева в массив и HTML

```php
$document = $parser->parse();

// В массив (для отладки)
$array = $document->toArray();
print_r($array);

// Обратно в HTML
$html = $document->toHtml();
echo $html;
```