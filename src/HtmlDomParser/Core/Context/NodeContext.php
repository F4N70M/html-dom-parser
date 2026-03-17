<?php

namespace HtmlDomParser\Core\Context;

use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Contract\ElementListInterface;
use HtmlDomParser\Contract\ContextDataResolverRulesInterface;
use HtmlDomParser\Contract\ContextDataResolverInterface;
use HtmlDomParser\Core\Collection\ElementList;
use HtmlDomParser\Core\Context\Constans\ContextType;
use DOMNode;

/**
 * Контекст узла в процессе парсинга HTML.
 * 
 * Содержит DOM-узел, его конфигурацию (правила извлечения данных,
 * типы контекста, разрешённые потомки) и методы для получения
 * информации об узле: метки (label), значения (value), имени,
 * дочерних элементов и различных флагов состояния.
 * 
 * @package HtmlDomParser\Core\Context
 */
class NodeContext implements NodeContextInterface
{

    /** @var ContextDataResolverInterface Объект для извлечения данных из узла **/
    protected ContextDataResolverInterface $dataResolver;
    /** @var DOMNode Оригинальный DOM-узел **/
    protected DOMNode $node;
    /** @var array Конфигурация узла из правил тегов **/
    protected array $config;
    /** @var int Тип контекста текущего узла для родителя **/
    protected int $thisContexrType;
    /** @var int Тип контекста текущего узла для потомков **/
    protected int $childrenContexrType;
    /** @var mixed Разрешённые теги прямых потомков **/
    protected mixed $allowedTags;
    /** @var string Имя узла (например, 'div', '#text', '#comment') **/
    protected string $name;
    /** @var array Ассоциативный массив атрибутов (для элементов) **/
    protected array $attributes;
    /** @var string Текстовая метка узла **/
    protected string $label;
    /** @var array|null Массив сущностей форматирования после схлопывания **/
    protected ?array $entities;
    /** @var mixed Основное содержимое узла, извлечённое DataResolver **/
    protected mixed $data;
    /** @var bool Флаг "сырого" текста (true для pre, style, script) **/
    protected bool $isRawText;
    /** @var bool Всегда true (преобразование сущностей) **/
    protected bool $isEscapable;
    /** @var ElementListInterface|null Коллекция дочерних элементов **/
    protected ?ElementListInterface $children;
    /** @var bool Флаг, было ли уже извлечено основное содержимое **/
    protected bool $dataResolved = false;
    /** @var NodeContextInterface|null Родительский контекст **/
    protected ?NodeContextInterface $parent = null;

    /**
     * Конструктор.
     *
     * @param DOMNode $node Оригинальный DOM-узел
     * @param ContextDataResolverInterface $dataResolver Объект для извлечения данных
     * @param array $config Конфигурация узла из правил тегов
     */
    public function __construct(
        DOMNode $node,
        // ?int $parentContextType,
        ContextDataResolverInterface $dataResolver,
        array $config
    ) {
        $this->node = $node;
        // $this->parentContextType = $parentContextType;
        $this->dataResolver = $dataResolver;
        $this->config = $config;

        $this->children = new ElementList();

        $nodeName = $this->getName();
        // $this->rules = $ContextDataResolverRules->get($this->node->nodeName);
    }

    /**
     * Возвращает оригинальный DOM-узел.
     *
     * @return DOMNode
     */
    public function getNode(): DOMNode {
        return $this->node;
    }

    /**
     * Возвращает метку (label) узла, извлечённую согласно правилу из конфигурации.
     *
     * @return string
     */
    public function getLabel(): string {
        return $this->dataResolver->resolveLabel($this->node, $this->config['label']);
    }

    /**
     * Возвращает значение (value) узла, извлечённое согласно правилу из конфигурации.
     *
     * @return mixed
     */
    public function getValue(): mixed {
        return $this->dataResolver->resolveValue($this->node, $this->config['value']);
    }

