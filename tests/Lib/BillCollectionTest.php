<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Lib;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use StudyPortals\GetOnBoard\Entity\BillItem;
use StudyPortals\GetOnBoard\Repository\BillsStaticRepository;

class BillCollectionTest extends TestCase
{
    private ObjectProphecy $billStaticRepositoryInterface;

    protected function setUp(): void
    {
        $this->billStaticRepositoryInterface = $this->prophesize(BillsStaticRepository::class);
    }

    public function testSuccessfulCreateBillCollection(): void
    {
        $billsArray = [
            '40.00 Thijs Danny,Danny,Thijs,Stefan,Den',
        ];

        $this->billStaticRepositoryInterface->getBillsArray()->willReturn($billsArray);

        $billCollection = new BillCollection($this->billStaticRepositoryInterface->reveal());

        $this->assertEquals([new BillItem($billsArray[0])], $billCollection->getBillItems());
    }

    public function testThrowUnableToCreateBillItemCollectionExceptionWhenCanCreateACollection(): void
    {
        $billsArray = [
            '40.00 Thijs',
        ];

        $this->billStaticRepositoryInterface->getBillsArray()->willReturn($billsArray);
        $this->expectException(UnableToCreateBillItemCollectionException::class);

        $billCollection = new BillCollection($this->billStaticRepositoryInterface->reveal());
    }
}
