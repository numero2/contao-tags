<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Event;

use Contao\Model\Collection;
use Contao\ModuleModel;
use Symfony\Contracts\EventDispatcher\Event;


class TagsGetListEvent {


    /**
     * @var Contao\Model\Collection
     */
    private $tags;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $table;

    /**
     * @var Contao\ModuleModel;
     */
    private $model;


    public function __construct( Collection $tags, string $field, string $table, ModuleModel $model ) {

        $this->tags = $tags;
        $this->field = $field;
        $this->table = $table;
        $this->model = $model;
    }


    public function getTags() {
        return $this->tags;
    }

    public function setTags( Collection $tags) {

        $this->tags = $tags;

        return $this;
    }

    public function getField() {
        return $this->field;
    }

    public function getTable() {
        return $this->table;
    }

    public function getModel() {
        return $this->model;
    }
}
