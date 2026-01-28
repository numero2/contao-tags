<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener\DataContainer;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\NewsBundle\ContaoNewsBundle;
use Contao\NewsModel;
use Doctrine\DBAL\Connection;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\TagsRelModel;


class ModuleListener {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private Connection $connection;


    public function __construct( Connection $connection ) {

        $this->connection = $connection;
    }


    /**
     * Add jumpToTags and ignoreTags to existing modules
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_module", target="config.onload")
     */
    public function modifyPalettes( DataContainer $dc ): void {

        if( class_exists(ContaoCalendarBundle::class) ) {

            PaletteManipulator::create()
                ->addField(['ignoreTags', 'tags_match_all'], 'config_legend', 'append')
                ->applyToPalette('eventlist', $dc->table);

            $pm = PaletteManipulator::create()
                ->addField('jumpToTags', 'config_legend', 'append');

            foreach( ['eventlist', 'eventreader', 'eventlist_related_tags', 'eventlist_tags'] as $palette ) {
                $pm->applyToPalette($palette, $dc->table);
            }

            PaletteManipulator::create()
                ->removeField('cal_readerModule')
                ->applyToPalette('eventlist_related_tags', $dc->table);

            PaletteManipulator::create()
                ->addField('event_tags', 'cal_calendar', 'after')
                ->addField('tags_match_all', 'config_legend', 'append')
                ->applyToPalette('eventlist_tags', $dc->table);

        }

        if( class_exists(ContaoNewsBundle::class) ) {

            PaletteManipulator::create()
                ->addField(['ignoreTags', 'tags_match_all'], 'config_legend', 'append')
                ->applyToPalette('newslist', $dc->table);

            $pm = PaletteManipulator::create()
                ->addField('jumpToTags', 'config_legend', 'append');

            foreach( ['newslist', 'newsreader', 'newslist_related_tags', 'newslist_tags'] as $palette ) {
                $pm->applyToPalette($palette, $dc->table);
            }

            PaletteManipulator::create()
                ->removeField('news_readerModule')
                ->applyToPalette('newslist_related_tags', $dc->table);

            PaletteManipulator::create()
                ->addField('news_tags', 'news_archives', 'after')
                ->addField('tags_match_all', 'config_legend', 'append')
                ->applyToPalette('newslist_tags', $dc->table);
        }
    }


    /**
     * Changes the field to not be mandatory on some module types
     *
     * @param mixed $value
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_module", target="fields.event_tags.load")
     * @Callback(table="tl_module", target="fields.news_tags.load")
     */
    public function changeFieldToNotMandatory( $value,  DataContainer $dc ) {

        if( in_array($dc->activeRecord->type, ['events_tag_cloud', 'news_tag_cloud']) ) {
            $GLOBALS['TL_DCA']['tl_module']['fields'][$dc->field]['eval']['mandatory'] = false;
        }

        return $value;
    }

    /**
     * Get all tags for news
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_module", target="fields.event_tags.options")
     * @Callback(table="tl_module", target="fields.news_tags.options")
     */
    public function getTags( DataContainer $dc ): array {

        $tTag = TagsModel::getTable();
        $tRel = TagsRelModel::getTable();

        $ptable = null;
        if( $dc->field === 'event_tags' ) {
            $ptable = CalendarEventsModel::getTable();
        } else if( $dc->field === 'news_tags' ) {
            $ptable = NewsModel::getTable();
        }

        $result = null;
        if( $ptable ) {

            $result = $this->connection->executeQuery(
                "SELECT DISTINCT tag.id, tag.tag
                FROM $tTag AS tag
                JOIN $tRel AS rel ON (rel.tag_id=tag.id AND rel.ptable=:ptable AND rel.field=:field)
                ORDER BY tag.tag ASC"
            ,   ['ptable'=>$ptable, 'field'=>'tags']
            );
        }

        $tags = [];

        if( $result && $result->rowCount() ) {

            $rows = $result->fetchAllAssociative();

            foreach( $rows as $row ) {
                $tags[$row['id']] = $row['tag'];
            }
        }

        return $tags;
    }
}
