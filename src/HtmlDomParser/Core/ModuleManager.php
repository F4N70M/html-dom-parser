<?php

namespace HtmlDomParser\Core;

use HtmlDomParser\Contract\ModuleManagerInterface;
use HtmlDomParser\Contract\ModuleInterface;

/**
 * Реализация менеджера модулей.
 *
 * Отвечает за обнаружение, загрузку и предоставление модулей.
 */
class ModuleManager implements ModuleManagerInterface
{
    /** @var ModuleInterface[] Ассоциативный массив загруженных модулей (имя => экземпляр) */
    private array $modules = [];

    /**
     * Обнаруживает доступные модули (из composer.json extra.modules).
     *
     * @return array Список информации о модулях.
     */
    public function discover(): array
    {
        // Заглушка – будет реализовано позже
        return [];
    }

    /**
     * Загружает и инициализирует все модули с проверкой зависимостей.
     *
     * @throws \RuntimeException При циклических зависимостях или несовместимости версий.
     */
    public function loadModules(): void
    {
        // Заглушка – будет реализовано позже
    }

    /**
     * Возвращает экземпляр модуля по имени.
     *
     * @param string $name
     * @return ModuleInterface|null
     */
    public function getModule(string $name): ?ModuleInterface
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Проверяет, загружен ли модуль с указанным именем.
     *
     * @param string $name
     * @return bool
     */
    public function hasModule(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Возвращает список всех загруженных модулей.
     *
     * @return ModuleInterface[]
     */
    public function getLoadedModules(): array
    {
        return $this->modules;
    }

    /**
     * Регистрирует модуль вручную (без автоматического обнаружения).
     *
     * @param ModuleInterface $module
     */
    public function registerModule(ModuleInterface $module): void
    {
        $this->modules[$module->getName()] = $module;
    }
}