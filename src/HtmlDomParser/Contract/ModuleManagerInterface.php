<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс менеджера модулей.
 *
 * Отвечает за обнаружение, загрузку и предоставление модулей.
 */
interface ModuleManagerInterface
{
    /**
     * Обнаруживает доступные модули (из composer.json extra.modules).
     *
     * @return array Список информации о модулях.
     */
    public function discover(): array;

    /**
     * Загружает и инициализирует все модули с проверкой зависимостей.
     *
     * @throws \RuntimeException При циклических зависимостях или несовместимости версий.
     */
    public function loadModules(): void;

    /**
     * Возвращает экземпляр модуля по имени.
     *
     * @param string $name
     * @return ModuleInterface|null
     */
    public function getModule(string $name): ?ModuleInterface;

    /**
     * Проверяет, загружен ли модуль с указанным именем.
     *
     * @param string $name
     * @return bool
     */
    public function hasModule(string $name): bool;

    /**
     * Возвращает список всех загруженных модулей.
     *
     * @return ModuleInterface[]
     */
    public function getLoadedModules(): array;

    /**
     * Регистрирует модуль вручную (без автоматического обнаружения).
     *
     * @param ModuleInterface $module
     */
    public function registerModule(ModuleInterface $module): void;
}