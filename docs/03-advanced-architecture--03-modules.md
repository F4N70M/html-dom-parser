[← К оглавлению](../README.md#📖-документация)

# Система модулей

В этом разделе рассматривается создание, обнаружение и подключение модулей для расширения возможностей библиотеки. Модули оформляются как Composer-пакеты и автоматически интегрируются в процесс парсинга.

## Общая концепция

Модули — это основной способ расширения функциональности HtmlDomParser. Они позволяют:

- Подписываться на события и модифицировать контекст узлов
- Добавлять новые методы для работы с готовым деревом
- Предоставлять дополнительные сервисы и утилиты
- Интегрироваться с другими библиотеками

Модули автоматически обнаруживаются через секцию `extra.modules` в `composer.json` и загружаются с проверкой зависимостей.

## Жизненный цикл модуля

```
composer.json (extra.modules)
         │
         ▼
   Обнаружение (ModuleManager::discover())
         │
         ▼
   Проверка зависимостей
         │
         ▼
   Загрузка классов модулей
         │
         ▼
   Инициализация (Module::initialize())
         │
         ▼
   Подписка на события
         │
         ▼
   Модуль готов к использованию
```

## API Reference

### ModuleInterface

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

### ModuleManagerInterface

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

## Предварительные требования

Для создания модуля необходимо:

- Понимание [событийной модели](./03-advanced-architecture--02-event-system.md)
- Базовые знания Composer и создания пакетов
- Знание структуры [контекста узла](./03-advanced-architecture--01-context-system.md)

## Пошаговые инструкции по созданию модуля

### Шаг 1: Создание класса модуля

Создайте класс, реализующий `ModuleInterface`:

```php
<?php

namespace Vendor\Package;

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Contract\NodeContextInterface;

class SeoModule implements ModuleInterface
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    public function getName(): string
    {
        return 'seo';
    }
    
    public function getDependencies(): array
    {
        return []; // Нет зависимостей
    }
    
    public function supportsCoreVersion(string $version): bool
    {
        // Поддерживаем версии 1.0 и выше
        return version_compare($version, '1.0.0', '>=');
    }
    
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        // Подписка на события будет здесь
        $dispatcher->subscribe('post-node', [$this, 'onPostNode']);
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        // Логика модуля
        if ($context->getName() === 'img' && !$context->hasAttribute('alt')) {
            // Добавляем предупреждение для изображений без alt
            $context->setAttribute('data-seo-warning', 'missing-alt');
        }
        
        return $context;
    }
}
```

### Шаг 2: Определение зависимостей

Если ваш модуль зависит от других модулей, укажите их в `getDependencies()`:

```php
public function getDependencies(): array
{
    return ['css-selector', 'validator'];
}
```

Менеджер модулей автоматически загрузит зависимости перед вашим модулем.

### Шаг 3: Реализация инициализации

В методе `initialize()` подпишитесь на необходимые события:

```php
public function initialize(EventDispatcherInterface $dispatcher): void
{
    // Подписка с разными приоритетами
    $dispatcher->subscribe('pre-node', [$this, 'onPreNode'], 100);  // высокий приоритет
    $dispatcher->subscribe('post-node', [$this, 'onPostNode'], 50); // средний приоритет
    $dispatcher->subscribe('pre-inline-collapse', [$this, 'onPreInlineCollapse']); // приоритет 0
}
```

### Шаг 4: Добавление секции в composer.json

В `composer.json` вашего пакета добавьте секцию `extra.modules`:

```json
{
    "name": "vendor/seo-module",
    "description": "SEO analysis module for HtmlDomParser",
    "type": "library",
    "require": {
        "php": ">=7.4",
        "f4n70m/html-dom-parser": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Vendor\\SeoModule\\": "src/"
        }
    },
    "extra": {
        "modules": {
            "Vendor\\SeoModule\\SeoModule": {
                "config": {
                    "check-alt": true,
                    "check-title": true
                }
            }
        }
    }
}
```

### Шаг 5: Публикация или локальное подключение

Опубликуйте пакет на Packagist или используйте локальный репозиторий:

```json
{
    "require": {
        "vendor/seo-module": "^1.0"
    },
    "repositories": [
        {
            "type": "path",
            "url": "./packages/seo-module"
        }
    ]
}
```

## Примеры кода

### Пример 1: Минимальный модуль

```php
<?php

namespace MyModule;

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;

class MinimalModule implements ModuleInterface
{
    public function getName(): string
    {
        return 'minimal';
    }
    
    public function getDependencies(): array
    {
        return [];
    }
    
    public function supportsCoreVersion(string $version): bool
    {
        return true; // Поддерживаем все версии
    }
    
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        // Без подписок — модуль ничего не делает
    }
}
```

### Пример 2: Модуль с конфигурацией

```php
<?php

namespace MyModule;

class ConfigurableModule implements ModuleInterface
{
    private array $options;
    
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'add-class' => 'processed',
            'log-level' => 'info'
        ], $options);
    }
    
    public function getName(): string
    {
        return 'configurable';
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
        $dispatcher->subscribe('pre-node', function($context) {
            if ($this->options['add-class']) {
                $class = $context->getAttribute('class') ?? '';
                $context->setAttribute('class', trim($class . ' ' . $this->options['add-class']));
            }
            return $context;
        });
    }
}
```

### Пример 3: composer.json модуля с зависимостями

```json
{
    "name": "mycompany/advanced-parser-module",
    "description": "Advanced parsing features for HtmlDomParser",
    "type": "library",
    "require": {
        "php": ">=8.0",
        "f4n70m/html-dom-parser": "^1.0",
        "mycompany/css-selector-module": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "MyCompany\\AdvancedModule\\": "src/"
        }
    },
    "extra": {
        "modules": {
            "MyCompany\\AdvancedModule\\AdvancedModule": {
                "config": {
                    "enable-xpath": true,
                    "max-depth": 100
                }
            }
        }
    }
}
```

### Пример 4: Использование модуля в проекте

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use HtmlDomParser\Parser;

$parser = new Parser($html);

// Модули автоматически загружены менеджером
$moduleManager = $parser->getModuleManager();

// Проверка загрузки модуля
if ($moduleManager->hasModule('seo')) {
    echo "SEO модуль загружен\n";
    
    // Получение экземпляра модуля
    $seoModule = $moduleManager->getModule('seo');
}

// Парсинг с активными модулями
$document = $parser->parse();

// Модули уже повлияли на результат через события
```

## Возможные проблемы

При работе с модулями могут возникать типичные сложности: циклические зависимости, несовместимость версий, проблемы с обнаружением модулей и конфликты обработчиков событий.

Подробное описание этих проблем и методы их решения вы найдете в разделе:

👉 **[FAQ: Проблемы с модулями](./05-appendix--01-faq-troubleshooting.md#проблемы-с-модулями)**

Там рассматриваются:
- Циклические зависимости между модулями
- Несовместимость с версией ядра
- Модуль не обнаруживается (ошибки в секции extra)
- Конфликты обработчиков событий

## Лучшие практики

1. **Именование модулей** — используйте уникальные имена, желательно в формате `vendor/name`
2. **Обработка ошибок** — не допускайте падения парсера из-за ошибок в модуле
3. **Документация** — документируйте события, на которые подписывается модуль
4. **Тестирование** — тестируйте модуль с разными версиями ядра
5. **Конфигурация** — используйте конфигурацию через extra.modules для гибкости

## Связанные разделы

- [Событийная модель](./03-advanced-architecture--02-event-system.md) — подписка на события
- [Система контекстов](./03-advanced-architecture--01-context-system.md) — модификация узлов
- [Ядро системы](./02-core-components--01-core-interfaces.md) — базовые интерфейсы
- [Обработка ошибок](./03-advanced-architecture--04-error-handling.md) — обработка ошибок в модулях