<?php
/**
 * User: dongww
 * Date: 2014-9-20
 * Time: 15:47
 */

namespace PhpGo\Framework\Core;

use PhpGo\Framework\CallbackResolver;
use Silex\Api\BootableProviderInterface;
use PhpGo\Framework\Application;
use PhpGo\Framework\BundleCollection;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouteCollection;

class BundleAbstract extends Container implements BundleInterface, HttpKernelInterface
{
    protected $parent = null;
    protected $app;
    protected $name;
    protected $subBundles;
    protected $providers = [];
    protected $booted = false;

    public function __construct($name, BundleAbstract $parent = null, array $params = [])
    {
        parent::__construct($params);

        if ($parent) {
            $this->setParent($parent);
        }

        if ('' == $name = preg_replace('/[^a-zA-Z0-9_\.-]+/', '', (string)$name)) {
            throw new \InvalidArgumentException('错误的名字，请仔细检查。');
        }

        $this->name = $name;

        $this->subBundles = new BundleCollection();

        $this['routes'] = function () {
            return new RouteCollection();
        };
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(BundleAbstract $parent)
    {
        $this->parent = $parent;
        $this->app    = $parent === null ? null : $parent->getApp();
    }

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    public function setApp(Application $app)
    {
        if ($this->parent !== null) {
            throw new \Exception('只有当上级 Bundle 等于 null 的时候才能调用该函数。');
        }

        $this->app = $app;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  BundleAbstract $bundle
     * @return BundleAbstract
     */
    public function addBundle(BundleAbstract $bundle)
    {
        if ($bundle->getParent() === null) {
            $bundle->setParent($this);
        }
        $this->subBundles->addBundle($bundle);

        return $this;
    }

    public function addBundles($bundles)
    {//BundleCollection 继承自迭代器，以便省略以下代码
        if ($bundles instanceof BundleCollection) {
            $bundles = $bundles->getBundles();
        }

        foreach ($bundles as $bundle) {
            $this->addBundle($bundle);
        }
    }

    /**
     * @return BundleCollection
     */
    public function getSubBundles()
    {
        return $this->subBundles;
    }

    public function getSubBundle($name)
    {
        $bundles = $this->subBundles->getBundles();

        return isset($bundles[$name]) ? $bundles[$name] : null;
    }

    public function getRouteCollection($onlyChildren = false)
    {
//        $this->loadRoutesFromConfig();
        $routes = new RouteCollection();

        if (!$onlyChildren) {
            $routes->addCollection($this['routes']);
        }

        foreach ($this->getSubBundles()->getBundles() as $child) {
            if ($child instanceof BundleAbstract) {
                $routes->addCollection($child->getRouteCollection());
            }
        }

        return $routes;
    }

    /**
     * @param  ServiceProviderInterface $provider
     * @param  array                    $values
     * @return BundleAbstract
     */
    public function register(ServiceProviderInterface $provider, array $values = [])
    {
        $this->providers[] = $provider;

        parent::register($provider, $values);

        return $this;
    }

    /** todo */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        foreach ($this->providers as $provider) {
            if ($provider instanceof BootableProviderInterface) {
                $provider->boot($this);
            }
        }
    }

    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $this->loadParametersFromConfig();
        $this->loadProvidersFromConfig();
        $this->loadBundlesFromConfig();
        $this->loadRoutesFromConfig();

        if (!$this->booted) {
            $this->boot();
        }

        foreach ($this->getSubBundles()->getBundles() as $child) {
            $child->run($request);
        }
    }

    protected function loadRoutesFromConfig()
    {
        $routesFile = $this->getDir() . '/_config/routes.config.yml';

        if (file_exists($routesFile)) {
            $loader     = $this->getApp()->getRoutesFileLoader();
            $collection = $loader->load($this->getDir() . '/_config/routes.config.yml');
            $collection->addOptions(['bundle_name' => $this->getName()]);
            $this['routes']->addCollection($collection);

            if (isset($this['routes_prefix'])) {
                $this['routes']->addPrefix($this['routes_prefix']);
            }
        }

        return $this;
    }

    public function loadBundlesFromConfig()
    {
        $configFile = $this->getDir() . '/_config/bundles.config.php';
        if (file_exists($configFile)) {
            $this->addBundles(require_once $configFile);
        }

        return $this;
    }

    public function loadProvidersFromConfig()
    {
        $bundle = $this;
        $app    = $this->getApp();

        $file = $this->getDir() . '/_config/provider.config.php';
        if (file_exists($file)) {
            require_once $file;
        }

        return $this;
    }

    public function loadParametersFromConfig()
    {
        $bundle = $this;
        $app    = $this->getApp();

        $file = $this->getDir() . '/_config/parameters.config.php';
        if (file_exists($file)) {
            require_once $file;
        }

        return $this;
    }

    public function getDir()
    {
        $className = get_class($this);
        $ref       = new \ReflectionClass($className);

        return dirname($ref->getFileName());
    }

    public function getType()
    {
        return BundleInterface::TYPE_BUNDLE;
    }

    public function on($eventName, $callback, $priority = 0)
    {
        // TODO: Implement on() method.
    }

    public function run(Request $request = null)
    {
        $this->handle($request);
    }
}
