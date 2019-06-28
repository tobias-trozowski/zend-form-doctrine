<?php
declare(strict_types=1);

namespace Tobias\Zend\Form\Doctrine\Element;

use Traversable;
use Zend\Form\Element\MultiCheckbox;
use Zend\Stdlib\ArrayUtils;
use function array_map;
use function is_array;

final class ObjectMultiCheckbox extends MultiCheckbox
{
    use ElementProxyTrait;

    public function setValue($value)
    {
        if ($value instanceof Traversable) {
            $value = ArrayUtils::iteratorToArray($value);
        } elseif ($value === null) {
            return parent::setValue([]);
        } elseif (!is_array($value)) {
            $value = (array)$value;
        }
        return parent::setValue(array_map([$this->getProxy(), 'getValue'], $value));
    }

    public function getValueOptions()
    {
        if (!empty($this->valueOptions)) {
            return $this->valueOptions;
        }
        $proxyValueOptions = $this->getProxy()->getValueOptions();
        if (!empty($proxyValueOptions)) {
            $this->setValueOptions($proxyValueOptions);
        }
        return $this->valueOptions;
    }
}
