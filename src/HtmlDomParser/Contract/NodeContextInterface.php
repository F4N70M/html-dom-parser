<?php

namespace HtmlDomParser\Contract;

use DOMNode;

/**
 * Интерфейс временного контекста узла в процессе парсинга.
 */
interface NodeContextInterface
{
    /**
     * Конструктор принимает DOM-узел и тип контекста родителя.
     *
     * @param DOMNode $domNode
     * @param int     $parentContextType Тип контекста родителя (одна из констант ContextTypeConstant).
     */
    public function __construct(DOMNode $domNode, ContextDataResolverInterface $dataResolver, array $config);
    // public function __construct(DOMNode $domNode, int $parentContextType, array $rules);
    // public function __construct(DOMNode $domNode, int $parentContextType, ContextDataResolverInterface $dataResolver);

    /**
     * Возвращает оригинальный DOM-узел.
     *
     * @return DOMNode
     */
    public function getNode(): DOMNode;

    /**
     * Возвращает имя тега (или '#text', '#comment' и т.д.).
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Возвращает родительский контекст, если есть.
     *
     * @return NodeContextInterface|null
     */
    public function getParent(): ?NodeContextInterface;

    /**
     * Возвращает коллекцию уже обработанных дочерних элементов.
     *
     * @return ElementListInterface
     */
    public function getChildren(): ElementListInterface;

    /**
     * Устанавливает коллекцию дочерних элементов.
     *
     * @param ElementListInterface $children
     */
    public function setChildren(ElementListInterface $children): void;

    /**
     * Возвращает данные узла (лениво извлекая при необходимости).
     *
     * @return mixed
     */
    public function getData(): mixed;

    /**
     * Принудительно устанавливает данные узла.
     *
     * @param mixed $data
     */
    public function setData($data): void;

    /**
     * Проверяет, является ли узел void (не может иметь детей).
     *
     * @return bool
     */
    public function isVoid(): bool;

    /**
     * Проверяет, будет ли узел включён в итоговое дерево.
     *
     * @return bool
     */
    public function isInclude(): bool;

    /**
     * Проверяет, является ли узел потоковым контейнером (блочным).
     *
     * @return bool
     */
    public function isContainer(): bool;

    /**
     * Проверяет, является ли узел фразовым контейнером.
     *
     * @return bool
     */
    public function isPhrase(): bool;

    /**
     * Проверяет, является ли узел строчным элементом.
     *
     * @return bool
     */
    public function isInline(): bool;

    /**
     * Проверяет, является ли элемент "прозрачным" (содержимое наследует контекст родителя).
     *
     * @return bool
     */
    public function isTransparent(): bool;

    /**
     * Проверяет, является ли узел "сырым текстом" (например, script, style).
     *
     * @return bool
     */
    public function isRawText(): bool;

    /**
     * Проверяет, нужно ли экранировать HTML-сущности внутри узла.
     *
     * @return bool
     */
    public function isEscapable(): bool;

    /**
     * Возвращает список тегов, разрешённых в качестве прямых потомков.
     *
     * @return array|mixed
     */
    public function getAllowedTags(): mixed;

    /**
     * Проверяет, все ли дочерние элементы являются строчными.
     *
     * @return bool
     */
    public function allChildrenIsInline(): bool;
}