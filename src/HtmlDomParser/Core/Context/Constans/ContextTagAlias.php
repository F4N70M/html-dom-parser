<?php

namespace HtmlDomParser\Core\Context\Constans;

/**
 * Константы типов контекста узлов.
 */
class ContextTagAlias
{
    /**
     * Аналоги устаревших тегов для подмены
     * @var array
     */
    const LIST = [
        "acronym"   => "abbr",  // заменить на аналог abbr
        "dir"       => "ul",    // заменить на аналог ul
        "big"       => "span",  // заменить на аналог span
        "blink"     => "span",  // заменить на аналог span
        "center"    => "span",  // заменить на аналог span
        "font"      => "span",  // заменить на аналог span
        "marquee"   => "span",  // заменить на аналог span
        "nobr"      => "span",  // заменить на аналог span
        "strike"    => "del",   // заменить на аналог del
        "tt"        => "samp",  // заменить на аналог samp
        "menu"      => "ul",    // заменить на аналог ul
        "menuitem"  => "li",    // заменить на аналог li
        "isindex"   => "input", // заменить на аналог input[type=text] // ???
        "plaintext" => "pre",   // заменить на аналог pre
        "xmp"       => "pre",   // заменить на аналог pre
        "listing"   => "pre"    // заменить на аналог pre
    ];

    private function __construct() {}
}