<?php
/**
 * User: dongww
 * Date: 2014-9-20
 * Time: 14:26
 */

namespace PhpGo\Framework;

use PhpGo\Framework\Core\BundleAbstract;
use PhpGo\Framework\Core\BundleInterface;
use PhpGo\Framework\Application\TwigTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\ResponseListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

//twig 注册路径的时候，指定名字空间，或者重写加载器
class Application extends BundleAbstract implements HttpKernelInterface, TerminableInterface
{
    use TwigTrait;

    protected $routesFileLoader;

    public function __construct($name, array $params = [])
    {
        $app = $this;

        $locator                = new FileLocator();
        $this->routesFileLoader = new YamlFileLoader($locator);

        $this['context'] = function () {
            return new RequestContext();
        };

        $this['matcher'] = function () {
            return new UrlMatcher($this['routes'], $this['context']);
        };

        $this['resolver'] = function () {
            return new ControllerResolver($this);
        };

        $this['dispatcher'] = function () use ($app) {
            $dispatcher = new EventDispatcher();
            $dispatcher->addSubscriber(new RouterListener($app['matcher']));
            $dispatcher->addSubscriber(new ResponseListener($app['charset']));

            return $dispatcher;
        };

        $this['kernel'] = function () use ($app) {
            return new HttpKernel($app['dispatcher'], $app['resolver']);
        };

//        $this['request.http_port']  = 80;
//        $this['request.https_port'] = 443;
        $this['charset'] = 'UTF-8';

        parent::__construct($name, null, $params);

        $this->setApp($this);
    }

    public function terminate(Request $request, Response $response)
    {
        $this['kernel']->terminate($request, $response);
    }

    /**
     * @param  Request $request
     * @param  int     $type
     * @param  bool    $catch
     * @return Response
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        parent::handle($request, $type, $catch);

        $this['routes']->addCollection($this->getRouteCollection(true));

        $response = $this['kernel']->handle($request, $type, $catch);

        return $response;
    }

    public function run(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $response = $this->handle($request);
        $response->send();
        $this->terminate($request, $response);
    }

    public function getRoutesFileLoader()
    {
        return $this->routesFileLoader;
    }

    public function getType()
    {
        return BundleInterface::TYPE_APPLICATION;
    }
}
