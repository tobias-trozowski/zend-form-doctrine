<?php
declare(strict_types=1);

namespace Tobias\Zend\Form\Doctrine\Element;

use Traversable;
use Zend\Form\ElementInterface;

/**
 * @target \Zend\Form\ElementInterface
 */
trait ElementProxyTrait
{
    /** @var ElementProxy */
    protected $proxy;

    public function getProxy(): ElementProxy
    {
        if (null === $this->proxy) {
            $this->proxy = new Proxy();
        }
        return $this->proxy;
    }

    /**
     * @param array|Traversable $options
     *
     * @return ElementInterface
     */
    public function setOptions($options): ElementInterface
    {
        $this->getProxy()->setOptions($options);
        return parent::setOptions($options);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return ElementInterface
     */
    public function setOption($key, $value): ElementInterface
    {
        $this->getProxy()->setOptions([$key => $value]);
        return parent::setOption($key, $value);
    }
}
