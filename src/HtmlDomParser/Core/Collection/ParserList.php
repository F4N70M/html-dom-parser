<?php

namespace HtmlDomParser\Core\Collection;

use HtmlDomParser\Contract\ParserListInterface;

/**
 * Абстрактная базовая коллекция.
 *
 * Реализует общие методы для всех коллекций библиотеки.
 */
abstract class ParserList implements ParserListInterface
{
    /** @var array Элементы коллекции */
    protected array $items = [];

    /**
     * Конструктор.
     *
     * @param array $items Начальные элементы.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Возвращает количество элементов в коллекции.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Возвращает итератор для перебора элементов.
     *
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Преобразует коллекцию в массив объектов.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Преобразует коллекцию в JSON-строку.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Применяет callback к каждому элементу и возвращает массив результатов.
     *
     * @param callable $callback Функция, принимающая элемент коллекции и возвращающая значение.
     * @return array
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }
}