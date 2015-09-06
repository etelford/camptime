<?php

namespace Camptime;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Command extends SymfonyCommand
{
    protected $input;

    protected $output;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @Override
     * Run the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     *
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = new SymfonyStyle($input, $output);

        return parent::run($input, $output);
    }
}
