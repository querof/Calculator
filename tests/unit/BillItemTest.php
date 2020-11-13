<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use StudyPortals\GetOnBoard\Entity\BillItem;

class BillItemTest extends TestCase
{
    public function testThrowInvalidArgumentExceptionWhenTheBillLineHasNotAProperStructure(): void
    {
        $billItemString = '45.00 Danny';

        $this->expectException(InvalidArgumentException::class);
        $billItem = new BillItem($billItemString);
    }

    public function testSuccessfullyPopulateBillItem()
    {
        $billItemString = '40.00 Thijs Danny,Danny,Thijs,Stefan,Den';
        $billItem = new BillItem($billItemString);

        $this->assertInstanceOf(BillItem::class, $billItem);
        $this->assertEquals(
            [
                'danny' => ['thijs' => 16.00], 'stefan' => ['thijs' => 8.00], 'den' => ['thijs' => 8.00]
            ],
            $billItem->getDebtByAttendee()
        );
    }
}
