<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс элемента (HTML-тега) в итоговом дереве.
 *
 * Расширяет NodeInterface и добавляет семантические методы.
 */
interface ElementInterface extends NodeInterface
{
    /**
     * Возвращает основное содержимое элемента (Data).
     *
     * @return mixed Например, для ссылки — URL, для изображения — src, для параграфа — текст.
     */
    public function getData(): mixed;

    /**
     * Устанавливает основное содержимое элемента.
     *
     * @param mixed $value Новое значение Data.
     */
    public function setData($value): void;

    /**
     * Возвращает текстовую метку элемента (Label).
     *
     * @return string Объединённый текст элемента без тегов.
     */
    public function getLabel(): string;

    /**
     * Устанавливает текстовую метку.
     *
     * @param string $label Новая метка.
     */
    public function setLabel(string $label): void;

    /**
     * Возвращает коллекцию фрагментов форматированного текста.
     *
     * @return RichTextFragmentListInterface
     */
    public function getFragments(): RichTextFragmentListInterface;

    /**
     * Добавляет фрагмент форматирования.
     *
     * @param RichTextFragmentInterface $fragment Фрагмент для добавления.
     */
    public function addFragment(RichTextFragmentInterface $fragment): void;

    /**
     * Проверяет, является ли элемент строчным (inline).
     *
     * @return bool
     */
    public function isInline(): bool;

    /**
     * Возвращает коллекцию дочерних элементов.
     *
     * @return ElementListInterface
     */
    public function getChildren(): ElementListInterface;

    /**
     * Проверяет наличие дочерних элементов.
     *
     * @return bool
     */
    public function hasChildren(): bool;

    /**
     * Возвращает числовой тип контекста элемента.
     *
     * @return int Одна из констант ContextTypeConstant.
     */
    public function getContextType(): int;
}