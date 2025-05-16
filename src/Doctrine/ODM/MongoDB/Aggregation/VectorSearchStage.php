<?php

declare(strict_types=1);

namespace App\Doctrine\ODM\MongoDB\Aggregation;

use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Query\Expr as QueryExpr;

final class VectorSearchStage extends Stage
{
    private ?bool $exact = null;
    /** @var array<string, mixed>|QueryExpr|null  */
    private array|QueryExpr|null $filter = null;
    private ?string $index = null;
    private ?int $limit = null;
    private ?int $numCandidates = null;
    private ?string $path = null;
    /** @var float[]|null  */
    private ?array $queryVector = null;

    /** @return array<string, array<string, mixed>>|null */
    public function getExpression(): ?array
    {
        $stage = [];
        if ($this->exact !== null) {
            $stage['exact'] = $this->exact;
        }

        if ($this->filter !== null) {
            $stage['filter'] = $this->filter instanceof QueryExpr
                ? $this->filter->getQuery()
                : $this->filter;
        }

        if ($this->index !== null) {
            $stage['index'] = $this->index;
        }

        if ($this->limit !== null) {
            $stage['limit'] = $this->limit;
        }

        if ($this->numCandidates !== null) {
            $stage['numCandidates'] = $this->numCandidates;
        }

        if ($this->path !== null) {
            $stage['path'] = $this->path;
        }

        if ($this->queryVector !== null) {
            $stage['queryVector'] = $this->queryVector;
        }

        return ['$vectorSearch' => $stage];
    }

    public function exact(bool $exact = true): self
    {
        $this->exact = $exact;

        return $this;
    }

    /** @param array<string, mixed>|QueryExpr $filter */
    public function filter(array|QueryExpr $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    public function index(string $index): self
    {
        $this->index = $index;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function numCandidates(int $numCandidates): self
    {
        $this->numCandidates = $numCandidates;

        return $this;
    }

    public function path(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /** @param float[] $queryVector */
    public function queryVector(array $queryVector): self
    {
        $this->queryVector = $queryVector;

        return $this;
    }
}
