<?php
declare(strict_types=1);

namespace Zalas\Injector\Service;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionProperty;
use Zalas\Injector\Service\Exception\FailedToInjectServiceException;
use Zalas\Injector\Service\Exception\MissingServiceException;

class Injector
{
    /**
     * @var ContainerFactory
     */
    private $containerFactory;

    /**
     * @var ExtractorFactory
     */
    private $extractorFactory;

    public function __construct(ContainerFactory $containerFactory, ExtractorFactory $extractorFactory)
    {
        $this->containerFactory = $containerFactory;
        $this->extractorFactory = $extractorFactory;
    }

    /**
     * @throws FailedToInjectServiceException
     * @throws MissingServiceException
     */
    public function inject(object $object): void
    {
        array_map($this->getPropertyInjector($object), $this->extractProperties($object));
    }

    private function getPropertyInjector(object $object): Closure
    {
        $container = $this->containerFactory->create();

        return function (Property $property) use ($object, $container) {
            return $this->injectService($property, $object, $container);
        };
    }

    private function injectService(Property $property, object $object, ContainerInterface $container): void
    {
        $reflectionProperty = new ReflectionProperty($property->getClassName(), $property->getPropertyName());
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $this->getService($container, $property));
    }

    /**
     * @return array|Property[]
     */
    private function extractProperties(object $object): array
    {
        return $this->extractorFactory->create()->extract(get_class($object));
    }

    private function getService(ContainerInterface $container, Property $property)
    {
        try {
            return $container->get($property->getServiceId());
        } catch (NotFoundExceptionInterface $e) {
            throw new MissingServiceException($property, $e);
        } catch (ContainerExceptionInterface $e) {
            throw new FailedToInjectServiceException($property, $e);
        }
    }
}
