<?php

namespace HtmlDomParser\Core\Collection;

use HtmlDomParser\Contract\RichTextFragmentInterface;
use HtmlDomParser\Contract\RichTextFragmentListInterface;

/**
 * Коллекция фрагментов форматирования.
 */
class FragmentList extends ParserList implements RichTextFragmentListInterface
{
    /**
     * Добавляет фрагмент в конец списка.
     *
     * @param RichTextFragmentInterface $fragment
     */
    public function push(RichTextFragmentInterface $fragment): void
    {
        $this->items[] = $fragment;
    }

    /**
     * Возвращает фрагмент по индексу.
     *
     * @param int $index
     * @return RichTextFragmentInterface|null
     */
    public function get(int $index): ?RichTextFragmentInterface
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Фильтрует коллекцию и возвращает новую коллекцию фрагментов.
     *
     * @param callable $callback Функция, принимающая RichTextFragmentInterface и возвращающая bool.
     * @return RichTextFragmentListInterface
     */
    public function filter(callable $callback): RichTextFragmentListInterface
    {
        $filtered = array_filter($this->items, $callback);
        return new static(array_values($filtered));
    }
}