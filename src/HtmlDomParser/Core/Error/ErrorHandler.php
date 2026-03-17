<?php

namespace HtmlDomParser\Core\Error;

use HtmlDomParser\Contract\ErrorHandlerInterface;
use HtmlDomParser\Contract\ErrorElementInterface;
use HtmlDomParser\Exception\HtmlDomParserException;

/**
 * Реализация обработчика ошибок.
 *
 * Собирает ошибки, классифицирует их и управляет поведением (исключения/сбор).
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /** @var ErrorElementInterface[] Список собранных ошибок */
    private array $errors = [];

    /** @var bool Бросать исключение при ошибке уровня ERROR */
    private bool $throwOnError = false;

    /** @var bool Бросать исключение при предупреждении (WARNING) */
    private bool $throwOnWarning = false;

    /** @var bool Бросать исключение при уведомлении (NOTICE) */
    private bool $throwOnNotice = false;

    /**
     * Добавляет ошибку в обработчик.
     *
     * @param ErrorElementInterface $error Узел-ошибка.
     * @throws HtmlDomParserException Если уровень ошибки соответствует настроенному throwOn... и исключение включено.
     */
    public function addError(ErrorElementInterface $error): void
    {
        $this->errors[] = $error;

        $severity = $error->getSeverity();
        if (
            ($severity === ErrorConstant::SEVERITY_ERROR && $this->throwOnError) ||
            ($severity === ErrorConstant::SEVERITY_WARNING && $this->throwOnWarning) ||
            ($severity === ErrorConstant::SEVERITY_NOTICE && $this->throwOnNotice)
        ) {
            throw new HtmlDomParserException($error->getLabel());
        }
    }

    /**
     * Возвращает все собранные ошибки.
     *
     * @return ErrorElementInterface[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Возвращает ошибки указанного уровня серьезности.
     *
     * @param string $severity Одна из констант ErrorConstant::SEVERITY_*.
     * @return ErrorElementInterface[]
     */
    public function getErrorsBySeverity(string $severity): array
    {
        return array_filter($this->errors, fn($e) => $e->getSeverity() === $severity);
    }

    /**
     * Проверяет наличие любых ошибок.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Проверяет наличие фатальных ошибок (уровня ERROR).
     *
     * @return bool
     */
    public function hasFatalErrors(): bool
    {
        foreach ($this->errors as $error) {
            if ($error->getSeverity() === ErrorConstant::SEVERITY_ERROR) {
                return true;
            }
        }
        return false;
    }

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня ERROR.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnError(bool $throw): self
    {
        $this->throwOnError = $throw;
        return $this;
    }

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня WARNING.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnWarning(bool $throw): self
    {
        $this->throwOnWarning = $throw;
        return $this;
    }

    /**
     * Устанавливает, нужно ли выбрасывать исключение при ошибке уровня NOTICE.
     *
     * @param bool $throw
     * @return self
     */
    public function setThrowOnNotice(bool $throw): self
    {
        $this->throwOnNotice = $throw;
        return $this;
    }
}