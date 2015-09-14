<?php

namespace BackBuilder\Bundle\GSABundle\Listener;

use BackBuilder\Event\Event,
    Symfony\Component\HttpFoundation\Request;

class SearchTextboxListener
{

    /**
     * @var \BackBuilder\BBApplication
     */
    private static $bbapp;
    private static $target;
    private static $renderer;

    private static function init(Event $event)
    {
        self::reset();
        if (null === self::$bbapp = $event->getDispatcher()->getApplication()) return false;
        if (null === self::$target = $event->getTarget()) return false;
        if (null === self::$renderer = $event->getEventArgs()) return false;

        return true;
    }

    private static function reset()
    {
        self::$bbapp = null;
        self::$target = null;
        self::$renderer = null;
    }

    public static function onPrerenderSearch(Event $event)
    {
        if (!self::initOnPrerenderSearch($event)) {
            return;
        }

        /**
         * @var Request
         */
        $request = self::$bbapp->getRequest();
        $query = $request->get('q');

        self::$target->setParam('query', $query);

        if (self::$target->getParam('autocomplete:array:checked'))
        {
           self::$renderer->addFooterScript('/ressources/js/external/js_autocomplete.js');
        }
    }

    private static function initOnPrerenderSearch(Event $event)
    {
        if (self::init($event)) {
            if (false === is_a(self::$target, '\BackBuilder\ClassContent\Block\search_textbox')) return false;
        } else {
            return false;
        }

        return true;
    }

}