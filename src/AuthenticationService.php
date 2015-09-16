<?php

namespace Lab2;

class AuthenticationService
{
    public function isValid($account, $password)
    {
        // 根據 account 取得自訂密碼
        $profile = new Profile();
        $customPassword = $profile->getPassword($account);

        // 根據 account 取得 RSA token 目前的亂數
        $rsaToken = new RsaToken();
        $randomCode = $rsaToken->getRandom($account);

        // 驗證傳入的 password 是否等於自訂密碼 + RSA token 亂數
        $validPassword = $customPassword . $randomCode;
        return $password === $validPassword;
    }
}
