<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс корневого документа.
 *
 * Представляет собой точку входа в дерево элементов.
 */
interface DocumentInterface extends NodeInterface
{
    /**
     * Возвращает коллекцию дочерних элементов (корневые узлы документа).
     *
     * @return ElementListInterface
     */
    public function getChildren(): ElementListInterface;
}