    /**
     * Возвращает имя узла в нижнем регистре.
     *
     * @return string
     */
    public function getName(): string {
        $nodeName = strtolower($this->node->nodeName);
        return $nodeName;
    }

    /**
     * Возвращает родительский контекст.
     *
     * @return NodeContextInterface|null
     */
    public function getParent(): ?NodeContextInterface {
        return $this->parent;
    }

    /**
     * Устанавливает родительский контекст.
     *
     * @param NodeContextInterface|null $parent
     * @return void
     */
    public function setParent(?NodeContextInterface $parent): void {
        $this->parent = $parent;
    }

    /**
     * Возвращает коллекцию дочерних элементов.
     *
     * @return ElementListInterface
     */
    public function getChildren(): ElementListInterface {
        return $this->children;
    }

    /**
     * Устанавливает коллекцию дочерних элементов.
     *
     * @param ElementListInterface $children
     * @return void
     */
    public function setChildren(ElementListInterface $children): void {
        $this->children = $children;
    }

    /**
     * Возвращает основное содержимое узла.
     * При первом вызове извлекает данные через dataResolver.
     *
     * @return mixed
     */
    public function getData(): mixed {
        if (!$this->dataResolved) {
            $this->data = $this->dataResolver->resolve($this->node);
            $this->dataResolved = true;
        }
        return $this->data;
    }

    /**
     * Устанавливает основное содержимое узла.
     *
     * @param mixed $data
     * @return void
     */
    public function setData($data): void {
        $this->data = $data;
        $this->dataResolved = true;
    }

    /**
     * Проверяет, является ли узел пустым (не имеет потомков).
     *
     * @return bool
     */
    public function isVoid(): bool {
        return $this->config['childrenContextType'] === ContextType::VOID;
    }

    /**
     * Проверяет, должен ли узел быть включён в итоговую структуру.
     *
     * @return bool
     */
    public function isInclude(): bool {
        return $this->config['thisContextType'] !== ContextType::SKIP;
    }

    /**
     * Проверяет, является ли узел контейнером (может содержать блочные элементы).
     *
     * @return bool
     */
    public function isContainer(): bool {
        return $this->rules['thisContextType'] === ContextType::CONTAINER;
    }

    /**
     * Проверяет, является ли узел фразовым (содержит только фразовый контент).
     *
     * @return bool
     */
    public function isPhrase(): bool {
        return $this->rules['thisContextType'] === ContextType::PHRASE;
    }

    /**
     * Проверяет, является ли узел строчным (inline).
     *
     * @return bool
     */
    public function isInline(): bool {
        return $this->rules['thisContextType'] === ContextType::INLINE;
    }

    /**
     * Проверяет, является ли узел прозрачным (наследует контекст от родителя).
     *
     * @return bool
     */
    public function isTransparent(): bool {
        // Пока не реализовано, всегда false
        return false;
    }

    /**
     * Проверяет, содержит ли узел "сырой" текст (без обработки HTML).
     *
     * @return bool
     */
    public function isRawText(): bool {
        return $this->rules['isRawText'] ?? false;
    }

    /**
     * Проверяет, нужно ли экранировать содержимое узла.
     *
     * @return bool
     */
    public function isEscapable(): bool {
        return $this->rules['isEscapable'] ?? true;
    }

    /**
     * Возвращает список разрешённых тегов для прямых потомков.
     *
     * @return mixed
     */
    public function getAllowedTags(): mixed {
        return $this->rules['allowedTags'] ?? true;
    }

    /**
     * Проверяет, что все дочерние элементы являются строчными (inline).
     * Используется для форматированного схлопывания.
     *
     * @return bool
     */
    public function allChildrenIsInline(): bool {
        /** TODO: реализовать роверка всех дочерних элементов (для форматированного схлопывания) **/
        return false;
    }

    /**
     * Возвращает тип контекста для дочерних элементов.
     *
     * @return int
     */
    public function getChildrenContextType(): int {
        return $this->rules['childrenContextType'] ?? ContextType::CONTAINER;
    }
}