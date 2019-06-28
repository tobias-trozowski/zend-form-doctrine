<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Form\Doctrine\Element;

use PHPUnit\Framework\TestCase;
use Tobias\Zend\Form\Doctrine\Element\ObjectRadio;
use Tobias\Zend\Form\Doctrine\Element\ElementProxy;

final class ObjectRadioTest extends TestCase
{
    use ProxyPrepareTrait;

    /**
     * {@inheritDoc}.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->element = new ObjectRadio();
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
