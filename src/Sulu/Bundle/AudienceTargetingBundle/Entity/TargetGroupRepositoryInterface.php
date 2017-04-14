<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Interface for target group repository.
 */
interface TargetGroupRepositoryInterface extends RepositoryInterface
{
    /**
     * Saves the given target group to the repository.
     *
     * @param TargetGroupInterface $targetGroup
     *
     * @return TargetGroupInterface
     */
    public function save(TargetGroupInterface $targetGroup);

    /**
     * Find the target groups with the given IDs.
     *
     * @param int[] $ids
     *
     * @return TargetGroupInterface[]
     */
    public function findByIds($ids);

    /**
     * Returns all active TargetGroups from the given webspace ordered by their priority.
     *
     * @param string $webspace
     *
     * @return TargetGroupInterface[]
     */
    public function findAllActiveForWebspaceOrderedByPriority($webspace);
}