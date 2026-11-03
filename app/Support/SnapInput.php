<?php

namespace App\Support;

/** Membersihkan masukan endpoint snap dari extension: kunci produk (shopId.itemId) dan angka. Baris tak valid dilewati, bukan ditolak. */
class SnapInput
{
    public const MAX = 150;

    public static function validKey(mixed $k): bool
    {
        return is_string($k) && preg_match('/^\d{1,12}\.\d{1,14}$/', $k) === 1;
    }

    private static function int(mixed $v): ?int
    {
        return is_numeric($v) ? max(0, (int) $v) : null;
    }

    /**
     * @param  array<int,mixed> $items baris {k, s, m, p}
     * @return array<string,array{item_key:string,sold:?int,m30:?int,price:?int}> per kunci; kunci ganda: yang terakhir menang
     */
    public static function rows(array $items): array
    {
        $out = [];
        foreach (array_slice($items, 0, self::MAX) as $it) {
            $k = is_array($it) ? ($it['k'] ?? null) : null;
            if (!self::validKey($k)) continue;
            $m = self::int($it['m'] ?? null);
            $sold = self::int($it['s'] ?? null);
            if ($m === null && $sold === null) continue;
            $out[$k] = ['item_key' => $k, 'sold' => $sold, 'm30' => $m, 'price' => self::int($it['p'] ?? null)];
        }
        return $out;
    }

    /** @return string[] */
    public static function keys(array $want): array
    {
        return array_values(array_filter(array_slice($want, 0, self::MAX), fn ($k) => self::validKey($k)));
    }
}
