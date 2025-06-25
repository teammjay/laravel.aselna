<?php

namespace App\Utils;

use Hashids\Hashids;

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

    public function generate($number)
    {
        return $this->hashids->encode($number);
    }


}
