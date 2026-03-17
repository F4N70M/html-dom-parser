<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс карты контекстов тегов.
 *
 * Содержит правила для всех стандартных HTML-тегов.
 */
interface ContextDataResolverRulesInterface
{
	/**
	 * Получает правила конфигурации для указанного тега.
	 * Правила определяют, откуда брать label, value, стили и допустимые потомки.
	 *
	 * @param string $tag Имя тега
	 * @return array Конфигурация тега с правилами извлечения данных
	 */
	public function get(string $tag): array;

	/**
	 * Проверяет наличие правил для указанного тега.
	 *
	 * @param string $tag Имя тега
	 * @return bool
	 */
	public function has(string $tag): bool;
}