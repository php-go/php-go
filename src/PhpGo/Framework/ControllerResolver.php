<?php
/**
 * User: dongww
 * Date: 2014-9-23
 * Time: 14:05
 */

namespace PhpGo\Framework;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\HttpFoundation\Request;

class ControllerResolver extends BaseControllerResolver
{
    protected $app;

    /**
     * Constructor.
     *
     * @param Application     $app    An Application instance
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(Application $app, LoggerInterface $logger = null)
    {
        $this->app = $app;

        parent::__construct($logger);
    }

    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        /** @var \ReflectionParameter $param */
        foreach ($parameters as $param) {
            /** @var \Symfony\Component\Routing\Route $route */
            $route  = $this->app['routes']->get($request->attributes->get('_route'));
            $bundle = $this->app->getSubBundle($route->getOption('bundle_name'));
            if ($bundle && $param->getClass() && $param->getClass()->isInstance($bundle)) {
                $request->attributes->set($param->getName(), $bundle);

                continue;
            }

            if ($param->getClass() && $param->getClass()->isInstance($this->app)) {
                $request->attributes->set($param->getName(), $this->app);

                continue;
            }
        }

        return parent::doGetArguments($request, $controller, $parameters);
    }
}
