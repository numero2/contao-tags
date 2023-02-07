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

        $tagsSelected = Input::post($dc->inputName);

        // adds a newly created tag on POST
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

            Input::setPost($dc->inputName, $tagsSelected);
        }

        // generate a list of all available tags
        $availableTags = [];

        $tags = null;

        // limit list of tags to ones already used for that field/table combination
        if( $dc->field && !empty($GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['groupTagsByField']) ) {
            $tags = TagsModel::findAllByFieldAndTable($dc->field,$dc->table);
        } else {
            $tags = TagsModel::findAll();
        }

        if( $tags ) {
            $availableTags= $tags->fetchEach('tag');
        }

        if( !empty($tagsSelected) ) {

            $selected = null;
            $selected = TagsModel::findMultipleByIds($tagsSelected);

            if( $selected ) {
                $availableTags = $availableTags + $selected->fetchEach('tag');
            }
        }

        return $availableTags;
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
                ->execute($dc->activeRecord->id, $dc->table, $dc->field);

            // add tag relations for this element
            foreach( $tags as $i => $id ) {
                $db->prepare("INSERT INTO ".TagsRelModel::getTable()." (tag_id, pid, ptable, field) VALUES(?,?,?,?)")
                    ->execute($id, $dc->activeRecord->id, $dc->table, $dc->field);
                
                // explicitly cast the id into a string, otherwise the filter options in the backend won't work
                $tags[$i] = (string)$id;
            }

            return serialize($tags);
        }

        return $varValue;
    }
}
