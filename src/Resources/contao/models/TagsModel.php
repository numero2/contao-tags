<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2022, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle;

use Contao\Database;
use Contao\Model;
use Contao\NewsArchiveModel;
use Contao\NewsModel;


class TagsModel extends Model {


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_tags';


    /**
     * Find all used tags in the given archives
     *
     * @param array $aArchives
     *
     * @return Collection|TagsModel|null A collection of models or null if there are no tags
     */
    public static function findByArchives( array $aArchives ) {

        $aArchives = (array)$aArchives;

        $objResult = Database::getInstance()->prepare("
            SELECT DISTINCT
                t.*
            FROM ".NewsArchiveModel::getTable()." AS a
                JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                JOIN ".self::getTable()." AS t ON (t.id = r.tag_id)
            WHERE
                a.id in (".implode(',', $aArchives).")
            ORDER BY t.tag ASC
        ")->execute();

        return static::createCollectionFromDbResult($objResult, self::$strTable);
    }


    /**
     * Count how many times the given tag was used
     *
     * @param int $id
     * @param array $aArchives
     *
     * @return int
     */
    public static function countById( $id, array $aArchives ): int {

        $aArchives = (array)$aArchives;

        $objResult = Database::getInstance()->prepare("
            SELECT
                COUNT(*) AS count
            FROM ".NewsArchiveModel::getTable()." AS a
                JOIN ".NewsModel::getTable()." AS n ON (n.pid = a.id)
                JOIN ".TagsRelModel::getTable()." AS r ON (r.pid = n.id AND r.ptable = '".NewsModel::getTable()."' AND r.field = 'tags')
                JOIN ".self::getTable()." AS t ON (t.id = r.tag_id)
            WHERE
                a.id in (".implode(',', $aArchives).") AND t.id = ?
        ")->execute( $id );

        return (int)$objResult->count;
    }
}