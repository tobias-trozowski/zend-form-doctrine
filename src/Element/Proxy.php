<?php
declare(strict_types=1);

namespace Tobias\Zend\Form\Doctrine\Element;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Tobias\Zend\Form\Doctrine\Element\Exception\InvalidRepositoryResultException;
use Traversable;
use Zend\Stdlib\Guard\ArrayOrTraversableGuardTrait;
use function array_change_key_case;
use function array_key_exists;
use function array_shift;
use function count;
use function current;
use function get_class;
use function gettype;
use function is_callable;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;
use function strtolower;

final class Proxy implements ElementProxy
{
    use ArrayOrTraversableGuardTrait;

    /**
     * @var array|Traversable
     */
    protected $objects;

    /**
     * @var string
     */
    protected $targetClass;

    /**
     * @var array
     */
    protected $valueOptions = [];

    /**
     * @var array
     */
    protected $findMethod = [];

    /**
     * @var
     */
    protected $property;

    /**
     * @var array
     */
    protected $option_attributes = [];

    /**
     * @var callable $labelGenerator A callable used to create a label based on an item in the collection an Entity
     */
    protected $labelGenerator;

    /**
     * @var bool
     */
    protected $isMethod = false;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var bool
     */
    protected $displayEmptyItem = false;

    /**
     * @var string
     */
    protected $emptyItemLabel = '';

    /**
     * @var string|null
     */
    protected $optgroupIdentifier;

    /**
     * @var string|null
     */
    protected $optgroupDefault;

    public function setOptions(array $options): void
    {
        if (isset($options['object_manager'])) {
            $this->setObjectManager($options['object_manager']);
        }
        if (isset($options['target_class'])) {
            $this->setTargetClass($options['target_class']);
        }
        if (isset($options['property'])) {
            $this->setProperty($options['property']);
        }
        if (isset($options['label_generator'])) {
            $this->setLabelGenerator($options['label_generator']);
        }
        if (isset($options['find_method'])) {
            $this->setFindMethod($options['find_method']);
        }
        if (isset($options['is_method'])) {
            $this->setIsMethod($options['is_method']);
        }
        if (isset($options['display_empty_item'])) {
            $this->setDisplayEmptyItem($options['display_empty_item']);
        }
        if (isset($options['empty_item_label'])) {
            $this->setEmptyItemLabel($options['empty_item_label']);
        }
        if (isset($options['option_attributes'])) {
            $this->setOptionAttributes($options['option_attributes']);
        }
        if (isset($options['optgroup_identifier'])) {
            $this->setOptgroupIdentifier($options['optgroup_identifier']);
        }
        if (isset($options['optgroup_default'])) {
            $this->setOptgroupDefault($options['optgroup_default']);
        }
    }

    public function getValueOptions(): array
    {
        if (empty($this->valueOptions)) {
            $this->loadValueOptions();
        }
        return $this->valueOptions;
    }

    /**
     * @return array|Traversable
     * @throws ReflectionException
     */
    public function getObjects(): iterable
    {
        $this->loadObjects();
        return $this->objects;
    }

    /**
     * Set the label for the empty option
     *
     * @param string $emptyItemLabel
     */
    public function setEmptyItemLabel(string $emptyItemLabel): void
    {
        $this->emptyItemLabel = $emptyItemLabel;
    }

    /**
     * @return string
     */
    public function getEmptyItemLabel(): string
    {
        return $this->emptyItemLabel;
    }

    /**
     * @return array
     */
    public function getOptionAttributes(): array
    {
        return $this->option_attributes;
    }

    /**
     * @param array $option_attributes
     */
    public function setOptionAttributes(array $option_attributes): void
    {
        $this->option_attributes = $option_attributes;
    }

    /**
     * Set a flag, whether to include the empty option at the beginning or not
     *
     * @param boolean $displayEmptyItem
     */
    public function setDisplayEmptyItem(bool $displayEmptyItem): void
    {
        $this->displayEmptyItem = $displayEmptyItem;
    }

    /**
     * @return boolean
     */
    public function getDisplayEmptyItem(): bool
    {
        return $this->displayEmptyItem;
    }

