<?php

declare(strict_types=1);

namespace Phasem;

use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function testHashSensitiveKeys(): void
    {
        $data = ['email' => 'someone@example.com', 'password' => 'fake password'];
        $actual = App::hashSensitiveKeys($data);
        $expected = ['email' => 'someone@example.com', 'password' => 'e6eee5d4ec9df1cf09b1f54cffc2430de2b90300cd271a380c229c342fffee8b'];
        $this->assertSame($expected, $actual);

        // replace other sensitive property values
        $data = ['email' => 'someone@example.com', 'newPassword' => 'hello world'];
        $actual = App::hashSensitiveKeys($data);
        $expected = ['email' => 'someone@example.com', 'newPassword' => 'b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9'];
        $this->assertSame($expected, $actual);

        $data = ['email' => 'person@example.com', 'currentPassword' => 'hello world'];
        $actual = App::hashSensitiveKeys($data);
        $expected = ['email' => 'person@example.com', 'currentPassword' => 'b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9'];
        $this->assertSame($expected, $actual);

        // don't replace blank properties
        $data = ['email' => 'someone@example.com', 'password' => ''];
        $actual = App::hashSensitiveKeys($data);
        $expected = ['email' => 'someone@example.com', 'password' => ''];
        $this->assertSame($expected, $actual);
    }
}
