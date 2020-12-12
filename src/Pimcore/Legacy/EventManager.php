<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Legacy;

use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\Object\ClassDefinitionEvent;
use Pimcore\Event\Model\Object\ClassificationStore\CollectionConfigEvent;
use Pimcore\Event\Model\Object\ClassificationStore\GroupConfigEvent;
use Pimcore\Event\Model\Object\ClassificationStore\KeyConfigEvent;
use Pimcore\Event\Model\Object\ClassificationStore\StoreConfigEvent;
use Pimcore\Event\Model\Object\CustomLayoutEvent;
use Pimcore\Event\Model\SearchBackendEvent;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Event\Model\VersionEvent;
use Pimcore\Event\Model\WorkflowEvent;
use Pimcore\Event\SystemEvents;
use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Symfony\Component\EventDispatcher\GenericEvent;

class EventManager extends \Zend_EventManager_EventManager {

    protected static $attachedEvents = [];

    public function attach($event, $callback = null, $priority = 1) {
        // support multiple events passed as array - Zend_EventManager is expected
        // to return an array of listeners if an array of events is passed
        if (is_array($event)) {
            $listeners = [];
            foreach ($event as $eventName) {
                $listeners[] = $this->attach($eventName, $callback, $priority);
            }

            return $listeners;
        }

        $self = $this;
        $eventName = $event;
        $newEventName = "pimcore." . $eventName;

        $listenerMappings = [
            "system.di.init" => "pimcore.system.php_di.init",
            "system.maintenance.activate" => SystemEvents::MAINTENANCE_MODE_ACTIVATE,
            "system.maintenance.deactivate" => SystemEvents::MAINTENANCE_MODE_DEACTIVATE,
            "system.cache.clearOutputCache" => SystemEvents::CACHE_CLEAR_FULLPAGE_CACHE
        ];

        if (isset($listenerMappings[$eventName])) {
            $newEventName = $listenerMappings[$eventName];
        }

        if (array_search($newEventName, self::$attachedEvents) === FALSE) {
            \Pimcore::getEventDispatcher()->addListener($newEventName, function ($event) use ($self, $eventName) {

                $target = null;
                $params = [];

                if ($event instanceof ElementEventInterface) {
                    $target = $event->getElement();
                }

                if ($event instanceof SearchBackendEvent) {
                    $target = $event->getData();
                }

                if ($event instanceof UserRoleEvent) {
                    $target = $event->getUserRole();
                }

                if ($event instanceof VersionEvent) {
                    $target = $event->getVersion();
                }

                if ($event instanceof WorkflowEvent) {
                    $target = $event->getWorkflowManager();
                }

                if ($event instanceof CollectionConfigEvent) {
                    $target = $event->getCollectionConfig();
                }

                if ($event instanceof GroupConfigEvent) {
                    $target = $event->getGroupConfig();
                }

                if ($event instanceof KeyConfigEvent) {
                    $target = $event->getKeyConfig();
                }

                if ($event instanceof StoreConfigEvent) {
                    $target = $event->getStoreConfig();
                }

                if ($event instanceof ClassDefinitionEvent) {
                    $target = $event->getClassDefinition();
                }

                if ($event instanceof CustomLayoutEvent) {
                    $target = $event->getCustomLayout();
                }

                if ($event instanceof ArgumentsAwareTrait) {
                    $params = $event->getArguments();
                }

                if ($event instanceof GenericEvent) {
                    $target = $event->getSubject();
                    $params = $event->getArguments();
                }

                // add the Symfony event for debugging purposes
                $params["__SYMFONY_EVENT"] = $event;

                $returnValueContainerMappings = [
                    "admin.document.get.preSendData" => [
                        "param" => "returnValueContainer",
                        "argument" => "data"
                    ],
                    "admin.asset.get.preSendData" => [
                        "param" => "returnValueContainer",
                        "argument" => "data"
                    ],
                    "admin.class.objectbrickList.preSendData" => [
                        "param" => "returnValueContainer",
                        "argument" => "list"
                    ],
                    "admin.object.treeGetChildsById.preSendData" => [
                        "param" => "returnValueContainer",
                        "argument" => "objects"
                    ],
                    "admin.object.get.preSendData" => [
                        "param" => "returnValueContainer",
                        "argument" => "data"
                    ],
                    "admin.search.list.beforeFilterPrepare" => [
                        "param" => "requestParams",
                        "argument" => "requestParams"
                    ],
                    "admin.search.list.beforeListLoad" => [
                        "param" => "list",
                        "argument" => "data"
                    ],
                    "admin.search.list.afterListLoad" => [
                        "param" => "list",
                        "argument" => "data"
                    ],
                ];

                $isUsingReturnValueContainer = false;
                $returnValueContainer = null;
                if (isset($returnValueContainerMappings[$eventName]) && $event instanceof GenericEvent) {
                    if ($event->hasArgument($returnValueContainerMappings[$eventName]["argument"])) {
                        $dataFromArgument = $event->getArgument($returnValueContainerMappings[$eventName]["argument"]);
                        $returnValueContainer = new \Pimcore\Model\Tool\Admin\EventDataContainer($dataFromArgument);
                        $isUsingReturnValueContainer = true;

                        $params[$returnValueContainerMappings[$eventName]["param"]] = $returnValueContainer;
                    }
                }

                $self->trigger($eventName, $target, $params);

                if ($isUsingReturnValueContainer) {
                    $event->setArgument($returnValueContainerMappings[$eventName]["argument"], $returnValueContainer->getData());
                }
            });
            self::$attachedEvents[] = $newEventName;
        }
        return parent::attach($event, $callback, $priority);
    }

}
