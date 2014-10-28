<?php
/**
 * User: dongww
 * Date: 2014-9-21
 * Time: 15:51
 */

namespace PhpGo\Framework\Core;

interface BundleInterface
{
    const TYPE_BUNDLE = 'bundle';
    const TYPE_APPLICATION = 'application';

    public function getRouteCollection();

    public function getParent();

    public function getApp();

    public function getName();

    public function getSubBundles();

    public function on($eventName, $callback, $priority = 0);

    public function run();
}
