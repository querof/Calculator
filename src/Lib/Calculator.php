<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Lib;

use StudyPortals\GetOnBoard\Entity\BillItem;
use StudyPortals\GetOnBoard\Repository\BillsStaticRepositoryInterface;

class Calculator implements CalculatorInterface
{
    private const CREDITOR = 'CREDITOR';
    private const DEBTOR = 'DEBTOR';
    private const DEBT = 'DEBT';

    private array $bills = [];

    public function __construct(BillsStaticRepositoryInterface $billsStaticRepository)
    {

        foreach ($billsStaticRepository->getBillsArray() as $billItem) {

            $this->bills[] = new BillItem($billItem);
        }
    }

    public function printBill(): void
    {
        $payout = $this->calculate();

        foreach ($payout as $debtor => $lines) {
            $debtor = ucfirst($debtor);

            foreach ($lines as $creditor => $amount) {

                $amount = number_format($amount, 2);
                $creditor = ucfirst($creditor);
                echo "$debtor pays $creditor $amount" . PHP_EOL;
            }
        }
    }

    private function calculate(): array
    {
        $paymentMatrix = [];

        foreach ($this->bills as $billItem) {
            $this->setPaymentMatrix($billItem, $paymentMatrix);
        }
        return $paymentMatrix;
    }

    private function setPaymentMatrix(BillItem $billItem, array &$paymentMatrix): void
    {
        $creditor = $billItem->getCreditor();

        foreach ($billItem->getDebtByAttendee() as $debtorKey => $debt) {
            $debtCell = [self::DEBTOR => $debtorKey, self::CREDITOR => $creditor, self::DEBT => $debt[$creditor]];

            if (isset($paymentMatrix[$debtCell[self::CREDITOR]][$debtCell[self::DEBTOR]])) {

                $debtCell[self::DEBT] = $this->getCreditorDebt($debtCell, $paymentMatrix);
            }

            if (isset($paymentMatrix[$debtorKey][$creditor])) {
                $paymentMatrix[$debtCell[self::DEBTOR]][$debtCell[self::CREDITOR]] += $debtCell[self::DEBT];
                continue;
            }

            if ($debtCell[self::DEBT] > 0) {
                $paymentMatrix = array_merge_recursive($paymentMatrix, [$debtCell[self::DEBTOR] => [$debtCell[self::CREDITOR] => $debtCell[self::DEBT]]]);
            }
        }
    }

    private function getCreditorDebt(array $debtCell, array &$paymentMatrix): float
    {
        $debtorDebt = $debtCell[self::DEBT] - $paymentMatrix[$debtCell[self::CREDITOR]][$debtCell[self::DEBTOR]];
        if ($debtorDebt > 0) {
            unset($paymentMatrix[$debtCell[self::CREDITOR]][$debtCell[self::DEBTOR]]);
            return $debtorDebt;
        }
        $paymentMatrix[$debtCell[self::CREDITOR]][$debtCell[self::DEBTOR]] = abs($debtorDebt);

        return $debtorDebt;
    }

    private function getDebtReconciliation(array &$paymentMatrix): void
    {
        foreach ($paymentMatrix as $debtor) {
            if($debtor)
        }
    }
}