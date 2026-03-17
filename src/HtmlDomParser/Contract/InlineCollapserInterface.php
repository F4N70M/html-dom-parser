<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс сервиса схлопывания строчных элементов.
 */
interface InlineCollapserInterface
{
    /**
     * Выполняет схлопывание последовательности inline-элементов в единый текст с фрагментами.
     *
     * @param NodeContextInterface $context Контекст узла.
     * @param array                $options Опции схлопывания (зарезервировано на будущее).
     * @return NodeContextInterface Модифицированный контекст с результатом схлопывания.
     */
    public function collapse(NodeContextInterface $context, array $options = []): NodeContextInterface;
}