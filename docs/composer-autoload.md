[← К оглавлению](index.md)

## **Автоподключение и Composer**

---

### 1. Введение

Библиотека `HtmlDomParser` разработана с учётом современной экосистемы PHP и максимально интегрируется с Composer. Это обеспечивает автоматическую загрузку классов (PSR-4), управление зависимостями и простоту установки модулей. Данный документ описывает, как организована интеграция с Composer, как подключать библиотеку в проекте и как разрабатывать собственные модули с возможностью автоподключения.

---

### 2. Установка библиотеки

#### 2.1 Через Packagist (в будущем)
Когда библиотека будет опубликована, установка будет выполняться стандартной командой:
```bash
composer require html-dom-parser/core
```

#### 2.2 Локальная установка (текущая версия)
Если библиотека находится в локальной директории (например, `packages/html-dom-parser`), добавьте в `composer.json` проекта:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/html-dom-parser"
        }
    ],
    "require": {
        "html-dom-parser/core": "*"
    }
}
```

Затем выполните `composer update`.

---

### 3. Автозагрузка PSR-4

В `composer.json` библиотеки прописана автозагрузка:

```json
{
    "autoload": {
        "psr-4": {
            "HtmlDomParser\\": "src/HtmlDomParser/"
        }
    }
}
```

Это гарантирует, что все классы из пространства имён `HtmlDomParser` будут автоматически загружены из директории `src/HtmlDomParser/` после выполнения `composer dump-autoload`.

---

### 4. Подключение модулей через `extra.modules`

Библиотека поддерживает модульное расширение. Модули могут быть как встроенными (в директории `Module/`), так и сторонними, установленными через Composer. Для автоматического обнаружения модулей используется секция `extra.modules` в корневом `composer.json` проекта.

**Пример корневого `composer.json` с подключением двух модулей:**

```json
{
    "require": {
        "html-dom-parser/core": "*",
        "html-dom-parser/css-selector": "^1.0",
        "html-dom-parser/xpath": "^1.0"
    },
    "extra": {
        "modules": {
            "css-selector": "HtmlDomParser\\Module\\CssSelector\\CssSelectorModule",
            "xpath": "HtmlDomParser\\Module\\XPath\\XPathModule"
        }
    }
}
```

**Как это работает:**
1. `ModuleManager` при инициализации читает секцию `extra.modules` из файла `composer.json` проекта (или из `composer.lock`).
2. Для каждого указанного класса проверяется, реализует ли он интерфейс `ModuleInterface`.
3. Выполняется проверка зависимостей модулей (если они объявлены в методе `getDependencies()`).
4. Модули сортируются в порядке, удовлетворяющем зависимостям (топологическая сортировка).
5. Каждый модуль инициализируется вызовом `initialize(EventDispatcherInterface $dispatcher)`.

---

### 5. Структура модуля

Каждый модуль должен:
- Реализовывать интерфейс `HtmlDomParser\Contract\ModuleInterface`.
- Иметь уникальное имя (`getName()`).
- При необходимости объявлять зависимости (`getDependencies()`), возвращая массив имён модулей.
- Проверять совместимость с версией ядра (`supportsCoreVersion()`).
- В методе `initialize()` подписываться на события через диспетчер.

**Пример минимального модуля (`ExampleModule.php`):**

```php
namespace MyApp\HtmlParserModule;

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
        $dispatcher->subscribe('pre-node', [$this, 'onPreNode']);
    }

    public function onPreNode(NodeContext $context): NodeContext
    {
        // логика модуля
        return $context;
    }
}
```

Если модуль устанавливается через Composer, его автозагрузка уже настроена, и `ModuleManager` сможет создать экземпляр класса.

---

### 6. Инициализация модулей в парсере

При создании объекта `Parser` автоматически создаётся `ModuleManager`, который:
- Загружает список модулей из `extra.modules`.
- Проверяет зависимости и совместимость.
- Инициализирует модули, передавая им глобальный диспетчер событий.

Модули становятся доступны через метод `$parser->modules()`, который возвращает экземпляр `ModuleManager`. Это позволяет получать конкретные модули по имени:

```php
$cssModule = $parser->modules()->getModule('css-selector');
if ($cssModule) {
    $elements = $cssModule->querySelectorAll($document, '.class');
}
```

---

### 7. Ручная регистрация модулей (без Composer)

Если проект не использует Composer или требуется загрузить модуль, не указанный в `extra.modules`, можно зарегистрировать его вручную:

```php
$moduleManager = $parser->modules();
$moduleManager->registerModule(new MyCustomModule());
```

При этом модуль должен быть уже создан и инициализирован.