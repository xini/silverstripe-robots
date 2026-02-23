<?php

namespace Innoweb\Robots\Extensions;

use Innoweb\Robots\Controllers\RobotsController;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Security;
use Wilr\GoogleSitemaps\GoogleSitemap;

class PageExtension extends Extension
{
    public function updateMetaComponents(array &$tags)
    {
        if ($this->getOwner()->config()->robots_enable_metatag == true) {
            $tags['robots'] = [
                'attributes' => [
                    'name' => 'robots',
                    'content' => $this->getOwner()->getRobotsTagString(),
                ],
            ];
        }
    }

    public function getRobotsTagString()
    {
        $follow = "follow";
        $index = "index";

        if (RobotsController::create()->getActiveMode() == RobotsController::MODE_DISALLOW) {
            $follow = "nofollow";
            $index = "noindex";
        } elseif (Controller::curr() && is_a(Controller::curr(), Security::class)) {
            $follow = "nofollow";
            $index = "noindex";
        } elseif (Controller::curr() && is_a(Controller::curr(), \SilverStripe\ErrorPage\ErrorPageController::class)) {
            $follow = "nofollow";
            $index = "noindex";
        } elseif (Controller::curr() && is_a(Controller::curr(), \Fromholdio\Errored\Errored::class)) {
            $follow = "nofollow";
            $index = "noindex";
        } elseif ($this->getOwner()->hasExtension(\Wilr\GoogleSitemaps\Extensions\GoogleSitemapSiteTreeExtension::class)
            && ($priority = $this->getOwner()->Priority)
            && $priority == -1
        ) {
            $index = "noindex";
        } elseif (class_exists(GoogleSitemap::class)
            && method_exists(GoogleSitemap::class, 'getFilterFieldName')
            && ($googleSitemap = GoogleSitemap::singleton())
            && ($filterFieldName = $googleSitemap->getFilterFieldName())
            && (!$this->getOwner()->{$filterFieldName})
        ) {
            $index = "noindex";
        } elseif ($this->getOwner()->hasExtension(\Wilr\GoogleSitemaps\Extensions\GoogleSitemapSiteTreeExtension::class)
            && !$this->getOwner()->ShowInSearch
        ) {
            $index = "noindex";
        }

        // get config settings
        if ($this->getOwner()->config()->robots_noindex == true) {
            $index = "noindex";
        }
        if ($this->getOwner()->config()->robots_nofollow == true) {
            $follow = "nofollow";
        }

        $robotsString = "$index, $follow";

        if ($this->getOwner()) {
            $this->getOwner()->invokeWithExtensions('updateRobotsTagString', $robotsString);
        }

        if (Controller::curr()) {
            Controller::curr()->invokeWithExtensions('updateRobotsTagString', $robotsString);
        }

        return $robotsString;
    }
}
