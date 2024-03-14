<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Events;
use Contao\Input;
use Contao\Module;
use Contao\ModuleEventlist;
use Contao\PageModel;
use Contao\StringUtil;
use numero2\TagsBundle\ModuleEventlistRelatedTags;
use numero2\TagsBundle\ModuleEventlistTags;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\Util\TagUtil;


class EventsListener {


    /**
     * Parse and filter all events
     *
     * @param array $events
     * @param array $calendars
     * @param int $timeStart
     * @param int $timeEnd
     * @param Contao\Module $module
     *
     * @return array
     *
     * @Hook("getAllEvents")
     */
    public function getAllEvents( array $events, array $calendars, int $timeStart, int $timeEnd, Module $module ): array {

        if( $module instanceof ModuleEventlistRelatedTags ) {

            $alias = Input::get('events');
            $currentEvent = CalendarEventsModel::findPublishedByParentAndIdOrAlias($alias, $calendars);

            $eventTags = TagsModel::findByIdForFieldAndTable($currentEvent->id, 'tags', CalendarEventsModel::getTable());

            $tagIds = $eventTags->fetchEach('id');

            $events = $this->filterEventsByTags($events, $tagIds, $module);

            // also remove current event
            foreach( $events as $day => $tstamps ) {
                foreach( $tstamps as $ts => $entries ) {
                    foreach( $entries as $i => $entry ) {

                        if( $entry['id'] === $currentEvent->id ) {
                            unset($events[$day][$ts][$i]);

                            if( empty($events[$day][$ts]) ) {
                                unset($events[$day][$ts]);
                            }

                            if( empty($events[$day]) ) {
                                unset($events[$day]);
                            }
                        }
                    }
                }
            }

        } else if( $module instanceof ModuleEventlistTags ) {

            $tags = StringUtil::deserialize($module->event_tags, true);

            $events = $this->filterEventsByTags($events, $tags, $module);

        } else if( $module instanceof ModuleEventlist ) {

            if( empty($module->ignoreTags) && !empty(Input::get('tag')) ) {

                $tags = TagUtil::getTagsFromUrl();

                // get tags id
                $tagIds = [];
                foreach( $tags as $tag ) {
                    $oTag = TagsModel::findOneByTag($tag);
                    if( $oTag ) {
                        $tagIds[] = $oTag->id;
                    }
                }

                $events = $this->filterEventsByTags($events, $tagIds, $module);
            }
        }


        // parse events
        foreach( $events as $day => $tstamps ) {
            foreach( $tstamps as $ts => $entries ) {
                foreach( $entries as $i => $entry ) {

                    $events[$day][$ts][$i] = $this->parseEvent($entry, $module);
                }
            }
        }

        return $events;
    }


    /**
     * Filter out events based on given tags
     *
     * @param array $events
     * @param array $tags
     * @param Contao\Module $module
     *
     * @return array
     */
    private function filterEventsByTags( array $events, array $tagsIds, Module $module ): array {

        $blnMultiple = !empty($module->tags_match_all);

        if( !empty($tagsIds) ) {
            foreach( $events as $day => $tstamps ) {
                foreach( $tstamps as $ts => $entries ) {
                    foreach( $entries as $i => $entry ) {

                        $e = &$events[$day][$ts][$i];

                        $eventTags = TagsModel::findByIdForFieldAndTable($e['id'], 'tags', CalendarEventsModel::getTable());

                        if( empty($eventTags) ) {
                            $eventTags = [];
                        } else {
                            $eventTags = $eventTags->fetchEach('id');
                        }

                        if( $blnMultiple ) {
                            if( count(array_intersect($tagsIds, $eventTags)) === count($tagsIds) ) {
                                continue;
                            }
                        } else {
                            if( count(array_intersect($tagsIds, $eventTags)) ) {
                                continue;
                            }
                        }

                        unset($events[$day][$ts][$i]);

                        if( empty($events[$day][$ts]) ) {
                            unset($events[$day][$ts]);
                        }

                        if( empty($events[$day]) ) {
                            unset($events[$day]);
                        }
                    }
                }
            }
        }

        return $events;
    }



    /**
     * Adds our additional data to the event
     *
     * @param array $aEvent
     * @param Contao\ModuleNews $module
     *
     * @return array
     */
    public function parseEvent( array $event, Module $module ): array {

        // add tags
        if( $event['tags'] ) {

            $pageList = null;

            if( $module->jumpToTags ) {
                $pageList = PageModel::findWithDetails($module->jumpToTags);
            }

            $oTags = null;
            $oTags = TagsModel::findByIdForFieldAndTable($event['id'], 'tags', CalendarEventsModel::getTable());

            if( $oTags ) {

                $event['tagsRaw'] = $oTags->fetchAll();

                if( $pageList ) {

                    $aLinks = [];

                    foreach( $oTags->fetchEach('tag') as $id => $tag ) {

                        $href = $pageList->getFrontendUrl('/tag/'.$tag);

                        $aLinks[] = sprintf(
                            '<a href="%s" class="tag_%s" rel="nofollow">%s</a>'
                        ,   $href
                        ,   StringUtil::standardize($tag)
                        ,   $tag
                        );
                    }

                    $event['tags'] = $aLinks;

                } else {

                    $event['tags'] = array_values($oTags->fetchEach('tag'));
                }
            }
        }

        return $event;
    }
}
