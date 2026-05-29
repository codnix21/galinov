<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Параметры частного дома (ТЗ: только для tip = house).
 */
class PropertyHouseAttributes
{
  public const TIP_KIRPICH = 'kirpichny';

  public const TIP_PANEL = 'panelny';

  public const TIP_WOOD = 'derevyanny';

  public const TIP_MONOLITH = 'monolitny';

  public const TIP_FRAME = 'karkasny';

  /** @var array<string, string> */
  public const TIP_DOMA_LABELS = [
    self::TIP_KIRPICH => 'Кирпичный',
    self::TIP_PANEL => 'Панельный',
    self::TIP_WOOD => 'Деревянный',
    self::TIP_MONOLITH => 'Монолитный',
    self::TIP_FRAME => 'Каркасный',
  ];

  /** @var list<string> */
  public const BOOLEAN_FIELDS = [
    'est_tsokol',
    'garazh',
    'parking',
    'internet',
    'otoplenie',
    'kanalizatsiya',
    'vodosnabzhenie',
    'gaz',
    'banya',
    'bassein',
    'okhrana',
    'zabor',
  ];

  /** @var array<string, string> */
  public const BOOLEAN_LABELS = [
    'est_tsokol' => 'Цокольный этаж',
    'garazh' => 'Гараж',
    'parking' => 'Парковка / стоянка',
    'internet' => 'Интернет',
    'otoplenie' => 'Отопление',
    'kanalizatsiya' => 'Канализация',
    'vodosnabzhenie' => 'Водоснабжение',
    'gaz' => 'Газ',
    'banya' => 'Баня',
    'bassein' => 'Бассейн',
    'okhrana' => 'Охрана',
    'zabor' => 'Забор',
  ];

  public static function isHouse(?string $tip): bool
  {
    return $tip === 'house';
  }

  /** @return array<string, string> */
  public static function validationRules(): array
  {
    $tipKeys = implode(',', array_keys(self::TIP_DOMA_LABELS));

    return [
      'tip_doma' => 'nullable|in:'.$tipKeys,
      'est_tsokol' => 'nullable|boolean',
      'ploshchad_uchastka' => 'nullable|numeric|min:0|max:99999',
      'garazh' => 'nullable|boolean',
      'parking' => 'nullable|boolean',
      'internet' => 'nullable|boolean',
      'otoplenie' => 'nullable|boolean',
      'kanalizatsiya' => 'nullable|boolean',
      'vodosnabzhenie' => 'nullable|boolean',
      'gaz' => 'nullable|boolean',
      'banya' => 'nullable|boolean',
      'bassein' => 'nullable|boolean',
      'okhrana' => 'nullable|boolean',
      'zabor' => 'nullable|boolean',
    ];
  }

  /**
   * @param  array<string, mixed>  $validated
   * @return array<string, mixed>
   */
  public static function mergeFromRequest(Request $request, array $validated): array
  {
    if (!self::isHouse($validated['tip'] ?? null)) {
      return self::clearHouseFields($validated);
    }

    foreach (self::BOOLEAN_FIELDS as $field) {
      $validated[$field] = $request->boolean($field);
    }

    if (!$request->filled('tip_doma')) {
      $validated['tip_doma'] = null;
    }

    if (!$request->filled('ploshchad_uchastka')) {
      $validated['ploshchad_uchastka'] = null;
    }

    return $validated;
  }

  /**
   * @param  array<string, mixed>  $validated
   * @return array<string, mixed>
   */
  public static function clearHouseFields(array $validated): array
  {
    $validated['tip_doma'] = null;
    $validated['ploshchad_uchastka'] = null;
    foreach (self::BOOLEAN_FIELDS as $field) {
      $validated[$field] = null;
    }

    return $validated;
  }

  public static function tipDomaLabel(?string $kod): ?string
  {
    if ($kod === null || $kod === '') {
      return null;
    }

    return self::TIP_DOMA_LABELS[$kod] ?? $kod;
  }

  /** @return list<array{field: string, label: string, value: string}> */
  public static function displayRows(object $property): array
  {
    if (!self::isHouse($property->tip ?? null)) {
      return [];
    }

    $rows = [];

    $tipLabel = self::tipDomaLabel($property->tip_doma ?? null);
    if ($tipLabel) {
      $rows[] = ['field' => 'tip_doma', 'label' => 'Тип дома', 'value' => $tipLabel];
    }

    if ($property->ploshchad_uchastka !== null && $property->ploshchad_uchastka !== '') {
      $rows[] = [
        'field' => 'ploshchad_uchastka',
        'label' => 'Земельный участок',
        'value' => rtrim(rtrim(number_format((float) $property->ploshchad_uchastka, 2, ',', ' '), '0'), ',').' сот.',
      ];
    }

    if ($property->vsego_etazhey) {
      $rows[] = [
        'field' => 'vsego_etazhey',
        'label' => 'Этажей в доме',
        'value' => (string) $property->vsego_etazhey,
      ];
    }

    foreach (self::BOOLEAN_FIELDS as $field) {
      if (!empty($property->{$field})) {
        $rows[] = [
          'field' => $field,
          'label' => self::BOOLEAN_LABELS[$field],
          'value' => 'Да',
        ];
      }
    }

    return $rows;
  }
}
