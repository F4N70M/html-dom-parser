<?php

namespace HtmlDomParser\Contract;

use DOMNode;

/**
 * Интерфейс резолвера данных (Data) из DOM-узла.
 */
interface ContextDataResolveRulesInterface
{
    /**
     * Отдает правила для извлечения полезной информации узла
     *
     * @param DOMNode $node Оригинальный DOM-узел.
     * @return array
     */
    public function get(DOMNode $node): array;
}