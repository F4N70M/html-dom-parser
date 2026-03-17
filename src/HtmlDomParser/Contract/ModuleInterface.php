<?php

namespace HtmlDomParser\Contract;

/**
 * Базовый интерфейс модуля.
 */
interface ModuleInterface
{
    /**
     * Уникальное имя модуля.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Список имён модулей, от которых зависит данный.
     *
     * @return string[]
     */
    public function getDependencies(): array;

    /**
     * Проверяет совместимость с указанной версией ядра.
     *
     * @param string $version Версия ядра (например, '1.0.0').
     * @return bool
     */
    public function supportsCoreVersion(string $version): bool;

    /**
     * Инициализирует модуль, подписываясь на события через диспетчер.
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function initialize(EventDispatcherInterface $dispatcher): void;
}