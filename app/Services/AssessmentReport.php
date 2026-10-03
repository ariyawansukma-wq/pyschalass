<?php

namespace App\Services;

/**
 * Value Object representing a compiled Assessment Report.
 * Consolidates report preparation data and encapsulates the presentation seam.
 */
class AssessmentReport
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->resolveArray($this->data);
    }

    /**
     * Recursively resolve nested AssessmentReport instances and arrays.
     */
    private function resolveArray(array $array): array
    {
        $resolved = [];
        foreach ($array as $key => $value) {
            if ($value instanceof self) {
                $resolved[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $resolved[$key] = $this->resolveArray($value);
            } else {
                $resolved[$key] = $value;
            }
        }
        return $resolved;
    }

    /**
     * Set a specific value in the report data.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a specific value from the report data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
