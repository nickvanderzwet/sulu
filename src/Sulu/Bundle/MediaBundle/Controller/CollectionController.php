<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Controller;

use DateTime;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes collections available through a REST API
 * @package Sulu\Bundle\MediaBundle\Controller
 */
class CollectionController extends RestController implements ClassResourceInterface
{
    /**
     * {@inheritdoc}
     */
    protected $entityName = 'SuluMediaBundle:Collection';

    /**
     * {@inheritdoc}
     */
    protected $unsortable = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsDefault = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsExcluded = array('lft', 'rgt', 'depth');

    /**
     * {@inheritdoc}
     */
    protected $fieldsHidden = array('created');

    /**
     * {@inheritdoc}
     */
    protected $fieldsRelations = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsSortOrder = array(0 => 'id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsTranslationKeys = array('id' => 'public.id');

    /**
     * {@inheritdoc}
     */
    protected $fieldsEditable = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsValidation = array();

    /**
     * {@inheritdoc}
     */
    protected $fieldsWidth = array();

    /**
     *
     * {@inheritdoc}
     */
    protected $bundlePrefix = 'media.collection.';

    /**
     * returns all fields that can be used by list
     * @Get("collection/fields")
     * @return mixed
     */
    public function getFieldsAction()
    {
        return $this->responseFields();
    }

    /**
     * persists a setting
     * @Put("collection/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
    }

    /**
     * Shows a single collection with the given id
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id, Request $request)
    {
        $userLocale = $this->getUser()->getLocale();
        $locale = $request->get('locale');
        if ($locale) {
            $userLocale = $locale;
        }

        $collection = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findCollectionById($id, true);

        if (!$collection) {
            $exception = new EntityNotFoundException($this->entityName, $id);
            // Return a 404 together with an error message, given by the exception, if the entity is not found
            $view = $this->view(
                $exception->toArray(),
                404
            );
        } else {
            $view = $this->view(
                array_merge(
                    array(
                        '_links' => array(
                            'self' => $request->getRequestUri()
                        )
                    ),
                    $this->flatCollection($collection, $userLocale)
                )
                , 200);
        }

        return $this->handleView($view);
    }

    /**
     * lists all collections
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $userLocale = $this->getUser()->getLocale();
        $locale = $request->get('locale');
        if ($locale) {
            $userLocale = $locale;
        }

        $parentId = $request->get('parent');
        $depth = $request->get('depth');

        $collections = $this->getDoctrine()->getRepository($this->entityName)->findCollections($parentId, $depth);

        $collections = $this->flatCollections($collections, $userLocale);

        $view = $this->view($this->createHalResponse($collections), 200);

        return $this->handleView($view);
    }

    /**
     * flat the collection array
     * @param $collection
     * @param $locale
     * @return array
     */
    protected function flatCollection ($collection, $locale)
    {
        $flatCollection = array();
        $flatCollection['locale'] = $locale;
        $mediaCount = 0;
        foreach ($collection as $key => $value) {
            $setKeyValue = true;
            switch ($key) {
                case 'style':
                    if ($value) {
                        $value = json_decode($value, true);
                    }
                    break;
                case 'metas':
                    $metaSet = false;
                    foreach ($value as $meta) {
                        if ($meta['locale'] == $locale) {
                            $metaSet = true;
                            foreach ($meta as $metaKey => $metaValue) {
                                if ($metaKey !== 'locale') {
                                    $flatCollection[$metaKey] = $metaValue;
                                }
                            }
                        }
                    }
                    if (!$metaSet) {
                        if (isset($value[0])) {
                            foreach ($value[0] as $metaKey => $metaValue) {
                                $flatCollection[$metaKey] = $metaValue;
                            }
                        }
                    }

                    $setKeyValue = false;
                    break;
                case 'children':
                    $newValue = array();
                    if ($value) {
                        foreach ($value as $children) {
                            array_push($newValue, $children['id']);
                            if ($children['medias']) {
                                $mediaCount += count($children['medias']);
                            }
                        }
                    }
                    $value = $newValue;
                    break;
                case 'parent':
                case 'type':
                    if ($value) {
                        $value = $value['id'];
                    }
                    break;
                case 'changer':
                case 'creator':
                    if ($value) {
                        if (isset($value['contact']['firstName'])) {
                            $value = $value['contact']['firstName'] . ' ' . $value['contact']['lastName'];
                        }
                    }
                    break;
                case 'changed':
                case 'created':
                    if ($value) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    break;
                case 'lft':
                case 'rgt':
                case 'depth':
                    $setKeyValue = false;
                    break;
                case 'medias':
                    if ($value) {
                        $mediaCount += count($value);
                    }
                    $setKeyValue = false;
                    break;
                default:
                    if (is_string($value) || is_int($value)) {
                        $flatCollection[$key] = $value;
                    } else {
                        $setKeyValue = false;
                    }
                    break;
            }
            if ($setKeyValue) {
                $flatCollection[$key] = $value;
            }
        }
        $flatCollection['mediaNumber'] = $mediaCount;

        return $flatCollection;
    }

    /**
     * collections to an flat collection array
     * @param $collections
     * @param $locale
     * @return array
     */
    protected function flatCollections ($collections, $locale)
    {
        $flatCollections = array();

        foreach ($collections as $collection) {
            $flatCollection = $this->flatCollection($collection, $locale);
            array_push($flatCollections, $flatCollection);
        }

        return $flatCollections;
    }

    /**
     * Creates a new collection
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $em = $this->getDoctrine()->getManager();

            $collection = new Collection();

            // set style
            $collection->setStyle($request->get('style'));

            // set type
            $typeData = $request->get('type');
            /** @var CollectionType $type */
            $type = $this->getDoctrine()->getRepository('SuluMediaBundle:CollectionType')->find($typeData['id']);

            if (!$type) {
                throw new EntityNotFoundException($this->entityName, $typeData['id']);
            }

            $collection->setType($type);

            // set parent
            $parentData = $request->get('parent');
            if ($this->checkDataForId($parentData)) {
                /** @var Collection $parent */
                $parent = $this->getDoctrine()
                    ->getRepository($this->entityName)
                    ->findCollectionById($parentData['id']);

                if (!$parent) {
                    throw new EntityNotFoundException($this->entityName, $parentData['id']);
                }

                $collection->setParent($parent);
            } else {
                $collection->setParent(null);
            }

            // set creator / changer
            $collection->setCreated(new DateTime());
            $collection->setChanged(new DateTime());
            $collection->setCreator($this->getUser());
            $collection->setChanger($this->getUser());

            // set metas
            $metas = $request->get('metas');
            if (!empty($metas)) {
                foreach ($metas as $metaData) {
                    $this->addMetas($collection, $metaData);
                }
            }

            $em->persist($collection);

            $em->flush();

            $view = $this->view($collection, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Edits the existing collection with the given id
     * @param integer $id The id of the collection to update
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function putAction($id, Request $request)
    {
        $collectionEntity = 'SuluMediaBundle:Collection';

        try {
            /** @var Collection $collection */
            $collection = $this->getDoctrine()
                ->getRepository($collectionEntity)
                ->findCollectionById($id);

            if (!$collection) {
                throw new EntityNotFoundException($collectionEntity, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                // set style
                $collection->setStyle($request->get('style'));

                // set type
                $typeData = $request->get('type');
                /** @var CollectionType $type */
                $type = $this->getDoctrine()->getRepository('SuluMediaBundle:CollectionType')->find($typeData['id']);

                if (!$type) {
                    throw new EntityNotFoundException($this->entityName, $typeData['id']);
                }

                $collection->setType($type);

                // set parent
                $parentData = $request->get('parent');
                if ($this->checkDataForId($parentData)) {
                    /** @var Collection $parent */
                    $parent = $this->getDoctrine()
                        ->getRepository($this->entityName)
                        ->findCollectionById($parentData['id']);

                    if (!$parent) {
                        throw new EntityNotFoundException($this->entityName, $parentData['id']);
                    }

                    $collection->setParent($parent);
                } else {
                    $collection->setParent(null);
                }

                // set changed
                $collection->setChanged(new DateTime());
                $user = $this->getUser();
                $collection->setChanger($user);

                // process details
                if (!$this->processMetas($collection, $request->get('metas'))) {
                    throw new RestException('Updating dependencies is not possible', 0);
                }

                $em->flush();
                $view = $this->view($collection, 200);
            }
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Delete a collection with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $entityName = 'SuluMediaBundle:Collection';

            /* @var Collection $collection */
            $collection = $this->getDoctrine()
                ->getRepository($entityName)
                ->findCollectionByIdForDelete($id);

            if (!$collection) {
                throw new EntityNotFoundException($entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();

            $em->remove($collection);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Check given data for a not empty id
     * @param $data
     * @return bool
     */
    protected function checkDataForId($data)
    {
        if ($data != null && isset($data['id']) && $data['id'] != 'null' && $data['id'] != '') {
            return true;
        }
        return false;
    }

    /**
     * Process all metas from request
     * @param Collection $collection The collection on which is worked
     * @param mixed $metas Request meta data to process
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processMetas(Collection $collection, $metas)
    {
        $delete = function ($meta) use ($collection) {
            $collection->removeMeta($meta);

            return true;
        };

        $update = function ($meta, $matchedEntry) {
            return $this->updateMeta($meta, $matchedEntry);
        };

        $add = function ($meta) use ($collection) {
            $this->addMetas($collection, $meta);

            return true;
        };

        return $this->processPut($collection->getMetas(), $metas, $delete, $update, $add);
    }

    /**
     * Adds META to a collection
     * @param Collection $collection
     * @param $metaData
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    private function addMetas(Collection $collection, $metaData)
    {
        $em = $this->getDoctrine()->getManager();
        $metaEntity = 'SuluMediaBundle:CollectionMeta';

        if (isset($metaData['id'])) {
            throw new EntityIdAlreadySetException($metaEntity, $metaData['id']);
        } else {
            $meta = new CollectionMeta();
            $meta->setCollection($collection);
            $meta->setTitle($metaData['title']);
            $meta->setDescription($metaData['description']);
            $meta->setLocale($metaData['locale']);

            $em->persist($meta);
            $collection->addMeta($meta);
        }
    }

    /**
     * Updates the given meta
     * @param CollectionMeta $meta The collection meta object to update
     * @param string $entry The entry with the new data
     * @return bool True if successful, otherwise false
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    protected function updateMeta(CollectionMeta $meta, $entry)
    {
        $success = true;

        $meta->setTitle($entry['title']);
        $meta->setDescription($entry['description']);
        $meta->setLocale($entry['locale']);

        return $success;
    }
}
