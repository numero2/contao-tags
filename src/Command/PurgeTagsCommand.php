<?php

/**
 * Tags Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL-3.0-or-later
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\TagsBundle\Command;

use numero2\TagsBundle\PurgeTags;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class PurgeTagsCommand extends Command {


    protected static $defaultName = 'contao:tags:purge';
    protected static $defaultDescription = 'Delete unused Tags';

    /**
     * @var numero2\TagsBundle\PurgeTags;
     */
    private PurgeTags $purgeTags;


    public function __construct( PurgeTags $purgeTags ) {

        $this->purgeTags = $purgeTags;

        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int {

        $this->purgeTags->__invoke();

        $output->writeln('Unused Tags deleted.');

        return Command::SUCCESS;
    }
}