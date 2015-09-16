<?php

use Lab2\AuthenticationService;

class AuthenticationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_be_valid()
    {
        // Arrange
        $target = new AuthenticationService();

        // Act
        $actual = $target->isValid('john', 'abc000000');

        // Assert
        //$this->assertTrue($actual);
    }
}
