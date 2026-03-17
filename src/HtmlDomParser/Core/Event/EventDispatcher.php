<?php

namespace HtmlDomParser\Core\Event;

use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Contract\NodeContextInterface;

/**
 * Заглушка диспетчера событий.
 *
 * Временная реализация, не выполняющая никаких действий.
 * Используется для обеспечения работоспособности кода до полноценной реализации.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Регистрирует обработчик события (заглушка).
     *
     * @param string   $event    Название события.
     * @param callable $handler  Обработчик.
     * @param int      $priority Приоритет.
     */
    public function subscribe(string $event, callable $handler, int $priority = 0): void
    {
        // Ничего не делаем
    }

    /**
     * Вызывает событие (заглушка).
     *
     * @param string                $event   Название события.
     * @param NodeContextInterface $context Контекст узла.
     * @return NodeContextInterface Тот же контекст без изменений.
     */
    public function dispatch(string $event, NodeContextInterface $context): NodeContextInterface
    {
        // Просто возвращаем контекст
        return $context;
    }

    /**
     * Проверяет наличие обработчиков (заглушка).
     *
     * @param string $event Название события.
     * @return bool Всегда false.
     */
    public function hasListeners(string $event): bool
    {
        return false;
    }

    /**
     * Удаляет все обработчики события (заглушка).
     *
     * @param string $event Название события.
     */
    public function clearListeners(string $event): void
    {
        // Ничего не делаем
    }
}