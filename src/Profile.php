<?php

namespace Lab2;

class Profile
{
    private $users = [
        'john' => 'abc',
    ];

    public function getPassword($account)
    {
        if (isset($this->users[strtolower($account)])) {
            return $this->users[strtolower($account)];
        } else {
            throw new \Exception('This account does not exist.');
        }
    }
}
