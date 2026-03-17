<?php

namespace HtmlDomParser\Core\Node;

use HtmlDomParser\Contract\ElementInterface;
use HtmlDomParser\Contract\RichTextFragmentInterface;
use HtmlDomParser\Contract\RichTextFragmentListInterface;
use HtmlDomParser\Contract\ElementListInterface;
use HtmlDomParser\Core\Collection\ElementList;
use HtmlDomParser\Core\Collection\FragmentList;

/**
 * Элемент HTML (тег).
 */
class Element extends Node implements ElementInterface
{
    protected mixed $data = null;
    protected string $label = '';
    protected RichTextFragmentListInterface $fragments;
    protected ElementListInterface $children;
    protected bool $isInline;
    protected int $contextType;

    public function __construct(string $name, array $attributes = [], bool $isInline = false, int $contextType = 0)
    {
        parent::__construct($name, $attributes);
        $this->isInline = $isInline;
        $this->contextType = $contextType;
        $this->fragments = new FragmentList();
        $this->children = new ElementList();
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData($value): void
    {
        $this->data = $value;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getFragments(): RichTextFragmentListInterface
    {
        return $this->fragments;
    }

    public function addFragment(RichTextFragmentInterface $fragment): void
    {
        $this->fragments->push($fragment);
    }

    public function isInline(): bool
    {
        return $this->isInline;
    }

    public function getChildren(): ElementListInterface
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return $this->children->count() > 0;
    }

    public function getContextType(): int
    {
        return $this->contextType;
    }
}