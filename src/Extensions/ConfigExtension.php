<?php

namespace Innoweb\Robots\Extensions;

use Innoweb\Robots\Controllers\RobotsController;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Core\Extension;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\SSViewer;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class ConfigExtension extends Extension
{
    private static $force_robots_mode;
    private static $enable_custom_robots = true;
    private static $robots_tab_path = 'Root.Robots';

    private static $robots_mode_labels = [
        RobotsController::MODE_ALLOW => 'Allow all',
        RobotsController::MODE_DISALLOW => 'Disallow all',
        RobotsController::MODE_CUSTOM => 'Set custom robots.txt content'
    ];

    private static $db = [
        'RobotsMode' => 'Varchar(20)',
        'RobotsContent' => 'Text'
    ];

    private static $casting = [
        'RenderedContentAllow' => 'Text',
        'RenderedContentDisallow' => 'Text',
    ];

    public function updateSiteCMSFields(FieldList $fields)
    {
        $fields = $this->getOwner()->applyRobotsCMSFields($fields);
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields = $this->getOwner()->applyRobotsCMSFields($fields);
    }

    public function applyRobotsCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'RobotsMode',
            'RobotsContent',
            'SiteAdvancedHeader',
            'RobotsTxt',
        ]);

        $tabPath = $this->getOwner()->getRobotsTabPath();
        if (!$tabPath) {
            return;
        }

        $isCustomAllowed = $this->getOwner()->getIsCustomRobotsModeAllowed();
        $customField = TextareaField::create('RobotsContent', 'Custom Content');

        $forcedMode = $this->getOwner()->getForcedRobotsMode();
        if ($forcedMode) {
            if ($isCustomAllowed && $forcedMode === RobotsController::MODE_CUSTOM) {
                $fields->addFieldToTab($tabPath, $customField);
            }
            return;
        }

        $options = $this->getOwner()->getRobotsModeOptions();
        if (empty($options)) {
            return;
        }

        $optionsCount = count($options);
        if ($optionsCount === 1) {
            if ($isCustomAllowed) {
                $fields->addFieldToTab($tabPath, $customField);
            } else {
                $tabPath = 'Root.Main';
            }

            $hiddenModeField = HiddenField::create('RobotsMode', null);
            $fields->addFieldToTab($tabPath, $hiddenModeField);
            $this->getOwner()->RobotsMode = array_key_first($options);
        } else {
            $modeField = OptionsetField::create('RobotsMode', 'Robots.txt', $options);
            $fields->addFieldToTab($tabPath, $modeField);

            if ($isCustomAllowed) {
                $fields->addFieldToTab($tabPath, $customField);
                $customField->displayIf('RobotsMode')->isEqualTo(RobotsController::MODE_CUSTOM);
            }
        }

        if (isset($options[RobotsController::MODE_ALLOW])) {
            $allowedOutputField = Wrapper::create(
                TextareaField::create(
                    'AllowedContent',
                    'Robots.txt output',
                    $this->owner->getRenderedContentAllow()
                )->setReadonly(true)
            );
            $fields->addFieldToTab($tabPath, $allowedOutputField);
            $allowedOutputField->displayIf('RobotsMode')->isEqualTo(RobotsController::MODE_ALLOW);
        }

        if (isset($options[RobotsController::MODE_DISALLOW])) {
            $disallowedOutputField = Wrapper::create(
                TextareaField::create(
                    'ContentDisallow',
                    'Robots.txt output',
                    $this->owner->getRenderedContentDisallow()
                )->setReadonly(true)
            );
            $fields->addFieldToTab($tabPath, $disallowedOutputField);
            $disallowedOutputField->displayIf('RobotsMode')->isEqualTo(RobotsController::MODE_DISALLOW);
        }

		$this->owner->invokeWithExtensions('updateRobotsCMSFields', $fields);
        return $fields;
    }

    public function onAfterPopulateDefaults()
    {
        $this->getOwner()->RobotsMode = $this->getOwner()->getDefaultRobotsMode();
    }

    public function onRequireDefaultRecords()
    {
        // get correct config class
        if (class_exists('Symbiote\Multisites\Multisites')) {
            $configs = \Symbiote\Multisites\Model\Site::get();
        } elseif (class_exists('Fromholdio\ConfiguredMultisites\Multisites')) {
            $configs = \Fromholdio\ConfiguredMultisites\Model\Site::get();
        } else {
			$class = get_class($this->owner);
			$configs = $class::get()->limit(1);
        }
        // update configs if required
		if ($configs->count() > 0) {
            foreach ($configs as $config) {
                if (!$config->RobotsMode) {
                    if ($config->RobotsContent) {
                        $config->RobotsMode = RobotsController::MODE_CUSTOM;
                    } else {
                        $config->RobotsMode = $config->getDefaultRobotsMode();
                    }
                    $config->write();
                }
            }
        }
    }

    public function getDefaultRobotsMode(): string
    {
        $mode = RobotsController::MODE_DISALLOW;
        $this->getOwner()->invokeWithExtensions('updateDefaultRobotsMode');
        return $mode;
    }

    public function getIsCustomRobotsModeAllowed(): bool
    {
        $isAllowed = false;
        $options = $this->getOwner()->getRobotsModeOptions();
        if ($options && isset($options[RobotsController::MODE_CUSTOM])) {
            $isAllowed = true;
        }
        $this->getOwner()->invokeWithExtensions('updateIsCustomRobotsModeAllowed', $isAllowed);
        return $isAllowed;
    }

    public function getForcedRobotsMode(): ?string
    {
        $mode = null;
        $options = $this->getOwner()->getRobotsModeOptions();
        $envMode = Environment::getEnv('FORCE_ROBOTS_MODE');
        if ($envMode && isset($options[$envMode])) {
            $mode = $envMode;
        }
        if (!$mode) {
            $configMode = $this->getOwner()->config()->get('force_robots_mode');
            if ($configMode && isset($options[$configMode])) {
                $mode = $configMode;
            }
        }
        $this->getOwner()->invokeWithExtensions('updateForcedRobotsMode', $mode);
        return $mode;
    }

    public function getRobotsModeOptions(): array
    {
        $options = $this->getOwner()->config()->get('robots_mode_labels');
        foreach ($options as $key => $value) {
            if (!$value) {
                unset($options[$key]);
            }
        }
        $this->getOwner()->invokeWithExtensions('updateRobotsModeOptions', $options);
        if (!$options || !is_array($options) || count($options) < 1) {
            $options = [];
        }
        return $options;
    }

    public function getRobotsTabPath(): ?string
    {
        $path = $this->getOwner()->config()->get('robots_tab_path');
        $this->getOwner()->invokeWithExtensions('updateRobotsTabPath', $path);
        return $path;
    }

    public function getRenderedContentAllow(): string
    {
        $oldThemes = SSViewer::get_themes();
        SSViewer::set_themes(SSViewer::config()->uninherited('themes'));
        $controller = RobotsController::create();
        $result = $controller->allow();
        SSViewer::set_themes($oldThemes);
		return (string) $result;
    }

    public function getRenderedContentDisallow(): string
    {
        $oldThemes = SSViewer::get_themes();
        SSViewer::set_themes(SSViewer::config()->uninherited('themes'));
        $controller = RobotsController::create();
        $result = $controller->disallow();
        SSViewer::set_themes($oldThemes);
        return (string) $result;
    }
}
