<?php

declare(strict_types=1);

namespace Phasem\model;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testVerifyPassword(): void
    {
        $user = new User([
            'account_id' => 0,
            'fullname' => 'my name',
            'email' => 'foo@example.com',
            'password' => '$2y$10$EjEaZbKUt4LpGpuMHiRyZOKPoMjFRBYys0X3PxewLHKUmiazjLBQm',
            'account_created' => '2017-07-24 00:23:57',
            'account_last_updated' => '2017-07-24 00:23:57',
        ]);

        $this->assertTrue($user->verifyPassword('password'));
        $this->assertFalse($user->verifyPassword('wrongPass'));
    }
}
