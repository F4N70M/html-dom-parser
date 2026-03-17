<?php

namespace HtmlDomParser\Contract;

use DOMNode;

/**
 * Интерфейс для резолвера данных из DOM-узла.
 * 
 * @package HtmlDomParser\Contract
 */
interface ContextDataResolverInterface
{
    /**
     * Устанавливает правила резолвинга для тегов.
     * 
     * @param ContextDataResolverRulesInterface $rules
     * @return void
     */
    public function setRules(ContextDataResolverRulesInterface $rules): void;

    /**
     * Возвращает правила резолвинга для тегов.
     * 
     * @return ContextDataResolverRulesInterface|null
     */
    public function getRules(): ?ContextDataResolverRulesInterface;

    /**
     * Извлекает значение из DOM-узла согласно правилу.
     * 
     * @param DOMNode $node DOM-узел
     * @param int|callable $rule Правило: константа ContextSrc или callable
     * @return mixed Значение или null, если не удалось извлечь
     */
    public function resolveValue(DOMNode $node, int|callable $rule): mixed;

    /**
     * Извлекает метку (label) из DOM-узла согласно правилу.
     * 
     * @param DOMNode $node DOM-узел
     * @param int|callable $rule Правило: константа ContextSrc или callable
     * @return string Метка или имя узла, если не удалось извлечь
     */
    public function resolveLabel(DOMNode $node, int|callable $rule): string;
}