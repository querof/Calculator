<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Entity;

use InvalidArgumentException;

class BillItem
{
    private float $price;
    private string $paidBy;
    private array $debtByAttendee = [];

    public function __construct(string $row)
    {
        $lunchBillLine = explode(' ', $row);

        if (count($lunchBillLine) < 3) {
            throw new InvalidArgumentException('This line does not has the proper structure', 0);
        }

        $this->setPrice((float)$lunchBillLine[0]);
        $this->setPaidBy($lunchBillLine[1]);
        $this->setAttendees($lunchBillLine[2]);
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPaidBy(): string
    {
        return $this->paidBy;
    }

    public function getDebtByAttendee(): array
    {
        return $this->debtByAttendee;
    }

    private function setPrice(float $price): void
    {
        $this->price = $price;
    }

    private function setPaidBy(string $paid_by): void
    {
        $this->paidBy = strtolower($paid_by);
    }

    private function setAttendees(string $attendees): void
    {
        $this->debtByAttendee = $this->mapDebtByAttendee($attendees);
    }

    private function mapDebtByAttendee(string $attendees):array
    {
        $attendees = explode(',', strtolower($attendees));
        $creditor = $this->getPaidBy();
        $debt = $this->getPrice() / count($attendees);

        $keys = array_diff($attendees, [$creditor]);
        $debtByAttendee = array_fill_keys($keys, [$creditor => $debt]);
        $countShares = array_diff(array_count_values($keys), [1]);

        foreach ($countShares as $key => $value) {
            $debtByAttendee[$key][$creditor] *= $value;
        }

        return $debtByAttendee;
    }
}