    /**
     * Set the object manager
     *
     * @param ObjectManager $objectManager
     */
    public function setObjectManager(ObjectManager $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Get the object manager
     *
     * @return ObjectManager
     */
    public function getObjectManager(): ObjectManager
    {
        return $this->objectManager;
    }

    /**
     * Set the FQCN of the target object
     *
     * @param string $targetClass
     */
    public function setTargetClass(string $targetClass): void
    {
        $this->targetClass = $targetClass;
    }

    /**
     * Get the target class
     *
     * @return string
     */
    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    /**
     * Set the property to use as the label in the options
     *
     * @param string $property
     */
    public function setProperty(string $property): void
    {
        $this->property = $property;
    }

    /**
     * @return mixed
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Set the label generator callable that is responsible for generating labels for the items in the collection
     *
     * @param callable $callable A callable used to create a label based off of an Entity
     *
     * @return void
     * @throws InvalidArgumentException
     *
     */
    public function setLabelGenerator(callable $callable): void
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException(
                'Property "label_generator" needs to be a callable function or a \Closure'
            );
        }
        $this->labelGenerator = $callable;
    }

    /**
     * @return callable|null
     */
    public function getLabelGenerator(): ?callable
    {
        return $this->labelGenerator;
    }

    /**
     * @return string|null
     */
    public function getOptgroupIdentifier(): ?string
    {
        return $this->optgroupIdentifier;
    }

    /**
     * @param string $optgroupIdentifier
     */
    public function setOptgroupIdentifier(string $optgroupIdentifier): void
    {
        $this->optgroupIdentifier = $optgroupIdentifier;
    }

    /**
     * @return string|null
     */
    public function getOptgroupDefault(): ?string
    {
        return $this->optgroupDefault;
    }

    /**
     * @param string $optgroupDefault
     */
    public function setOptgroupDefault(string $optgroupDefault): void
    {
        $this->optgroupDefault = $optgroupDefault;
    }

    /**
     * Set if the property is a method to use as the label in the options
     *
     * @param boolean $method
     */
    public function setIsMethod(bool $method): void
    {
        $this->isMethod = $method;
    }

    public function getIsMethod(): bool
    {
        return $this->isMethod;
    }

    /** Set the findMethod property to specify the method to use on repository
     *
     * @param array $findMethod
     */
    public function setFindMethod(array $findMethod): void
    {
        $this->findMethod = $findMethod;
    }

    /**
     * Get findMethod definition
     *
     * @return array
     */
    public function getFindMethod(): array
    {
        return $this->findMethod;
    }

    /**
     * @param object $targetEntity
     *
     * @return string|null
     */
    protected function generateLabel(object $targetEntity): ?string
    {
        if (null === ($labelGenerator = $this->getLabelGenerator())) {
            return null;
        }
        return $labelGenerator($targetEntity);
    }

    /**
     * @param  $value
     *
     * @return array|mixed|object
     * @throws RuntimeException
     */
    public function getValue($value)
    {
        if (!($om = $this->getObjectManager())) {
            throw new RuntimeException('No object manager was set');
        }
        if (!($targetClass = $this->getTargetClass())) {
            throw new RuntimeException('No target class was set');
        }
        $metadata = $om->getClassMetadata($targetClass);
        if (is_object($value)) {
            if ($value instanceof Collection) {
                $data = [];
                foreach ($value as $object) {
                    $values = $metadata->getIdentifierValues($object);
                    $data[] = array_shift($values);
                }
                $value = $data;
            } else {
                $metadata = $om->getClassMetadata(get_class($value));
                $identifier = $metadata->getIdentifierFieldNames();
                // TODO: handle composite (multiple) identifiers
                if (null !== $identifier && count($identifier) > 1) {
                    //$value = $key;
                } else {
                    $value = current($metadata->getIdentifierValues($value));
                }
            }
        }
        return $value;
    }

    /**
     * Load objects
     *
     * @return void
     * @throws Exception\InvalidRepositoryResultException
     * @throws RuntimeException
     * @throws ReflectionException
     */
    protected function loadObjects(): void
    {
        if (!empty($this->objects)) {
            return;
        }
        $findMethod = $this->getFindMethod();
        if (!$findMethod) {
            $findMethodName = 'findAll';
            $repository = $this->objectManager->getRepository($this->targetClass);
            $objects = $repository->findAll();
        } else {
            if (!isset($findMethod['name'])) {
                throw new RuntimeException('No method name was set');
            }
            $findMethodName = $findMethod['name'];
            $findMethodParams = isset($findMethod['params']) ? array_change_key_case($findMethod['params']) : [];
            $repository = $this->objectManager->getRepository($this->targetClass);
            if (!method_exists($repository, $findMethodName)) {
                throw new RuntimeException(
                    sprintf(
                        'Method "%s" could not be found in repository "%s"',
                        $findMethodName,
                        get_class($repository)
                    )
                );
            }
            $r = new ReflectionMethod($repository, $findMethodName);
            $args = [];
            foreach ($r->getParameters() as $param) {
                if (array_key_exists(strtolower($param->getName()), $findMethodParams)) {
                    $args[] = $findMethodParams[strtolower($param->getName())];
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } elseif (!$param->isOptional()) {
                    throw new RuntimeException(
                        sprintf(
                            'Required parameter "%s" with no default value for method "%s" in repository "%s"'
                            . ' was not provided',
                            $param->getName(),
                            $findMethodName,
                            get_class($repository)
                        )
                    );
                }
            }
            $objects = $r->invokeArgs($repository, $args);
        }
        $this->guardForArrayOrTraversable(
            $objects,
            sprintf('%s::%s() return value', get_class($repository), $findMethodName),
            InvalidRepositoryResultException::class
        );
        $this->objects = $objects;
    }

    /**
     * Load value options
     *
     * @return void
     * @throws RuntimeException
     * @throws ReflectionException
     */
    protected function loadValueOptions(): void
    {
        if (!($om = $this->objectManager)) {
            throw new RuntimeException('No object manager was set');
        }
        if (!($targetClass = $this->targetClass)) {
            throw new RuntimeException('No target class was set');
        }
        $metadata = $om->getClassMetadata($targetClass);
        $identifier = $metadata->getIdentifierFieldNames();
        $objects = $this->getObjects();
        $options = [];
        $optionAttributes = [];
        if ($this->displayEmptyItem) {
            $options[''] = $this->getEmptyItemLabel();
        }
        foreach ($objects as $key => $object) {
            if (null !== ($generatedLabel = $this->generateLabel($object))) {
                $label = $generatedLabel;
            } elseif ($property = $this->property) {
                if ($this->isMethod === false && !$metadata->hasField($property)) {
                    throw new RuntimeException(
                        sprintf(
                            'Property "%s" could not be found in object "%s"',
                            $property,
                            $targetClass
                        )
                    );
                }
                $getter = 'get' . Inflector::classify($property);
                if (!is_callable([$object, $getter])) {
                    throw new RuntimeException(
                        sprintf('Method "%s::%s" is not callable', $this->targetClass, $getter)
                    );
                }
                $label = $object->{$getter}();
            } else {
                if (!is_callable([$object, '__toString'])) {
                    throw new RuntimeException(
                        sprintf(
                            '%s must have a "__toString()" method defined if you have not set a property'
                            . ' or method to use.',
                            $targetClass
                        )
                    );
                }
                $label = (string)$object;
            }
            if (null !== $identifier && count($identifier) > 1) {
                $value = $key;
            } else {
                $value = current($metadata->getIdentifierValues($object));
            }
            foreach ($this->getOptionAttributes() as $optionKey => $optionValue) {
                if (is_string($optionValue)) {
                    $optionAttributes[$optionKey] = $optionValue;
                    continue;
                }
                if (is_callable($optionValue)) {
                    $callableValue = $optionValue($object);
                    $optionAttributes[$optionKey] = (string)$callableValue;
                    continue;
                }
                throw new RuntimeException(
                    sprintf(
                        'Parameter "option_attributes" expects an array of key => value where value is of type'
                        . '"string" or "callable". Value of type "%s" found.',
                        gettype($optionValue)
                    )
                );
            }
            // If no optgroup_identifier has been configured, apply default handling and continue
            if ($this->getOptgroupIdentifier() === null) {
                $options[] = ['label' => $label, 'value' => $value, 'attributes' => $optionAttributes];
                continue;
            }
            // optgroup_identifier found, handle grouping
            $optgroupGetter = 'get' . Inflector::classify($this->getOptgroupIdentifier());
            if (!is_callable([$object, $optgroupGetter])) {
                throw new RuntimeException(
                    sprintf('Method "%s::%s" is not callable', $this->targetClass, $optgroupGetter)
                );
            }
            $optgroup = $object->{$optgroupGetter}();
            // optgroup_identifier contains a valid group-name. Handle default grouping.
            if ($optgroup !== null && trim($optgroup) !== '') {
                $options[$optgroup]['label'] = $optgroup;
                $options[$optgroup]['options'][] = [
                    'label'      => $label,
                    'value'      => $value,
                    'attributes' => $optionAttributes,
                ];
                continue;
            }
            $optgroupDefault = $this->getOptgroupDefault();
            // No optgroup_default has been provided. Line up without a group
            if ($optgroupDefault === null) {
                $options[] = ['label' => $label, 'value' => $value, 'attributes' => $optionAttributes];
                continue;
            }
            // Line up entry with optgroup_default
            $options[$optgroupDefault]['label'] = $optgroupDefault;
            $options[$optgroupDefault]['options'][] = [
                'label'      => $label,
                'value'      => $value,
                'attributes' => $optionAttributes,
            ];
        }
        $this->valueOptions = $options;
    }
}
