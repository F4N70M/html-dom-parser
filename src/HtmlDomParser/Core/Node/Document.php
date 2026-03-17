<?php

namespace HtmlDomParser\Core\Node;

use HtmlDomParser\Contract\DocumentInterface;
use HtmlDomParser\Contract\ElementListInterface;
use HtmlDomParser\Core\Collection\ElementList;

/**
 * Корневой документ.
 *
 * Представляет собой точку входа в дерево элементов.
 * Имя узла всегда '#document'.
 */
class Document extends Node implements DocumentInterface
{
    /** @var ElementListInterface Коллекция дочерних элементов (корневые узлы) */
    protected ElementListInterface $children;

    /**
     * Конструктор.
     *
     * @param ElementListInterface|null $children Начальная коллекция дочерних элементов.
     *                                             Если не передана, создаётся пустая коллекция.
     */
    public function __construct(?ElementListInterface $children = null)
    {
        parent::__construct('#document');
        $this->children = $children ?? new ElementList();
    }

    /**
     * Возвращает коллекцию дочерних элементов (корневые узлы документа).
     *
     * @return ElementListInterface Коллекция корневых элементов.
     */
    public function getChildren(): ElementListInterface
    {
        return $this->children;
    }
}