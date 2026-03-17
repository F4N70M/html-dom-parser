<?php

namespace HtmlDomParser;

use HtmlDomParser\Contract\ParserInterface;
use HtmlDomParser\Contract\DocumentInterface;
use HtmlDomParser\Contract\ErrorHandlerInterface;
use HtmlDomParser\Contract\ModuleManagerInterface;
use HtmlDomParser\Contract\ContextConverterInterface;
use HtmlDomParser\Contract\EventDispatcherInterface;
use HtmlDomParser\Contract\InlineCollapserInterface;
use HtmlDomParser\Contract\NodeContextInterface;
use HtmlDomParser\Contract\ElementInterface;
use HtmlDomParser\Contract\ElementListInterface;
use HtmlDomParser\Core\Error\ErrorHandler;
use HtmlDomParser\Core\ModuleManager;
use HtmlDomParser\Core\Context\ContextConverter;
use HtmlDomParser\Core\Context\ContextDataResolverRules;
use HtmlDomParser\Core\Context\ContextDataResolver;
use HtmlDomParser\Core\Context\Constans\ContextType;
use HtmlDomParser\Core\Event\EventDispatcher;
use HtmlDomParser\Core\Utilite\InlineCollapser;
use HtmlDomParser\Core\Node\Document;
use HtmlDomParser\Core\Collection\ElementList;
use HtmlDomParser\Core\Context\ContextTypeConstant;
use HtmlDomParser\Core\Event\EventConstant;
use HtmlDomParser\Exception\ParserException;

/**
 * Главный класс парсера HTML.
 *
 * Реализует интерфейс ParserInterface и управляет процессом парсинга.
 */
class Parser implements ParserInterface
{
    /** @var string Исходная HTML-строка */
    private string $html;

    /** @var ErrorHandlerInterface Обработчик ошибок */
    private ErrorHandlerInterface $errorHandler;

    /** @var ModuleManagerInterface Менеджер модулей */
    private ModuleManagerInterface $moduleManager;

    /** @var ContextConverterInterface Конвертер контекстов */
    private ContextConverterInterface $contextConverter;

    /** @var EventDispatcherInterface Диспетчер событий */
    private EventDispatcherInterface $eventDispatcher;

    /** @var InlineCollapserInterface Сервис схлопывания */
    private InlineCollapserInterface $inlineCollapser;

    /** @var bool Флаг сохранения комментариев */
    private bool $keepComments = false;

    /**
     * @param string $html Исходный HTML.
     */
    public function __construct(string $html) {
        $this->html = $html;

        $this->errorHandler = new ErrorHandler();
        $this->moduleManager = new ModuleManager();
        $this->eventDispatcher = new EventDispatcher();
        $this->inlineCollapser = new InlineCollapser();
        $this->contextConverter = new ContextConverter();
    }

    /**
     * Запускает процесс парсинга HTML.
     *
     * @param bool $keepComments Сохранять ли HTML-комментарии в итоговом дереве.
     * @return DocumentInterface Корневой объект документа с построенным деревом.
     * @throws HtmlDomParserException Если загрузка HTML не удалась.
     */
    public function parse(bool $keepComments = false): DocumentInterface
    {
        try {

            $this->keepComments = $keepComments;

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $this->configureDom($dom);

            // Конвертируем UTF-8 в HTML-сущности перед загрузкой
            // $encodedHtml = mb_convert_encoding($this->html, 'HTML-ENTITIES', 'UTF-8');
            // $preparedHtml = $this->html;
            $preparedHtml = $this->prepareHtml($this->html);

            $useErrors = libxml_use_internal_errors(true);
            // $loaded = $dom->loadHTML($this->html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $loaded = $dom->loadHTML($preparedHtml);
            $errors = libxml_get_errors();
            libxml_use_internal_errors($useErrors);

            $this->handleLibXmlErrors($errors);

            if (!$loaded) {
                throw new ParserException('Failed to load HTML');
            }

            $document = new Document();

            foreach ($dom->childNodes as $childNode) {
                // Пропускаем DOCTYPE
                if ($childNode->nodeType === XML_DOCUMENT_TYPE_NODE) {
                    // TODOO: обработать doctype
                    continue;
                }
                $element = $this->parseNode($childNode, ContextType::DOCUMENT);
                if ($element !== null) {
                    $document->getChildren()->push($element);
                }
            }
        } catch (ParserException $e) {
            $e->print();
        }

        return $document;
    }

    /**
     * Возвращает обработчик ошибок для настройки и анализа.
     *
     * @return ErrorHandlerInterface Обработчик ошибок.
     */
    public function getErrorHandler(): ErrorHandlerInterface
    {
        return $this->errorHandler;
    }

