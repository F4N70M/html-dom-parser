<?php

namespace HtmlDomParser\Contract;

/**
 * Базовый интерфейс для всех узлов дерева.
 *
 * Обеспечивает доступ к имени и атрибутам узла.
 */
interface NodeInterface
{
    /**
     * Возвращает имя узла.
     *
     * @return string Например, 'div', '#text', '#document', '#comment', '#error'.
     */
    public function getName(): string;

    /**
     * Возвращает все атрибуты узла.
     *
     * @return array Ассоциативный массив вида ['атрибут' => 'значение'].
     */
    public function getAttributes(): array;

    /**
     * Возвращает значение атрибута по имени.
     *
     * @param string $name Имя атрибута.
     * @return mixed Значение атрибута или null, если атрибут не найден.
     */
    public function getAttribute(string $name): mixed;

    /**
     * Проверяет наличие атрибута.
     *
     * @param string $name Имя атрибута.
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Устанавливает значение атрибута.
     *
     * @param string $name  Имя атрибута.
     * @param mixed  $value Значение атрибута.
     */
    public function setAttribute(string $name, $value): void;

    /**
     * Удаляет атрибут.
     *
     * @param string $name Имя атрибута.
     */
    public function removeAttribute(string $name): void;
}