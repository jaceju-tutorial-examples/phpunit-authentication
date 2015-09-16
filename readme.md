# Lab2 - 測試身份驗證邏輯

目標：學習如何在程式碼中做依賴注入，以便做隔離測試。

## 無法被測試的程式碼

打開 `src/AuthenticationService.php` ，瞭解運作方式。

打開 `tests/AuthenticationServiceTest.php` ，查看驗證方式。

將 `// $this->assertTrue($actual);` 的 `//` 註解拿掉。

執行測試。

## 採用「提煉方法並覆寫」的測試方式

編輯 `AuthenticationService` 類別，將 `isValid` 方法中的：

```php
$profile = new Profile();
$customPassword = $profile->getPassword($account);
```

用 Extract Method 與 Inline 重構為：

```php
$customPassword = $this->getCustomPassword($account);
```

與：

```php
protected function getCustomPassword($account)
{
    $profile = new Profile();
    return $profile->getPassword($account);
}
```

將：

```php
$rsaToken = new RsaToken();
$randomCode = $rsaToken->getRandom($account);
```

用 Extract Method 與 Inline 重構為：

```php
$randomCode = $this->getRandomCode($account);
```

及：

```php
protected function getRandomCode($account)
{
    $rsaToken = new RsaToken();
    return $rsaToken->getRandom($account);
}
```

編輯 `AuthenticationServiceTest` 測試類別，在檔案後加上：

```php
class StubAuthenticationService extends AuthenticationService
{
    public function getCustomPassword($account)
    {
        return 'abc';
    }

    public function getRandomCode($account)
    {
        return '000000';
    }
}
```

修改 `` 測試方法，將：

```php
$target = new AuthenticationService();
```

改為：

```php
$target = new StubAuthenticationService();
```

執行測試。

## 手動建立 Stub

先還原到一開始的範例。

在 `src/` 建立 `IProfile.php` ，內容為：

```php
<?php

namespace Lab2;

interface IProfile
{
    public function getPassword($account);
}
```

在 `src/` 建立 `IRsaToken.php` ，內容為：

```php
<?php

namespace Lab2;

interface IRsaToken
{
    public function getRandom($account);
}
```

在 `AuthenticationService` 類別裡加入以下程式碼：

```php
    /**
     * @var IProfile
     */
    private $profile;

    /**
     * @var IRsaToken
     */
    private $rsaToken;

    public function __construct(IProfile $profile, IRsaToken $rsaToken)
    {
        $this->profile = $profile;
        $this->rsaToken = $rsaToken;
    }
```

將 `isValid` 方法裡的：

```php
$profile = new Profile();
$customPassword = $profile->getPassword($account);
```

取代成：

```
$customPassword = $this->profile->getPassword($account);
```

以及：

```php
$rsaToken = new RsaToken();
$randomCode = $rsaToken->getRandom($account);
```

取代成：

```php
$randomCode = $this->rsaToken->getRandom($account);
```

打開 `tests/AuthenticationServiceTest.php` ，在檔案後方加入：

```php
class StubProfile implements IProfile
{
    public function getPassword($account)
    {
        return "abc";
    }
}

class StubRsaToken implements IRsaToken
{
    public function getRandom($account)
    {
        return "000000";
    }
}
```

並記得引用正確的類別：

```php
use Lab2\IProfile;
use Lab2\IRsaToken;
```

將 `it_should_be_valid()` 方法中的：

```php
$target = new AuthenticationService();
```

改成：

```php
$profile = new StubProfile;
$rsaToken = new StubRsaToken;
$target = new AuthenticationService($profile, $rsaToken);
```

執行測試。

## 用 Mockery 建立 Stub

在 `tests/AuthenticationServiceTest.php` 檔案中引用 `Mockery` 類別：

```php
use Mockery as m;
```

在 `AuthenticationServiceTest` 類別中加入 `tearDown()` 方法，並呼叫 `m::close` 來清除所有 Mockery 所建立的 mock 物件。

```php
public function tearDown()
{
    m::close();
}
```

透過 `m::mock(className)` 來動態產生 stub 物件，也就是將：

```php
$profile = new StubProfile;
$rsaToken = new StubRsaToken;
```

改為：

```php
$profile = m::mock(IProfile::class); // ::class 為 PHP 5.5 的新語法
$rsaToken = m::mock(IRsaToken::class);
```

設定當呼叫 stub 物件的「哪個方法」，「傳入什麼參數」時，要「回傳什麼」？在 `// Act` 註解前直接加入以下程式碼：

```php
$profile->shouldReceive('getPassword') // 要呼叫的方法
    ->withAnyArgs()                    // 傳入任何參數
    ->andReturn('abc');                // 固定的回傳值

$rsaToken->shouldReceive('getRandom')
    ->withAnyArgs()
    ->andReturn('000000');

// Act
```

刪掉原來的 `StubProfile` 與 `StubRsaToken` 類別定義。

執行測試。

## 加入 Log 機制

將原來 `AuthenticationService` 類別的建構式：

```php
    public function __construct(IProfile $profile, IRsaToken $rsaToken)
    {
        $this->profile = $profile;
        $this->rsaToken = $rsaToken;
    }
```

加入 log 物件參考，也就是改成：

```php
    /**
     * @var ILog
     */
    private $log;

    public function __construct(IProfile $profile, IRsaToken $rsaToken, ILog $log)
    {
        $this->profile = $profile;
        $this->rsaToken = $rsaToken;
        $this->log = $log;
    }
```

將 `isValid` 方法的回傳式：

```php
return $password === $validPassword;
```

加入是否紀錄 log 判斷，也就是改成：

```php
$isValid = $password === $validPassword;

if (!$isValid) {
    // @todo 如何驗證當有非法登入的情況發生時，有正確記錄下來？
    $content = sprintf('Account: %s try to login failed', $account);
    $this->log->log($content);
}

return $isValid;
```

在 `src/` 建立 `ILog.php` ，內容為：

```php
<?php

namespace Lab2;

interface ILog
{
    public function log($content);
}
```

執行測試。

思考看看在還沒有實作 `ILog` 介面的類別前，要怎麼驗證 `ILog::log` 方法有被呼叫？

### 用 Mockery 建立 Mock

在 `tests/AuthenticationServiceTest.php` 的 `it_should_be_valid` 方法中加入 log 物件，也就是把：

```php
$target = new AuthenticationService($profile, $rsaToken);
```

改成：

```php
$log = m::mock(ILog::class);
$target = new AuthenticationService($profile, $rsaToken, $log);
```

要記得引用 `Lab2\ILog` 類別。

執行測試。

在 `AuthenticationServiceTest` 加入以下新方法 `it_should_be_not_valid` ：

```php
/**
 * @test
 */
public function it_should_be_not_valid()
{
}
```

將 `it_should_be_valid` 方法的內容複製到 `it_should_be_not_valid` 方法裡。

用 mock 來測試驗證不通過時，應該會寫入 log 的情境。在 `// Act` 註解前加入以下程式碼：

```php
$log->shouldReceive('log')
    ->once()
    ->with('Account: john try to login failed')
    ->andReturnNull();
```

執行測試。

想想會什麼測試會失敗？

將驗證的部份從：

```php
// Act
$actual = $target->isValid('john', 'abc000000');

// Assert
$this->assertTrue($actual);
```

改成：

```php
// Act
$actual = $target->isValid('john', 'abc999999');

// Assert
$this->assertFalse($actual);
```

執行測試。
