<?php

namespace App\Services\Supabase;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SupabaseQuery
{
    protected string $table;

    protected string $select = '*';

    protected array $params = [];

    protected array $headers = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function select(mixed ...$columns): self
    {
        $flattened = [];
        foreach ($columns as $col) {
            if (is_array($col)) {
                $flattened = array_merge($flattened, $col);
            } else {
                $flattened[] = $col;
            }
        }
        $this->select = implode(',', $flattened);

        return $this;
    }

    private function formatValue(mixed $val): string
    {
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }

        return (string) $val;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null) {
            $val = $this->formatValue($operatorOrValue);
            $this->params[$column] = 'eq.'.$val;
        } else {
            $op = match (strtolower((string) $operatorOrValue)) {
                '=' => 'eq',
                '!=' => 'neq',
                '>' => 'gt',
                '>=' => 'gte',
                '<' => 'lt',
                '<=' => 'lte',
                'like' => 'like',
                'ilike' => 'ilike',
                default => 'eq'
            };
            $this->params[$column] = $op.'.'.$this->formatValue($value);
        }

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->params[$column] = 'is.null';

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->params[$column] = 'not.is.null';

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->params[$column] = 'in.('.implode(',', $values).')';

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->params['order'] = $column.'.'.strtolower($direction);

        return $this;
    }

    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'desc');
    }

    public function orderByRaw(string $sql): self
    {
        return $this;
    }

    public function lockForUpdate(): self
    {
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->params['limit'] = $limit;

        return $this;
    }

    private function getClient(): array
    {
        $url = rtrim((string) env('SUPABASE_URL'), '/');
        $key = (string) env('SUPABASE_KEY');

        return [$url, Http::withoutVerifying()->withHeaders([
            'apikey' => $key,
            'Authorization' => 'Bearer '.$key,
            'Content-Type' => 'application/json',
            ...$this->headers,
        ])];
    }

    public function get(): Collection
    {
        [$url, $http] = $this->getClient();
        $params = array_merge(['select' => $this->select], $this->params);

        $res = $http->get("{$url}/rest/v1/{$this->table}", $params);

        if (! $res->successful()) {
            return collect();
        }

        return collect($res->json())->map(fn ($item) => is_array($item) ? (object) $item : $item);
    }

    public function first(): ?object
    {
        $this->limit(1);
        $collection = $this->get();

        return $collection->first();
    }

    public function exists(): bool
    {
        return $this->first() !== null;
    }

    public function value(string $column): mixed
    {
        $first = $this->first();

        return $first?->{$column} ?? null;
    }

    public function count(): int
    {
        return $this->get()->count();
    }

    public function insert(array $data): bool
    {
        [$url, $http] = $this->getClient();
        $res = $http->post("{$url}/rest/v1/{$this->table}", $data);

        return $res->successful();
    }

    public function insertGetId(array $data, string $idColumn = 'id'): int|string|null
    {
        $this->headers['Prefer'] = 'return=representation';
        [$url, $http] = $this->getClient();
        $res = $http->post("{$url}/rest/v1/{$this->table}", $data);

        if ($res->successful() && ! empty($res->json())) {
            $inserted = $res->json()[0] ?? null;

            return $inserted[$idColumn] ?? null;
        }

        return null;
    }

    public function upsert(array $values, array|string $uniqueBy, ?array $update = null): int
    {
        $conflictCols = is_array($uniqueBy) ? implode(',', $uniqueBy) : $uniqueBy;
        $this->headers['Prefer'] = 'resolution=merge-duplicates,return=representation';
        [$url, $http] = $this->getClient();
        $res = $http->withQueryParameters(['on_conflict' => $conflictCols])
            ->post("{$url}/rest/v1/{$this->table}", $values);

        if (! $res->successful()) {
            return 0;
        }

        $json = $res->json();

        return is_array($json) ? count($json) : 1;
    }

    public function update(array $data): int
    {
        $this->headers['Prefer'] = 'return=representation';
        [$url, $http] = $this->getClient();
        $res = $http->withQueryParameters($this->params)
            ->patch("{$url}/rest/v1/{$this->table}", $data);

        if (! $res->successful()) {
            return 0;
        }

        $json = $res->json();

        return is_array($json) ? count($json) : 0;
    }

    public function delete(): int
    {
        $this->headers['Prefer'] = 'return=representation';
        [$url, $http] = $this->getClient();
        $res = $http->withQueryParameters($this->params)
            ->delete("{$url}/rest/v1/{$this->table}");

        if (! $res->successful()) {
            return 0;
        }

        $json = $res->json();

        return is_array($json) ? count($json) : 0;
    }
}
