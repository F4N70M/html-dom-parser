<?php

namespace HtmlDomParser\Core\Node;

use HtmlDomParser\Contract\RichTextFragmentInterface;

/**
 * Фрагмент форматированного текста.
 */
class Fragment implements RichTextFragmentInterface
{
    protected string $type;
    protected int $start;
    protected int $end;
    protected array $attributes;

    public function __construct(string $type, int $start, int $end, array $attributes = [])
    {
        $this->type = $type;
        $this->start = $start;
        $this->end = $end;
        $this->attributes = $attributes;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'start' => $this->start,
            'end' => $this->end,
            'attributes' => $this->attributes,
        ];
    }
}