<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Lib;

use StudyPortals\GetOnBoard\Entity\BillItem;
use StudyPortals\GetOnBoard\Repository\BillsStaticRepositoryInterface;

class Calculator implements CalculatorInterface
{
    private array $bills = [];
    private array $paymentMatrix = [];

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
        foreach ($this->bills as $billItem) {
            $creditor = $billItem->getPaidBy();
            $this->setPaymentMatrix($creditor, $billItem->getDebtByAttendee());
        }
        return $this->paymentMatrix;
    }

    private function setPaymentMatrix(string $creditor, array $debtByAttendee): void
    {
        foreach ($debtByAttendee as $debtorKey => $debt) {
            $debtorDebt = $debt[$creditor];

            if (isset($this->paymentMatrix[$creditor][$debtorKey])) {
                $debtorDebt = $this->getCreditorDebt($creditor, $debtorKey, $debtorDebt);
            }

            if (isset($this->paymentMatrix[$debtorKey][$creditor])) {
                $this->paymentMatrix[$debtorKey][$creditor] += $debtorDebt;
                continue;
            }

            if ($debtorDebt > 0) {
                $this->paymentMatrix = array_merge_recursive($this->paymentMatrix, [$debtorKey => [$creditor => $debtorDebt]]);
            }
        }
    }

    private function getCreditorDebt(string $creditor, string $debtorKey, float $debtorDebt): float
    {
        $debtorDebt = $debtorDebt - $this->paymentMatrix[$creditor][$debtorKey];
        if ($debtorDebt > 0) {
            unset($this->paymentMatrix[$creditor][$debtorKey]);
            return $debtorDebt;
        }
        $this->paymentMatrix[$creditor][$debtorKey] = abs($debtorDebt);

        return $debtorDebt;
    }
}