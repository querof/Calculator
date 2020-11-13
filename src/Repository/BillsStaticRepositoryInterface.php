<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Repository;

interface BillsStaticRepositoryInterface
{
    public function getBillsArray(): array;
}