<?php

declare(strict_types=1);

namespace Phasem\db;

use PHPUnit\Framework\TestCase;

class UsersTest extends TestCase
{
    public function testValidateFullName()
    {
        try {
            Users::validateFullName('   ');
            $this->fail('Failed to throw exception for whitespace-only name');
        } catch (\Exception $e) {
            $this->assertSame('Name cannot be blank', $e->getMessage());
        }

        Users::validateFullName('Valid Name');
    }

    public function testValidateEmail()
    {
        $expectedError = 'Invalid email format';

        try {
            Users::validateEmail('');
            $this->fail('Failed to throw exception for blank email');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        try {
            Users::validateEmail('foo@');
            $this->fail('Failed to throw exception for invalid email');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        Users::validateEmail('foo@bar.com');
    }

    public function testValidatePassword()
    {
        $expectedError = 'Password must be at least 8 characters in length';

        try {
            Users::validatePassword('');
            $this->fail('Failed to throw exception for blank password');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        try {
            Users::validatePassword('1234567');
            $this->fail('Failed to throw exception for password that is too short');
        } catch (\Exception $e) {
            $this->assertSame($expectedError, $e->getMessage());
        }

        Users::validatePassword('12345678');
    }
}
