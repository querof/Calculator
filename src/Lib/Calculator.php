<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Lib;

use Exception;
use phpDocumentor\Reflection\Types\Self_;
use StudyPortals\GetOnBoard\Entity\BillItem;
use TypeError;

class Calculator implements CalculatorInterface
{
    private const CREDITOR = 'CREDITOR';
    private const DEBTOR = 'DEBTOR';
    private const DEBT = 'DEBT';
    private bool $reconcileDebt = false;

    /**
     * @var BillCollectionInterface
     */
    private BillCollectionInterface $billCollection;

    public function __construct(BillCollectionInterface $billCollection)
    {
        $this->billCollection = $billCollection;
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
        try {
            $paymentMatrix = [];
            foreach ($this->billCollection->getBillItems() as $billItem) {
                $this->setPaymentMatrix($billItem, $paymentMatrix);
            }

            $paymentMatrix = array_filter($paymentMatrix);

            if ($this->reconcileDebt) {
                $this->getTotalDebtReconciliation($paymentMatrix);
            }
            return $paymentMatrix;
        } catch (TypeError|Exception $exception) {
            throw new UnableToCalculateException(
                'An exception occurs when try to calculate debts',
                0,
                $exception);
        }
    }

    private function setPaymentMatrix(BillItem $billItem, array &$paymentMatrix): void
    {
        $creditor = $billItem->getCreditor();

        foreach ($billItem->getDebtByAttendee() as $currentDebtorKey => $debt) {
            $debtCell = [self::DEBTOR => $currentDebtorKey, self::CREDITOR => $creditor, self::DEBT => $debt[$creditor]];

            if (isset($paymentMatrix[$debtCell[self::CREDITOR]][$debtCell[self::DEBTOR]])) {

                $debtCell[self::DEBT] = $this->getCreditorDebt($debtCell, $paymentMatrix);
            }

            if (isset($paymentMatrix[$currentDebtorKey][$creditor])) {
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

    private function getTotalDebtReconciliation(array &$paymentMatrix): void
    {
        foreach ($paymentMatrix as $currentDebtorKey => $debt) {
            $creditor = key($debt);
            $searchMatrix = $this->getSearchMatrix([self::DEBTOR => $currentDebtorKey, self::CREDITOR => $creditor, self::DEBT => $debt], $paymentMatrix);
            $commonDebtor = $this->filterCommonDebtor($searchMatrix, $creditor);

            if (count($commonDebtor) === 0) {
                continue;
            }

            $debtDifference = $paymentMatrix[$currentDebtorKey][$commonDebtor[self::DEBTOR]] - $commonDebtor[self::DEBT];

            $paymentMatrix[$currentDebtorKey][$creditor] += $debtDifference > 0 ? $commonDebtor[self::DEBT] : $paymentMatrix[$currentDebtorKey][$commonDebtor[self::DEBTOR]];

            $paymentMatrix[$currentDebtorKey][$commonDebtor[self::DEBTOR]] = $debtDifference > 0 ? $debtDifference : 0;

            if ($paymentMatrix[$currentDebtorKey][$commonDebtor[self::DEBTOR]] === 0) {
                unset($paymentMatrix[$currentDebtorKey][$commonDebtor[self::DEBTOR]]);
            }

            $paymentMatrix[$commonDebtor[self::DEBTOR]][$creditor] = $debtDifference < 0 ? abs($debtDifference) : 0;

            if ($paymentMatrix[$commonDebtor[self::DEBTOR]][$creditor] === 0) {
                unset($paymentMatrix[$commonDebtor[self::DEBTOR]][$creditor]);
            }
        }
    }

    private function filterCommonDebtor(array $searchMatrix, string $creditor): array
    {
        foreach ($searchMatrix as $debtor => $debt) {
            $creditorDebt = array_intersect_key($searchMatrix[$debtor], [$creditor => []]);
            if (count($creditorDebt) > 0) {
                return [self::DEBTOR => $debtor, self::DEBT => current($creditorDebt)];
            }
        }
        return [];
    }

    private function getSearchMatrix(array $debtorDebt, &$paymentMatrix): array
    {
        $creditorsKeys = array_keys($debtorDebt[self::DEBT]);
        $creditors = array_fill_keys($creditorsKeys, []);
        $excludeSelfMatrix = array_diff_key($paymentMatrix, [$debtorDebt[self::DEBTOR] => []]);

        return array_intersect_key($excludeSelfMatrix, $creditors);
    }

    public function setReconcileDebt(bool $reconcileDebt): void
    {
        $this->reconcileDebt = $reconcileDebt;
    }

    public function isReconcileDebt(): bool
    {
        return $this->reconcileDebt;
    }
}