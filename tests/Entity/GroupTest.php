<?php
namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Group;

class GroupTest extends TestCase
{
    public function testGroupName()
    {
        $group = new Group();
        $group->setName('Test Group');

        $this->assertSame('Test Group', $group->getName());
    }
}
