<?php

namespace Propel\PropelBundle\Form\EventListener;

use Symfony\Component\Form\Events;
use Symfony\Component\Form\Event\FilterDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MergeCollectionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return Events::onBindNormData;
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
            foreach ($collection as $model) {
                if (!$data->contains($model)) {
                    $collection->remove($model);
                } else {
                    $data->remove($model);
                }
            }

            foreach ($data as $model) {
                $collection->append($model);
            }
        }

        $event->setData($collection);
    }
}
