<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Form\Doctrine\Element;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use ReflectionProperty;
use Tobias\Zend\Form\Doctrine\Element\ElementProxy;
use TobiasTest\Zend\Form\Doctrine\Element\TestAsset\FormObject;
use Zend\Form\ElementInterface;
use function get_class;

trait ProxyPrepareTrait
{
    /** @var ElementInterface */
    private $element;

    /** @var MockObject */
    private $metadata;

    private function prepareProxy(): void
    {
        $objectClass = FormObject::class;
        $objectOne = new FormObject();
        $objectTwo = new FormObject();
        $objectOne->setId(1)
            ->setUsername('object one username')
            ->setPassword('object one password')
            ->setEmail('object one email')
            ->setFirstname('object one firstname')
            ->setSurname('object one surname');
        $objectTwo->setId(2)
            ->setUsername('object two username')
            ->setPassword('object two password')
            ->setEmail('object two email')
            ->setFirstname('object two firstname')
            ->setSurname('object two surname');
        $result = new ArrayCollection([$objectOne, $objectTwo]);
        $this->values = $result;
        /** @var MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturnCallback(
                static function () use ($objectOne, $objectTwo) {
                    $input = func_get_args();
                    $input = array_shift($input);
                    if ($input == $objectOne) {
                        return ['id' => 1];
                    }

                    if ($input == $objectTwo) {
                        return ['id' => 2];
                    }
                    return [];
                }
            );
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository->expects($this->any())
            ->method('findAll')
            ->willReturn($result);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->equalTo($objectClass))
            ->willReturn($metadata);
        $objectManager
            ->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo($objectClass))
            ->willReturn($objectRepository);
        $this->element->getProxy()->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
        ]);
        $this->metadata = $metadata;
    }

    /**
     * Proxy should stay read only, use with care
     *
     * @param ElementProxy                $proxy
     * @param MockObject|ElementInterface $element
     *
     * @throws ReflectionException
     */
    private function setProxyViaReflection($proxy, $element = null): void
    {
        if (!$element) {
            $element = $this->element;
        }
        $prop = new ReflectionProperty(get_class($this->element), 'proxy');
        $prop->setAccessible(true);
        $prop->setValue($element, $proxy);
    }
}
