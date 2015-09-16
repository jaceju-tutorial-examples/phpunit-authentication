<?php

namespace Lab2;

class RsaToken
{
    public function getRandom($account)
    {
        $r = 0;
        foreach (str_split($account) as $c) {
            $r += ord($c);
        }
        srand(time() + $r);
        return sprintf('%06d', rand(0, 999999));
    }
}
