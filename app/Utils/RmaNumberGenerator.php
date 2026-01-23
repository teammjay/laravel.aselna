<?php

namespace App\Utils;

use App\Models\Sequence;
use Hashids\Hashids;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RmaNumberGenerator
{
    private $hashIdLen = 7;
    private $hashIdSalt = 'f45919574949d107ad724acacc0ebd05';
    private $validHashChars = 'ABCDHTVXZ23456789';
    private $hashids;

    public function __construct()
    {
        // Requires the Hashids library: https://github.com/vinkla/hashids
        $this->hashids = new Hashids($this->hashIdSalt, $this->hashIdLen, $this->validHashChars);
    }

    public function generate($store)
    {
        $number = $this->getNextRMANumberForStore($store);
        Log::info('Number:'.$number);
        return $this->generateRma($number);
    }

    public function generateRma($number)
    {
        return $this->hashids->encode($number);
    }

    function getNextRMANumberForStore($store) {
        $key = 'rma_' . str_replace('-', '', $store->short_code);

        return DB::transaction(function () use ($key) {
            $sequence = Sequence::lockForUpdate()->find($key);

            if (!$sequence) {
                $sequence = Sequence::create([
                    'key' => $key,
                    'number' => 1
                ]);
            } else {
                $sequence->increment('number');
            }

            return $sequence->number;
        });
    }

}
