<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CurrenciesHelper
{
    public static function addCreditor($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->creditor;
            }
        }
        return $items;
    }
    public static function addCurrency($items)
    {
        if (count($items) > 0) {
            foreach ($items as $key => $item) {
                $item->currency;
            }
        }
        return $items;
    }
}
