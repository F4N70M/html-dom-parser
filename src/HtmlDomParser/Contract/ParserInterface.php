<?php

namespace HtmlDomParser\Contract;

use HtmlDomParser\Exception\HtmlDomParserException;

/**
 * Главный интерфейс парсера HTML.
 *
 * Отвечает за инициализацию процесса парсинга и возврат готового документа.
 */
interface ParserInterface
{
    /**
     * Конструктор принимает исходный HTML-код.
     *
     * @param string $html Исходная HTML-строка.
     */
    public function __construct(string $html);

    /**
     * Запускает процесс парсинга.
     *
     * @param bool $keepComments Сохранять ли HTML-комментарии в дереве.
     * @return DocumentInterface Корневой объект документа с построенным деревом.
     * @throws HtmlDomParserException При фатальной ошибке, если включено соответствующее поведение.
     */
    public function parse(bool $keepComments = false): DocumentInterface;

    /**
     * Возвращает обработчик ошибок для настройки и анализа.
     *
     * @return ErrorHandlerInterface
     */
    public function getErrorHandler(): ErrorHandlerInterface;

    /**
     * Возвращает менеджер модулей для доступа к загруженным модулям.
     *
     * @return ModuleManagerInterface
     */
    public function getModuleManager(): ModuleManagerInterface;
}