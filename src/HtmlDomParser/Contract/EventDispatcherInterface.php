<?php

namespace HtmlDomParser\Contract;

use HtmlDomParser\Exception\InvalidEventListenerException;

/**
 * Интерфейс диспетчера событий.
 */
interface EventDispatcherInterface
{
    /**
     * Регистрирует обработчик для события.
     *
     * @param string   $event    Название события (одна из констант EventConstant).
     * @param callable $handler  Функция-обработчик: function(NodeContextInterface $context): NodeContextInterface.
     * @param int      $priority Приоритет (чем выше, тем раньше вызывается). По умолчанию 0.
     * @throws InvalidEventListenerException При несоответствии сигнатуры обработчика.
     */
    public function subscribe(string $event, callable $handler, int $priority = 0): void;

    /**
     * Вызывает все обработчики события.
     *
     * @param string                $event   Название события.
     * @param NodeContextInterface $context Текущий контекст узла.
     * @return NodeContextInterface Модифицированный контекст после всех обработчиков.
     */
    public function dispatch(string $event, NodeContextInterface $context): NodeContextInterface;

    /**
     * Проверяет, есть ли обработчики у события.
     *
     * @param string $event
     * @return bool
     */
    public function hasListeners(string $event): bool;

    /**
     * Удаляет все обработчики события.
     *
     * @param string $event
     */
    public function clearListeners(string $event): void;
}