<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс узла-ошибки, замещающего проблемный HTML-узел в дереве.
 */
interface ErrorElementInterface extends ElementInterface
{
    /**
     * Возвращает уровень серьезности ошибки.
     *
     * @return string Одна из констант ErrorConstant::SEVERITY_*.
     */
    public function getSeverity(): string;

    /**
     * Возвращает тип ошибки (например, 'missingRule', 'disallowedChild').
     *
     * @return string
     */
    public function getErrorType(): string;

    /**
     * Возвращает backtrace в момент возникновения ошибки.
     *
     * @return array Стандартный PHP-массив, возвращаемый debug_backtrace().
     */
    public function getBacktrace(): array;

    /**
     * Возвращает оригинальные атрибуты тега, вызвавшего ошибку.
     *
     * @return array Ассоциативный массив атрибутов исходного узла.
     */
    public function getOriginalAttributes(): array;

    /**
     * Проверяет, является ли ошибка фатальной (уровня ERROR).
     *
     * @return bool
     */
    public function isFatal(): bool;
}