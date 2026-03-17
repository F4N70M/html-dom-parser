<?php

/**
 * Пример 1: Базовое использование парсера.
 *
 * Демонстрирует:
 * - Создание парсера с HTML-строкой
 * - Получение корневого документа
 * - Доступ к элементам, их именам, атрибутам и текстовой метке (label)
 * - Различие между data и label для разных тегов
 */

require_once __DIR__ . '/../vendor/autoload.php';

use HtmlDomParser\Parser;

// 1. Исходный HTML
$html = <<<HTML
<!DOCTYPE html>
<html>
<body>
    <div class="container" id="main">
        <h1>Заголовок страницы</h1>
        <p>Это простой <strong>параграф</strong> с текстом.</p>
        <a href="https://example.com">Ссылка на пример</a>
        <img src="/images/logo.png" alt="Логотип">
    </div>
</body>
</html>
HTML;

// 2. Создаём парсер
$parser = new Parser($html);

// 3. Запускаем парсинг (keepComments = false — комментарии не сохраняются)
$document = $parser->parse();

// 4. Получаем корневые элементы (прямые потомки <body>)
$bodyChildren = $document->getChildren();

// Ожидаем, что первый элемент — div.container
$div = $bodyChildren->get(0);

echo "Имя тега: " . $div->getName() . "\n"; // div
echo "Атрибут class: " . $div->getAttribute('class') . "\n"; // container
echo "Атрибут id: " . $div->getAttribute('id') . "\n"; // main

// 5. Получаем дочерние элементы div
$children = $div->getChildren();

// h1
$h1 = $children->get(0);
echo "h1: " . $h1->getLabel() . "\n"; // "Заголовок страницы"
// data для h1 совпадает с label (текст)
echo "h1 data: " . $h1->getData() . "\n"; // "Заголовок страницы"

// p
$p = $children->get(1);
echo "p: " . $p->getLabel() . "\n"; // "Это простой параграф с текстом."
// strong внутри p будет схлопнут, поэтому fragments содержат информацию о форматировании

// a
$a = $children->get(2);
echo "a label: " . $a->getLabel() . "\n"; // "Ссылка на пример"
echo "a data (href): " . $a->getData() . "\n"; // "https://example.com"

// img
$img = $children->get(3);
echo "img src: " . $img->getData() . "\n"; // "/images/logo.png"
echo "img alt: " . $img->getAttribute('alt') . "\n"; // "Логотип"

// 6. Фильтрация: получить все ссылки внутри div
$links = $div->getChildren()->filter(fn($el) => $el->getName() === 'a');
foreach ($links as $link) {
    echo "Найдена ссылка: " . $link->getData() . " -> " . $link->getLabel() . "\n";
}