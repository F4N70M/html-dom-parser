<?php
namespace HtmlDomParser\Core\Context;

use HtmlDomParser\Contract\ContextDataResolverRulesInterface;
use HtmlDomParser\Exception\InvalidContextAllowedTags;
use HtmlDomParser\Core\Context\Constans\ContextType;
use HtmlDomParser\Core\Context\Constans\ContextPermission;
use HtmlDomParser\Core\Context\Constans\ContextSrc;
use HtmlDomParser\Core\Context\Constans\ContextStyle;
use HtmlDomParser\Core\Context\Constans\ContextTagAlias;
use HtmlDomParser\Core\Context\ContextDataResolver;
use ReflectionMethod;
use ReflectionFunction;
use DOMElement;

/**
 * Карта правил для HTML-тегов.
 *
 * Содержит конфигурации для всех стандартных HTML-тегов.
 */
class ContextDataResolverRules implements ContextDataResolverRulesInterface
{
	/** @var array Карта тегов */
	protected ContextDataResolver $dataResolver;
	protected array $map;
	protected array $cache = [];

	/**
	 * Конструктор.
	 */
	public function __construct(ContextDataResolver $dataResolver) {
		$this->dataResolver = $dataResolver;
		$this->map = $this->buildDefaultMap();
		var_dump([$this->dataResolver, 'resolveImgLabel']);
		var_dump(is_callable([$this->dataResolver, 'resolveImgLabel']));
	}

	/**
	 * Получает правила конфигурации для указанного тега.
	 * Правила определяют, откуда брать label, value, стили и допустимые потомки.
	 *
	 * @param string $tag Имя тега
	 * @return array Конфигурация тега с правилами извлечения данных
	 */
	public function get(string $tag): array
	{
		/** Проверить алиасы (синонимы) тега **/
		$tag = $this->getCurrentTagName($tag);
		/** Проверить кеш **/

		return $this->getDecodeConfig($tag);
	}

	/**
	 * Проверяет наличие правил для указанного тега.
	 *
	 * @param string $tag Имя тега
	 * @return bool
	 */
	public function has(string $tag): bool
	{
		$tag = $this->getCurrentTagName($tag);
		return isset($this->map[$tag]);
	}

	/**
	 * Получает обработанную конфигурацию из кеша или создаёт новую.
	 *
	 * @param string $tag Имя тега
	 * @return array Готовая конфигурация
	 */
	protected function getDecodeConfig(string $tag): array
	{
		if (!isset($this->cache[$tag])) {
			$rawConfig = $this->map[$tag] ?? $this->getDefault();
			/** Закешировать конфигурацию **/
			$this->cache[$tag] = $this->decodeConfig($tag, $rawConfig);
		}

		return $this->cache[$tag];
	}

	/**
	 * Преобразует сырую конфигурацию в готовую, применяя валидацию и значения по умолчанию.
	 *
	 * @param string $nodeName Имя узла
	 * @param array $rawConfig Исходная конфигурация из карты
	 * @return array Конфигурация с проверенными значениями
	 */
	protected function decodeConfig(string $nodeName, array $rawConfig): array {
		$result = [
			'thisContextType'		=> $rawConfig['thisContextType'] ?? ContextType::TRANSPARENT,
			'childrenContextType'	=> $rawConfig['childrenContextType'] ?? ContextType::TRANSPARENT,
			'style'					=> $rawConfig['style'] ?? ContextType::TRANSPARENT,
			'label'					=> $this->getValidSource( $nodeName, $rawConfig['label'] ?? ContextSrc::DEFAULT,  ContextSrc::DEFAULT ),
			'value'					=> $this->getValidSource( $nodeName, $rawConfig['value'] ?? ContextSrc::CHILDREN, ContextSrc::DEFAULT ),
			'allowedTags'			=> $this->getValidPermission( $nodeName, $rawConfig['allowedTags']??ContextPermission::ANY,  ContextPermission::ANY  ),
			'deniedTags'			=> $this->getValidPermission( $nodeName, $rawConfig['deniedTags'] ??ContextPermission::NONE, ContextPermission::NONE ),
			// 'isEscapable' => true,
		];

		return $result;
	}

