<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Form\Doctrine\Element;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Tobias\Zend\Form\Doctrine\Element\ObjectSelect;
use Tobias\Zend\Form\Doctrine\Element\Proxy;
use Tobias\Zend\Form\Doctrine\Element\ElementProxy;

final class ObjectSelectTest extends TestCase
{
    use ProxyPrepareTrait;

    /**
     * @var ArrayCollection
     */
    protected $values;

    /**
     * {@inheritDoc}.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->element = new ObjectSelect();
        $this->prepareProxy();
    }

    public function testSetValueWithCollection(): void
    {
        $this->element->setAttribute('multiple', true);
        $this->element->setValue(
            $this->values
        );
        $this->assertEquals(
            [1, 2],
            $this->element->getValue()
        );
    }

    public function testSetValueWithArray(): void
    {
        $this->element->setAttribute('multiple', true);
        $this->element->setValue(
            $this->values->toArray()
        );
        $this->assertEquals(
            [1, 2],
            $this->element->getValue()
        );
    }

    public function testSetValueSingleValue(): void
    {
        $value = $this->values->toArray();
        $this->element->setValue(
            $value[0]
        );
        $this->assertEquals(
            1,
            $this->element->getValue()
        );
    }

    public function testGetValueOptionsDoesntCauseInfiniteLoopIfProxyReturnsEmptyArrayAndValidatorIsInitialized(): void
    {
        $options = [];
        $proxy = $this->createMock(ElementProxy::class);
        $proxy->expects($this->exactly(2))
            ->method('getValueOptions')
            ->willReturn($options);
        $this->setProxyViaReflection($proxy, $this->element);
        $this->element->getInputSpecification();
        $this->assertEquals($options, $this->element->getValueOptions());
    }

    public function testGetValueOptionsDoesntInvokeProxyIfOptionsNotEmpty(): void
    {
        $options = ['foo' => 'bar'];
        $proxy = $this->createMock(ElementProxy::class);
        $proxy->expects($this->once())
            ->method('getValueOptions')
            ->willReturn($options);
        $this->setProxyViaReflection($proxy);
        $this->assertEquals($options, $this->element->getValueOptions());
        $this->assertEquals($options, $this->element->getValueOptions());
    }

    public function testOptionsCanBeSetSingle(): void
    {
        $proxy = $this->createMock(ElementProxy::class);
        $proxy->expects($this->once())->method('setOptions')->with(['is_method' => true]);
        $this->setProxyViaReflection($proxy);
        $this->element->setOption('is_method', true);
    }
}
