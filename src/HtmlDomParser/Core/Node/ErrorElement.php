<?php

namespace HtmlDomParser\Core\Node;

use HtmlDomParser\Contract\ErrorElementInterface;
use HtmlDomParser\Core\Error\ErrorConstant;

/**
 * Узел-ошибка, замещающий проблемный HTML-узел.
 */
class ErrorElement extends Element implements ErrorElementInterface
{
    protected string $severity;
    protected string $errorType;
    protected array $backtrace;
    protected array $originalAttributes;

    public function __construct(
        string $name,
        string $severity,
        string $errorType,
        string $message,
        array $originalAttributes = [],
        array $backtrace = []
    ) {
        parent::__construct($name, $originalAttributes, false, 0);
        $this->severity = $severity;
        $this->errorType = $errorType;
        $this->setLabel($message);
        $this->originalAttributes = $originalAttributes;
        $this->backtrace = $backtrace ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getBacktrace(): array
    {
        return $this->backtrace;
    }

    public function getOriginalAttributes(): array
    {
        return $this->originalAttributes;
    }

    public function isFatal(): bool
    {
        return $this->severity === ErrorConstant::SEVERITY_ERROR;
    }
}