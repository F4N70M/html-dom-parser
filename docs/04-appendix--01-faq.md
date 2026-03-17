[← К оглавлению](../README.md#-документация)

# FAQ и решение проблем {#faq}

В этом разделе собраны ответы на частые вопросы и решения типичных проблем при работе с HtmlDomParser.

## Содержание {#contents}

- [Установка и настройка](#установка-и-настройка)
- [Парсинг и работа с данными](#парсинг-и-работа-с-данными)
- [Модули](#проблемы-с-модулями)
- [Обработка ошибок](#проблемы-с-обработкой-ошибок)
- [ContextDataResolver](#проблемы-с-contextdataresolver)
- [InlineCollapser](#проблемы-с-inlinecollapser)
- [Производительность](#производительность)
- [Прочее](#прочее)

---

## Установка и настройка {#установка-и-настройка}

### Вопрос: Какие требования к PHP для работы библиотеки? {#requirements}

**Ответ:** Библиотека требует PHP версии 7.4+ и расширение `ext-dom` (обычно включено в стандартных сборках PHP). Подробнее в разделе [Установка](./01-general-information--02-installation.md).

### Вопрос: При установке получаю ошибку "ext-dom not found" {#ext-dom-missing}

**Ответ:** Это означает, что в вашей PHP-установке отсутствует расширение DOM. Установите его:

- **Ubuntu/Debian:** `sudo apt-get install php-dom`
- **CentOS/RHEL:** `sudo yum install php-xml`
- **Windows:** Раскомментируйте в `php.ini` строку `extension=dom`

### Вопрос: Можно ли установить библиотеку без Composer? {#without-composer}

**Ответ:** Нет, библиотека использует Composer для автозагрузки и управления зависимостями. Установка без Composer не поддерживается.

### Вопрос: После установки библиотека не находится — классы не загружаются {#autoloading-issues}

**Ответ:** Убедитесь, что вы подключили автозагрузчик Composer:
```php
require_once __DIR__ . '/vendor/autoload.php';
```

---

## Парсинг и работа с данными {#парсинг-и-работа-с-данными}

### Вопрос: Как получить чистый текст без HTML-тегов? {#get-plain-text}

**Ответ:** Используйте метод [`getLabel()`](./04-appendix--02-api-reference.md#elementinterface) — после схлопывания он содержит текст без тегов, но с сохранением информации о форматировании в fragments.

```php
$text = $element->getLabel(); // "Это жирный текст"
```

Подробнее в разделе [Модель данных](./02-core--01-data-model.md#label).

### Вопрос: Как получить атрибуты элемента? {#get-attributes}

**Ответ:** Используйте методы [`NodeInterface`](./04-appendix--02-api-reference.md#nodeinterface):

```php
$attrs = $element->getAttributes(); // все атрибуты
$href = $element->getAttribute('href'); // конкретный атрибут
$hasClass = $element->hasAttribute('class'); // проверка наличия
```

### Вопрос: Почему в дереве появляются узлы с типом `#error`? {#error-nodes}

**Ответ:** Это узлы-ошибки ([`ErrorElementInterface`](./04-appendix--02-api-reference.md#errorelementinterface)), которые замещают проблемные места в HTML. Они создаются, когда парсер сталкивается с ситуацией, которую не может обработать стандартными правилами.

Для анализа используйте методы интерфейса:

```php
if ($element->getName() === '#error') {
    $severity = $element->getSeverity(); // notice/warning/error
    $type = $element->getErrorType(); // missingRule, disallowedChild и т.д.
}
```

Подробнее в разделе [Обработка ошибок](./02-core--04-error-handling.md).

### Вопрос: Как парсить не только строку, но и URL? {#parse-url}

**Ответ:** Библиотека принимает только HTML-строку. Скачайте содержимое URL перед передачей в парсер:

```php
$html = file_get_contents('https://example.com');
$parser = new Parser($html);
$document = $parser->parse();
```

### Вопрос: Как сохранить HTML-комментарии? {#keep-comments}

**Ответ:** Передайте `true` в метод [`parse()`](./04-appendix--02-api-reference.md#parserinterface):

```php
$document = $parser->parse(true); // комментарии будут сохранены
```

### Вопрос: Можно ли модифицировать дерево и сохранить обратно в HTML? {#save-to-html}

**Ответ:** Библиотека focused на парсинге и анализе, не на генерации HTML. Для обратной конвертации вам потребуется дополнительная логика или другой инструмент.

---

## Модули {#проблемы-с-модулями}

### Вопрос: Мой модуль не загружается / не обнаруживается {#module-not-loading}

**Ответ:** Проверьте следующие моменты:

1. **Секция `extra.modules` в composer.json:**
```json
{
    "extra": {
        "modules": {
            "MyCompany\\MyModule\\MyModuleClass": {}
        }
    }
}
```

2. **Класс существует и автозагружается** — проверьте namespace и psr-4 автозагрузку

3. **Класс реализует [`ModuleInterface`](./04-appendix--02-api-reference.md#moduleinterface)** — все методы должны быть реализованы

4. **Нет циклических зависимостей** — проверьте `getDependencies()`

Подробнее в разделе [Система модулей](./03-events-modules--02-modules.md).

### Вопрос: Как передать конфигурацию в модуль? {#module-configuration}

**Ответ:** Используйте секцию `extra.modules` для передачи параметров:

```json
{
    "extra": {
        "modules": {
            "MyCompany\\MyModule\\MyModuleClass": {
                "config": {
                    "option1": "value1",
                    "option2": "value2"
                }
            }
        }
    }
}
```

В конструкторе модуля эти параметры будут доступны:

```php
class MyModule implements ModuleInterface
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
}
```

### Вопрос: Как проверить, загружен ли модуль? {#check-module-loaded}

**Ответ:** Используйте [`ModuleManagerInterface`](./04-appendix--02-api-reference.md#modulemanagerinterface):

```php
$moduleManager = $parser->getModuleManager();

if ($moduleManager->hasModule('seo')) {
    $seoModule = $moduleManager->getModule('seo');
}
```

### Вопрос: Что такое циклическая зависимость и как её избежать? {#circular-dependencies}

**Ответ:** Циклическая зависимость возникает, когда модуль A зависит от B, а B зависит от A. Проверьте граф зависимостей и убедитесь, что нет взаимных ссылок:

```php
// Неправильно
class ModuleA {
    public function getDependencies(): array { return ['b']; }
}
class ModuleB {
    public function getDependencies(): array { return ['a']; } // циклическая зависимость
}

// Правильно — иерархическая структура
class ModuleA {
    public function getDependencies(): array { return []; }
}
class ModuleB {
    public function getDependencies(): array { return ['a']; } // B зависит от A
}
```

### Вопрос: Мой модуль несовместим с новой версией ядра {#version-compatibility}

**Ответ:** Проверьте метод `supportsCoreVersion()`:

```php
public function supportsCoreVersion(string $version): bool
{
    // Укажите поддерживаемые версии
    return version_compare($version, '1.0.0', '>=') && 
           version_compare($version, '2.0.0', '<');
}
```

---

## Обработка ошибок {#проблемы-с-обработкой-ошибок}

### Вопрос: Как отловить все ошибки парсинга? {#catch-all-errors}

**Ответ:** Используйте [`ErrorHandlerInterface`](./04-appendix--02-api-reference.md#errorhandlerinterface):

```php
$errorHandler = $parser->getErrorHandler();

if ($errorHandler->hasErrors()) {
    foreach ($errorHandler->getErrors() as $error) {
        echo $error->getSeverity() . ': ' . $error->getErrorType() . PHP_EOL;
        echo $error->getLabel() . PHP_EOL; // сообщение об ошибке
    }
}
```

### Вопрос: Что означает ошибка "missing rule for tag [тег]"? {#missing-rule}

**Ответ:** Библиотека не знает правил обработки для этого тега — он отсутствует во встроенной карте тегов. Решения:

1. Добавить правило через модуль (подписка на `pre-node`)
2. Игнорировать — тег будет обработан по умолчанию как обычный элемент

### Вопрос: Как настроить, чтобы парсинг прерывался при ошибках? {#throw-on-error}

**Ответ:** Используйте методы `setThrowOn...()`:

```php
$errorHandler = $parser->getErrorHandler();

// Прерывать только при фатальных ошибках
$errorHandler->setThrowOnError(true);

// Прерывать при любых ошибках (включая уведомления)
$errorHandler
    ->setThrowOnError(true)
    ->setThrowOnWarning(true)
    ->setThrowOnNotice(true);

// Отключить все исключения (поведение по умолчанию)
$errorHandler
    ->setThrowOnError(false)
    ->setThrowOnWarning(false)
    ->setThrowOnNotice(false);
```

### Вопрос: Как найти источник ошибки по backtrace? {#error-backtrace}

**Ответ:** Используйте `getBacktrace()`:

```php
foreach ($errorHandler->getErrors() as $error) {
    $trace = $error->getBacktrace();
    $firstFrame = $trace[0] ?? null;
    
    if ($firstFrame) {
        echo "Ошибка возникла в: " . $firstFrame['file'] . ':' . $firstFrame['line'] . PHP_EOL;
    }
}
```

---

## ContextDataResolver {#проблемы-с-dataresolver}

### Вопрос: Почему `getData()` возвращает пустую строку, хотя атрибут есть? {#data-empty}

**Ответ:** Проверьте имя атрибута — для разных тегов используются разные правила:

```php
// Для ссылки
$data = $link->getData(); // ищет атрибут 'href'

// Для изображения
$data = $img->getData();  // ищет атрибут 'src'

// Для мета-тега
$data = $meta->getData(); // ищет атрибут 'content'
```

Подробнее в разделе [ContextDataResolver](./02-core--03-utilities.md#contextdataresolver).

### Вопрос: Можно ли изменить правило извлечения Data для определенного тега? {#custom-data-rule}

**Ответ:** Да, через модуль и событие `post-node`:

```php
use HtmlDomParser\Core\Event\EventConstant;

class CustomDataModule implements ModuleInterface
{
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe(EventConstant::POST_NODE, [$this, 'modifyData']);
    }
    
    public function modifyData(NodeContextInterface $context): NodeContextInterface
    {
        if ($context->getName() === 'a') {
            $data = $context->getData();
            $context->setData(strtoupper($data)); // модификация Data
        }
        return $context;
    }
}
```

### Вопрос: Почему `getData()` и `getLabel()` иногда возвращают одно и то же? {#data-vs-label}

**Ответ:** Для многих тегов (p, div, span) Data и Label совпадают, так как основным содержимым является текст. Различие проявляется для тегов с атрибутами (a, img) и специальных тегов (script, style).

### Вопрос: Data извлекается каждый раз при вызове `getData()`? {#data-caching}

**Ответ:** Нет, Data извлекается **один раз** в момент создания элемента из контекста и сохраняется (кэшируется) в элементе. Последующие вызовы `getData()` возвращают сохраненное значение мгновенно.

---

## InlineCollapser {#проблемы-с-inlinecollapser}

### Вопрос: Как отключить схлопывание для определенных элементов? {#disable-collapse}

**Ответ:** Используйте событие `pre-inline-collapse`:

```php
use HtmlDomParser\Core\Event\EventConstant;

$dispatcher->subscribe(EventConstant::PRE_INLINE_COLLAPSE, function(NodeContextInterface $context) {
    // Не схлопывать содержимое <pre> и <code>
    if (in_array($context->getName(), ['pre', 'code'])) {
        // Модифицируем контекст так, чтобы allChildrenIsInline вернул false
    }
    return $context;
});
```

### Вопрос: Почему после схлопывания у элемента нет детей? {#no-children-after-collapse}

**Ответ:** Это нормальное поведение — все дочерние элементы были объединены в единый текст. Информация о форматировании теперь доступна через `getFragments()`. Подробнее в разделе [InlineCollapser](./02-core--03-utilities.md#inline-collapser).

### Вопрос: Как получить исходную структуру до схлопывания? {#original-structure}

**Ответ:** Схлопывание происходит автоматически и необратимо. Если вам нужна исходная структура, рассмотрите возможность:

1. Отключения схлопывания через событие
2. Создания своего модуля, который сохраняет копию структуры до схлопывания

### Вопрос: Что происходит с вложенными строчными элементами? {#nested-inline}

**Ответ:** Они схлопываются в плоский список fragments. Например:

```html
<b>жирный <i>и курсивный</i></b>
```

Превращается в два фрагмента:
```php
foreach ($element->getFragments() as $fragment) {
    echo $fragment->getType() . ': ' . $fragment->getStart() . '-' . $fragment->getEnd();
}
// Вывод: b: 0-20, i: 7-20
```

### Вопрос: Можно ли получить fragments для всего документа целиком? {#all-fragments}

**Ответ:** fragments привязаны к конкретным элементам. Для получения всех fragments нужно рекурсивно обойти дерево:

```php
use HtmlDomParser\Contract\ElementInterface;

function collectFragments(ElementInterface $element): array
{
    $fragments = $element->getFragments()->toArray();

    if ($element->hasChildren()) {
        foreach ($element->getChildren() as $child) {
            $fragments = array_merge($fragments, collectFragments($child));
        }
    }

    return $fragments;
}
```

---

## Производительность {#производительность}

### Вопрос: Как быстро парсятся большие HTML-файлы? {#performance}

**Ответ:** Производительность зависит от размера документа и сложности структуры. Основные факторы:

- Загрузка в DOMDocument (зависит от размера)
- Рекурсивный обход всех узлов (O(n) по количеству узлов)
- Схлопывание строчных элементов (дополнительные O(m) для каждого узла)

Для файлов размером несколько мегабайт парсинг обычно занимает доли секунды.

### Вопрос: Как оптимизировать парсинг, если нужны не все данные? {#optimization}

**Ответ:** Используйте модули и события для раннего выхода или фильтрации:

```php
use HtmlDomParser\Core\Event\EventConstant;

$dispatcher->subscribe(EventConstant::PRE_NODE, function(NodeContextInterface $context) {
    // Пропускаем ненужные узлы
    if (in_array($context->getName(), ['script', 'style'])) {
        // Можно пометить как SKIP через контекст
    }
    return $context;
});
```

### Вопрос: Потребляет ли библиотека много памяти? {#memory-usage}

**Ответ:** Память используется для:

- Исходного DOMDocument (основной потребитель)
- Временных контекстов в процессе парсинга
- Итогового дерева элементов

Для очень больших документов (>10-20 МБ) может потребоваться увеличение `memory_limit` в php.ini.

---

## Прочее {#прочее}

### Вопрос: Как добавить поддержку нового HTML-тега? {#new-tag}

**Ответ:** Создайте модуль, который через событие `pre-node` будет задавать правильный контекст для нового тега:

```php
use HtmlDomParser\Core\Event\EventConstant;

class CustomTagModule implements ModuleInterface
{
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe(EventConstant::PRE_NODE, [$this, 'handleCustomTag']);
    }
    
    public function handleCustomTag(NodeContextInterface $context): NodeContextInterface
    {
        if ($context->getName() === 'my-custom-tag') {
            // Устанавливаем правила обработки
        }
        return $context;
    }
}
```

### Вопрос: Где найти примеры использования библиотеки? {#examples}

**Ответ:** Смотрите раздел [Быстрый старт](./01-general-information--03-quick-start.md) и примеры в каждом разделе документации.

### Вопрос: Как сообщить об ошибке или предложить улучшение? {#report-issue}

**Ответ:** Создайте issue на GitHub-репозитории проекта: https://github.com/f4n70m/html-dom-parser/issues

### Вопрос: Где найти список всех доступных модулей? {#module-list}

**Ответ:** Список модулей нужно искать на Packagist по тегу `html-dom-parser-module` или в документации соответствующих пакетов.

---

## Связанные разделы {#see-also}

- [Установка](./01-general-information--02-installation.md)
- [Быстрый старт](./01-general-information--03-quick-start.md)
- [Модель данных](./02-core--01-data-model.md)
- [Обработка ошибок](./02-core--04-error-handling.md)
- [Система модулей](./03-events-modules--02-modules.md)
- [Справочник API](./04-appendix--02-api-reference.md)