<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Symfony\Component\HttpFoundation\Response;

interface StepInterface
{
    /**
     * Get step name in current scenario.
     */
    public function getName(): string;

    /**
     * Set step name.
     */
    public function setName(string $name): void;

    /**
     * Display action.
     */
    public function displayAction(ProcessContextInterface $context): ActionResult|Response;

    /**
     * Forward action.
     */
    public function forwardAction(ProcessContextInterface $context): null|ActionResult|Response;

    /**
     * Is step active in process?
     */
    public function isActive(): bool;

    /**
     * Proceeds to the next step.
     */
    public function complete(): ActionResult;

    /**
     * Proceeds to the given step.
     */
    public function proceed(string $nextStepName): ActionResult;
}
