<?php

declare(strict_types=1);

namespace Phasem\db;

use PHPUnit\Framework\TestCase;

class AccountsTest extends TestCase
{
    public function testValidateFullName()
    {
        try {
            Accounts::validateFullName('   ');
            $this->fail('Failed to throw exception for whitespace-only name');
        } catch (\Exception $e) {
            $this->assertSame('Name cannot be blank', $e->getMessage());
        }

        Accounts::validateFullName('Valid Name');
    }

    public function testValidateEmail()
    {
        $expectedError = 'Invalid email format';

        try {
            Accounts::validateEmail('');
            $this->fail('Failed to throw exception for blank email');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        try {
            Accounts::validateEmail('foo@');
            $this->fail('Failed to throw exception for invalid email');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        Accounts::validateEmail('foo@bar.com');
    }

    public function testValidatePassword()
    {
        $expectedError = 'Password must be at least 8 characters in length';

        try {
            Accounts::validatePassword('');
            $this->fail('Failed to throw exception for blank password');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        try {
            Accounts::validatePassword('1234567');
            $this->fail('Failed to throw exception for password that is too short');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        Accounts::validatePassword('12345678');
    }
}
