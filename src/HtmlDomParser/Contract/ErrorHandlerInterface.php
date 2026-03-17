<?php

namespace HtmlDomParser\Contract;

/**
 * Интерфейс обработчика ошибок парсинга.
 *
 * Собирает ошибки, классифицирует их и управляет поведением (исключения/сбор).
 */
interface ErrorHandlerInterface
{
    /**
     * Добавляет ошибку в обработчик.
     *
     * @param ErrorElementInterface $error Узел-ошибка.
     * @throws \HtmlDomParserException Если уровень ошибки соответствует настроенному throwOn... и исключение включено.
     */
    public function addError(ErrorElementInterface $error): void;

    /**
     * Возвращает все собранные ошибки.
     *
     * @return ErrorElementInterface[]
     */
    public function getErrors(): array;

    /**
     * Возвращает ошибки указанного уровня серьезности.
     *
     * @param string $severity Одна из констант ErrorConstant::SEVERITY_*.
     * @return ErrorElementInterface[]
     */
    public function getErrorsBySeverity(string $severity): array;

    /**
     * Проверяет наличие любых ошибок.
     *
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * Проверяет наличие фатальных ошибок (уровня ERROR).
     *
     * @return bool
     */
    public function hasFatalErrors(): bool;

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня ERROR.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnError(bool $throw): self;

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня WARNING.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnWarning(bool $throw): self;

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня NOTICE.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnNotice(bool $throw): self;
}