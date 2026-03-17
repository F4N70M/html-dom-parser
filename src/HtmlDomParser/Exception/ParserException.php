<?php

namespace HtmlDomParser\Exception;

/**
 * Основное исключение библиотеки.
 *
 * Выбрасывается при ошибках парсинга, если соответствующее поведение включено.
 */
class ParserException extends \Exception
{
    // Используем стандартные константы PHP
    const NOTICE     = E_USER_NOTICE;     // 1024
    const WARNING    = E_USER_WARNING;    // 512
    const ERROR      = E_USER_ERROR;      // 256
    const DEPRECATED = E_USER_DEPRECATED; // 16384

    const LABELS = [
        self::NOTICE     => 'NOTICE',
        self::WARNING    => 'WARNING', 
        self::ERROR      => 'ERROR',
        self::DEPRECATED => 'DEPRECATED',
    ];

    const DEFAULT    = self::ERROR;
    
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

    public function getLog() {
        $log = sprintf(
            "<strong>PARSER_%s:</strong> [%s]\n> %s\n",
            self::LABELS[$this->getCode()]??'UNKNOWN',
            get_class($this),
            $this->getMessage(),
        );
        return $log;
    }

    public function print($preformatted = true) {
        if ($preformatted) echo "<pre>";
        echo $this->getLog();
        if ($preformatted) echo "</pre>";
    }
}