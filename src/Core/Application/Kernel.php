<?php


namespace Olympus\Core\Application;

use Olympus\Core\OlympusCoreBundle;
use Olympus\Website\OlympusWebsiteBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as HttpKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as RoutingPhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

class Kernel extends HttpKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundlesPath = $this->getConfigDir().'/bundles.php';
        $contents = require $bundlesPath;
        foreach($this->getOlympusBundles() as $bundle){
            $contents[$bundle] = ['all' => true];
        }

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getOlympusBundles()
    {
        return [
            OlympusCoreBundle::class,
            OlympusWebsiteBundle::class,
        ];
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $configDir = $this->getConfigDir();

        $routes->import($configDir.'/{routes}/'.$this->environment.'/*.{php,yaml}');
        $routes->import($configDir.'/{routes}/*.{php,yaml}');

        if (is_file($configDir.'/routes.yaml')) {
            $routes->import($configDir.'/routes.yaml');
        } else {
            $routes->import($configDir.'/{routes}.php');
        }

        if (false !== ($fileName = (new \ReflectionObject($this))->getFileName())) {
            $routes->import($fileName, 'annotation');
        }

        foreach($this->getOlympusBundles() as $bundle){
            $file = (new \ReflectionClass($bundle))->getFileName();
            $dir = dirname($file);
            if(is_dir($controllerDir = $dir.'/Controller/')){
                $routes->import($controllerDir,'attribute');
            }

            if(is_dir($routesDir = $dir.'/Resources/routes')){
                $routes->import($routesDir);
            }
        }
    }

    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $file = (new \ReflectionObject($this))->getFileName();
        /* @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        $kernelLoader->setCurrentDir(dirname($file));
        $collection = new RouteCollection();
        $configurator = new RoutingConfigurator($collection, $kernelLoader, $file, $file,$this->getEnvironment());

        $this->configureRoutes($configurator);

        foreach ($collection as $route) {
            $controller = $route->getDefault('_controller');

            if (\is_array($controller) && [0, 1] === array_keys($controller) && $this === $controller[0]) {
                $route->setDefault('_controller', ['kernel', $controller[1]]);
            } elseif ($controller instanceof \Closure && $this === ($r = new \ReflectionFunction($controller))->getClosureThis() && !str_contains($r->name, '{closure}')) {
                $route->setDefault('_controller', ['kernel', $r->name]);
            }
        }

        return $collection;
    }
}