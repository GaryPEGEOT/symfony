<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\KernelEventListenerAutoConfigurationPass;
use Symfony\Bundle\FrameworkBundle\EventListener\KernelEventListenerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Unit tests for KernelEventListenerAutoConfigurationPass.
 *
 * @internal
 *
 * @author Gary PEGEOT <garypegeot@gmail.com>
 */
class KernelEventListenerAutoConfigurationPassTest extends TestCase
{
    public function testHasEvent()
    {
        $this->assertTrue(
            KernelEventListenerAutoConfigurationPass::hasEvent(GetResponseEvent::class),
            'GetResponseEvent should be registered.'
        );

        $this->assertFalse(
            KernelEventListenerAutoConfigurationPass::hasEvent('Foo'),
            'Random string should not be registered.'
        );
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->register('foo', MyEventListener::class)->addTag('kernel.auto_configured_event_listener');
        $container->register('bar', 'stdClass');

        KernelEventListenerAutoConfigurationPass::addEvent('stdClass', 'my_custom_event');
        (new KernelEventListenerAutoConfigurationPass())->process($container);

        $definition = $container->getDefinition('foo');

        $this->assertTrue(
            $definition->hasTag('kernel.event_listener'),
            'Definition should have tag "kernel.event_listener".'
        );
        $this->assertSame(
            $definition->getTag('kernel.event_listener'),
            array(
                array(
                    'event' => 'kernel.request',
                    'method' => 'onKernelRequest',
                ),
                array(
                    'event' => 'kernel.response',
                    'method' => '__invoke',
                ),
                array(
                    'event' => 'my_custom_event',
                    'method' => 'onCustomEvt',
                ),
            )
        );
    }

    public function testAddEvent()
    {
        KernelEventListenerAutoConfigurationPass::addEvent('Bar', 'baz');

        $this->assertTrue(
            KernelEventListenerAutoConfigurationPass::hasEvent('Bar'),
            'Foo event should be registered.'
        );
    }

    public function testAddEventWithExistingEvent()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(sprintf('Event name for "%s" is already defined.', GetResponseEvent::class));

        KernelEventListenerAutoConfigurationPass::addEvent(GetResponseEvent::class, 'something_else');
    }
}

/**
 * @internal
 */
class MyEventListener implements KernelEventListenerInterface
{
    public function notTypedMethod($myParam)
    {
    }

    public function typedMethod(int $myParam)
    {
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Do something
    }

    public function almostAListener(GetResponseEvent $event, string $randomParam)
    {
        // Do something
    }

    public function __invoke(FilterResponseEvent $event)
    {
        // Do something else
    }

    public function onCustomEvt(\stdClass $event)
    {
    }

    private function shouldNotBeRegistred(FinishRequestEvent $event)
    {
        // Do nothing
    }
}
