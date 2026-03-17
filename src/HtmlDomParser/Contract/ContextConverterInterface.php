<?php

namespace HtmlDomParser\Contract;

use DOMNode;

/**
 * Интерфейс конвертера для преобразования DOM-узлов в контексты и контекстов в элементы.
 */
interface ContextConverterInterface
{
    /**
     * Создаёт контекст для DOM-узла на основе правил тегов и родительского контекста.
     *
     * @param DOMNode $node
     * @param int     $parentContextType Тип контекста родителя.
     * @return NodeContextInterface
     */
    public function nodeToContext(DOMNode $node, int $parentContextType): ?NodeContextInterface;

    /**
     * Создаёт объект Element из контекста.
     * Копирует атрибуты, label, вызывает ContextDataResolver для извлечения Data.
     *
     * @param NodeContextInterface $nodeContext
     * @return ElementInterface
     */
    public function contextToElement(NodeContextInterface $nodeContext): ElementInterface;
}