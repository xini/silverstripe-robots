<?php

namespace Innoweb\Robots\Controllers;

use Fromholdio\SuperLinkerRedirection\Pages\RedirectionPage;
use Innoweb\FolderPage\Pages\FolderPage;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\SiteConfig\SiteConfig;
use Wilr\GoogleSitemaps\GoogleSitemap;

class RobotsController extends Controller
{
    const MODE_ALLOW = 'allow';
    const MODE_DISALLOW = 'disallow';
    const MODE_CUSTOM = 'custom';

    public function index()
    {
        $mode = $this->getActiveMode();

        $this->getResponse()->addHeader(
            'Content-Type',
            'text/plain; charset="utf-8"'
        );

        if ($mode === self::MODE_ALLOW) {
            return $this->allow();
        } elseif ($mode === self::MODE_DISALLOW) {
            return $this->disallow();
        } elseif ($mode === self::MODE_CUSTOM) {
            return $this->custom();
        }

        return $this->httpError(404);
    }

    public function allow()
    {
        return $this->renderWith(self::class . '_allow');
    }

    public function disallow()
    {
        return $this->renderWith(self::class . '_disallow');
    }

    public function custom()
    {
        $site = $this->getRobotsSite();
        $custom = trim($site->getField('RobotsContent') ?? '');

        if (!$custom || empty($custom)) {
            return $this->disallow();
        }

        return $this->renderWith(
            [self::class . '_custom'],
            ['RobotsContent' => $custom]
        );
    }

    public function getActiveMode()
    {
        $site = $this->getRobotsSite();

        $forcedMode = $site->getForcedRobotsMode();
        if ($forcedMode) {
            return $forcedMode;
        }

        $siteMode = $site->RobotsMode;
        if ($siteMode) {
            return $siteMode;
        }

        return $site->getDefaultRobotsMode();
    }

    public function getRobotsSite()
    {
        $multisitesClass = $this->getMultisitesClassName();
        if (isset($multisitesClass) && class_exists($multisitesClass)) {
            $site = $multisitesClass::inst()->getCurrentSite();
        } else {
            $site = SiteConfig::current_site_config();
        }

        $this->extend('updateRobotsSite', $site);
        return $site;
    }

    public function getGoogleSitemapURL()
    {
        $url = '/sitemap.xml';

        $isGoogleSitemapsEnabled = ModuleLoader::inst()
            ->getManifest()
            ->moduleExists('wilr/silverstripe-googlesitemaps');

        if ($isGoogleSitemapsEnabled) {
            $isGoogleSitemapsEnabled = GoogleSitemap::enabled();
        }

        if ($isGoogleSitemapsEnabled) {
            $multisitesClass = $this->getMultisitesClassName();
            if (!empty($multisitesClass)) {
                $site = $multisitesClass::inst()->getCurrentSite();
                $url = $site->getURL() . $url;
            } else {
                $url = Director::absoluteURL($url);
            }
        } else {
            $url = null;
        }

        $this->extend('updateGoogleSitemapURL', $url);
        return $url;
    }

    public function getDisallowedPages()
    {
        $isGoogleSitemapsEnabled = ModuleLoader::inst()
            ->getManifest()
            ->moduleExists('wilr/silverstripe-googlesitemaps');

        if ($isGoogleSitemapsEnabled) {
            $isGoogleSitemapsEnabled = GoogleSitemap::enabled();
        }

        if ($isGoogleSitemapsEnabled) {
            $pages = SiteTree::get();

            // exclude redirector page
            $pages = $pages->exclude([
                'ClassName' => RedirectorPage::class
            ]);

            $siteClass = $this->getMultisitesSiteClassName();
            if (!empty($siteClass)) {
                $pages = $pages->exclude([
                    'ClassName' => $siteClass,
                ]);
            }

            // exclude redirection page
            $isRedirectionEnabled = ModuleLoader::inst()
                ->getManifest()
                ->moduleExists('fromholdio/silverstripe-superlinker-redirection');
            if ($isRedirectionEnabled) {
                $pages = $pages->exclude([
                    'ClassName' => RedirectionPage::class
                ]);
            }

            // exclude folder pages
            $isFoldersEnabled = ModuleLoader::inst()
                ->getManifest()
                ->moduleExists('innoweb/silverstripe-folder-page');
            if ($isFoldersEnabled) {
                $pages = $pages->exclude([
                    'ClassName' => FolderPage::class
                ]);
            }

            $googleSitemap = GoogleSitemap::singleton();
            $isFiltered = (bool) $googleSitemap->config()->get('use_show_in_search');
            $filterFieldName = 'ShowInSearch';
			if (GoogleSitemap::singleton()->hasMethod('getFilterFieldName')) {
                $filterFieldName = $googleSitemap->getFilterFieldName();
            }
            if ($isFiltered) {
                $pages = $pages->exclude($filterFieldName, true);
            } else {
                $pages = $pages->filter(['Priority' => '-1']);
            }

            return $pages;
        }

        return null;
    }

    public function getMultisitesClassName(): ?string
    {
        $manifest = ModuleLoader::inst()->getManifest();
        if ($manifest->moduleExists('symbiote/silverstripe-multisites')) {
            return \Symbiote\Multisites\Multisites::class;
        }
        if ($manifest->moduleExists('fromholdio/silverstripe-configured-multisites')) {
            return \Fromholdio\ConfiguredMultisites\Multisites::class;
        }
        return null;
    }

    public function getMultisitesSiteClassName(): ?string
    {
        $manifest = ModuleLoader::inst()->getManifest();
        if ($manifest->moduleExists('symbiote/silverstripe-multisites')) {
            return \Symbiote\Multisites\Model\Site::class;
        }
        if ($manifest->moduleExists('fromholdio/silverstripe-configured-multisites')) {
            return \Fromholdio\ConfiguredMultisites\Model\Site::class;
        }
        return null;
    }
}
