<?php

namespace App\Contracts;

interface Searchable
{
    /**
     * The type key used to namespace this model's points in the search
     * index (e.g. as a payload field and for building a stable point ID).
     */
    public function searchableType(): string;

    /**
     * @return array{0: ?string, 1: ?string} Title and body text to embed.
     *                                        A null title means "nothing to index".
     */
    public function searchableContent(): array;
}
