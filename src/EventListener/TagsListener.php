<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\PageModel;
use numero2\TagsBundle\TagsModel;
use numero2\TagsBundle\Util\TagUtil;


class TagsListener {


    /**
     * Replace inserttags for tags
     *
     * @param string $insertTag
     * @param bool $useCache
     * @param string $cachedValue
     * @param array $flags
     *
     * @return string|bool
     *
     * @Hook("replaceInsertTags")
     */
    public function replaceInsertTags( string $insertTag, bool $useCache, string $cachedValue, array $flags ) {

        $tag = explode('::', $insertTag);

        if( $tag[0] === 'tag_link' ) {

            $page = PageModel::findByIdOrAlias($tag[1]);
            $tags = array_slice($tag, 2);

            if( !$page || empty($tags) ) {
                return '';
            }

            $tagNames = [];
            foreach( $tags as $t ) {

                $oTag = TagsModel::findByIdOrName($t);

                if( $oTag ) {
                    $tagNames[] = $oTag->tag;
                }
            }

            $tagNames = array_unique($tagNames);
            if( empty($tagNames) ) {
                return '';
            }

            $blnGetParameter = \in_array('get', $flags, true);
            $blnAbsolute = \in_array('absolute', $flags, true);

            return TagUtil::generateUrlWithTags($page, $tagNames, $blnGetParameter, $blnAbsolute);

        } else if( $tag[0] === 'tags_active' ) {

            $glue = $tag[1] ?? ', ';
            $glueLast = $tag[2] ?? null;

            $tags = TagUtil::getTagsFromUrl();
            $tags[] ='dlas';
            $tags[] ='dddlas';

            if( empty($tags) ) {
                return '';
            } else if( count($tags) === 1 ) {
                return array_pop($tags);
            }

            if( $glueLast === null ) {
                return implode($glue, $tags);
            } else {
                $lastTag = array_pop($tags);

                return implode($glue, $tags) . $glueLast . $lastTag;
            }

            return '';
        }

        return false;
    }


    /**
     * Replace flags for tag inserttags
     *
     * @param string $flag
     * @param string $tag
     * @param string $cachedValue
     * @param array $flags
     * @param bool $useCache
     * @param array $tags
     * @param array $cache
     * @param int $_rit
     * @param int $_cnt
     *
     * @return string|bool
     *
     * @Hook("insertTagFlags")
     */
    public function insertTagFlags( string $flag, string $tag, string $cachedValue, array $flags, bool $useCache, array $tags, array $cache, int $_rit, int $_cnt ) {

        // Note: This function does not do anything, it is just there to let Contao know that we've already taken care of the flags for our insert tags

        $tag = explode('::', $tag);

        if( $tag[0] === 'tag_link' ) {

            if( in_array($flag,['get','absolute']) ) {
                return $cachedValue;
            }
        }

        return false;
    }
}
