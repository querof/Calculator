<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Lib;

use StudyPortals\GetOnBoard\Entity\BillItem;

interface BillCollectionInterface
{
    /**
     * @return BillItem[]
     */
   public function getBillItems():array;
}