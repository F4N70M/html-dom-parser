<?php

namespace HtmlDomParser\Core\Utilite;

use HtmlDomParser\Contract\InlineCollapserInterface;
use HtmlDomParser\Contract\NodeContextInterface;

/**
 * Заглушка сервиса схлопывания строчных элементов.
 *
 * Временная реализация, не выполняющая никаких действий.
 * Используется для обеспечения работоспособности кода до полноценной реализации.
 */
class InlineCollapser implements InlineCollapserInterface
{
    /**
     * Выполняет схлопывание последовательности inline-элементов (заглушка).
     *
     * @param NodeContextInterface $context Контекст узла.
     * @param array                $options Опции схлопывания (не используются).
     * @return NodeContextInterface Тот же контекст без изменений.
     */
    public function collapse(NodeContextInterface $context, array $options = []): NodeContextInterface
    {
        // Ничего не делаем, просто возвращаем контекст
        return $context;
    }
}