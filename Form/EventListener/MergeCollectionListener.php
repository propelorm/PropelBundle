<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\PropelBundle\Form\EventListener;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Event\FilterDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MergeCollectionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(FormEvents::BIND_NORM_DATA => 'onBindNormData');
    }

    public function onBindNormData(FilterDataEvent $event)
    {
        $collection = $event->getForm()->getData();
        $data = $event->getData();

        if (!$collection) {
            $collection = $data;
        } else if (count($data) === 0) {
            $collection->clear();
        } else {
            // merge $data into $collection
            foreach ($collection as $i => $model) {
                if (false === $key = $data->search($model)) {
                    $collection->remove($i);
                } else {
                    $data->remove($key);
                }
            }

            foreach ($data as $model) {
                $collection->append($model);
            }
        }

        $event->setData($collection);
    }
}
