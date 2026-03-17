<?php

/**
 * Пример 2: Работа с фрагментами форматированного текста (RichTextFragment).
 *
 * Демонстрирует:
 * - Как после схлопывания строчных элементов получить единый текст
 * - Как извлечь фрагменты форматирования
 * - Атрибуты фрагментов (например, href для ссылок)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HtmlDomParser\Parser;

$html = <<<HTML
<div>
    <p>Это <b>жирный</b> текст и <i>курсив</i>, а также <a href="https://example.com">ссылка</a> внутри параграфа.</p>
    <p>Второй параграф с <span class="highlight">выделенным</span> словом.</p>
</div>
HTML;

$parser = new Parser($html);
$document = $parser->parse();

// Получаем первый параграф
$div = $document->getChildren()->get(0);
$firstP = $div->getChildren()->get(0);

// 1. Объединённый текст (label)
echo "Текст параграфа: " . $firstP->getLabel() . "\n";
// Ожидаемый вывод: "Это жирный текст и курсив, а также ссылка внутри параграфа."

// 2. Фрагменты форматирования
$fragments = $firstP->getFragments();

echo "Найдено фрагментов: " . $fragments->count() . "\n";

foreach ($fragments as $index => $fragment) {
    $type = $fragment->getType();
    $start = $fragment->getStart();
    $end = $fragment->getEnd();
    $text = substr($firstP->getLabel(), $start, $end - $start);

    echo "#$index: $type [$start-$end] \"$text\"";

    // Если это ссылка, выведем атрибут href
    if ($type === 'a' && $fragment->hasAttribute('href')) {
        echo " -> " . $fragment->getAttribute('href');
    }
    echo "\n";
}

// Ожидаемый вывод (индексы могут отличаться в зависимости от реализации):
// #0: b [4-10] "жирный"
// #1: i [18-24] "курсив"
// #2: a [31-37] "ссылка" -> https://example.com

// 3. Второй параграф
$secondP = $div->getChildren()->get(1);
echo "\nВторой параграф: " . $secondP->getLabel() . "\n";
// "Второй параграф с выделенным словом."

foreach ($secondP->getFragments() as $fragment) {
    if ($fragment->getType() === 'span') {
        $attrs = $fragment->getAttributes();
        echo "span class: " . ($attrs['class'] ?? '') . "\n";
    }
}
// Ожидаемый вывод: span class: highlight