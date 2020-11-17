<?php

declare(strict_types=1);

namespace StudyPortals\GetOnBoard\Lib;

use InvalidArgumentException;
use StudyPortals\GetOnBoard\Entity\BillItem;
use StudyPortals\GetOnBoard\Repository\BillsStaticRepositoryInterface;

class BillCollection implements BillCollectionInterface
{
    /** @var BillItem[] */
    private array $bills = [];

    public function __construct(BillsStaticRepositoryInterface $billsStaticRepository)
    {
        try {
            foreach ($billsStaticRepository->getBillsArray() as $billItem) {

                $this->bills[] = new BillItem($billItem);
            }
        } catch (InvalidArgumentException $exception) {
            throw new UnableToCreateBillItemCollectionException(
                'Unable to create bill item collection, an exception occurs',
                0,
                $exception);
        }
    }

    /** @returns BillItem[] */
    public function getBillItems(): array
    {
        return $this->bills;
    }
}