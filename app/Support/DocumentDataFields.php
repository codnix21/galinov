<?php

namespace App\Support;

use App\Models\Property;
use App\Models\User;
use App\Models\UserPersonalData;
use Illuminate\Http\Request;

/**
 * Схема полей документов: ручной ввод + прикреплённый файл.
 */
class DocumentDataFields
{
    /**
     * @return array<string, array{label: string, type: string, required?: bool, placeholder?: string, cols?: int}>
     */
    public static function fieldsForTip(string $tip): array
    {
        return match ($tip) {
            'egrn', 'egrn_land' => [
                'kadastrovy_nomer' => [
                    'label' => 'Кадастровый номер',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => '38:36:000000:12345',
                ],
                'nomer_vypiski' => [
                    'label' => 'Номер выписки ЕГРН',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Необязательно',
                ],
                'data_vypiski' => [
                    'label' => 'Дата выписки',
                    'type' => 'date',
                    'required' => false,
                ],
                'adres_obekta' => [
                    'label' => 'Адрес объекта (как в выписке)',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Город, улица, дом',
                    'cols' => 2,
                ],
            ],
            'ownership' => [
                'nomer_registracii' => [
                    'label' => 'Номер регистрации права',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'Например: 38-38-01/123/2020-1',
                ],
                'data_registracii' => [
                    'label' => 'Дата регистрации',
                    'type' => 'date',
                    'required' => true,
                ],
                'osnovanie' => [
                    'label' => 'Основание (договор, решение суда…)',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Договор купли-продажи от …',
                    'cols' => 2,
                ],
            ],
            'egrul' => [
                'ogrn' => [
                    'label' => 'ОГРН',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => '13 цифр',
                ],
                'inn_ul' => [
                    'label' => 'ИНН организации',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => '10 цифр',
                ],
                'naimenovanie_yurlica' => [
                    'label' => 'Наименование юрлица',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'ООО «…»',
                    'cols' => 2,
                ],
            ],
            'cadastral' => [
                'kadastrovy_nomer' => [
                    'label' => 'Кадастровый номер',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => '38:36:000000:12345',
                ],
                'data_vypiski' => [
                    'label' => 'Дата кадастрового паспорта / плана',
                    'type' => 'date',
                    'required' => false,
                ],
            ],
            'rent_contract' => [
                'nomer_dogovora' => [
                    'label' => 'Номер договора',
                    'type' => 'text',
                    'required' => true,
                ],
                'data_nachala' => [
                    'label' => 'Дата начала',
                    'type' => 'date',
                    'required' => true,
                ],
                'data_okonchaniya' => [
                    'label' => 'Дата окончания',
                    'type' => 'date',
                    'required' => false,
                ],
            ],
            'inn' => [
                'inn' => [
                    'label' => 'ИНН',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Как в документе',
                ],
                'snils' => [
                    'label' => 'СНИЛС',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Как в документе',
                ],
            ],
            default => [],
        };
    }

    public static function hasFields(string $tip): bool
    {
        return self::fieldsForTip($tip) !== [];
    }

    /** @return array<string, mixed> */
    public static function validationRules(string $tip): array
    {
        $rules = [];
        foreach (self::fieldsForTip($tip) as $key => $meta) {
            $rule = ['nullable', 'string', 'max:255'];
            if (($meta['type'] ?? '') === 'date') {
                $rule = ['nullable', 'date'];
            }
            if (!empty($meta['required'])) {
                $rule[0] = 'required';
            }
            $rules['dannye.'.$key] = $rule;
        }

        return $rules;
    }

    /** @return array<string, string> */
    public static function validationAttributes(string $tip): array
    {
        $attrs = [];
        foreach (self::fieldsForTip($tip) as $key => $meta) {
            $attrs['dannye.'.$key] = $meta['label'];
        }

        return $attrs;
    }

    /**
     * @return array<string, string|null>
     */
    public static function extractFromRequest(Request $request, string $tip): array
    {
        $input = $request->input('dannye', []);
        if (!is_array($input)) {
            return [];
        }

        $out = [];
        foreach (self::fieldsForTip($tip) as $key => $meta) {
            $value = $input[$key] ?? null;
            if ($value === null || $value === '') {
                $out[$key] = null;
                continue;
            }
            $out[$key] = is_string($value) ? trim($value) : (string) $value;
        }

        return $out;
    }

    /**
     * @param  array<string, string|null>|null  $data
     * @return list<array{label: string, value: string}>
     */
    public static function displayLines(string $tip, ?array $data, ?Property $property = null): array
    {
        $data = $data ?? [];
        $lines = [];

        foreach (self::fieldsForTip($tip) as $key => $meta) {
            $value = $data[$key] ?? null;
            if (($value === null || $value === '') && $property !== null) {
                if ($key === 'kadastrovy_nomer' && $property->kadastrovy_nomer) {
                    $value = $property->kadastrovy_nomer;
                }
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (($meta['type'] ?? '') === 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                try {
                    $value = \Carbon\Carbon::parse($value)->format('d.m.Y');
                } catch (\Throwable) {
                }
            }
            $lines[] = ['label' => $meta['label'], 'value' => (string) $value];
        }

        return $lines;
    }

    /**
     * Паспортные данные из профиля (для модерации).
     *
     * @return list<array{label: string, value: string}>
     */
    public static function personalDataLines(?UserPersonalData $pd): array
    {
        if ($pd === null) {
            return [];
        }

        $lines = [];
        if ($pd->pasport_seriya_nomer) {
            $lines[] = ['label' => 'Серия и номер', 'value' => $pd->pasport_seriya_nomer];
        }
        if ($pd->pasport_data_vydachi) {
            $lines[] = ['label' => 'Дата выдачи', 'value' => $pd->pasport_data_vydachi->format('d.m.Y')];
        }
        if ($pd->pasport_kem_vydan) {
            $lines[] = ['label' => 'Кем выдан', 'value' => $pd->pasport_kem_vydan];
        }
        if ($pd->inn) {
            $lines[] = ['label' => 'ИНН', 'value' => $pd->inn];
        }
        if ($pd->snils) {
            $lines[] = ['label' => 'СНИЛС', 'value' => $pd->snils];
        }

        return $lines;
    }

    public static function summaryForComment(string $tip, array $data): string
    {
        $parts = [];
        foreach (self::displayLines($tip, $data) as $line) {
            $parts[] = $line['label'].': '.$line['value'];
        }

        return implode('; ', $parts);
    }
}
