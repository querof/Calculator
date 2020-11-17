<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use StudyPortals\GetOnBoard\Entity\BillItem;
use StudyPortals\GetOnBoard\Lib\BillCollection;
use StudyPortals\GetOnBoard\Lib\Calculator;
use StudyPortals\GetOnBoard\Lib\UnableToCalculateException;
use StudyPortals\GetOnBoard\Repository\BillsStaticRepository;

class CalculatorTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private ObjectProphecy $billCollection;

    protected function setUp(): void
    {
        $this->billCollection = $this->prophesize(BillCollection::class);
    }

    public function testSuccessfullyPrintBill(): void
    {
        $billStaticRepository = new BillsStaticRepository();

        $this->billCollection->getBillItems()->willReturn($this->getBillItems());

        $calculator = new Calculator($this->billCollection->reveal());

        $calculator->printBill();

        $outputString =
            'Stefan pays Danny 20.25' . PHP_EOL .
            'Stefan pays Den 8.60' . PHP_EOL .
            'Stefan pays Thijs 10.00' . PHP_EOL .
            'Den pays Thijs 40.80' . PHP_EOL .
            'Den pays Danny 19.65' . PHP_EOL .
            'Thijs pays Danny 9.45' . PHP_EOL;

        $this->expectOutputString($outputString);
    }

    public function testSuccessfullyPrintBillReconcileDebt(): void
    {
        $billStaticRepository = new BillsStaticRepository();

        $this->billCollection->getBillItems()->willReturn($this->getBillItems());

        $calculator = new Calculator($this->billCollection->reveal());

        $calculator->setReconcileDebt(true);
        $calculator->printBill();

        $outputString =
            'Stefan pays Danny 28.85' . PHP_EOL .
            'Stefan pays Thijs 10.00' . PHP_EOL .
            'Den pays Thijs 40.80' . PHP_EOL .
            'Den pays Danny 11.05' . PHP_EOL .
            'Thijs pays Danny 9.45' . PHP_EOL;

        $this->expectOutputString($outputString);
    }

    public function testThrowUnableToCalculateExceptionWhenAnExceptionOccurs(): void
    {
        $billStaticRepository = new BillsStaticRepository();

        $this->billCollection->getBillItems()->willReturn($this->getBillItemsMalformed());
        $this->expectException(UnableToCalculateException::class);
        $calculator = new Calculator($this->billCollection->reveal());

        $calculator->printBill();

        $outputString =
            'Stefan pays Danny 20.25' . PHP_EOL .
            'Stefan pays Den 8.60' . PHP_EOL .
            'Stefan pays Thijs 10.00' . PHP_EOL .
            'Den pays Thijs 40.80' . PHP_EOL .
            'Den pays Danny 19.65' . PHP_EOL .
            'Thijs pays Danny 9.45' . PHP_EOL;

        $this->expectOutputString($outputString);
    }

    private function getBillItems(): array
    {
        $billsArray = [
            '40.00 Thijs Danny,Danny,Thijs,Stefan,Den',
            '45.00 Danny Danny,Thijs,Stefan,Den',
            '36.00 Stefan Danny,Thijs,Stefan',
            '40.00 Stefan Danny,Thijs,stefan,Den',
            '40.00 Danny Danny,Thijs,Stefan,Den',
            '12.00 Stefan Thijs,Stefan,Den',
            '44.00 Danny Danny,Thijs,Stefan,Den',
            '42.40 Den Danny,Stefan,Den,Den',
            '40.00 danny Danny,Thijs,Stefan,Den',
            '50.40 Thijs Danny,Thijs,Den',
            '48.00 Den Danny,thijs,Stefan,Den',
            '84.00 Thijs Thijs,Stefan,den'
        ];

        return array_map(fn($billItem) => new BillItem($billItem), $billsArray);
    }

    private function getBillItemsMalformed(): array
    {

        return ['MALFORMED'];
    }
}
