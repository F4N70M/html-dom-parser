<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс фрагмента форматированного текста.
 *
 * Представляет часть текста внутри элемента, оформленную определённым тегом.
 */
interface RichTextFragmentInterface
{
    /**
     * Возвращает тип форматирования (имя тега).
     *
     * @return string Например, 'b', 'i', 'a', 'strong'.
     */
    public function getType(): string;

    /**
     * Возвращает начальную позицию фрагмента в тексте (индекс символа).
     *
     * @return int
     */
    public function getStart(): int;

    /**
     * Возвращает конечную позицию фрагмента (индекс символа, не включая).
     *
     * @return int
     */
    public function getEnd(): int;

    /**
     * Возвращает все атрибуты, связанные с фрагментом.
     *
     * @return array Ассоциативный массив вида ['атрибут' => 'значение'].
     */
    public function getAttributes(): array;

    /**
     * Возвращает значение конкретного атрибута фрагмента.
     *
     * @param string $name Имя атрибута.
     * @return mixed Значение атрибута или null, если атрибут отсутствует.
     */
    public function getAttribute(string $name): mixed;

    /**
     * Проверяет наличие атрибута у фрагмента.
     *
     * @param string $name Имя атрибута.
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Преобразует фрагмент в массив для сериализации.
     *
     * @return array Структура: ['type' => string, 'start' => int, 'end' => int, 'attributes' => array].
     */
    public function toArray(): array;
}