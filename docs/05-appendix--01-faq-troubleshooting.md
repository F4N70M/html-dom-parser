[← К оглавлению](../README.md#📖-документация)

# FAQ и решение проблем

В этом разделе собраны ответы на частые вопросы и решения типичных проблем при работе с HtmlDomParser.

## Содержание

- [Установка и настройка](#установка-и-настройка)
- [Парсинг и работа с данными](#парсинг-и-работа-с-данными)
- [Модули](#проблемы-с-модулями)
- [Обработка ошибок](#проблемы-с-обработкой-ошибок)
- [DataResolver](#проблемы-с-dataresolver)
- [InlineCollapser](#проблемы-с-inlinecollapser)
- [Производительность](#производительность)
- [Прочее](#прочее)

---

## Установка и настройка

### Вопрос: Какие требования к PHP для работы библиотеки?

**Ответ:** Библиотека требует PHP версии 7.4 или 8.0+ и расширение `ext-dom` (обычно включено в стандартных сборках PHP).

### Вопрос: При установке получаю ошибку "ext-dom not found"

**Ответ:** Это означает, что в вашей PHP-установке отсутствует расширение DOM. Установите его:

- **Ubuntu/Debian:** `sudo apt-get install php-dom`
- **CentOS/RHEL:** `sudo yum install php-xml`
- **Windows:** Раскомментируйте в `php.ini` строку `extension=dom`

### Вопрос: Можно ли установить библиотеку без Composer?

**Ответ:** Нет, библиотека использует Composer для автозагрузки и управления зависимостями. Установка без Composer не поддерживается.

### Вопрос: После установки библиотека не находится — классы не загружаются

**Ответ:** Убедитесь, что вы подключили автозагрузчик Composer:
```php
require_once __DIR__ . '/vendor/autoload.php';
```

---

## Парсинг и работа с данными

### Вопрос: Как получить чистый текст без HTML-тегов?

**Ответ:** Используйте метод `getLabel()` — после схлопывания он содержит текст без тегов, но с сохранением информации о форматировании в entities.

```php
$text = $element->getLabel(); // "Это жирный текст"
```

### Вопрос: Как получить атрибуты элемента?

**Ответ:** Используйте методы `NodeInterface`:

```php
$attrs = $element->getAttributes(); // все атрибуты
$href = $element->getAttribute('href'); // конкретный атрибут
$hasClass = $element->hasAttribute('class'); // проверка наличия
```

### Вопрос: Почему в дереве появляются узлы с типом `#error`?

**Ответ:** Это узлы-ошибки, которые замещают проблемные места в HTML. Они создаются, когда парсер сталкивается с ситуацией, которую не может обработать стандартными правилами (например, отсутствие правила для тега или запрещенный дочерний элемент).

Для анализа используйте `ErrorElementInterface`:

```php
if ($element->getName() === '#error') {
    $severity = $element->getSeverity(); // notice/warning/error
    $type = $element->getErrorType(); // missingRule, disallowedChild и т.д.
}
```

### Вопрос: Как парсить не только строку, но и URL?

**Ответ:** Библиотека принимает только HTML-строку. Скачайте содержимое URL перед передачей в парсер:

```php
$html = file_get_contents('https://example.com');
$parser = new Parser($html);
$document = $parser->parse();
```

### Вопрос: Как сохранить HTML-комментарии?

**Ответ:** Передайте `true` в метод `parse()`:

```php
$document = $parser->parse(true); // комментарии будут сохранены
```

### Вопрос: Можно ли модифицировать дерево и сохранить обратно в HTML?

**Ответ:** Библиотека focused на парсинге и анализе, не на генерации HTML. Для обратной конвертации вам потребуется дополнительная логика или другой инструмент.

---

## Модули {#проблемы-с-модулями}

### Вопрос: Мой модуль не загружается / не обнаруживается

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

3. **Класс реализует `ModuleInterface`** — все методы должны быть реализованы

4. **Нет циклических зависимостей** — проверьте `getDependencies()`

### Вопрос: Как передать конфигурацию в модуль?

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

### Вопрос: Как проверить, загружен ли модуль?

**Ответ:** Используйте `ModuleManagerInterface`:

```php
$moduleManager = $parser->getModuleManager();

if ($moduleManager->hasModule('seo')) {
    $seoModule = $moduleManager->getModule('seo');
}
```

### Вопрос: Что такое циклическая зависимость и как её избежать?

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

### Вопрос: Мой модуль несовместим с новой версией ядра

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

### Вопрос: Как отловить все ошибки парсинга?

**Ответ:** Используйте `ErrorHandlerInterface`:

```php
$errorHandler = $parser->getErrorHandler();

if ($errorHandler->hasErrors()) {
    foreach ($errorHandler->getErrors() as $error) {
        echo $error->getSeverity() . ': ' . $error->getErrorType() . PHP_EOL;
        echo $error->getLabel() . PHP_EOL; // сообщение об ошибке
    }
}
```

### Вопрос: Что означает ошибка "missing rule for tag [тег]"?

**Ответ:** Библиотека не знает правил обработки для этого тега — он отсутствует во встроенной карте тегов. Решения:

1. Добавить правило через модуль (подписка на `pre-node`)
2. Создать собственную карту тегов (если библиотека поддерживает)
3. Игнорировать — тег будет обработан по умолчанию как обычный элемент

### Вопрос: Как настроить, чтобы парсинг прерывался при ошибках?

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

### Вопрос: Как найти источник ошибки по backtrace?

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

### Вопрос: Почему в дереве появились узлы-ошибки?

**Ответ:** Узлы-ошибки создаются, когда парсер не может корректно обработать часть HTML, но продолжает работу. Это позволяет:

- Не терять остальное содержимое документа
- Анализировать проблемы после парсинга
- Принимать решение о дальнейших действиях

Проверьте, не являются ли эти ошибки критическими для вашего сценария использования.

---

## DataResolver {#проблемы-с-dataresolver}

### Вопрос: Почему `getData()` возвращает пустую строку, хотя атрибут есть?

**Ответ:** Проверьте имя атрибута — для разных тегов используются разные правила:

```php
// Для ссылки
$data = $link->getData(); // ищет атрибут 'href'

// Для изображения
$data = $img->getData();  // ищет атрибут 'src'

// Для мета-тега
$data = $meta->getData(); // ищет атрибут 'content'
```

### Вопрос: Можно ли изменить правило извлечения Data для определенного тега?

**Ответ:** Да, через модуль и событие `post-node`:

```php
class CustomDataModule implements ModuleInterface
{
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe('post-node', [$this, 'modifyData']);
    }
    
    public function modifyData(NodeContextInterface $context): NodeContextInterface
    {
        if ($context->getName() === 'a') {
            // Изменяем Data до создания элемента
            $data = $context->getData();
            $context->setData(strtoupper($data));
        }
        
        return $context;
    }
}
```

### Вопрос: Почему `getData()` и `getLabel()` иногда возвращают одно и то же?

**Ответ:** Для многих тегов (p, div, span) Data и Label совпадают, так как основным содержимым является текст. Различие проявляется для тегов с атрибутами (a, img) и специальных тегов (script, style).

### Вопрос: Data извлекается каждый раз при вызове `getData()`?

**Ответ:** Нет, Data извлекается **один раз** в момент создания элемента из контекста и сохраняется (кэшируется) в элементе. Последующие вызовы `getData()` возвращают сохраненное значение мгновенно.

### Вопрос: Как принудительно обновить Data из DOM-узла?

**Ответ:** Элемент не хранит связь с исходным DOM-узлом, поэтому "обновить" Data нельзя. Можно только:

1. Создать новый элемент (новый парсинг)
2. Вручную установить значение через `setData()`

---

## InlineCollapser {#проблемы-с-inlinecollapser}

### Вопрос: Как отключить схлопывание для определенных элементов?

**Ответ:** Используйте событие `pre-inline-collapse`:

```php
$dispatcher->subscribe('pre-inline-collapse', function(NodeContextInterface $context) {
    // Не схлопывать содержимое <pre> и <code>
    if (in_array($context->getName(), ['pre', 'code'])) {
        // Модифицируем контекст так, чтобы allChildrenIsInline вернул false
        // (зависит от реализации)
    }
    
    return $context;
});
```

### Вопрос: Почему после схлопывания у элемента нет детей?

**Ответ:** Это нормальное поведение — все дочерние элементы были объединены в единый текст. Информация о форматировании теперь доступна через `getEntities()`.

### Вопрос: Как получить исходную структуру до схлопывания?

**Ответ:** Схлопывание происходит автоматически и необратимо. Если вам нужна исходная структура, рассмотрите возможность:

1. Отключения схлопывания через событие
2. Создания своего модуля, который сохраняет копию структуры до схлопывания

### Вопрос: Что происходит с вложенными строчными элементами?

**Ответ:** Они схлопываются в плоский список entities. Например:

```html
<b>жирный <i>и курсивный</i></b>
```

Превращается в две сущности:
```php
[
    ['type' => 'b', 'start' => 0, 'end' => 20],
    ['type' => 'i', 'start' => 7, 'end' => 20]
]
```

### Вопрос: Пропадают ли пробелы при схлопывании?

**Ответ:** Пробелы сохраняются в тексте. Однако множественные пробелы, переносы строк и табуляции могут быть нормализованы в зависимости от реализации парсера.

### Вопрос: Можно ли получить entities для всего документа целиком?

**Ответ:** Entities привязаны к конкретным элементам, в которых произошло схлопывание. Для получения всех entities нужно рекурсивно обойти дерево:

```php
function collectEntities(ElementInterface $element): array
{
    $entities = $element->getEntities();
    
    if ($element->hasChildren()) {
        foreach ($element->getChildren() as $child) {
            $entities = array_merge($entities, collectEntities($child));
        }
    }
    
    return $entities;
}
```

---

## Производительность

### Вопрос: Как быстро парсятся большие HTML-файлы?

**Ответ:** Производительность зависит от размера документа и сложности структуры. Основные факторы:

- Загрузка в DOMDocument (зависит от размера)
- Рекурсивный обход всех узлов (O(n) по количеству узлов)
- Схлопывание строчных элементов (дополнительные O(m) для каждого узла)

Для файлов размером несколько мегабайт парсинг обычно занимает доли секунды.

### Вопрос: Как оптимизировать парсинг, если нужны не все данные?

**Ответ:** Используйте модули и события для раннего выхода или фильтрации:

```php
$dispatcher->subscribe('pre-node', function(NodeContextInterface $context) {
    // Пропускаем ненужные узлы
    if (in_array($context->getName(), ['script', 'style'])) {
        // Можно пометить как SKIP через контекст
    }
    
    return $context;
});
```

### Вопрос: Потребляет ли библиотека много памяти?

**Ответ:** Память используется для:

- Исходного DOMDocument (основной потребитель)
- Временных контекстов в процессе парсинга
- Итогового дерева элементов

После парсинга DOMDocument может быть освобожден, но итоговое дерево остается в памяти. Для очень больших документов это может быть существенно.

### Вопрос: Есть ли ограничения на размер HTML?

**Ответ:** Прямых ограничений нет, но учитывайте лимиты памяти PHP. Для файлов > 10-20 МБ может потребоваться увеличение memory_limit в php.ini.

---

## Прочее

### Вопрос: Как добавить поддержку нового HTML-тега?

**Ответ:** Создайте модуль, который через событие `pre-node` будет задавать правильный контекст для нового тега:

```php
class CustomTagModule implements ModuleInterface
{
    public function initialize(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->subscribe('pre-node', [$this, 'handleCustomTag']);
    }
    
    public function handleCustomTag(NodeContextInterface $context): NodeContextInterface
    {
        if ($context->getName() === 'my-custom-tag') {
            // Устанавливаем правила обработки
            // Например, делаем его строчным или блочным
        }
        
        return $context;
    }
}
```

### Вопрос: Где найти примеры использования библиотеки?

**Ответ:** Смотрите раздел [Быстрый старт](./01-getting-started--03-quick-start.md) и примеры в каждом разделе документации.

### Вопрос: Как сообщить об ошибке или предложить улучшение?

**Ответ:** Создайте issue на GitHub-репозитории проекта: https://github.com/f4n70m/html-dom-parser/issues

### Вопрос: Где найти список всех доступных модулей?

**Ответ:** На данный момент список модулей нужно искать на Packagist по тегу `html-dom-parser-module` или в документации соответствующих пакетов.

---

## Связанные разделы

- [Установка](./01-getting-started--02-installation.md)
- [Быстрый старт](./01-getting-started--03-quick-start.md)
- [Ядро системы](./02-core-components--01-core-interfaces.md)
- [Обработка ошибок](./03-advanced-architecture--04-error-handling.md)
- [Система модулей](./03-advanced-architecture--03-modules.md)