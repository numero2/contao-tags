<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\DependencyInjection;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\NewsBundle\ContaoNewsBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class TagsExtension extends Extension {


    /**
     * {@inheritdoc}
     */
    public function load( array $mergedConfig, ContainerBuilder $container ): void {

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('commands.yaml');
        $loader->load('listener.yaml');
        $loader->load('services.yaml');
        $loader->load('migrations.yaml');

        //only load some services if bundle it depends on exists
        if( class_exists(ContaoCalendarBundle::class) ) {
            $loader->load('services_events.yaml');
        }
        if( class_exists(ContaoNewsBundle::class) ) {
            $loader->load('services_news.yaml');
        }
    }
}
