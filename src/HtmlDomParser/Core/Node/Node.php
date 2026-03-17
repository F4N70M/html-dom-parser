<?php

namespace HtmlDomParser\Core\Node;

use HtmlDomParser\Contract\NodeInterface;

/**
 * Абстрактный базовый класс для всех узлов дерева.
 */
abstract class Node implements NodeInterface
{
    /** @var string Имя узла */
    protected string $name;

    /** @var array Атрибуты узла */
    protected array $attributes = [];

    /**
     * Конструктор.
     *
     * @param string $name Имя узла.
     * @param array  $attributes Атрибуты узла (по умолчанию []).
     */
    public function __construct(string $name, array $attributes = [])
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }

    /**
     * Возвращает имя узла.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Возвращает все атрибуты узла.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Возвращает значение атрибута по имени.
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Проверяет наличие атрибута.
     *
     * @param string $name
     * @return bool
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Устанавливает атрибут.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setAttribute(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Удаляет атрибут.
     *
     * @param string $name
     */
    public function removeAttribute(string $name): void
    {
        unset($this->attributes[$name]);
    }
}