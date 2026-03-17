<?php

namespace HtmlDomParser\Core\Context\Constans;

/**
 * Константы типов контекста узлов.
 */
class ContextStyle
{
    // Служебные
    const NORMAL       = 200; // Без стиля
    const ITALIC       = 201; // Курсив
    const BOLD         = 202; // Жирный
    const EMPHASIS     = 203; // Акцент (курсив)
    const STRONG       = 204; // Важный (жирный)
    const PREWRAP      = 205; // pre-wrap
    const MONOSCAPED   = 206; // pre-форматированный
    const PREFORMATTED = 207; // pre-форматированный

    const LABELS = [
        self::NORMAL       => 'NORMAL',
        self::ITALIC       => 'ITALIC',
        self::BOLD         => 'BOLD',
        self::EMPHASIS     => 'EMPHASIS',
        self::STRONG       => 'STRONG',
        self::PREWRAP      => 'PREWRAP',
        self::MONOSCAPED   => 'MONOSCAPED',
        self::PREFORMATTED => 'PREFORMATTED',
    ];

    private function __construct() {}
}