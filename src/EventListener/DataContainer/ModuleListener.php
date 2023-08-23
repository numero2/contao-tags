<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\TagsRelModel;


class ModuleListener {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;


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

        PaletteManipulator::create()
            ->addField('ignoreTags', 'config_legend', 'append')
            ->applyToPalette('newslist', $dc->table);

        $pm = PaletteManipulator::create()
            ->addField('jumpToTags', 'config_legend', 'append');

        foreach( ['newslist', 'newsreader'] as $palette ) {
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


    /**
     * Get all tags for news
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_module", target="fields.news_tags.options")
     */
    public function getNewsTags( DataContainer $dc ): array {

        $tTag = TagsModel::getTable();
        $tRel = TagsRelModel::getTable();

        $result = $this->connection
            ->prepare(
                "SELECT DISTINCT tag.id, tag.tag
                FROM $tTag AS tag
                JOIN $tRel AS rel ON (rel.tag_id=tag.id AND rel.ptable=:ptable AND rel.field=:field)
                ORDER BY tag.tag ASC")
            ->executeQuery(['ptable'=>'tl_news', 'field'=>'tags'])
        ;

        $tags = [];

        if( $result && $result->rowCount() ) {

            $rows = $result->fetchAll();

            foreach( $rows as $row ) {
                $tags[$row['id']] = $row['tag'];
            }
        }

        return $tags;
    }
}
