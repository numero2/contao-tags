<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Migration;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use numero2\TagsBundle\TagsModel;


class Version007Update extends AbstractMigration {


    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework
     */
    private $framework;

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;

    private $fields = [];


    public function __construct( ContaoFramework $framework, Connection $connection ) {

        $this->framework = $framework;
        $this->framework->initialize();
        
        $this->connection = $connection;
    }


    public function shouldRun(): bool {

        $t = TagsModel::getTable();

        // check which DataContainer contain a tags field
        if( !empty($GLOBALS['TL_DCA']) ) {

            foreach( $GLOBALS['TL_DCA'] as $dca => $config ) {

                if( !empty($GLOBALS['TL_DCA'][$dca]['fields']) ) {

                    foreach( $GLOBALS['TL_DCA'][$dca]['fields'] as $field => $config ) {

                        // if a field references our tags table it's a match
                        if( !empty($config['foreignKey']) && stripos($config['foreignKey'], $t.'.') !== false ) {

                            if( !is_array($this->fields[$dca]) ) {
                                $this->fields[$dca] = [];
                            }
                            
                            if( in_array($field,$this->fields[$dca]) === false ) {
                                $this->fields[$dca][] = $field;
                            }
                        }
                    }
                }
            }
        }

        // check if any of these fields contain data that needs to be altered
        if( !empty($this->fields) ) {

            foreach( $this->fields as $dca => $fields ) {

                foreach( $fields as $field ) {

                    $res = $this->connection->prepare("SELECT 1 FROM $dca WHERE $field IS NOT NULL AND $field NOT LIKE '%:\"%'; ")->executeQuery();

                    // return as soon as we found our first value in the wrong format
                    if( $res && $res->rowCount() ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }


    public function run(): MigrationResult {

        foreach( $this->fields as $dca => $fields ) {

            foreach( $fields as $field ) {

                $selectStmt = $this->connection->prepare("SELECT id, $field FROM $dca WHERE $field IS NOT NULL AND $field NOT LIKE '%:\"%'; ")->executeQuery();

                if( $selectStmt && $selectStmt->rowCount() ) {

                    foreach( $selectStmt->fetchAll() as $row ) {

                        $value = StringUtil::deserialize($row[$field]);

                        if( !empty($value) ) {
                            
                            // cast each id explicitly into a string
                            $value = array_map(fn($v): string => (string)$v, $value);

                            $row[$field] = serialize($value);

                            // update the row
                            $updateStmt = $this->connection->prepare("UPDATE $dca SET $field = :$field WHERE id = :id");
                            $updateStmt->execute($row);
                        }
                    }
                }
            }
        }

        return $this->createResult(true);
    }
}
