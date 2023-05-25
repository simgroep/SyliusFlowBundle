<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Process\Coordinator;

use Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilderInterface;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\ProcessInterface;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Sylius\Bundle\FlowBundle\Process\Step\ActionResult;
use Sylius\Bundle\FlowBundle\Process\Step\StepInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Default coordinator implementation.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class Coordinator implements CoordinatorInterface
{
    protected array $scenarios = [];

    public function __construct(
        protected RouterInterface $router,
        protected ProcessBuilderInterface $builder,
        protected ProcessContextInterface $context
    ){}

    /**
     * {@inheritdoc}
     */
    public function start(string $scenarioAlias, ParameterBag $queryParameters = null): RedirectResponse
    {
        $process = $this->buildProcess($scenarioAlias);
        $step = $process->getFirstStep();

        $this->context->initialize($process, $step);
        $this->context->close();

        if (!$this->context->isValid()) {
            return $this->processStepResult($process, $this->context->getProcess()->getValidator()->getResponse($step));
        }

        return $this->redirectToStepDisplayAction($process, $step, $queryParameters);
    }

    /**
     * {@inheritdoc}
     */
    public function display(
        string $scenarioAlias,
        string $stepName,
        ParameterBag $queryParameters = null
    ): Response {
        $process = $this->buildProcess($scenarioAlias);
        $step = $process->getStepByName($stepName);

        $this->context->initialize($process, $step);

        try {
            $this->context->rewindHistory();
        } catch (NotFoundHttpException $e) {
            return $this->goToLastValidStep($process, $scenarioAlias);
        }

        return $this->processStepResult(
            $process,
            $this->context->isValid() ? $step->displayAction($this->context) : $this->context->getProcess()->getValidator()->getResponse($step)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function forward(string $scenarioAlias, string $stepName): Response
    {
        $process = $this->buildProcess($scenarioAlias);
        $step = $process->getStepByName($stepName);

        $this->context->initialize($process, $step);

        try {
            $this->context->rewindHistory();
        } catch (NotFoundHttpException $e) {
            return $this->goToLastValidStep($process, $scenarioAlias);
        }

        return $this->processStepResult(
            $process,
            $this->context->isValid() ? $step->forwardAction($this->context) : $this->context->getProcess()->getValidator()->getResponse($step)
        );
    }

    /**
     * @param ProcessInterface $process
     * @param $result
     *
     * @return mixed
     */
    public function processStepResult(ProcessInterface $process, $result): mixed
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof ActionResult) {
            // Handle explicit jump to step.
            if ($result->getNextStepName()) {
                $this->context->setNextStepByName($result->getNextStepName());

                return $this->redirectToStepDisplayAction($process, $this->context->getNextStep());
            }

            // Handle last step.
            if ($this->context->isLastStep()) {
                $this->context->close();

                return new RedirectResponse(
                    $this->router->generate($process->getRedirect(), $process->getRedirectParams())
                );
            }

            // Handle default linear behaviour.
            return $this->redirectToStepDisplayAction($process, $this->context->getNextStep());
        }

        throw new \RuntimeException('Wrong action result, expected Response or ActionResult');
    }

    /**
     * {@inheritdoc}
     */
    public function registerScenario(string $alias, ProcessScenarioInterface $scenario): void
    {
        if (isset($this->scenarios[$alias])) {
            throw new InvalidArgumentException(
                sprintf('Process scenario with alias "%s" is already registered', $alias)
            );
        }

        $this->scenarios[$alias] = $scenario;
    }

    /**
     * {@inheritdoc}
     */
    public function loadScenario(string $alias): ProcessScenarioInterface
    {
        if (!isset($this->scenarios[$alias])) {
            throw new InvalidArgumentException(sprintf('Process scenario with alias "%s" is not registered', $alias));
        }

        return $this->scenarios[$alias];
    }

    protected function redirectToStepDisplayAction(
        ProcessInterface $process,
        StepInterface $step,
        ?ParameterBag $queryParameters = null
    ): RedirectResponse {
        $this->context->addStepToHistory($step->getName());

        if (null !== $route = $process->getDisplayRoute()) {
            $url = $this->router->generate($route, array_merge(
                $process->getDisplayRouteParams(),
                ['stepName' => $step->getName()],
                $queryParameters ? $queryParameters->all() : []
            ));

            return new RedirectResponse($url);
        }

        // Default parameters for display route
        $routeParameters = [
            'scenarioAlias' => $process->getScenarioAlias(),
            'stepName' => $step->getName(),
        ];

        if (null !== $queryParameters) {
            $routeParameters = array_merge($queryParameters->all(), $routeParameters);
        }

        return new RedirectResponse(
            $this->router->generate('sylius_flow_display', $routeParameters)
        );
    }

    protected function goToLastValidStep(ProcessInterface $process, string $scenarioAlias): RedirectResponse
    {
        //the step we are supposed to display was not found in the history.
        if (null === $this->context->getPreviousStep()) {
            //there is no previous step go to start
            return $this->start($scenarioAlias);
        }

        //we will go back to previous step...
        $history = $this->context->getStepHistory();
        if (empty($history)) {
            //there is no history
            return $this->start($scenarioAlias);
        }
        $step = $process->getStepByName(end($history));

        $this->context->initialize($process, $step);

        return $this->redirectToStepDisplayAction($process, $step);
    }

    /**
     * Builds process for given scenario alias.
     */
    protected function buildProcess(string $scenarioAlias): ProcessInterface
    {
        $process = $this->builder->build($this->loadScenario($scenarioAlias));
        $process->setScenarioAlias($scenarioAlias);

        return $process;
    }
}
