<?php

/**
 * Пример 3: Обработка ошибок парсинга.
 *
 * Демонстрирует:
 * - Как получить обработчик ошибок
 * - Настройку поведения (исключения / сбор ошибок)
 * - Анализ узлов-ошибок в дереве
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HtmlDomParser\Parser;
use HtmlDomParser\Core\Error\ErrorConstant;

// HTML с проблемными местами:
// - Неизвестный тег <unknown> (нет правила в карте)
// - Запрещённый дочерний элемент: <div> внутри <p> (в спецификации <p> не может содержать блочные элементы)
$html = <<<HTML
<div>
    <p>Нормальный текст.</p>
    <unknown>Какой-то непонятный тег</unknown>
    <p>Текст с <div>вложенным div</div> внутри параграфа.</p>
</div>
HTML;

// 1. Создаём парсер (по умолчанию исключения не выбрасываются)
$parser = new Parser($html);
$errorHandler = $parser->getErrorHandler();

// 2. Настраиваем: хотим получать исключения только при фатальных ошибках
$errorHandler->setThrowOnError(true);      // ERROR -> exception
$errorHandler->setThrowOnWarning(false);   // WARNING -> только собирать
$errorHandler->setThrowOnNotice(false);    // NOTICE -> только собирать

try {
    $document = $parser->parse();
} catch (\HtmlDomParser\Exception\HtmlDomParserException $e) {
    echo "Поймано исключение: " . $e->getMessage() . "\n";
}

// 3. После парсинга (или даже после исключения) можно получить все ошибки
if ($errorHandler->hasErrors()) {
    echo "\nСписок ошибок парсинга:\n";
    foreach ($errorHandler->getErrors() as $error) {
        echo "- [" . $error->getSeverity() . "] " . $error->getErrorType() . ": " . $error->getLabel() . "\n";

        // Для узлов-ошибок можно получить оригинальные атрибуты и backtrace
        if ($error->getSeverity() === ErrorConstant::SEVERITY_ERROR) {
            $trace = $error->getBacktrace();
            $first = $trace[0] ?? null;
            if ($first) {
                echo "  Возникло в: {$first['file']}:{$first['line']}\n";
            }
        }
    }
}

// 4. Также можно найти узлы-ошибки непосредственно в дереве
// (если парсинг не прервался исключением)
if (isset($document)) {
    $div = $document->getChildren()->get(0);

    // Рекурсивная функция поиска узлов с именем '#error'
    $findErrors = function($element) use (&$findErrors) {
        $errors = [];
        if ($element->getName() === '#error') {
            $errors[] = $element;
        }
        if ($element->hasChildren()) {
            foreach ($element->getChildren() as $child) {
                $errors = array_merge($errors, $findErrors($child));
            }
        }
        return $errors;
    };

    $errorNodes = $findErrors($div);
    echo "\nНайдено узлов-ошибок в дереве: " . count($errorNodes) . "\n";
}