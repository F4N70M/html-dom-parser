<?php

namespace HtmlDomParser\Exception;

/**
 * Исключение, выбрасываемое при попытке подписать обработчик с неверной сигнатурой.
 */
class InvalidEventListenerException extends \Exception
{
    const DEFAULT    = self::ERROR;
}