<?php

namespace App\Support\Import;

readonly class ImportColumnDefinition
{
    /**
     * @param  array<int, string>  $guesses
     */
    public function __construct(
        public string $name,
        public string $labelKey,
        public string $hintKey,
        public ?string $exampleKey = null,
        public bool $required = false,
        public array $guesses = [],
        public ?string $helperTextKey = null,
    ) {}

    public function label(): string
    {
        return __("personalia.import.columns.{$this->name}");
    }

    public function formatHint(): string
    {
        return __($this->hintKey);
    }

    public function example(): ?string
    {
        if ($this->exampleKey === null) {
            return null;
        }

        $value = __("personalia.import.examples.{$this->name}");

        return $value === '' ? null : $value;
    }

    public function helperText(): ?string
    {
        if ($this->helperTextKey === null) {
            return null;
        }

        return __($this->helperTextKey);
    }

    /**
     * @return array<int, string>
     */
    public function getGuesses(): array
    {
        $fromLabel = [
            str($this->label())->lower()->toString(),
            str($this->name)->replace('_', ' ')->lower()->toString(),
        ];

        return array_values(array_unique([...$this->guesses, ...$fromLabel]));
    }
}