	/**
	 * Проверяет и возвращает валидное значение для полей label/value.
	 * Допустимые типы: целые числа (константы ContextSrc) и callable-функции.
	 *
	 * @param string $tag Имя тега
	 * @param mixed $rule Проверяемое значение
	 * @return int|array|callable Валидное значение
	 * @throws InvalidContextAllowedTags
	 */
	protected function getValidSource(string $tag, mixed $rule): int|array|callable
	{
		if (is_int($rule)) {
			$this->validateConst($tag, $rule, ContextSrc::class);
			return $rule;
		}
		if (is_callable($rule)) {
			$this->validateCallable($tag, $rule);
			return $rule;
		}
		throw new InvalidContextAllowedTags(
			sprintf(
				'Значение источника данных тега `<b>%s</b>` должно быть `<b>%s::CONST</b>` или `<b>callable</b>`, получено `<b>%s</b>`',
				$tag,
				ContextSrc::class,
				gettype($rule)
			)
		);
	}

	/**
	 * Проверяет и возвращает валидное значение для полей allowedTags/deniedTags.
	 * Допустимые типы: булевы (константы ContextPermission), callable, массивы строк.
	 *
	 * @param string $tag Имя тега
	 * @param mixed $rule Проверяемое значение
	 * @return bool|array|callable Валидное значение
	 * @throws InvalidContextAllowedTags
	 */
	protected function getValidPermission(string $tag, mixed $rule): bool|array|callable
	{
		if (is_bool($rule)) {
			$this->validateConst($tag, $rule, ContextPermission::class);
			return $rule;
		}
		if (is_callable($rule)) {
			$this->validateCallable($tag, $rule);
			return $rule;
		}
		if (is_array($rule)) {
			$this->validateChildrenTags($tag, $rule);
			return $rule;
		}
		throw new InvalidContextAllowedTags(
			sprintf(
				'Значение allowed/denied тега `%s` должно быть bool, callable или array, получено %s',
				$tag,
				gettype($rule)
			)
		);
	}

	/**
	 * Проверяет callable-функцию на соответствие требованиям:
	 * - должна принимать ровно 1 аргумент
	 * - первый аргумент должен иметь тип DOMElement
	 *
	 * @param string $tag Имя тега
	 * @param string|callable|array $callable Проверяемая функция
	 * @return void
	 * @throws InvalidContextAllowedTags
	 */
	protected function validateCallable(string $tag, string|callable|array $callable): void
	{
		$argTypes = $this->getCallableParamTypes($callable);
		if (count($argTypes) !== 1) {
			throw new InvalidContextAllowedTags(
				sprintf(
					'Метод определения значения конфигурации `<b>%s</b>` тега `<b>%s</b>` должен принимать ровно <b>1</b> аргумент, передано <b>%d</b>',
					is_string($callable) ? $callable : (is_array($callable) ? implode('::', $callable) : get_debug_type($callable)),
					$tag,
					count($argTypes)
				)
			);
		}
		if ($argTypes[0][0] !== DOMElement::class) {
			throw new InvalidContextAllowedTags(
				sprintf('Первый аргумент метода определения значения конфигурации тега `<b>%s</b>` должен иметь тип `<b>%s</b>`, получен <b>%s</b>', $tag, NodeContext::class, $argTypes[0][0])
			);
		}
	}

	/**
	 * Получает информацию о типах аргументов callable-функции.
	 *
	 * @param callable $callable Проверяемая функция
	 * @return array Массив с информацией о параметрах [тип, допускаетNull]
	 */
	protected function getCallableParamTypes(callable $callable): array
	{
	    $ref = match(true) {
	        is_array($callable) => new \ReflectionMethod($callable[0], $callable[1]),
	        is_string($callable) && str_contains($callable, '::') => new \ReflectionMethod(...explode('::', $callable, 2)),
	        default => new \ReflectionFunction($callable)
	    };

	    $result = [];
	    foreach ($ref->getParameters() as $p) {
	        $result[] = $p->hasType()
	            ? [$p->getType()->getName(), $p->allowsNull()]
	            : ['mixed', true];
	    }
	    return $result;
	}

