<?php

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateCommand extends \Symfony\Component\Console\Command\Command {

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generates API documentation')
            ->addArgument('input',null,'Path to a valid postman collection')
            ->addArgument('output',null,'Path to output documentation to', 'docodile')
            ->addOption('force','f',null,'force output directory deletion');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->rootDir = dirname(__DIR__);

        if (!file_exists($input->getArgument('input'))) {
            throw new ErrorException('The Postman collection specified cannot be found');
        }

        $folders = array();
        $fs = new Filesystem();
        $outputDir = $input->getArgument('output');
        if ($fs->exists($outputDir)) {
            if (!$input->getOption('force')) {
                $output->writeln("output directory '" . $outputDir . "' exists. Chickening out of deleting directory. --force to force");
                return 1;
            }

            $fs->remove($outputDir);
        }


        $twig = $this->getTwig();
        $env = $this->getExampleParameters();

        $json = file_get_contents($input->getArgument('input'));
        foreach($env as $key => $val) {
            $json = str_ireplace($key, $val, $json);
        }
        $c = json_decode($json);

        $fs->mkdir($outputDir . '/requests');

        foreach($c->requests as $rkey => $request) {
            $pageFilename = str_ireplace(array('/','\\','.',' ','?'),'_', $request->name) . ".html";
            $relpath = "/requests/" .  $pageFilename;
            $c->requests[$rkey]->page_path = 'requests/' . $pageFilename;
            if (!property_exists($request, 'responses') ||  !count($request->responses)) {
                $output->writeln("Warning: {$request->name} has no response examples");
            }

            if($request->method=="GET" && count($request->data)) {
                $output->writeln("Warning: {$request->name} has form-data parameters defined but is a GET request");
            }

            file_put_contents($outputDir . $relpath , $twig->render('request.html', [ "request" => $request ]));
        }

        foreach ($c->folders as $folder) {
            foreach ($folder->order as $id) {
                foreach ($c->requests as $request) {
                    if($request->id == $id) {
                        $folder->requests[] = $request;
                    }
                }
            }
            $folders[] = $folder;
        }

        file_put_contents($outputDir . "/index.html", $twig->render('index.html', array("folders" => $folders)));
        copy($this->rootDir . "/templates/styles.css", $outputDir . "/styles.css");
    }

    public function getExampleParameters(){
        srand(1);
        return array(
            "{{client_id}}" => sha1(rand()),
            "{{url}}" => "http://192.168.33.99",
        );
    }

    /**
     * @return Twig_Environment
     */
    protected function getTwig()
    {
        $loader = new Twig_Loader_Filesystem($this->rootDir . '/templates/');

        $twig = new Twig_Environment($loader, array(
            'cache' => false
        ));
        $twig->clearCacheFiles();

        $f = new Twig_SimpleFunction("querify", function ($data) {
            $string = array();
            foreach ($data as $d) {
                $string[] = urlencode($d->key) . "=" . urlencode($d->value);
            }
            return implode("&", $string);
        });
        $twig->addFunction("querify", $f);

        $f = new Twig_SimpleFunction("prettify", function ($data) {
            return json_encode(json_decode($data), JSON_PRETTY_PRINT);
        });

        $twig->addFunction("prettify", $f);
        return $twig;
    }
}