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

    /** @param $fn */
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

        if (!is_numeric(filter_var($value, FILTER_VALIDATE_FLOAT))) {
            $this->add_error("must be a number");
        }

        return $this;
    }

    public function is_int(): self
    {
        $value = $this->get_value();

        if (!is_int(filter_var($value, FILTER_VALIDATE_INT))) {
            $this->add_error("must be an int");
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
        if ($this->currentField && isset($this->data[$this->currentField])) {
            return $this->data[$this->currentField];
        }
        return null;
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

    public function get_errors_as_string(): string
    {
        $messages = [];
        foreach ($this->errors as $key => $value) {
            array_push($messages, $key . ": ");
            foreach ($value as $error) {
                array_push($messages, "\t" . $error);
            }
        }
        return implode("\n", $messages);
    }
}

?>
