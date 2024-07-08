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

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\TagsRelModel;


class TagsListener {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;


    public function __construct( Connection $connection ) {

        $this->connection = $connection;
    }


    /**
     * Returns a list of possible tags
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    public function getTagOptions( DataContainer $dc ) {

        $tagsSelected = Input::post($dc->inputName);

        // adds a newly created tag on POST
        if( $tagsSelected ) {

            foreach( $tagsSelected as $i => $tag ) {

                if( empty($tag) ) {
                    unset($tagsSelected[$i]);
                    continue;
                }

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
        }

        if( $tags === null || $tags->count() == 0 ) {
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
     * @param Contao\DataContainer $dc
     *
     * @return string|null
     */
    public function saveTags( $varValue, DataContainer $dc ): ?string {

        $tRel = TagsRelModel::getTable();

        // remove all tag relations for this element
        $this->connection
            ->prepare("DELETE FROM $tRel WHERE pid=? AND ptable=? AND field=?")
            ->executeStatement([$dc->activeRecord->id, $dc->table, $dc->field])
        ;

        if( !empty($varValue) ) {

            $tags = StringUtil::deserialize($varValue, true);

            // add tag relations for this element
            foreach( $tags as $i => $id ) {

                $this->connection
                    ->prepare("INSERT INTO $tRel (tag_id, pid, ptable, field) VALUES (?,?,?,?)")
                    ->executeStatement([$id, $dc->activeRecord->id, $dc->table, $dc->field])
                ;

                // explicitly cast the id into a string, otherwise the filter options in the backend won't work
                $tags[$i] = (string)$id;
            }

            return serialize($tags);
        }

        return $varValue;
    }


    /**
     * Load tags from rel table
     *
     * @param mixed $varValue
     * @param Contao\DataContainer $dc
     *
     * @return array|null
     */
    public function loadTags( $varValue, DataContainer $dc ): ?array {

        $tags = TagsModel::findByIdForFieldAndTable($dc->activeRecord->id??'', $dc->field, $dc->table);

        if( $tags ) {

            return $tags->fetchEach('id');
        }

        return [];
    }


    /**
     * Add tag merge button to the select section and handle it
     *
     * @param array $buttons
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_tags", target="select.buttons")
     */
    public function mergeTagSelectButton( $buttons, DataContainer $dc ): array {

        // start merge selected tags
        if( Input::post('FORM_SUBMIT') === 'tl_select' && isset($_POST['tags_merge']) ) {

            $objSession = System::getContainer()->get('session');
            $session = $objSession->all();
            $ids = $session['CURRENT']['IDS'] ?? [];

            if( count($ids) > 1 ) {

                $tTag = TagsModel::getTable();
                $tRel = TagsRelModel::getTable();

                $newId = $ids[0];

                $res = $this->connection->executeQuery(
                    "SELECT * FROM $tRel WHERE tag_id in (:ids)"
                ,   ['ids'=>$ids]
                ,   ['ids'=>Connection::PARAM_INT_ARRAY]
                );

                if( $res && $res->rowCount() ) {

                    $tagsRel = $res->fetchAll();
                    $rowsProcessed = [];

                    // create rel for first tag and gather entries
                    foreach( $tagsRel as $rel ) {

                        $hash = md5('pid:' . $rel['pid'] . 'ptable' . $rel['ptable'] . 'field' . $rel['field']);

                        if( !array_key_exists($hash, $rowsProcessed) ) {

                            $rowsProcessed[$hash] = ['pid'=>$rel['pid'], 'ptable'=>$rel['ptable'], 'field'=>$rel['field']];

                            if( $rel['tag_id'] != $newId ) {

                                $this->connection
                                    ->prepare("INSERT INTO $tRel (tag_id, pid, ptable, field) VALUES (?,?,?,?)")
                                    ->executeStatement([$newId, $rel['pid'], $rel['ptable'], $rel['field']])
                                ;
                            }
                        }
                    }

                    // delete rel for other tags
                    foreach( $rowsProcessed as $hash => $rel ) {

                        $this->connection
                            ->prepare("DELETE FROM $tRel WHERE tag_id!=? AND pid=? AND ptable=? AND field=?")
                            ->executeStatement([$newId, $rel['pid'], $rel['ptable'], $rel['field']])
                        ;
                    }

                    // delete tag for other tags
                    $res = $this->connection->executeStatement(
                        "DELETE FROM $tTag WHERE id!=:id AND id in (:ids)"
                    ,   ['id'=>$newId, 'ids'=>$ids]
                    ,   ['ids'=>Connection::PARAM_INT_ARRAY]
                    );
                }

                // redirect to edit on that id
                Controller::redirect(Backend::addToUrl('act=edit&amp;id=' . $newId));
            }

            Controller::redirect(Controller::getReferer());
        }

        // add the button
        $buttons['tags_merge'] = '<button type="submit" name="tags_merge" id="tags_merge" class="tl_submit" accesskey="m">' . $GLOBALS['TL_LANG']['MSC']['tagsMergeSelected'] . '</button> ';

        return $buttons;
    }
}
