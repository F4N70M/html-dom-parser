<?php

namespace HtmlDomParser\Core\Collection;

use HtmlDomParser\Contract\ElementInterface;
use HtmlDomParser\Contract\ElementListInterface;

/**
 * Коллекция элементов.
 */
class ElementList extends ParserList implements ElementListInterface
{
    /**
     * Добавляет элемент в конец списка.
     *
     * @param ElementInterface $element
     */
    public function push(ElementInterface $element): void
    {
        $this->items[] = $element;
    }

    /**
     * Возвращает элемент по индексу.
     *
     * @param int $index
     * @return ElementInterface|null
     */
    public function get(int $index): ?ElementInterface
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Фильтрует коллекцию по заданному callback'у и возвращает новую коллекцию.
     *
     * @param callable $callback Функция, принимающая ElementInterface и возвращающая bool.
     * @return ElementListInterface
     */
    public function filter(callable $callback): ElementListInterface
    {
        $filtered = array_filter($this->items, $callback);
        return new static(array_values($filtered));
    }
}