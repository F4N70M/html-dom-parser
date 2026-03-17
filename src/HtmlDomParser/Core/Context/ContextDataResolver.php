<?php

namespace HtmlDomParser\Core\Context;

use HtmlDomParser\Contract\ContextDataResolverInterface;
use HtmlDomParser\Contract\ContextDataResolverRulesInterface;
use HtmlDomParser\Core\Context\Constans\ContextSrc;
use DOMNode;
use DOMElement;
use DOMText;

/**
 * Извлекает данные (label, value) из DOM-узла согласно правилам.
 * 
 * Класс получает на вход DOM-узел и правило (константу или callable),
 * определяющее, откуда брать данные: из атрибутов (src, alt, value и т.д.),
 * текстового содержимого или через пользовательскую функцию.
 * 
 * Правила для каждого тега хранятся в ContextDataResolverRules.
 * 
 * @package HtmlDomParser\Core\Context
 */
class ContextDataResolver implements ContextDataResolverInterface
{
	protected ContextDataResolverRules $rules;

    /**
     * Устанавливает правила для тегов.
     * 
     * @param ContextDataResolverRulesInterface $rules Правила для тегов
     * @return void
     */
	public function setRules(ContextDataResolverRulesInterface $rules): void{
		$this->rules = $rules;
	}

    /**
     * Возвращает правила для тегов.
     * 
     * @return ContextDataResolverRulesInterface|null
     */
	public function getRules(): ?ContextDataResolverRulesInterface {
		return $this->rules;
	}

    /**
     * Извлекает значение из DOM-узла согласно правилу.
     * 
     * @param DOMNode $node DOM-узел
     * @param int|callable $rule Правило: константа ContextSrc или callable
     * @return mixed Значение или null, если не удалось извлечь
     */
	public function resolveValue(DOMNode $node, int|callable $rule): mixed {

		/** Для текстовых узлов, комментариев **/
		if (in_array($node->nodeType, [ XML_TEXT_NODE, XML_COMMENT_NODE ])) {
			return $node->textContent;
		}
		/** Все кроме элементов отсекаем (#text и #comment уже обработканы) **/
		if ($node->nodeType !== XML_ELEMENT_NODE) {
			return null;
		}
		/** Отсекаем элементы имеющие потомков **/
		if ($rule === ContextSrc::CHILDREN) {
			return null;
		}
		/** Если вызов метода **/
		if (is_callable($rule)) {
			$value = $rule($node);
			return $value;
		}
		/** Если константа **/
		if ( is_int( $rule ) ) {
			$value = $this->resolveSource($node, $rule, ['title']);
			if ( $value )
				return $value;
		}

		return null;
	}

    /**
     * Извлекает метку (label) из DOM-узла согласно правилу.
     * 
     * @param DOMNode $node DOM-узел
     * @param int|callable $rule Правило: константа ContextSrc или callable
     * @return string Метка или имя узла, если не удалось извлечь
     */
	public function resolveLabel(DOMNode $node, mixed $rule): string {

		$nodeName = strtolower($node->nodeName);
		
		/** Для текстовых узлов, комментариев **/
		if (in_array($node->nodeType, [ XML_TEXT_NODE, XML_COMMENT_NODE ])) {
			return $nodeName;
		}
		/** Все кроме элементов отсекаем (#text и #comment уже обработканы) **/
		if ($node->nodeType !== XML_ELEMENT_NODE) {
			return null;
		}
		/** Если вызов метода **/
		if (is_callable($rule)) {
			$value = $rule($node);
			return $value;
		}
		/** Если константа **/
		if ( is_int( $rule ) ) {
			$value = $this->resolveSource($node, $rule, ['title']);
			if ( $value )
				return $value;
		}

		return $node->nodeName;
	}

    /**
     * Извлекает данные из DOM-элемента на основе константы ContextSrc.
     * 
     * @param DOMElement $node DOM-элемент
     * @param int $rule Константа ContextSrc
     * @param array $default Дополнительные атрибуты для поиска
     * @return mixed Найденное значение или null
     */
	protected function resolveSource(DOMElement $node, int $rule, array $default = []) {

			switch ($rule) {

				case ContextSrc::SRC :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						['src', 'srcset', 'data-src', 'data-srcset'], $default
					));
					break;

				case ContextSrc::SRCSET :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'srcset', 'src', 'data-srcset', 'data-src' ], $default
					));
					break;

				case ContextSrc::TEXT :
					$value = $node->textContent;
					break;

				case ContextSrc::NAME :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'name' ], $default
					));
					break;

				case ContextSrc::VALUE :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'value' ], $default
					));
					break;

				case ContextSrc::LABEL :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'label' ], $default
					));
					break;

				case ContextSrc::TITLE :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'title' ], $default
					));
					break;

				case ContextSrc::CONTENT :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'content' ], $default
					));
					break;

				case ContextSrc::ACTION :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'action' ], $default
					));
					break;

				case ContextSrc::POSTER :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'poster' ], $default
					));
					break;

				case ContextSrc::ALT :
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[ 'alt' ], $default
					));
					break;

				case ContextSrc::NONE :
				case ContextSrc::DEFAULT :
				case ContextSrc::CHILDREN :
				default:
					$value = $this->resolveNodeAttributes( $node, $this->mergeUniqueStrings(
						[], $default
					));
					break;
			}
			return $value;
	}

	/**
	 * Объединяет два массива строк, сохраняя порядок и удаляя дубликаты.
	 * 
	 * @param array $array1 Первый массив (его порядок сохраняется полностью)
	 * @param array $array2 Второй массив (добавляются только уникальные элементы в исходном порядке)
	 * @return array Новый массив без дубликатов
	 */
	protected function mergeUniqueStrings(array $array1, array $array2): array
	{
		$result = $array1;
		/** Создаём lookup-таблицу для быстрой проверки существования **/
		$lookup = array_flip($array1);
		/** Проходим по второму массиву **/
		foreach ($array2 as $value) {
			if (!isset($lookup[$value])) {
				/** Добавляем в результат и в lookup **/
				$lookup[$value] = true;
				$result[] = $value;
			}
		}
		return $result;
	}

    /**
     * Извлекает значение из DOM-элемента по списку атрибутов.
     * Перебирает атрибуты в заданном порядке, возвращает первое непустое значение.
     * Для srcset/data-srcset извлекает первый URL из списка.
     *
     * @param DOMElement $node DOM-элемент
     * @param array $attributes Список атрибутов для проверки (в порядке приоритета)
     * @return string|null Найденное значение или null
     */
	protected function resolveNodeAttributes(DOMElement $node, array $attributes) {
		if (!$node instanceof \DOMElement) {
			return null;
		}
		foreach ($attributes as $attr) {
			if (!$node->hasAttribute($attr)) {
				continue;
			}
			$value = trim($node->getAttribute($attr));
			switch ($attr) {
				case 'srcset':
				case 'data-srcset':
					// Для srcset и data-srcset извлекаем первый URL из списка
					// Разделяем по запятой, берём первый элемент
					$parts = explode(',', $value);
					$firstPart = trim($parts[0]);

					// Удаляем дескриптор (всё после пробела)
					$spacePos = strpos($firstPart, ' ');
					if ($spacePos !== false) {
						$url = substr($firstPart, 0, $spacePos);
					} else {
						$url = $firstPart;
					}
					return trim($url);
					break;
				
				default:
					if ($value === '') continue(2);
					return $value;
					break;
			}
		}
		return null;
	}

}