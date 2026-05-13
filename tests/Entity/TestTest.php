<?php

namespace App\Tests\Entity;

use App\Entity\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TestTest extends TestCase
{
    #[DataProvider('nameAndDescriptionProvider')]
    public function testGetNameAndDescription(?string $name, ?string $description, string $expectedValue): void
    {
        $test = new Test();
        if ($name !== null) {
            $test->setName($name);
        }
        $test->setDescription($description);
        $this->assertSame($expectedValue, $test->getNameAndDescription());
    }

    public static function nameAndDescriptionProvider(): array
    {
        return [
            'no name or description' => [null, null, ''],
            'name, no description' => ['name', null, 'name'],
            'no name, description'  => [null, 'description', 'description'],
            'name, description' => ['name', 'description', 'name - description']
        ];
    }
}
