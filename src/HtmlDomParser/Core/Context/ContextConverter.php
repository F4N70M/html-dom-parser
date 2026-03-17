<?php

namespace HtmlDomParser\Core\Context;

use HtmlDomParser\Contract\ContextConverterInterface;
use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Contract\ElementInterface;
use HtmlDomParser\Contract\ContextDataResolverRulesInterface;
use HtmlDomParser\Contract\ContextDataResolverInterface;
use HtmlDomParser\Core\Context\Constans\ContextType;
use HtmlDomParser\Core\Node\Element;
use DOMNode;

/**
 * Конвертирует DOM-узлы в контексты парсинга и контексты в итоговые элементы.
 * 
 * Класс выполняет две основные функции:
 * 1. Преобразование DOMNode в NodeContext с применением правил конфигурации
 * 2. Преобразование NodeContext в готовый элемент с меткой (label) и значением (value)
 * 
 * @package HtmlDomParser\Core\Context
 */
class ContextConverter implements ContextConverterInterface
{

	/** @var ContextDataResolverInterface Резолвер данных */
	protected ContextDataResolverInterface $dataResolver;

    /**
     * Конструктор.
     * 
     * Инициализирует dataResolver и устанавливает для него правила.
     */
	public function __construct()
	{
		$this->dataResolver = new ContextDataResolver();
		$dataResolverRules = new ContextDataResolverRules($this->dataResolver);
		$this->dataResolver->setRules($dataResolverRules);
	}

    /**
     * Преобразует DOM-узел в контекст парсинга.
     * 
     * Получает конфигурацию для узла из правил dataResolver.
     * Если узел заблокирован (thisContextType === BLOCKED), возвращает null.
     *
     * @param DOMNode $node Исходный DOM-узел
     * @param int|null $parentContextType Тип контекста родительского узла (пока не используется)
     * @return NodeContextInterface|null Контекст узла или null, если узел заблокирован
     */
	public function nodeToContext(DOMNode $node, ?int $parentContextType): ?NodeContextInterface
	{
		// Получить конфигурацию контекста
		$contextConfig = $this->dataResolver->getRules()->get($node->nodeName);

		/** Пропустить заблокированный узел **/
		if ($contextConfig['thisContextType'] === ContextType::BLOCKED)
			return null;

		return new NodeContext(
			$node,
			$this->dataResolver,
			$contextConfig,
		);
	}

    /**
     * Преобразует контекст узла в готовый элемент.
     * 
     * Извлекает из контекста имя узла, метку (label) и значение (value),
     * создаёт элемент и добавляет к нему дочерние элементы из контекста.
     *
     * @param NodeContextInterface $nodeContext Контекст узла
     * @return ElementInterface Готовый элемент
     */
	public function contextToElement(NodeContextInterface $nodeContext): ElementInterface
	{
		$nodeName = $nodeContext->getName();

		$attributes = [];
		$attributes['label'] = $nodeContext->getLabel();
		$attributes['value'] = $nodeContext->getValue();

		$element = new Element($nodeName, $attributes);
		// $element = new Element($name, $attributes, $isInline, $contextType);

		// // Копируем label
		// $element->setLabel($this->extractLabel($nodeContext));

		// // Копируем data (уже извлечено или будет извлечено сейчас)
		// $element->setData($nodeContext->getData());

		// Добавляем дочерние элементы
		foreach ($nodeContext->getChildren() as $child) {
			$element->getChildren()->push($child);
		}

		// Фрагменты будут добавлены позже, после схлопывания
		// В contextToElement мы не занимаемся фрагментами

		return $element;
	}

}