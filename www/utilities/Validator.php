<?php

class Validator
{
    private array $data;
    private array $errors = [];
    private ?string $currentField = null;

    /** @param array $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function field(string $fieldName): self
    {
        $this->currentField = $fieldName;
        return $this;
    }

    public function is_required(): self
    {
        $value = $this->get_value();

        if ($value === null || $value === "") {
            $this->add_error("is required");
        }

        return $this;
    }

    /** @@param $fn */
    public function custom(callable $fn, string $message): self
    {
        if (!$fn($this->get_value())) {
            $this->add_error($message);
        }

        return $this;
    }

    public function is_email(): self
    {
        $value = $this->get_value();

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->add_error("must be a valid email address");
        }

        return $this;
    }

    public function is_numeric(): self
    {
        $value = $this->get_value();

        if (!empty($value) && !is_numeric($value)) {
            $this->add_error("must be a number");
        }

        return $this;
    }

    public function has_min_length(int $min): self
    {
        $value = $this->get_value();

        if (!empty($value) && strlen((string) $value) < $min) {
            $this->add_error("must be at least {$min} characters long");
        }

        return $this;
    }

    public function has_max_length(int $max): self
    {
        $value = $this->get_value();

        if (!empty($value) && strlen((string) $value) > $max) {
            $this->add_error("must be less than {$max} characters long");
        }

        return $this;
    }

    private function get_value(): mixed
    {
        if ($this->currentField === null) {
            return null;
        }
        return $this->data[$this->currentField] ?? null;
    }

    private function add_error(string $message): void
    {
        if ($this->currentField !== null) {
            $this->errors[$this->currentField][] = $message;
        }
    }

    public function is_valid(): bool
    {
        return empty($this->errors);
    }

    public function get_errors(): array
    {
        return $this->errors;
    }
}

?>
