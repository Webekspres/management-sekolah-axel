<?php

namespace App\Support\Import;

use Filament\Actions\Imports\ImportColumn;
use Illuminate\Support\Str;

class ImportColumnFactory
{
    /**
     * @param  array<int, ImportColumnDefinition>  $definitions
     * @return array<int, ImportColumn>
     */
    public static function fromDefinitions(array $definitions): array
    {
        return array_map(
            function (ImportColumnDefinition $definition): ImportColumn {
                $column = ImportColumn::make($definition->name)
                    ->label($definition->label())
                    ->guess($definition->getGuesses());

                if ($definition->required) {
                    $column->requiredMapping();
                }

                $rules = [];

                if ($definition->required) {
                    $rules[] = 'required';
                }

                if (Str::startsWith($definition->hintKey, 'personalia.import.hints.tanggal')) {
                    if (! $definition->required) {
                        $rules[] = 'nullable';
                    }

                    $rules[] = 'date';
                }

                if ($definition->name === 'email') {
                    $rules[] = 'email';
                }

                if ($definition->name === 'password') {
                    $rules[] = 'min:8';
                    $column->sensitive();
                }

                if ($definition->name === 'jenis_kelamin') {
                    $rules[] = 'in:L,P';
                }

                if ($rules !== []) {
                    $column->rules($rules);
                }

                if ($helper = $definition->helperText()) {
                    $column->helperText($helper);
                } elseif ($hint = $definition->formatHint()) {
                    $column->helperText($hint);
                }

                if ($example = $definition->example()) {
                    $column->example($example);
                }

                return $column;
            },
            $definitions,
        );
    }
}
