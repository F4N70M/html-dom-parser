<?php

namespace HtmlDomParser\Core\Event;

/**
 * Константы событий жизненного цикла обработки узла.
 *
 * Используются для подписки в диспетчере событий.
 */
class EventConstant
{
    public const PRE_NODE               = 'pre-node';             // Событие перед обработкой детей узла
    public const POST_NODE              = 'post-node';            // Событие после обработки детей и схлопывания, перед преобразованием в элемент
    public const PRE_INLINE_COLLAPSE    = 'pre-inline-collapse';  // Событие перед запуском схлопывания inline-элементов
    public const POST_INLINE_COLLAPSE   = 'post-inline-collapse'; // Событие после схлопывания inline-элементов
}