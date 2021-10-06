<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2021, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\TagsRelModel;


class TagsListener {


    /**
     * Returns a list of possible tags
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getTagOptions( DataContainer $dc ) {

        $tagsSelected = Input::post($dc->field);

        if( $tagsSelected ) {

            foreach( $tagsSelected as $i => $tag ) {

                if( !is_numeric($tag) ) {

                    $model = TagsModel::findByTag($tag);

                    // save new tags
                    if( !$model ) {

                        $model = new TagsModel();
                        $model->tag = $tag;
                        $model->save();
                    }

                    $tagsSelected[$i] = (int)$model->id;
                }
            }

            Input::setPost($dc->field,$tagsSelected);
        }

        $oTags = null;
        $oTags = TagsModel::findAll();

        if( $oTags ) {
            return $oTags->fetchEach('tag');
        }

        return [];
    }


    /**
     * Save new tags to database
     *
     * @param mixed $varValue
     * @param DataContainer $dc
     *
     * @return string|null
     */
    public function saveTags( $varValue, DataContainer $dc ): ?string {

        if( !empty($varValue) ) {

            $tags = StringUtil::deserialize( $varValue );
            $db = Database::getInstance();

            // remove all tag relations for this element
            $db->prepare("DELETE FROM ".TagsRelModel::getTable()." WHERE pid = ? AND ptable = ? AND field = ?")
                ->execute( $dc->activeRecord->id, $dc->table, $dc->field );

            // add tag relations for this element
            foreach( $tags as $i => $id ) {
                $db->prepare("INSERT INTO ".TagsRelModel::getTable()." (tag_id, pid, ptable, field) VALUES(?,?,?,?)")
                    ->execute( $id, $dc->activeRecord->id, $dc->table, $dc->field );
                $tags[$i] = (int)$id;
            }

            return serialize($tags);
        }

        return $varValue;
    }
}
