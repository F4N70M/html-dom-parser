<?php

namespace HtmlDomParser\Contract;

/**
 * Базовый интерфейс для всех коллекций библиотеки.
 *
 * Обеспечивает единообразные методы для подсчёта, итерации, преобразования в массив/JSON.
 */
interface ParserListInterface extends \IteratorAggregate, \Countable
{
    /**
     * Возвращает количество элементов в коллекции.
     *
     * @return int
     */
    public function count(): int;

    /**
     * Возвращает итератор для перебора элементов.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator;

    /**
     * Преобразует коллекцию в массив объектов.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Преобразует коллекцию в JSON-строку.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Применяет callback к каждому элементу и возвращает массив результатов.
     *
     * @param callable $callback Функция, принимающая элемент коллекции и возвращающая значение.
     * @return array
     */
    public function map(callable $callback): array;
}