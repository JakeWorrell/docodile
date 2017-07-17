<?php
namespace Docodile\Command;
use Docodile\Postman\Collection;
use ErrorException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateCommand extends \Symfony\Component\Console\Command\Command {

    protected $rootDir;

    protected function configure()
    {
        $this->rootDir = dirname(__DIR__);

        $this
            ->setName('generate')
            ->setDescription('Generates API documentation')
            ->addArgument('input',null,'Path to a valid postman collection')
            ->addArgument('output',null,'Path to output documentation to', getcwd() .'/docodile')
            ->addOption('force','f',null,'force output directory deletion');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
        $fs->mkdir($outputDir);
        $fs->mkdir($outputDir . '/requests');

        $twig = $this->getTwig();

        $collection = Collection::fromFile($input->getArgument('input'), $this->getExampleParameters());

        if (version_compare($collection->getVersion(),'2.0.0') < 0) {
            throw new ErrorException('No longer supporting postman collections with versions < 2.0.0');
        }

        foreach ($collection->getItems() as $item) {
            if (isset($item->item)) { // this is actually a folder
                $folders[$item->name]['name'] = $item->name;

                foreach ($item->item as $request) {
                    if (is_object($request->request->url)){
                        $request->request->url = $request->request->url->raw;
                    }
                    $pageFilename = str_ireplace(array('/','\\','.',' ','?'),'_', $request->name) . ".html";
                    $relpath = "/requests/" .  $pageFilename;
                    $request->page_path = 'requests/' . $pageFilename;
                    if (!property_exists($request, 'response') ||  !count($request->response)) {
                        $output->writeln("Warning: {$request->name} has no response examples");
                    }

                    if($request->request->method=="GET" && isset($request->request->body->formdata)) {
                        $output->writeln("Warning: {$request->name} has form-data parameters defined but is a GET request");
                    }

                    file_put_contents($outputDir . $relpath , $twig->render('request.html', [ "request" => $request ]));
                    $folders[$item->name]['requests'][] = $request;

                }
            }
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
     * @return \Twig_Environment
     */
    protected function getTwig()
    {
        $loader = new \Twig_Loader_Filesystem($this->rootDir . '/templates/');

        $twig = new \Twig_Environment($loader, array(
            'cache' => false
        ));
        $twig->clearCacheFiles();

        $f = new \Twig_SimpleFunction("querify", function ($data) {
            $string = array();
            foreach ($data as $d) {
                $string[] = urlencode($d->key) . "=" . urlencode($d->value);
            }
            return implode("&", $string);
        });
        $twig->addFunction("querify", $f);

        $f = new \Twig_SimpleFunction("prettify", function ($data) {
            return json_encode(json_decode($data), JSON_PRETTY_PRINT);
        });

        $twig->addFunction("prettify", $f);
        return $twig;
    }
}