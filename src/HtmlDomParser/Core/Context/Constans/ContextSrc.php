<?php

namespace HtmlDomParser\Core\Context\Constans;

/**
 * Константы типов контекста узлов.
 */
class ContextSrc
{
    // Служебные
    const NONE     = 100; // none
    const DEFAULT  = 101; // none
    const CHILDREN = 102; // none
    const TEXT     = 103; // textContent
    const HREF     = 104; // attr: href
    const SRC      = 105; // attr: src srcset
    const SRCSET   = 106; // attr: srcset src 
    const NAME     = 107; // attr: name
    const VALUE    = 108; // attr: value
    const LABEL    = 109; // attr: label
    const TITLE    = 110; // attr: title
    const CONTENT  = 111; // attr: content
    const ACTION   = 112; // attr: action
    const POSTER   = 113; // attr: poster
    const ALT      = 114; // attr: poster

    const LABELS = [
        self::NONE     => 'NONE',
        self::DEFAULT  => 'DEFAULT',
        self::CHILDREN => 'CHILDREN',
        self::TEXT     => 'TEXT',
        self::HREF     => 'HREF',
        self::SRC      => 'SRC',
        self::SRCSET   => 'SRCSET',
        self::NAME     => 'NAME',
        self::VALUE    => 'VALUE',
        self::LABEL    => 'LABEL',
        self::TITLE    => 'TITLE',
        self::CONTENT  => 'CONTENT',
        self::ACTION   => 'ACTION',
        self::POSTER   => 'POSTER',
        self::ALT      => 'ALT',
    ];

    private function __construct() {}
}