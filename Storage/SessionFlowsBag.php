<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full license information, view the LICENSE file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Storage;

/**
 * Separate session bag to store flows data.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class SessionFlowsBag extends NamespacedAttributeBag
{
    const STORAGE_KEY = '_sylius_flow_bag';
    const NAME = '_sylius_flow_bag';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(self::STORAGE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
