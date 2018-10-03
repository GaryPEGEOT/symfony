<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Fetch every "kernel.auto_configured_event_listener" tagged services (Instances of KernelEventListenerInterface) and try to guess the related event(s).
 *
 * @author Gary PEGEOT <garypegeot@gmail.com>
 */
class KernelEventListenerAutoConfigurationPass implements CompilerPassInterface
{
    private static $eventMapping = array(
        'Symfony\Component\HttpKernel\Event\GetResponseEvent' => KernelEvents::REQUEST,
        'Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent' => KernelEvents::EXCEPTION,
        'Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent' => KernelEvents::VIEW,
        'Symfony\Component\HttpKernel\Event\FilterControllerEvent' => KernelEvents::CONTROLLER,
        'Symfony\Component\HttpKernel\Event\FilterControllerArgumentsEvent' => KernelEvents::CONTROLLER_ARGUMENTS,
        'Symfony\Component\HttpKernel\Event\FilterResponseEvent' => KernelEvents::RESPONSE,
        'Symfony\Component\HttpKernel\Event\PostResponseEvent' => KernelEvents::TERMINATE,
        'Symfony\Component\HttpKernel\Event\FinishRequestEvent' => KernelEvents::FINISH_REQUEST,
    );

    /**
     * Register an event => name combination.
     *
     * @param string $className the FQCN of the event
     * @param string $eventName the event name
     */
    public static function addEvent(string $className, string $eventName): void
    {
        if (static::hasEvent($className)) {
            throw new \InvalidArgumentException("Event name for \"$className\" is already defined.");
        }

        static::$eventMapping[$className] = $eventName;
    }

    /**
     * Check if an event is registered.
     *
     * @param string $className the FQCN of the event
     *
     * @return bool
     */
    public static function hasEvent(string $className): bool
    {
        return isset(static::$eventMapping[$className]);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach (array_keys($container->findTaggedServiceIds('kernel.auto_configured_event_listener')) as $id) {
            $definition = $container->getDefinition($id);

            if (null === $definition->getClass()) {
                continue;
            }

            $ref = $container->getReflectionClass($definition->getClass());

            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (1 !== $method->getNumberOfParameters()) {
                    continue;
                }

                $param = $method->getParameters()[0];

                if (null !== ($class = $param->getClass()) && static::hasEvent($class->getName())) {
                    $definition->addTag(
                        'kernel.event_listener',
                        array('event' => static::$eventMapping[$class->getName()], 'method' => $method->getName())
                    );
                }
            }
        }
    }
}
