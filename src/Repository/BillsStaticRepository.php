<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Repository;

class BillsStaticRepository implements BillsStaticRepositoryInterface
{

    public function getBillsArray(): array
    {
        return [
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
    }
}