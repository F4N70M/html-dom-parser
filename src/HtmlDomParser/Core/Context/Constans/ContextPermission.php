<?php

namespace HtmlDomParser\Core\Context\Constans;

/**
 * Константы типов контекста узлов.
 *
 * Определяют роль элемента в документе: блочный, строчный, фразовый и т.д.
 * Используются при создании контекста узла и в карте тегов.
 */
class ContextPermission
{
    const NONE = false;
    const ANY  = true;
    
    const LABELS = [
        self::NONE => 'NONE',
        self::ANY  => 'ANY',
    ];

    private function __construct() {}
}