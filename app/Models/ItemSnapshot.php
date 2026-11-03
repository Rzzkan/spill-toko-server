<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Riwayat harian per produk (dari semua pengguna) untuk menghitung trend 30 hari. Unik per (item_key, day). */
class ItemSnapshot extends Model
{
    protected $fillable = ['item_key', 'day', 'sold', 'm30', 'price'];

    /** Simpan angka hari ini untuk banyak produk sekaligus (satu query, bukan satu per produk). */
    public static function recordToday(array $rows): void
    {
        if (!$rows) return;
        $day = now('UTC')->toDateString();
        $now = now();
        static::query()->upsert(
            array_map(fn ($r) => $r + ['day' => $day, 'created_at' => $now, 'updated_at' => $now], array_values($rows)),
            ['item_key', 'day'], ['sold', 'm30', 'price', 'updated_at']
        );
    }

    /** Angka ±30 hari lalu (jendela 20-45 hari; yang paling dekat 30 hari menang). @return array<string,array{m:?int,s:?int,d:string}> */
    public static function monthAgo(array $keys): array
    {
        if (!$keys) return [];
        $today = now('UTC')->startOfDay();
        $best = [];
        $rows = static::query()->whereIn('item_key', $keys)->whereBetween('day', [now('UTC')->subDays(45)->toDateString(), now('UTC')->subDays(20)->toDateString()])
            ->whereNotNull('m30')->orderBy('day')->get();
        foreach ($rows as $row) {
            $age = abs($today->diffInDays(Carbon::parse($row->day, 'UTC')->startOfDay()) - 30);
            if (!isset($best[$row->item_key]) || $age < $best[$row->item_key]['_a']) {
                $best[$row->item_key] = ['m' => $row->m30, 's' => $row->sold, 'd' => substr((string) $row->day, 0, 10), '_a' => $age];
            }
        }
        foreach ($best as &$b) unset($b['_a']);
        return $best;
    }

    /** Total terjual pasti terbaru (maks 14 hari terakhir). @return array<string,array{s:int,m:?int,d:string}> */
    public static function latestSold(array $keys): array
    {
        if (!$keys) return [];
        $last = [];
        $rows = static::query()->whereIn('item_key', $keys)->where('day', '>=', now('UTC')->subDays(14)->toDateString())->whereNotNull('sold')->orderBy('day')->get();
        foreach ($rows as $row) {
            $last[$row->item_key] = ['s' => (int) $row->sold, 'm' => $row->m30 !== null ? (int) $row->m30 : null, 'd' => substr((string) $row->day, 0, 10)];
        }
        return $last;
    }
}
