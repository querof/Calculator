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
    private const REMOVE_CELL = 'REMOVE_CELL';
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

            if ($this->isReconcileDebt()) {
                $this->setTotalDebtReconciliation($paymentMatrix);
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

                $debtCell[self::DEBT] = $debt[$creditor] - $paymentMatrix[$creditor][$currentDebtorKey];

                $this->updatePaymentMatrixCell(
                    [
                        self::DEBTOR => $creditor,
                        self::CREDITOR => $currentDebtorKey,
                        self::DEBT => $debtCell[self::DEBT],
                        self::REMOVE_CELL => $debtCell[self::DEBT] > 0
                    ],
                    $paymentMatrix
                );
            }

            if (isset($paymentMatrix[$currentDebtorKey][$creditor])) {
                $paymentMatrix[$debtCell[self::DEBTOR]][$debtCell[self::CREDITOR]] += $debtCell[self::DEBT];
                continue;
            }

            if ($debtCell[self::DEBT] > 0) {
                $paymentMatrix = array_merge_recursive(
                    $paymentMatrix,
                    [
                        $debtCell[self::DEBTOR] => [$debtCell[self::CREDITOR] => $debtCell[self::DEBT]]
                    ]
                );
            }
        }
    }

    private function setTotalDebtReconciliation(array &$paymentMatrix): void
    {
        foreach ($paymentMatrix as $currentDebtorKey => $debt) {
            $creditor = key($debt);
            $searchMatrix = $this->getSearchMatrix(
                [
                    self::DEBTOR => $currentDebtorKey,
                    self::CREDITOR => $creditor,
                    self::DEBT => $debt
                ],
                $paymentMatrix
            );

            $commonDebtor = $this->filterCommonDebtor($searchMatrix, $creditor);

            if (count($commonDebtor) === 0) {
                continue;
            }

            $debtDifference = $paymentMatrix[$currentDebtorKey][$commonDebtor[self::DEBTOR]] - $commonDebtor[self::DEBT];

            $paymentMatrix[$currentDebtorKey][$creditor] +=
                $debtDifference > 0 ? $commonDebtor[self::DEBT] : $paymentMatrix[$currentDebtorKey][$commonDebtor[self::DEBTOR]];

            $debtCell = [
                self::DEBTOR => $currentDebtorKey,
                self::CREDITOR => $commonDebtor[self::DEBTOR],
                self::DEBT => $debtDifference,
                self::REMOVE_CELL => $debtDifference <= 0
            ];

            $this->updatePaymentMatrixCell($debtCell, $paymentMatrix);

            $debtCell = [
                self::DEBTOR => $commonDebtor[self::DEBTOR],
                self::CREDITOR => $creditor,
                self::DEBT => $debtDifference,
                self::REMOVE_CELL => $debtDifference >= 0
            ];

            $this->updatePaymentMatrixCell($debtCell, $paymentMatrix);
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

    private function updatePaymentMatrixCell(array $debtCell, array &$paymentMatrix): void
    {
        if ($debtCell[self::REMOVE_CELL]) {
            unset($paymentMatrix[$debtCell[self::DEBTOR]][$debtCell[self::CREDITOR]]);
            return;
        }
        $paymentMatrix[$debtCell[self::DEBTOR]][$debtCell[self::CREDITOR]] = abs($debtCell[self::DEBT]);
    }

    public function setReconcileDebt(bool $reconcileDebt): void
    {
        $this->reconcileDebt = $reconcileDebt;
    }

    private function isReconcileDebt(): bool
    {
        return $this->reconcileDebt;
    }
}