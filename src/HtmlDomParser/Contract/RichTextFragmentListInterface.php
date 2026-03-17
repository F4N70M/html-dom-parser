<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс коллекции фрагментов форматированного текста.
 */
interface RichTextFragmentListInterface extends ParserListInterface
{
    /**
     * Добавляет фрагмент в конец списка.
     *
     * @param RichTextFragmentInterface $fragment
     */
    public function push(RichTextFragmentInterface $fragment): void;

    /**
     * Возвращает фрагмент по индексу.
     *
     * @param int $index
     * @return RichTextFragmentInterface|null
     */
    public function get(int $index): ?RichTextFragmentInterface;

    /**
     * Фильтрует коллекцию и возвращает новую коллекцию фрагментов.
     *
     * @param callable $callback Функция, принимающая RichTextFragmentInterface и возвращающая bool.
     * @return RichTextFragmentListInterface
     */
    public function filter(callable $callback): RichTextFragmentListInterface;
}