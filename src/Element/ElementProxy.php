<?php
declare(strict_types=1);

namespace Tobias\Zend\Form\Doctrine\Element;

use Doctrine\Common\Persistence\ObjectManager;
use RuntimeException;

interface ElementProxy
{
    public function setOptions(array $options): void;

    public function getValueOptions(): array;

    public function getObjects(): iterable;

    public function setEmptyItemLabel(string $emptyItemLabel): void;

    public function getEmptyItemLabel(): string;

    public function getOptionAttributes(): array;

    public function setOptionAttributes(array $option_attributes): void;

    public function setDisplayEmptyItem(bool $displayEmptyItem): void;

    public function getDisplayEmptyItem(): bool;

    public function setObjectManager(ObjectManager $objectManager): void;

    public function getObjectManager(): ObjectManager;

    public function setTargetClass(string $targetClass): void;

    public function getTargetClass(): string;

    public function setProperty(string $property): void;

    public function getProperty();

    public function setLabelGenerator(callable $callable): void;

    public function getLabelGenerator(): ?callable;

    public function getOptgroupIdentifier(): ?string;

    public function setOptgroupIdentifier(string $optgroupIdentifier): void;

    public function getOptgroupDefault(): ?string;

    public function setOptgroupDefault(string $optgroupDefault): void;

    public function setIsMethod(bool $method): void;

    public function getIsMethod(): bool;

    public function setFindMethod(array $findMethod): void;

    public function getFindMethod(): array;

    /**
     * @param  $value
     *
     * @return array|mixed|object
     * @throws RuntimeException
     */
    public function getValue($value);
}
