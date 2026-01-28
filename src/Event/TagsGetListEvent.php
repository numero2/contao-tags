<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Event;

use Contao\Model\Collection;
use Contao\ModuleModel;


class TagsGetListEvent {


    /**
     * @var Contao\Model\Collection
     */
    private Collection $tags;

    /**
     * @var string
     */
    private string $field;

    /**
     * @var string
     */
    private string $table;

    /**
     * @var Contao\ModuleModel;
     */
    private ModuleModel $model;


    public function __construct( Collection $tags, string $field, string $table, ModuleModel $model ) {

        $this->tags = $tags;
        $this->field = $field;
        $this->table = $table;
        $this->model = $model;
    }


    /**
     * Get the tags as a collection
     *
     * @return Contao\Model\Collection
     */
    public function getTags(): Collection {

        return $this->tags;
    }


    /**
     * Set the tags as a collection
     *
     * @param Contao\Model\Collection $tags
     *
     * @return numero2\TagsBundle\Event\TagsGetListEvent
     */
    public function setTags( Collection $tags): self {

        $this->tags = $tags;

        return $this;
    }


    /**
     * Get the field in which the tags are searched
     *
     * @return string
     */
    public function getField(): string {

        return $this->field;
    }


    /**
     * Get the table in which the tags are searched
     *
     * @return string
     */
    public function getTable(): string {

        return $this->table;
    }


    /**
     * Get the corresponding model
     *
     * @return Contao\ModuleModel
     */
    public function getModel(): ModuleModel {

        return $this->model;
    }
}
