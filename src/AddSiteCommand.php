<?php

namespace Laravel\Homestead;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class AddSiteCommand extends Command
{
    /**
     * The base path of the Laravel installation.
     *
     * @var string
     */
    protected $basePath;

    protected $configFile;

    protected $config;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->basePath = getcwd();
        $this->configFile = $this->pathJoin(getenv('HOME'), '.homestead', 'Homestead.yaml');
        $this->config = Yaml::parse(file_get_contents($this->configFile));

        $this
            ->setName('site')
            ->setDescription('Add site to homestead')
            ->addArgument('project', InputArgument::REQUIRED, 'The Laravel project directory')
            ->addOption('site', 's', InputOption::VALUE_REQUIRED, 'The domain of the site')
            ->addOption('replace', 'R', InputOption::VALUE_NONE, 'Replace the current sites config')
            ->addOption('no-backup', 'N', InputOption::VALUE_NONE, 'Do not backup configuration')
            ->addOption(
                'database',
                'd',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Database to add'
            );
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $project = $input->getArgument('project');
        $mapTo = '/home/vagrant/sites/' .basename($project);

        $domain = $input->getOption('site');
        if (! $domain) {
            $domain = strtolower(basename($project)) . '.app';
        }

        $config = [
            'folders' => [
                [
                    'map' => $project,
                    'to' => $mapTo
                ]
            ],
            'sites' => [
                [
                    'map' => $domain,
                    'to' => $mapTo . '/public'
                ]
            ],
            'databases' => $input->getOption('database')
        ];

        if ($input->getOption('replace')) {
            $this->config['folders'] = [];
            $this->config['sites'] = [];
            $this->config['databases'] = [];
        }

        $config = array_merge_recursive($this->config, $config);

        $yaml = Yaml::dump($config, 4);

        if (! $input->getOption('no-backup')) {
            copy($this->configFile, $this->pathJoin(dirname($this->configFile), '_Homestead-' . time() . '.yaml'));
        }

        file_put_contents($this->configFile, $yaml);
    }

    public function pathJoin(...$path) {
        return join(DIRECTORY_SEPARATOR, $path);
    }

}
