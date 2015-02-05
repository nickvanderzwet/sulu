<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Metadata;

use Massive\Bundle\SearchBundle\Search\Factory;
use Massive\Bundle\SearchBundle\Search\Metadata\IndexMetadataInterface;
use Metadata\Driver\DriverInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Bundle\SearchBundle\Search\SuluSearchEvents;
use Sulu\Bundle\SearchBundle\Search\Event\StructureMetadataLoadEvent;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\PropertyInterface;
use Metadata\ClassMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\ComplexMetadata;
use Massive\Bundle\SearchBundle\Search\Metadata\Field;

/**
 * Provides a Metadata Driver for massive search-bundle
 * @package Sulu\Bundle\SearchBundle\Metadata
 */
class StructureDriver implements DriverInterface
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(Factory $factory, EventDispatcherInterface $eventDispatcher)
    {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * loads metadata for a given class if its derived from StructureInterface
     * @param \ReflectionClass $class
     * @throws \InvalidArgumentException
     * @return IndexMetadataInterface|null
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        if (!$class->implementsInterface('Sulu\Component\Content\StructureInterface')) {
            return null;
        }

        if ($class->isAbstract()) {
            return null;
        }

        /** @var StructureInterface $structure */
        $structure = $class->newInstance();

        $indexMeta = $this->factory->makeIndexMetadata($class->name);

        $indexMeta->setIndexName(new Field('content'));
        $indexMeta->setIdField(new Field('uuid'));
        $indexMeta->setLocaleField(new Field('languageCode'));

        $allProperties = array();

        foreach ($structure->getProperties(true) as $property) {

            if ($property instanceof BlockProperty) {
                $propertyMapping = new ComplexMetadata();
                foreach ($property->getTypes() as $type) {
                    foreach ($type->getChildProperties() as $typeProperty) {
                        $this->mapProperty($typeProperty, $propertyMapping);
                    }
                }

                $indexMeta->addFieldMapping(
                    $property->getName(),
                    array(
                        'type' => 'complex',
                        'mapping' => $propertyMapping,
                        'field' => new Field($property->getName()),
                    )
                );
            } else {
                $this->mapProperty($property, $indexMeta);
            }
        }

        if ($structure->hasTag('sulu.rlp')) {
            $prop = $structure->getPropertyByTagName('sulu.rlp');
            $indexMeta->setUrlField(new Field($prop->getName()));
        }

        if (!$indexMeta->getTitleField()) {
            $prop = $structure->getProperty('title');
            $indexMeta->setTitleField(new Field($prop->getName()));

            $indexMeta->addFieldMapping(
                $prop->getName(),
                array(
                    'type' => 'string',
                    'field' => new Field($prop->getName())
                )
            );
        }

        // index the webspace
        $indexMeta->addFieldMapping('webspaceKey', array('type' => 'string', 'field' => new Field('webspaceKey')));

        $this->eventDispatcher->dispatch(
            SuluSearchEvents::STRUCTURE_LOAD_METADATA,
            new StructureMetadataLoadEvent($structure, $indexMeta)
        );

        return $indexMeta;
    }

    private function mapProperty(PropertyInterface $property, $metadata)
    {
        if ($property->hasTag('sulu.search.field')) {
            $tag = $property->getTag('sulu.search.field');
            $tagAttributes = $tag->getAttributes();

            if ($metadata instanceof ClassMetadata && isset($tagAttributes['role'])) {
                switch ($tagAttributes['role']) {
                    case 'title':
                        $metadata->setTitleField(new Field($property->getName()));
                        $metadata->addFieldMapping($property->getName(), array('field' => new Field($property->getName()), 'type' => 'string'));
                        break;
                    case 'description':
                        $metadata->setDescriptionField(new Field($property->getName()));
                        $metadata->addFieldMapping($property->getName(), array('field' => new Field($property->getName()), 'type' => 'string'));
                        break;
                    case 'image':
                        $metadata->setImageUrlField(new Field($property->getName()));
                        break;
                    default:
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Unknown search field role "%s", role must be one of "%s"',
                                $tagAttributes['role'],
                                implode(', ', array('title', 'description', 'image'))
                            )
                        );
                }
            } elseif (!isset($tagAttributes['index']) || $tagAttributes['index'] !== 'false') {
                $metadata->addFieldMapping(
                    $property->getName(),
                    array(
                        'type' => isset($tagAttributes['type']) ? $tagAttributes['type'] : 'string',
                        'field' => new Field($property->getName()), 
                    )
                );
            }
        }
    }
}
