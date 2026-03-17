[← К оглавлению](../README.md#-документация)

# Система модулей {#modules}

В этом разделе рассматривается создание, обнаружение и подключение модулей для расширения возможностей библиотеки. Модули оформляются как Composer-пакеты и автоматически интегрируются в процесс парсинга.

## Общая концепция {#overview}

Модули — это основной способ расширения функциональности HtmlDomParser. Они позволяют:

- Подписываться на события и модифицировать контекст узлов (см. [Событийная модель](./03-events-modules--01-event-system.md))
- Добавлять новые методы для работы с готовым деревом
- Предоставлять дополнительные сервисы и утилиты
- Интегрироваться с другими библиотеками

Модули автоматически обнаруживаются через секцию `extra.modules` в `composer.json` и загружаются с проверкой зависимостей.

## Жизненный цикл модуля {#lifecycle}

```
composer.json (extra.modules)
         │
         ▼
   Обнаружение (ModuleManagerInterface::discover())
         │
         ▼
   Проверка зависимостей
         │
         ▼
   Загрузка классов модулей
         │
         ▼
   Инициализация (ModuleInterface::initialize())
         │
         ▼
   Подписка на события через EventDispatcherInterface
         │
         ▼
   Модуль готов к использованию
```

## API Reference {#api-reference}

### ModuleInterface {#moduleinterface}

Базовый интерфейс для всех модулей. Полное описание методов см. в [Справочнике API](./04-appendix--02-api-reference.md#moduleinterface).

```php
namespace HtmlDomParser\Contract;

interface ModuleInterface
{
    public function getName(): string;
    public function getDependencies(): array;
    public function supportsCoreVersion(string $version): bool;
    public function initialize(EventDispatcherInterface $dispatcher): void;
}
```

### ModuleManagerInterface {#modulemanagerinterface}

Менеджер модулей отвечает за обнаружение, загрузку и предоставление доступа к модулям. Доступен через [`ParserInterface::getModuleManager()`](./04-appendix--02-api-reference.md#parserinterface). Полное описание методов см. в [Справочнике API](./04-appendix--02-api-reference.md#modulemanagerinterface).

```php
namespace HtmlDomParser\Contract;

interface ModuleManagerInterface
{
    public function discover(): array;
    public function loadModules(): void;
    public function getModule(string $name): ?ModuleInterface;
    public function hasModule(string $name): bool;
    public function getLoadedModules(): array;
    public function registerModule(ModuleInterface $module): void;
}
```

## Предварительные требования {#prerequisites}

Для создания модуля необходимо:

- Понимание [событийной модели](./03-events-modules--01-event-system.md)
- Базовые знания Composer и создания пакетов
- Знание структуры [контекста узла](./02-core--02-context-system.md)

## Пошаговые инструкции по созданию модуля {#step-by-step}

### Шаг 1: Создание класса модуля {#step-1}

Создайте класс, реализующий [`ModuleInterface`](./04-appendix--02-api-reference.md#moduleinterface):

```php
<?php

namespace Vendor\Package;

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Core\Event\EventConstant;

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
        // Подписка на события
        $dispatcher->subscribe(EventConstant::POST_NODE, [$this, 'onPostNode']);
    }
    
    public function onPostNode(NodeContextInterface $context): NodeContextInterface
    {
        // Логика модуля
        if ($context->getName() === 'img' && !$context->hasAttribute('alt')) {
            $context->setAttribute('data-seo-warning', 'missing-alt');
        }
        
        return $context;
    }
}
```

### Шаг 2: Определение зависимостей {#step-2}

Если ваш модуль зависит от других модулей, укажите их в `getDependencies()`:

```php
public function getDependencies(): array
{
    return ['css-selector', 'validator'];
}
```

Менеджер модулей автоматически загрузит зависимости перед вашим модулем.

### Шаг 3: Реализация инициализации {#step-3}

В методе `initialize()` подпишитесь на необходимые события с учетом приоритетов:

```php
use HtmlDomParser\Core\Event\EventConstant;

public function initialize(EventDispatcherInterface $dispatcher): void
{
    // Подписка с разными приоритетами
    $dispatcher->subscribe(EventConstant::PRE_NODE, [$this, 'onPreNode'], 100);  // высокий приоритет
    $dispatcher->subscribe(EventConstant::POST_NODE, [$this, 'onPostNode'], 50); // средний приоритет
    $dispatcher->subscribe(EventConstant::PRE_INLINE_COLLAPSE, [$this, 'onPreInlineCollapse']); // приоритет 0
}
```

### Шаг 4: Добавление секции в composer.json {#step-4}

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

### Шаг 5: Публикация или локальное подключение {#step-5}

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

## Примеры кода {#examples}

### Пример 1: Минимальный модуль {#example-minimal}

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

### Пример 2: Модуль с конфигурацией {#example-configurable}

```php
<?php

namespace MyModule;

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Core\Event\EventConstant;

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
        $dispatcher->subscribe(EventConstant::PRE_NODE, function(NodeContextInterface $context) {
            if ($this->options['add-class']) {
                $class = $context->getAttribute('class') ?? '';
                $context->setAttribute('class', trim($class . ' ' . $this->options['add-class']));
            }
            return $context;
        });
    }
}
```

### Пример 3: composer.json модуля с зависимостями {#example-composer}

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

### Пример 4: Использование модуля в проекте {#example-usage}

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

### Пример 5: Модуль, добавляющий ошибки валидации {#example-validation}

```php
<?php

use HtmlDomParser\Contract\ModuleInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Contract\ErrorHandlerInterface;
use HtmlDomParser\Contract\ErrorElementInterface;
use HtmlDomParser\Core\Event\EventConstant;
use HtmlDomParser\Core\Error\ErrorConstant;

class ValidationModule implements ModuleInterface
{
    private ErrorHandlerInterface $errorHandler;
    
    public function getName(): string
    {
        return 'validator';
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
        // Получение обработчика ошибок (зависит от реализации)
        $this->errorHandler = $errorHandler;
        
        $dispatcher->subscribe(EventConstant::POST_NODE, [$this, 'validateNode']);
    }
    
    public function validateNode(NodeContextInterface $context): NodeContextInterface
    {
        if ($context->getName() === 'img' && !$context->hasAttribute('alt')) {
            // Добавление ошибки в обработчик
            // См. раздел [Обработка ошибок](./02-core--04-error-handling.md)
            $error = $this->createError(
                'missingAlt',
                'Изображение не имеет alt-текста',
                ErrorConstant::SEVERITY_WARNING
            );
            $this->errorHandler->addError($error);
        }
        return $context;
    }
    
    private function createError(string $type, string $message, string $severity): ErrorElementInterface
    {
        // Создание узла-ошибки (упрощенно)
        // В реальности создается через соответствующий сервис
    }
}
```

## Лучшие практики {#best-practices}

1. **Именование модулей** — используйте уникальные имена, желательно в формате `vendor/name`
2. **Обработка ошибок** — не допускайте падения парсера из-за ошибок в модуле
3. **Документация** — документируйте события, на которые подписывается модуль
4. **Тестирование** — тестируйте модуль с разными версиями ядра
5. **Конфигурация** — используйте конфигурацию через `extra.modules` для гибкости

## Возможные проблемы {#troubleshooting}

При работе с модулями могут возникать типичные сложности: циклические зависимости, несовместимость версий, проблемы с обнаружением модулей и конфликты обработчиков событий.

Подробное описание этих проблем и методы их решения вы найдете в разделе:

👉 **[FAQ: Проблемы с модулями](./04-appendix--01-faq.md#проблемы-с-модулями)**

## Связанные разделы {#see-also}

- [Справочник API: ModuleInterface](./04-appendix--02-api-reference.md#moduleinterface)
- [Справочник API: ModuleManagerInterface](./04-appendix--02-api-reference.md#modulemanagerinterface)
- [Событийная модель](./03-events-modules--01-event-system.md) — подписка на события
- [Система контекстов](./02-core--02-context-system.md) — модификация узлов
- [Обработка ошибок](./02-core--04-error-handling.md) — добавление ошибок из модулей