<?php
declare(strict_types=1);

namespace Tobias\Zend\Form\Doctrine\Element;

use Zend\Form\Element\Radio;

final class ObjectRadio extends Radio
{
    use ElementProxyTrait;

    public function setValue($value)
    {
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
