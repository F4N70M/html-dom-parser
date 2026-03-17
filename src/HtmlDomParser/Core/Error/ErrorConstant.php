<?php

namespace HtmlDomParser\Core\Error;

/**
 * Константы уровней серьезности ошибок.
 *
 * Используются для классификации ошибок парсинга и настройки поведения
 * (выброс исключений, создание узлов-ошибок).
 */
class ErrorConstant
{
    public const SEVERITY_NOTICE  = 'notice';  // Уведомление, не влияет на результат
    public const SEVERITY_WARNING = 'warning'; // Предупреждение, возможны незначительные проблемы
    public const SEVERITY_ERROR   = 'error';   // Фатальная ошибка, результат может быть некорректен
}