<?php
require_once (__DIR__ . "/../vendor/autoload.php");

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends \Symfony\Component\Console\Command\Command {
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generates API documentation')
            ->addArgument('input',null,'Path to a valid postman collection');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folders = array();
        $rootDir = dirname(__DIR__);
        $loader = new Twig_Loader_Filesystem($rootDir . '/templates/');
        $twig = new Twig_Environment($loader, array(
            'cache' => $rootDir .'/cache/',
        ));
        $twig->clearCacheFiles();

        $f = new Twig_SimpleFunction("querify", function($data){
            $string = array();
            foreach ($data as $d) {
                $string[] = urlencode($d->key) . "=" .  urlencode($d->value);
            }
            return implode("&", $string);
        });
        $twig->addFunction("querify", $f);

        $f = new Twig_SimpleFunction("prettify", function($data){
            return json_encode(json_decode($data), JSON_PRETTY_PRINT);
        });
        $twig->addFunction("prettify", $f);

        /**
         * TODO Refactor
         * TODO if valid json - Format JSON (perhaps even with syntax highlighting if possible
         */
        srand(1);

        $env = array(
            "{{client_id}}" => sha1(rand()),
            "{{url}}" => "http://192.168.33.99",
        );

        $json = file_get_contents($input->getArgument('input'));
        foreach($env as $key => $val) {
            $json = str_ireplace($key, $val, $json);
        }
        $c = json_decode($json);

        mkdir($rootDir . '/output/requests',0777,true);


        foreach($c->requests as $rkey => $request) {
            $relpath = "requests/" . str_ireplace(array('/','\\','.',' '),'_', $request->name) . ".html";
            $c->requests[$rkey]->page_path = $relpath;
            if (!count($request->responses)) {
                $output->writeln("Warning: {$request->name} has no response examples");
            }
            if (!count($request->responses)) {
                $output->writeln("Warning: {$request->name} has no description");
            }
            if($request->method=="GET" && count($request->data)) {
                $output->writeln("Warning: {$request->name} has form-data parameters defined but is a GET request");
            }
            file_put_contents($rootDir . "/output/" . $relpath , $twig->render('request.html', array("request" => $request)));
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

        file_put_contents($rootDir . "/output/index.html", $twig->render('index.html', array("folders" => $folders)));
        copy($rootDir . "/templates/styles.css",$rootDir . "/output/styles.css");
    }
}