<?php

namespace HtmlDomParser\Core\Context\Constans;

/**
 * Константы типов контекста узлов.
 */
class ContextType
{
    // Служебные
    const BLOCKED     = -1; // (для this)      Заблокировать (не включать в результат)
    const VOID        = -1; // (для children)  Пустой элемент (не может иметь детей) – void-элементы
    const TRANSPARENT =  0; // (для all)      «Прозрачный элемент» (не учитывается при проверках)
    // Основные
    const INLINE      =  1; // (для all)       Фразовый элемент (строчный) – например, <b>, <i>, <a>
    const PHRASE      =  2; // (для all)       Фразовый контейнер – например, <p>, <h1>
    const CONTAINER   =  3; // (для all)       Потоковый контейнер (блочный) – например, <div>, <section>
    const DOCUMENT    =  4; // (для all)       Корень


    const LABELS = [
        self::BLOCKED     => 'BLOCKED',
        self::VOID        => 'VOID',
        self::TRANSPARENT => 'TRANSPARENT',
        self::INLINE      => 'INLINE',
        self::PHRASE      => 'PHRASE',
        self::CONTAINER   => 'CONTAINER',
        self::DOCUMENT    => 'DOCUMENT',
    ];

    private function __construct() {}
}