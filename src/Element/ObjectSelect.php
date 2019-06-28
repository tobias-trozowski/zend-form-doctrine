<?php
declare(strict_types=1);

namespace Tobias\Zend\Form\Doctrine\Element;

use Traversable;
use Zend\Form\Element\Select;
use Zend\Stdlib\ArrayUtils;
use function array_map;
use function is_array;

final class ObjectSelect extends Select
{
    use ElementProxyTrait;

    public function setValue($value)
    {
        $multiple = $this->getAttribute('multiple');
        if (true === $multiple || 'multiple' === $multiple) {
            if ($value instanceof Traversable) {
                $value = ArrayUtils::iteratorToArray($value);
            } elseif ($value === null) {
                return parent::setValue([]);
            } elseif (!is_array($value)) {
                $value = (array)$value;
            }
            return parent::setValue(array_map([$this->getProxy(), 'getValue'], $value));
        }
        return parent::setValue($this->getProxy()->getValue($value));
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