	/**
	 * Проверяет, что значение является допустимой константой указанного класса.
	 *
	 * @param string $tag Имя тега
	 * @param mixed $rule Проверяемое значение
	 * @param string $constClass Класс с константами
	 * @return void
	 * @throws InvalidContextAllowedTags
	 */
	protected function validateConst(string $tag, mixed $rule, string $constClass): void
	{
		$constants = (new \ReflectionClass($constClass))->getConstants();
		if (!in_array($rule, $constants, true)) {
			throw new InvalidContextAllowedTags(
				sprintf(
					'Недопустимая константа %s для тега `%s`: %s',
					$constClass,
					$tag,
					var_export($rule, true)
				)
			);
		}
	}

	/**
	 * Проверяет, что массив содержит только строки (имена тегов).
	 *
	 * @param string $tag Имя тега
	 * @param array $array Проверяемый массив
	 * @return void
	 * @throws InvalidContextAllowedTags
	 */
	protected function validateChildrenTags(string $tag, array $array): void
	{
		foreach ($array as $index => $item) {
			if (!is_string($item)) {
				throw new InvalidContextAllowedTags(
					sprintf(
						'Элемент #%d массива разрешённых потомков тега `%s` должен быть строкой, получен %s',
						$index,
						$tag,
						gettype($item)
					)
				);
			}
		}
	}

	/**
	 * Возвращает каноническое имя тега с учётом алиасов устаревших тегов.
	 *
	 * @param string $name Исходное имя тега
	 * @return string Нормализованное имя
	 */
	protected function getCurrentTagName(string $name): string {
		return ContextTagAlias::LIST[$name] ?? $name;
	}

	/**
	 * Возвращает базовую конфигурацию для тегов, не указанных в карте.
	 *
	 * @return array Конфигурация по умолчанию
	 */
	protected function getDefault(): array
	{
		return [
			'thisContextType'		=> ContextType::TRANSPARENT,
			'childrenContextType'	=> ContextType::TRANSPARENT,
			'allowedTags'			=> ContextPermission::ANY,
		];
	}

