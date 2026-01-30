<?php

declare(strict_types=1);

namespace Sylius\Bundle\FlowBundle\Storage;

use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactoryDecorator implements SessionFactoryInterface
{
    public function __construct(
        private SessionFactoryInterface $decoratedFactory,
        private SessionFlowsBag $bag,
    ) {
    }

    public function createSession(): SessionInterface
    {
        $session = $this->decoratedFactory->createSession();
        $session->registerBag($this->bag);

        return $session;
    }
}