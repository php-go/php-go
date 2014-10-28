<?php
/**
 * User: dongww
 * Date: 2014-9-21
 * Time: 19:38
 */

namespace PhpGo\Framework;


use PhpGo\Framework\Core\BundleAbstract;

class BundleCollection
{
    protected $bundles = [];

    public function __construct($bundles = [])
    {
        if($bundles){
            $this->addBundles($bundles);
        }
    }

    public function addBundle(BundleAbstract $bundle)
    {
        $this->bundles[$bundle->getName()] = $bundle;

        return $this;
    }

    public function addBundles($bundles = [])
    {
        if ($bundles instanceof BundleCollection) {
            $bundles = $bundles->getBundles();
        }

        foreach ($bundles as $component) {
            $this->addBundle($component);
        }

        return $this;
    }

    public function getBundle($name)
    {
        return isset($this->bundles[$name]) ? $this->bundles[$name] : null;
    }

    /**
     * @return BundleAbstract[]
     */
    public function getBundles()
    {
        return $this->bundles;
    }
}