	/**
	 * Создаёт карту конфигураций для всех стандартных HTML-тегов.
	 * Каждый тег содержит правила для:
	 * - типа контекста (thisContextType, childrenContextType)
	 * - стиля отображения (style)
	 * - источников метки и значения (label, value)
	 * - допустимых и запрещённых потомков (allowedTags, deniedTags)
	 *
	 * @return array Карта тегов с конфигурациями
	 */
	protected function buildDefaultMap(): array
	{
		try {
			
		return [
			/**
			 * Текстовые узлы и комментарии
			 */
			"#text" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::VOID,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::ANY,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::TEXT,
				'style'                => ContextStyle::NORMAL
			],
			"#comment" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::VOID,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::ANY,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::TEXT,
				'style'                => ContextStyle::NORMAL
			],
			/**
			 * Корневые и служебные теги
			 * ------------------------------------------------------------------
			 * Эти теги обычно не включаются в итоговую структуру, так как парсер
			 * работает с фрагментами контента. Они исключены (includeType = null),
			 * но в комментариях приведены примеры возможных конфигураций на случай,
			 * если потребуется их обработка.
			 */
			"html" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"head" => [
				'thisContextType'      => ContextType::BLOCKED
			],
			"body" => [
				'thisContextType'      => ContextType::TRANSPARENT,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** title — должен содержать только текст, поэтому в примере allowedTags = ['#text']. **/
			"title" => [
				'thisContextType'      => ContextType::BLOCKED,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::TEXT,
				'style'                => ContextStyle::NORMAL
			],
			/** base, link, meta — пустые элементы, не имеют потомков. **/
			"base" => [
				'thisContextType'      => ContextType::BLOCKED,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::HREF,
				'style'                => ContextStyle::NORMAL
			],
			"link" => [
				'thisContextType'      => ContextType::BLOCKED,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::HREF,
				'style'                => ContextStyle::NORMAL
			],
			"meta" => [
				'thisContextType'      => ContextType::BLOCKED,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => [ $this->dataResolver, 'resolveMetaLabel' ],
				'value'                => ContextSrc::CONTENT,
				'style'                => ContextStyle::NORMAL
			],
			/** style и script — содержат только текст (CSS/JS). **/
			"style" => [
				'thisContextType'      => ContextType::BLOCKED,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::TEXT,
				'style'                => ContextStyle::PREFORMATTED
			],
			"script" => [
				'thisContextType'      => ContextType::BLOCKED,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => [ $this->dataResolver, 'resolveScriptValue' ],
				'style'                => ContextStyle::PREFORMATTED
			],
			"noscript" => [
				'thisContextType'      => ContextType::BLOCKED
			],
			
			"area" => [
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::HREF,
				'style'                => ContextStyle::NORMAL
			],

			/**
			 * ------------------------------------------------------------------
			 * Секционные и группирующие элементы (блочные)
			 * ------------------------------------------------------------------
			 * Эти теги являются контейнерами и могут содержать любые другие элементы.
			 * includeType = container позволяет им находиться в любом блочном контексте,
			 * childrenType = container разрешает внутри любые категории.
			 */
			"div" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"figure" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => [ $this->dataResolver, 'resolveFigureLabel' ], // из figcaption (1)
				'value'                => ContextSrc::CHILDREN, // кроме figcaption
				'style'                => ContextStyle::NORMAL
			],
			"figcaption" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY
			],
			"main" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"section" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"article" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"nav" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"aside" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"header" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"footer" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"search" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			/** Списки: ul и ol должны содержать только li. **/
			"ol" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'li' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			"ul" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'li' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			/** Список определений: dl содержит dt и dd. **/
			"dl" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'dt', 'dd' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			/** Элементы списка: li может содержать любой контент. **/
			"li" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			/** dt — термин в списке определений, обычно фразовый контент. **/
			"dt" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::STRONG
			],
			/** dd — описание термина, может содержать блочные элементы. **/
			"dd" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			/** Блочная цитата. **/
			"blockquote" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN,
				'style'                => ContextStyle::NORMAL
			],
			/** pre — предварительно форматированный текст, обычно фразовый. **/
			"pre" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::CHILDREN, // ??
				'style'                => ContextStyle::PREFORMATTED
			],
			/** Заголовки и параграфы — фразовый контент. **/
			"p" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"h1" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::TEXT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::STRONG
			],
			"h2" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::TEXT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::STRONG
			],
			"h3" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::TEXT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::STRONG
			],
			"h4" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::TEXT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::STRONG
			],
			"h5" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::TEXT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::STRONG
			],
			"h6" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY
			],
			/** address — контактная информация, может содержать блочные элементы. **/
			"address" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::TEXT,
				'style'                => ContextStyle::ITALIC
			],
			/**
			 * ------------------------------------------------------------------
			 * Текстовые семантические (фразовые)
			 * ------------------------------------------------------------------
			 * Эти теги обычно являются строчными и могут содержать только фразовый контент.
			*/
			/** a — ссылка, прозрачный элемент (childrenType = null), наследует модель содержимого от родителя. **/
			"a" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::TEXT,
				'value'                => ContextSrc::HREF,
				'style'                => ContextStyle::NORMAL
			],
			/** span — универсальный строчный контейнер. **/
			"span" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** Пустые элементы: hr, br, wbr — не имеют потомков. **/
			"hr" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"br" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"wbr" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** Элементы выделения и смыслового форматирования. **/
			"em" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::EMPHASIS
			],
			"strong" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::STRONG
			],
			"small" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"s" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"cite" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::ITALIC
			],
			"q" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"dfn" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::EMPHASIS
			],
			"abbr" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"data" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"time" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"code" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::MONOSCAPED
			],
			"var" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::ITALIC
			],
			"samp" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::MONOSCAPED
			],
			"kbd" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::MONOSCAPED
			],
			"sub" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"sup" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"i" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::ITALIC
			],
			"b" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::BOLD
			],
			"u" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"mark" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"bdi" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"bdo" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** ins и del — вставка/удаление, прозрачные элементы. **/
			"ins" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			"del" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/**
			 * ------------------------------------------------------------------
			 * Табличные элементы
			 * ------------------------------------------------------------------
			 * Таблицы имеют сложную структуру,
			 * поэтому для table используется кастомный валидатор.
			 * Остальные элементы таблицы имеют строгие правила вложенности.
			*/
			/** table — прозрачный элемент, валидируется методом validateTable. **/
			"table" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => [ $this->dataResolver, 'validateTable' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => [ $this->dataResolver, 'resolveTableLabel' ], // caption
				'value'                => ContextSrc::CHILDREN, // ? особый объект
				'style'                => ContextStyle::NORMAL
			],
			/** caption — заголовок таблицы, может содержать фразовый контент. **/
			"caption" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY
			],
			/** colgroup и col — для форматирования колонок. **/
			"colgroup" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'col' ]
			],
			"col" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE
			],
			/** Секции таблицы: thead, tbody, tfoot должны содержать только tr. **/
			"thead" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'tr' ]
			],
			"tbody" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'tr' ]
			],
			"tfoot" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'tr' ]
			],
			/** tr — строка таблицы, содержит td и th. **/
			"tr" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'td', 'th' ]
			],
			/** td и th — ячейки, могут содержать любой контент. **/
			"td" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY
			],
			"th" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY
			],
			/**
			 * ------------------------------------------------------------------
			 * Элементы форм
			 * ------------------------------------------------------------------
			*/
			/** form — контейнер для элементов формы. **/
			"form" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::ACTION,
				'style'                => ContextStyle::NORMAL
			],
			/** fieldset — группировка полей. **/
			"fieldset" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => [ $this->dataResolver, 'resolvefiledsetLabel' ], // из legend (1)
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** legend — заголовок fieldset. **/
			"legend" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY
			],
			/** input — пустой элемент. **/
			"input" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => [ $this->dataResolver, 'resolveInputValue' ],
				'style'                => ContextStyle::NORMAL
			],
			/** textarea — многострочное поле, должно содержать только текст. **/
			"textarea" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => [ '#text' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::TEXT,
				'style'                => ContextStyle::PREWRAP
			],
			/** progress и meter — индикаторы, могут содержать фразовый контент. **/
			"progress" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::VALUE,
				'style'                => ContextStyle::NORMAL
			],
			"meter" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::VALUE,
				'style'                => ContextStyle::NORMAL
			],
			/** button — кнопка, может содержать фразовый контент. **/
			"button" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::VALUE,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** select — выпадающий список, содержит только option и optgroup. **/
			"select" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => [ 'option', 'optgroup' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => [ $this->dataResolver, 'resolveSelectLabel' ], // ?option без value
				'value'                => [ $this->dataResolver, 'resolveSelectValue' ], // из option и optgroup (NN)
				'style'                => ContextStyle::NORMAL
			],
			/** datalist — предопределённый список опций, содержит только option. **/
			"datalist" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => [ 'option' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => [ $this->dataResolver, 'resolveDatalistLabel' ], // input input[attr:list] == datalist[attr:id]
				'value'                => [ $this->dataResolver, 'resolveDatalistValue' ], // из datalist>option (N)
				'style'                => ContextStyle::NORMAL
			],
			/** optgroup — группа опций, содержит только option. **/
			"optgroup" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'option' ]
			],
			/** option — элемент списка. По спецификации должен содержать только текст,
				но для совместимости оставлено true (можно настроить при необходимости). **/
			"option" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => [ '#text' ]
			],
			/** label — подпись к полю. **/
			"label" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** output — результат вычисления. **/
			"output" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/**
			 * ------------------------------------------------------------------
			 * Интерактивные элементы
			 * ------------------------------------------------------------------
			*/
			/** dialog — диалоговое окно, контейнер. **/
			"dialog" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** details — раскрывающийся блок, требует специальной валидации для summary. **/
			"details" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => [ $this->dataResolver, 'validateDetails' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => [ $this->dataResolver, 'resolveImgLabel' ], // из дочернего summary (1) 
				'value'                => ContextSrc::CHILDREN, // кроме summary
				'style'                => ContextStyle::NORMAL
			],
			/** summary — заголовок details, фразовый. **/
			"summary" => [
				'thisContextType'      => ContextType::PHRASE,
				'childrenContextType'  => ContextType::PHRASE,
				'allowedTags'          => ContextPermission::ANY
			],
			/**
			 * ------------------------------------------------------------------
			 * Мультимедиа и встраиваемые
			 * ------------------------------------------------------------------
			*/
			/** img — изображение, пустой элемент. **/
			"img" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::ALT,
				'value'                => ContextSrc::SRC,
				'style'                => ContextStyle::NORMAL
			],
			/** iframe — фрейм, может содержать произвольный контент (запасной). **/
			"iframe" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::SRC,
				'style'                => ContextStyle::NORMAL
			],
			/** embed — пустой встраиваемый элемент. **/
			"embed" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** object — сложный встраиваемый объект, прозрачный, с валидацией. **/
			"object" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => [ $this->dataResolver, 'validateObject' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => [ $this->dataResolver, 'resolveObjectValue' ], // из param (N) [attr:name => attr:value]
				'style'                => ContextStyle::NORMAL
			],
			/** param — параметры для object, пустой. **/
			"param" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::NONE
			],
			/** video и audio — медиаэлементы, прозрачные, с валидацией. **/
			"video" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => [ $this->dataResolver, 'validateVideo' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => [ $this->dataResolver, 'resolveVideoValue' ], // из source (или постер) (N)
				'style'                => ContextStyle::NORMAL
			],
			"audio" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => [ $this->dataResolver, 'validateAudio' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => [ $this->dataResolver, 'resolveVideoValue' ], // из source(N)
				'style'                => ContextStyle::NORMAL
			],
			/** canvas — холст для рисования, может содержать запасной контент. **/
			"canvas" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** svg — масштабируемая векторная графика, прозрачный. **/
			"svg" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => [ $this->dataResolver, 'resolveVideoValue' ], // из source(N)
				'style'                => ContextStyle::NORMAL
			],
			/** picture — контейнер для разных источников изображений. **/
			"picture" => [
				'thisContextType'      => ContextType::CONTAINER,
				'childrenContextType'  => ContextType::CONTAINER,
				'allowedTags'          => [ 'source', 'img' ],
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => [ $this->dataResolver, 'resolvePictureValue' ], // из source(N) или img
				'style'                => ContextStyle::NORMAL
			],
			/** source — источник для video/audio/picture, пустой. **/
			"source" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::INLINE,
				'allowedTags'          => ContextPermission::NONE
			],
			/** track — текстовая дорожка для видео, пустой. **/
			"track" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::NONE
			],
			/** template — шаблон, может содержать любой контент (не отображается). **/
			"template" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** slot — слот для веб-компонентов, прозрачный. **/
			"slot" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/** portal — экспериментальный, прозрачный. **/
			"portal" => [
				'thisContextType'      => ContextType::INLINE,
				'childrenContextType'  => ContextType::TRANSPARENT,
				'allowedTags'          => ContextPermission::ANY,
				'deniedTags'           => ContextPermission::NONE,
				'label'                => ContextSrc::DEFAULT,
				'value'                => ContextSrc::NONE,
				'style'                => ContextStyle::NORMAL
			],
			/**
			 * ------------------------------------------------------------------
			 * Устаревшие теги
			 * ------------------------------------------------------------------
			 * Эти теги не поддерживаются современным HTML и исключены из обработки.
			*/
			"applet" => [
				'thisContextType'      => ContextType::BLOCKED
			],
			"frame" => [
				'thisContextType'      => ContextType::BLOCKED
			],
			"frameset" => [
				'thisContextType'      => ContextType::BLOCKED
			],
			"noembed" => [
				'thisContextType'      => ContextType::BLOCKED
			],
			"noframes" => [
				'thisContextType'      => ContextType::BLOCKED
			],
			"basefont" => [
				'thisContextType'      => ContextType::BLOCKED
			],
		];
		} catch (Exception $e) {
			echo "<pre>";
			print_r($e);
			echo "</pre>";
		}
	}
}