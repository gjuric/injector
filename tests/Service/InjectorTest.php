<?php
declare(strict_types=1);

namespace Zalas\Injector\Tests\Service;

use Exception;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zalas\Injector\Service\ContainerFactory;
use Zalas\Injector\Service\Exception\FailedToInjectServiceException;
use Zalas\Injector\Service\Exception\MissingServiceException;
use Zalas\Injector\Service\Extractor;
use Zalas\Injector\Service\ExtractorFactory;
use Zalas\Injector\Service\Injector;
use PHPUnit\Framework\TestCase;
use Zalas\Injector\Service\Property;
use Zalas\Injector\Tests\Service\Fixtures\Service1;
use Zalas\Injector\Tests\Service\Fixtures\Service2;
use Zalas\Injector\Tests\Service\Fixtures\Services;

class InjectorTest extends TestCase
{
    /**
     * @var Injector
     */
    private $injector;

    /**
     * @var ContainerFactory|ObjectProphecy
     */
    private $containerFactory;

    /**
     * @var ExtractorFactory|ObjectProphecy
     */
    private $extractorFactory;

    /**
     * @var ContainerInterface|ObjectProphecy
     */
    private $container;

    /**
     * @var Extractor|ObjectProphecy
     */
    private $extractor;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->extractor = $this->prophesize(Extractor::class);

        $this->containerFactory = $this->prophesize(ContainerFactory::class);
        $this->extractorFactory = $this->prophesize(ExtractorFactory::class);

        $this->containerFactory->create()->willReturn($this->container);
        $this->extractorFactory->create()->willReturn($this->extractor);

        $this->container->get(Argument::any())->willThrow(
            new class extends Exception implements ContainerExceptionInterface
            {
            }
        );
        $this->extractor->extract(Argument::any())->willReturn([]);

        $this->injector = new Injector($this->containerFactory->reveal(), $this->extractorFactory->reveal());
    }

    public function test_it_injects_services_into_properties()
    {
        $property1 = new Property(Services::class, 'service1', 'foo.service1');
        $property2 = new Property(Services::class, 'service2', 'foo.service2');
        $service1 = new Service1();
        $service2 = new Service2();
        $services = new Services();

        $this->extractor->extract(Services::class)->willReturn([$property1, $property2]);
        $this->container->get('foo.service1')->willReturn($service1);
        $this->container->get('foo.service2')->willReturn($service2);

        $this->injector->inject($services);

        $this->assertSame($service1, $services->getService1());
        $this->assertSame($service2, $services->getService2());
    }

    public function test_it_throws_an_exception_if_container_fails_in_any_way()
    {
        $this->expectException(FailedToInjectServiceException::class);
        $this->expectExceptionCode(0);

        $property1 = new Property(Services::class, 'service1', 'foo.service1');

        $this->extractor->extract(Services::class)->willReturn([$property1]);
        $this->container->get('foo.service1')->willThrow(
            new class extends Exception implements ContainerExceptionInterface
            {
            }
        );

        $this->injector->inject(new Services());
    }

    public function test_it_throws_an_exception_if_service_is_not_found()
    {
        $this->expectException(MissingServiceException::class);
        $this->expectExceptionCode(0);

        $property1 = new Property(Services::class, 'service1', 'foo.service1');

        $this->extractor->extract(Services::class)->willReturn([$property1]);
        $this->container->get('foo.service1')->willThrow(new class extends Exception implements NotFoundExceptionInterface
        {
        });

        $this->injector->inject(new Services());
    }
}