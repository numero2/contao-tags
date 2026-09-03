<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Migration;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\BooleanType;


class BooleanFieldsMigration extends AbstractMigration {


    /**
     * @var Doctrine\DBAL\Connection
     */
    private Connection $connection;


    public function __construct( Connection $connection ) {

        $this->connection = $connection;
    }


    public function shouldRun(): bool {

        // only run on Contao 6.0 or greater, as old version have this migration build in
        if( version_compare(ContaoCoreBundle::getVersion(), '6.0', '<') ) {
            return false;
        }

        foreach( $this->getNoneBooleanFields() as $table => $fields ) {
            foreach( $fields as $field ) {

                $count = intval($this->connection->fetchOne("SELECT count(1) FROM $table WHERE $field=''"));

                if( $count > 0 ) {
                    return true;
                }
            }
        }

        return false;
    }


    public function run(): MigrationResult {

        foreach( $this->getNoneBooleanFields() as $table => $fields ) {
            foreach( $fields as $field ) {

                $this->connection->update($table, [$field => '0'], [$field => '']);
            }
        }

        return $this->createResult(true);
    }


    private function getNoneBooleanFields(): array {

        $result = [];
        $tableFields = [
            'tl_module' => ['ignoreTags', 'tags_select_multiple', 'use_get_parameter', 'tags_match_all', 'tags_exclude']
        ,   'tl_tags' => ['invisible']
        ,   'tl_user_group' => ['tags_disable_add_new']
        ];
        $schemaManager = $this->connection->createSchemaManager();

        foreach( $tableFields as $table => $fields ) {

            $columns = $schemaManager->listTableColumns($table);

            foreach( $fields as $field ) {

                $fieldConfig = $columns[$field] ?? $columns[strtolower($field)] ?? null;

                if( !$fieldConfig ) {
                    continue;
                }

                if( !($fieldConfig->getType() instanceof BooleanType) ) {

                    if( !array_key_exists($table, $tableFields) ) {
                        $result[$table] = [];
                    }

                    $result[$table][] = $field;
                }
            }
        }

        return $result;
    }
}
