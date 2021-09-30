<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2021, numero2 - Agentur für digitales Marketing GbR
 */

namespace numero2\TagsBundle\EventSubscriber;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class KernelRequestSubscriber implements EventSubscriberInterface {


    protected $scopeMatcher;


    public function __construct(ScopeMatcher $scopeMatcher) {
        $this->scopeMatcher = $scopeMatcher;
    }

    public static function getSubscribedEvents() {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $e): void {

        $request = $e->getRequest();

        if ($this->scopeMatcher->isBackendRequest($request)) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/tags/js/backend.js';
        }
    }
}