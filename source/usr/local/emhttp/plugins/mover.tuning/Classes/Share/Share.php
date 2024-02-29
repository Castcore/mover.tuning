<?php

require_once '../../mover.tuning.helpers.php';
require_once PLUGINS_PATH . '/dynamix/include/Wrappers.php';
require_once '../FileFinder/FileFinder.php';

class Share
{
    private $sharePath;
    private $shareName;
    private $shareUseCache;
    private $shareCachePool;
    private $globalSettingsOverride;
    private $moverTuningSettingsCfg;
    private $filesToMoveList;
    private $emptyDirectoriesList;
    private $fileFinder;

    // Constructor method
    public function __construct($sharePath)
    {
        $this->sharePath = $sharePath;
        $this->shareName = basename($sharePath, '.cfg');
        $this->checkShareMoverSettings();   // Gets the shares cache->array, array->cache options and cache pool name
        if ($this->checkShareUsesMover())   // Only sets variables if the share uses the mover
        {    
            $this->moverTuningSettingsCfg = parse_plugin_cfg(MOVER_TUNING_PLUGIN_NAME);
            $this->checkShareMoverTuningSettings();
            $this->fileFinder = new FileFinder($sharePath, $this->moverTuningSettingsCfg);
        } else
        {
            $this->moverTuningSettingsCfg = null;
            $this->fileFinder = null;
        }
        $this->filesToMoverList = null;
        $this->emptyDirectoriesList = null;
    }


    // Getter methods
    public function getName()
    {
        return $this->shareName;
    }

    // Function to populate the "fileToMoveList" variable
    public function getFilesToMoveList()
    { 
        // Will update the list everytime this method is called
        $this->filesToMoveList = $this->checkShareUsesMover() ? $this->fileFinder->createShareTunedFilesList() : null;
        return $this->filesToMoveList;
    }

    // Function to populate the emptyDirectoriesList variable
    public function getEmptyDirectoriesList()
    {
        $this->emptyDirectoriesList = $this->checkShareUsesMover() ? $this->fileFinder->createShareEmptyDirectoriesList() : null;
    }

    public function checkShareUsesMover()
    {
        return in_array($this->shareUseCache, ['yes', 'prefer']);
    }


    // Class methods
    private function checkShareMoverSettings()
    {
        $shareCfg = parse_ini_file(CONFIG_PATH . $this->shareName . '.cfg');
        $this->shareUseCache = isset($shareCfg['shareUseCache']) ? $shareCfg['shareUseCache'] : null;
        $this->shareCachePool = isset($shareCfg['shareCachePool']) ? $shareCfg['shareCachePool'] : null;
    }

    // Checks if share has it's own mover tuning settings 
    private function checkShareMoverTuningSettings()
    {
        $overrideCfgPath = MOVER_TUNING_BOOT_PATH . "/shareOverrideCfg/{$this->shareName}.cfg";
        if (file_exists($overrideCfgPath))
        {
            $this->globalSettingsOverride = true;
            array_replace_recursive($this->moverTuningSettingsCfg, parse_ini_file($overrideCfgPath));
        } else
        {
            $this->globalSettingsOverride = false;
        }
    }


}