<?php

declare(strict_types=1);

namespace Phasem\model;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testVerifyPassword()
    {
        $user = new User([
            'user_id' => 0,
            'user_fullname' => 'my name',
            'user_email' => 'foo@example.com',
            'user_password' => '$2y$10$EjEaZbKUt4LpGpuMHiRyZOKPoMjFRBYys0X3PxewLHKUmiazjLBQm',
            'user_created' => '2017-07-24 00:23:57',
            'user_last_updated' => '2017-07-24 00:23:57',
        ]);

        $this->assertTrue($user->verifyPassword('password'));
        $this->assertFalse($user->verifyPassword('wrongPass'));
    }
}
