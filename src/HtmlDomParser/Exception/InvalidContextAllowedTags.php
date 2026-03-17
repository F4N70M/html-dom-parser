<?php

namespace HtmlDomParser\Exception;

/**
 * Исключение, выбрасываемое при попытке подписать обработчик с неверной сигнатурой.
 */
class InvalidContextAllowedTags extends ParserException
{
    const DEFAULT = self::WARNING;

    public function __construct(
        string $message = "", 
        int $level = null,  // по умолчанию ошибка
        ?\Throwable $previous = null
    ) {
        if ($level === null) $level = self::DEFAULT;
        $this->code = $level; // переиспользуем code для хранения уровня
        parent::__construct($message, $level, $previous);
        // var_dump($this->getLevelName());
    }
}