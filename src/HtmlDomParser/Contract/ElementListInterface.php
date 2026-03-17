<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс коллекции элементов.
 *
 * Предоставляет методы для работы с набором элементов.
 */
interface ElementListInterface extends ParserListInterface
{
    /**
     * Добавляет элемент в конец списка.
     *
     * @param ElementInterface $element
     */
    public function push(ElementInterface $element): void;

    /**
     * Возвращает элемент по индексу.
     *
     * @param int $index
     * @return ElementInterface|null
     */
    public function get(int $index): ?ElementInterface;

    /**
     * Фильтрует коллекцию по заданному callback'у и возвращает новую коллекцию.
     *
     * @param callable $callback Функция, принимающая ElementInterface и возвращающая bool.
     * @return ElementListInterface
     */
    public function filter(callable $callback): ElementListInterface;
}