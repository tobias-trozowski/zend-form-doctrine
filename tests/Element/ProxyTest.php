<?php
declare(strict_types=1);

namespace TobiasTest\Zend\Form\Doctrine\Element;

use function get_class;
use RuntimeException;
use stdClass;
use Tobias\Zend\Form\Doctrine\Element\Exception\InvalidRepositoryResultException;
use Tobias\Zend\Form\Doctrine\Element\Proxy;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;
use TobiasTest\Zend\Form\Doctrine\Element\TestAsset\FormObject;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use TypeError;

final class ProxyTest extends TestCase
{
    /**
     * @var \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected $metadata;
    /**
     * @var Proxy
     */
    protected $proxy;
    /**
     * {@inheritDoc}.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->proxy = new Proxy();
    }
    public function testExceptionThrownForMissingObjectManager(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No object manager was set');
        $this->proxy->setOptions(['target_class' => FormObject::class]);
        $this->proxy->getValueOptions();
    }
    public function testExceptionThrownForMissingTargetClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No target class was set');
        $this->proxy->setOptions([
            'object_manager' => $this->createMock(ObjectManager::class),
        ]);
        $this->proxy->getValueOptions();
    }
    public function testExceptionThrownForMissingFindMethodName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No method name was set');
        $objectClass = FormObject::class;
        $metadata    = $this->createMock(ClassMetadata::class);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo($objectClass))
            ->willReturn($metadata);
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
            'find_method'    => ['no_name'],
        ]);
        $this->proxy->getValueOptions();
    }
    public function testExceptionFindMethodNameNotExistentInRepository(): void
    {
        $objectClass = FormObject::class;
        $metadata    = $this->createMock(ClassMetadata::class);
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo($objectClass))
            ->willReturn($metadata);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($objectClass))
            ->willReturn($objectRepository);
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
            'find_method'    => ['name' => 'NotExistent'],
        ]);
        $this->expectException(
            'RuntimeException'
        );
        $this->expectExceptionMessage(
            'Method "NotExistent" could not be found in repository "' . get_class($objectRepository) . '"'
        );
        $this->proxy->getValueOptions();
    }
    public function testExceptionThrownForMissingRequiredParameter(): void
    {
        $objectClass = FormObject::class;
        $metadata    = $this->createMock(ClassMetadata::class);
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo($objectClass))
            ->willReturn($metadata);
        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($objectClass))
            ->willReturn($objectRepository);
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
            'find_method'    => [
                'name' => 'findBy',
                'params' => [],
            ],
        ]);
        $this->expectException(
            'RuntimeException'
        );
        $this->expectExceptionMessage(
            'Required parameter "criteria" with no default value for method "findBy" in repository "'
            . get_class($objectRepository) . '" was not provided'
        );
        $this->proxy->getValueOptions();
    }
    public function testToStringIsUsedForGetValueOptions(): void
    {
        $this->prepareProxy();
        $result = $this->proxy->getValueOptions();
        $this->assertEquals($result[0]['label'], 'object one username');
        $this->assertEquals($result[1]['label'], 'object two username');
        $this->assertEquals($result[0]['value'], 1);
        $this->assertEquals($result[1]['value'], 2);
    }
    public function testPropertyGetterUsedForGetValueOptions(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions(['property' => 'password']);
        $this->metadata->expects($this->exactly(2))
            ->method('hasField')
            ->with($this->equalTo('password'))
            ->willReturn(true);
        $result = $this->proxy->getValueOptions();
        $this->assertEquals($result[0]['label'], 'object one password');
        $this->assertEquals($result[1]['label'], 'object two password');
        $this->assertEquals($result[0]['value'], 1);
        $this->assertEquals($result[1]['value'], 2);
    }
    public function testPublicPropertyUsedForGetValueOptions(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions(['property' => 'email']);
        $this
            ->metadata
            ->expects($this->exactly(2))
            ->method('hasField')
            ->with($this->equalTo('email'))
            ->willReturn(true);
        $result = $this->proxy->getValueOptions();
        $this->assertEquals($result[0]['label'], 'object one email');
        $this->assertEquals($result[1]['label'], 'object two email');
        $this->assertEquals($result[0]['value'], 1);
        $this->assertEquals($result[1]['value'], 2);
    }
    public function testIsMethodOptionUsedForGetValueOptions(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions([
            'property'  => 'name',
            'is_method' => true,
        ]);
        $this->metadata->expects($this->never())
            ->method('hasField');
        $result = $this->proxy->getValueOptions();
        $this->assertEquals($result[0]['label'], 'object one firstname object one surname');
        $this->assertEquals($result[1]['label'], 'object two firstname object two surname');
        $this->assertEquals($result[0]['value'], 1);
        $this->assertEquals($result[1]['value'], 2);
    }
    public function testDisplayEmptyItemAndEmptyItemLabelOptionsUsedForGetValueOptions(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions([
            'display_empty_item' => true,
            'empty_item_label'   => '---',
        ]);
        $result = $this->proxy->getValueOptions();
        $this->assertArrayHasKey('', $result);
        $this->assertEquals($result[''], '---');
    }
    public function testLabelGeneratorUsedForGetValueOptions(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions([
            'label_generator' => static function ($targetEntity) {
                return $targetEntity->getEmail();
            },
        ]);
        $this->metadata->expects($this->never())
            ->method('hasField');
        $result = $this->proxy->getvalueOptions();
        $this->assertEquals($result[0]['label'], 'object one email');
        $this->assertEquals($result[1]['label'], 'object two email');
        $this->assertEquals($result[0]['value'], 1);
        $this->assertEquals($result[1]['value'], 2);
    }
    public function testExceptionThrownForNonCallableLabelGenerator(): void
    {
        $this->prepareProxy();
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('must be callable, string given');
        $this->proxy->setOptions(['label_generator' => 'I throw an InvalidArgumentException']);
    }
    public function testUsingOptionAttributesOfTypeString(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions([
            'option_attributes' => [
                'class' => 'foo',
                'lang' => 'en',
            ],
        ]);
        $options = $this->proxy->getValueOptions();
        $expectedAttributes = [
            'class' => 'foo',
            'lang' => 'en',
        ];
        $this->assertCount(2, $options);
        $this->assertArrayHasKey('attributes', $options[0]);
        $this->assertArrayHasKey('attributes', $options[1]);
        $this->assertEquals($expectedAttributes, $options[0]['attributes']);
        $this->assertEquals($expectedAttributes, $options[1]['attributes']);
    }
    public function testUsingOptionAttributesOfTypeCallableReturningString(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions([
            'option_attributes' => [
                'data-id' => static function ($object) {
                    return $object->getId();
                },
            ],
        ]);
        $options = $this->proxy->getValueOptions();
        $this->assertCount(2, $options);
        $this->assertArrayHasKey('attributes', $options[0]);
        $this->assertArrayHasKey('attributes', $options[1]);
        $this->assertEquals(['data-id' => 1], $options[0]['attributes']);
        $this->assertEquals(['data-id' => 2], $options[1]['attributes']);
    }
    public function testRuntimeExceptionOnWrongOptionAttributesValue(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions([
            'option_attributes' => [
                'data-id' => new stdClass(['id' => 1]),
            ],
        ]);
        $this->expectException('RuntimeException');
        $this->proxy->getValueOptions();
    }
    public function testCanWorkWithEmptyTables(): void
    {
        $this->prepareEmptyProxy();
        $result = $this->proxy->getValueOptions();
        $this->assertEquals([], $result);
    }
    public function testCanWorkWithEmptyDataReturnedAsArray(): void
    {
        $this->prepareEmptyProxy([]);
        $result = $this->proxy->getValueOptions();
        $this->assertEquals([], $result);
    }
    public function testExceptionThrownForNonTraversableResults(): void
    {
        $this->prepareEmptyProxy(new stdClass());
        $this->expectException(InvalidRepositoryResultException::class);
        $this->expectExceptionMessage(
            'return value must be an array or Traversable'
        );
        $this->proxy->getValueOptions();
    }
    public function testUsingFindMethod(): void
    {
        $this->prepareFilteredProxy();
        $this->proxy->getValueOptions();
    }
    /**
     * A \RuntimeException should be thrown when the optgroup_identifier option does not reflect an existing method
     * within the target object
     */
    public function testExceptionThrownWhenOptgroupIdentifiesNotCallable(): void
    {
        $this->prepareProxyWithOptgroupPreset();
        $this->proxy->setOptions([
            'optgroup_identifier' => 'NonExistantFunctionName',
        ]);
        $this->expectException('RuntimeException');
        $this->proxy->getValueOptions();
    }
    /**
     * Tests the following case:
     *
     * An optgroup identifier has been given.
     * Two entries have the optgroup value "Group One".
     * One entry has the optgroup value "Group Two".
     *
     * Entries should be grouped accordingly under the respective keys.
     */
    public function testValueOptionsGeneratedProperlyWithOptgroups(): void
    {
        $this->prepareProxyWithOptgroupPreset();
        $this->proxy->setOptions([
            'optgroup_identifier' => 'optgroup',
        ]);
        $valueOptions = $this->proxy->getValueOptions();
        $expectedOutput = [
            'Group One' => [
                'label' => 'Group One',
                'options' => [
                    0 => [
                        'label' => 'object one username',
                        'value' => 1,
                        'attributes' => [],
                    ],
                    1 => [
                        'label' => 'object two username',
                        'value' => 2,
                        'attributes' => [],
                    ],
                ],
            ],
            'Group Two' => [
                'label' => 'Group Two',
                'options' => [
                    0 => [
                        'label' => 'object three username',
                        'value' => 3,
                        'attributes' => [],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedOutput, $valueOptions);
    }
    /**
     * Tests the following case:
     *
     * An optgroup identifier has been given.
     * Both entries do not have an optgroup value.
     * optgroup_default has been configured.
     *
     * Both entries should be grouped under the optgroup_default key.
     */
    public function testEmptyOptgroupValueBelongsToOptgroupDefaultIfConfigured(): void
    {
        $this->prepareProxy();
        $this->proxy->setOptions([
            'optgroup_identifier' => 'optgroup',
            'optgroup_default'    => 'Others',
        ]);
        $valueOptions = $this->proxy->getValueOptions();
        $expectedOutput = [
            'Others' => [
                'label' => 'Others',
                'options' => [
                    0 => [
                        'label' => 'object one username',
                        'value' => 1,
                        'attributes' => [],
                    ],
                    1 => [
                        'label' => 'object two username',
                        'value' => 2,
                        'attributes' => [],
                    ],
                ],
            ],
        ];
        $this->assertEquals($expectedOutput, $valueOptions);
    }
    /**
     * Tests the following case:
     *
     * An optgroup identifier has been given.
     * One entry has a valid value.
     * A second entry has a null value.
     * No optgroup_default has been configured.
     *
     * Entry one should be grouped, entry two shouldn't be.
     */
    public function testEmptyOptgroupValueBelongsToNoOptgroupIfNotConfigured(): void
    {
        $this->prepareProxyWithOptgroupPresetThatHasPartiallyEmptyOptgroupValues();
        $this->proxy->setOptions([
            'optgroup_identifier' => 'optgroup',
        ]);
        $valueOptions = $this->proxy->getValueOptions();
        $expectedOutput = [
            'Group One' => [
                'label' => 'Group One',
                'options' => [
                    0 => [
                        'label' => 'object one username',
                        'value' => 1,
                        'attributes' => [],
                    ],
                ],
            ],
            0 => [
                'label' => 'object two username',
                'value' => 2,
                'attributes' => [],
            ],
        ];
        $this->assertEquals($expectedOutput, $valueOptions);
    }
    protected function prepareProxy(): void
    {
        $objectClass = FormObject::class;
        $objectOne   = new FormObject();
        $objectTwo   = new FormObject();
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
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
        ]);
        $this->metadata = $metadata;
    }
    protected function prepareProxyWithOptgroupPreset(): void
    {
        $objectClass = FormObject::class;
        $objectOne   = new FormObject();
        $objectTwo   = new FormObject();
        $objectThree = new FormObject();
        $objectOne->setId(1)
            ->setUsername('object one username')
            ->setPassword('object one password')
            ->setEmail('object one email')
            ->setFirstname('object one firstname')
            ->setSurname('object one surname')
            ->setOptgroup('Group One');
        $objectTwo->setId(2)
            ->setUsername('object two username')
            ->setPassword('object two password')
            ->setEmail('object two email')
            ->setFirstname('object two firstname')
            ->setSurname('object two surname')
            ->setOptgroup('Group One');
        $objectThree->setId(3)
            ->setUsername('object three username')
            ->setPassword('object three password')
            ->setEmail('object three email')
            ->setFirstname('object three firstname')
            ->setSurname('object three surname')
            ->setOptgroup('Group Two');
        $result = new ArrayCollection([$objectOne, $objectTwo, $objectThree]);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->expects($this->any())
            ->method('getIdentifierValues')
            ->willReturnCallback(
                static function () use ($objectOne, $objectTwo, $objectThree) {
                    $input = func_get_args();
                    $input = array_shift($input);
                    if ($input == $objectOne) {
                        return ['id' => 1];
                    }

                    if ($input == $objectTwo) {
                        return ['id' => 2];
                    }

                    if ($input == $objectThree) {
                        return ['id' => 3];
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
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
        ]);
        $this->metadata = $metadata;
    }
    protected function prepareProxyWithOptgroupPresetThatHasPartiallyEmptyOptgroupValues(): void
    {
        $objectClass = FormObject::class;
        $objectOne   = new FormObject();
        $objectTwo   = new FormObject();
        $objectOne->setId(1)
            ->setUsername('object one username')
            ->setPassword('object one password')
            ->setEmail('object one email')
            ->setFirstname('object one firstname')
            ->setSurname('object one surname')
            ->setOptgroup('Group One');
        $objectTwo->setId(2)
            ->setUsername('object two username')
            ->setPassword('object two password')
            ->setEmail('object two email')
            ->setFirstname('object two firstname')
            ->setSurname('object two surname');
        $result = new ArrayCollection([$objectOne, $objectTwo]);
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
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
        ]);
        $this->metadata = $metadata;
    }
    protected function prepareFilteredProxy(): void
    {
        $objectClass = FormObject::class;
        $objectOne   = new FormObject();
        $objectTwo   = new FormObject();
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
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->expects($this->exactly(2))
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
        $objectRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturn($result);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo($objectClass))
            ->willReturn($metadata);
        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($objectClass))
            ->willReturn($objectRepository);
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
            'find_method' => [
                'name' => 'findBy',
                'params' => [
                    'criteria' => ['email' => 'object one email'],
                ],
            ],
        ]);
        $this->metadata = $metadata;
    }
    public function prepareEmptyProxy($result = null): void
    {
        if ($result === null) {
            $result = new ArrayCollection();
        }
        $objectClass      = FormObject::class;
        $metadata         = $this->createMock(ClassMetadata::class);
        $objectRepository = $this->createMock(ObjectRepository::class);
        $objectRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($result);
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo($objectClass))
            ->willReturn($metadata);
        $objectManager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo($objectClass))
            ->willReturn($objectRepository);
        $this->proxy->setOptions([
            'object_manager' => $objectManager,
            'target_class'   => $objectClass,
        ]);
        $this->metadata = $metadata;
    }
}