    /**
     * Возвращает менеджер модулей для доступа к загруженным модулям.
     *
     * @return ModuleManagerInterface Менеджер модулей.
     */
    public function getModuleManager(): ModuleManagerInterface
    {
        return $this->moduleManager;
    }

    /**
     * Настраивает DOMDocument перед загрузкой.
     *
     * @param \DOMDocument $dom Документ для настройки.
     */
    protected function configureDom(\DOMDocument $dom): void
    {
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->recover = true;
        // Устанавливаем кодировку UTF-8
        $dom->encoding = 'UTF-8';
    }

    protected function prepareHtml(string $html): string
    {
        // Проверяем, есть ли уже meta-тег с кодировкой
        if (stripos($html, '<meta') === false || 
            stripos($html, 'charset=utf-8') === false) {
            // Добавляем meta-тег в начало head или перед первым тегом
            if (stripos($html, '<head>') !== false) {
                $html = str_replace('<head>', '<head><meta charset="UTF-8">', $html);
            } else {
                // Если нет head, добавляем meta перед первым тегом
                $html = '<meta charset="UTF-8">' . $html;
            }
        }
        return $html;
    }

    /**
     * Обрабатывает ошибки libxml, преобразуя их в узлы-ошибки.
     *
     * @param array $errors Массив ошибок libxml.
     */
    protected function handleLibXmlErrors(array $errors): void
    {
        // TODO: реализовать преобразование ошибок libxml в ErrorElementInterface
    }

    /**
     * Обрабатывает DOM-узел, создаёт контекст, рекурсивно обходит дочерние узлы,
     * выполняет схлопывание строчных элементов и возвращает готовый элемент.
     */
	private function parseNode(\DOMNode $domNode, ?int $parentContextType, bool $keepComments=false): ?ElementInterface
	{
        // Пропустить 
        if ($parentContextType === ContextType::VOID)
            return null;
	    // 1. Пропуск комментариев
	    if ($domNode instanceof \DOMComment && !$keepComments) {
	        return null;
	    }

	    // 2. Создание контекста
	    $context = $this->contextConverter->nodeToContext($domNode, $parentContextType);
        if (!$context) return null;

	    // 3. Событие pre-node
	    $context = $this->eventDispatcher->dispatch('pre-node', $context);

	    // 4. Обработка детей (рекурсия) – используем $context->getNode()
	    if (!$context->isVoid() && $context->getNode()->hasChildNodes()) {
	        $children = new ElementList();
	        // Предполагаем, что контекст хранит тип контекста для дочерних узлов
	        $childContextType = $context->getChildrenContextType();

	        foreach ($context->getNode()->childNodes as $childNode) {
	            $childElement = $this->parseNode(
	                $childNode,
	                $childContextType,
	                $keepComments
	            );
	            if ($childElement !== null) {
	                $children->push($childElement);
	            }
	        }
	        $context->setChildren($children);
	    }

	    // 5. Проверка необходимости схлопывания
	    if ($context->allChildrenIsInline()) {
	        // 6. Событие pre-inline-collapse
	        $context = $this->eventDispatcher->dispatch('pre-inline-collapse', $context);

	        // 7. Схлопывание
	        $context = $this->inlineCollapser->collapse($context);

	        // 8. Событие post-inline-collapse
	        $context = $this->eventDispatcher->dispatch('post-inline-collapse', $context);
	    }

	    // 9. Событие post-node
	    $context = $this->eventDispatcher->dispatch('post-node', $context);

	    // 10. Преобразование в элемент
	    return $this->contextConverter->contextToElement($context);
	}

    /**
     * Обходит коллекцию DOM-узлов, рекурсивно обрабатывает разрешённые и возвращает список элементов.
     *
     * @param \DOMNodeList $children Коллекция дочерних DOM-узлов.
     * @param NodeContextInterface $context Контекст родительского узла (для проверки разрешённых тегов).
     * @return ElementListInterface Список обработанных дочерних элементов.
     */
    private function getChildrenElementList(\DOMNodeList $children, NodeContextInterface $context): ElementListInterface
    {
        $elementList = new ElementList();

        $allowedNodeTypes = [
            XML_ELEMENT_NODE,
            XML_TEXT_NODE,
        ];

        if ($this->keepComments) {
            $allowedNodeTypes[] = XML_COMMENT_NODE;
        }

        foreach ($children as $childNode) {
            if (!in_array($childNode->nodeType, $allowedNodeTypes, true)) {
                continue;
            }

            if ($childNode->nodeType === XML_ELEMENT_NODE) {
                $allowedTags = $context->getAllowedTags();
                if (!in_array('*', $allowedTags, true) && !in_array($childNode->nodeName, $allowedTags, true)) {
                    // TODO: создать узел-ошибку
                    continue;
                }
            }

            $childElement = $this->processNode($childNode, $context);
            if ($childElement !== null) {
                $elementList->push($childElement);
            }
        }

        return $elementList;
    }
}