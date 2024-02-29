<?php

class FileFinder
{
    private $sharePath;
    private $bashFindString;

    private $age_Enabled;
    private $age_Days;
    private $age_UseCtime;
    private $age_UseLessThan;
    private $size_Enabled;
    private $size_MB;
    private $size_UseLessThan;
    private $sparseness_Enabled;
    private $sparseness_Value;
    private $sparseness_UseLessThan;
    private $skipList_Enabled;
    private $skipList_Path;
    private $skipList_UseMoveOnly;
    private $skipList_FileList;
    private $skipList_DirectoriesList;
    private $fileType_Enabled;
    private $fileType_FileTypes;
    private $fileType_CaseSensitive;
    private $fileType_UseMoveOnly;
    private $hiddenFiles_Enabled;
    private $hiddenFiles_UseMoveOnly;

    // Implement the MOVE ONLY Feature set
    // Implement functionality for finding files on the ARRAY, need a variable for knowing what storage group it is
    // Implement functionality for subbing the "/mnt/user" with "/mnt/[cache]"



    // Constructor method
    public function __construct($sharePath, $moverTuningCfg)
    {
        $this->sharePath = $sharePath;
        $this->age_Enabled = $moverTuningCfg['age_Enabled'];
        $this->age_Days = $moverTuningCfg['age_Days'];
        $this->age_UseCtime = $moverTuningCfg['age_UseCtime'];
        $this->age_UseLessThan = $moverTuningCfg['age_UseLessThan'];
        $this->size_Enabled = $moverTuningCfg['size_Enabled'];
        $this->size_MB = $moverTuningCfg['size_MB'];
        $this->size_UseLessThan = $moverTuningCfg['size_UseLessThan'];
        $this->sparseness_Enabled = $moverTuningCfg['sparseness_Enabled'];
        $this->sparseness_Value = $moverTuningCfg['sparseness_Value'];
        $this->sparseness_UseLessThan = $moverTuningCfg['sparseness_UseLessThan'];
        $this->skipList_Enabled = $moverTuningCfg['skipList_Enabled'];
        $this->skipList_Path = $moverTuningCfg['skipList_Path'];
        $this->skipList_UseMoveOnly = $moverTuningCfg['skipList_UseMoveOnly'];
        $this->skipList_FileList = [];
        $this->skipList_DirectoriesList = [];
        $this->fileType_Enabled = $moverTuningCfg['fileType_Enabled'];
        $this->fileType_FileTypes = $moverTuningCfg['fileType_FileTypes'];
        $this->fileType_CaseSensitive = $moverTuningCfg['fileType_CaseSensitive'];
        $this->fileType_UseMoveOnly = $moverTuningCfg['fileType_UseMoveOnly'];
        $this->hiddenFiles_Enabled = $moverTuningCfg['hiddenFiles_Enabled'];
        $this->hiddenFiles_UseMoveOnly = $moverTuningCfg['hiddenFiles_UseMoveOnly'];
    }


    // Getter methods


    // Class methods
    public function createShareTunedFilesList()
    {
        $tunedFilesList = [];

        // Build the find command string based on the users tuning settings
        $this->bashFindString = $this->buildFindString($this->sharePath);

        // Executes the find command and returns a filtered associative array of ['path', 'size']
        $tunedFilesList = $this->executeFindCommand($this->bashFindString);

        return $tunedFilesList;
    }

    private function buildFindString($sharePath)
    {

        // builds the find string based on the settings
        $bashFindString = "find '$sharePath' -depth";

        // Age settings
        if ($this->age_Enabled)
        {
            $bashFindString .= $this->age_UseCtime ? ' -ctime' : ' -mtime';
            $bashFindString .= $this->age_UseLessThan ? ' -' . ($this->age_Days + 1) : ' +' . ($this->age_Days - 1);
        }

        // Size settings
        if ($this->size_Enabled)
        {
            $bashFindString .= $this->size_UseLessThan ? ' -size -' . ($this->size_MB + 1) . 'M' : ' -size +' . ($this->size_MB - 1) . 'M';
        }

        // hidden file
        if ($this->hiddenFiles_Enabled)
        {
            $bashFindString .= " ! -path '*/\.*'";
        }

        // file type
        if ($this->fileType_Enabled)
        {
            $this->fileType_FileTypes = $this->delimitFileTypes($this->fileType_FileTypes);
            $findOption = $this->fileType_CaseSensitive ? ' -name ' : ' -iname ';
            foreach ($this->fileType_FileTypes as &$type)
            {
                $bashFindString .= $findOption . $type;
            }
        }

        // folder list
        if ($this->skipList_Enabled)
        {
            list($this->skipList_DirectoriesList, $this->skipList_FileList) = $this->seperateFilesAndDirectories($this->skipList_Path);
            foreach ($this->skipList_DirectoriesList as $path)
            {
                $bashFindString .= " ! -path '$path'";
            }
        }

        // Skip file list done after find string in php for efficiency

        // Custom printout include path and file size
        $bashFindString .= ' -printf %p:%s';

        // Sparseness done after find string in php for efficiency
        if ($this->sparseness_Enabled)
        {
            $bashFindString .= ':%S\n';
        } else
        {
            $bashFindString .= '\n';
        }

        return $bashFindString;
    }

    // Returns list of delimited file types
    private function delimitFileTypes($fileTypesString)
    {
        $fileTypes = explode(',', $fileTypesString);
        foreach ($fileTypes as &$type)
        {
            $type = trim($type);
            if ($type[0] !== '.')
            {
                $type = '.' . $type;
            }
        }
        return $fileTypes;
    }

    // Returns list of files to exclude and folders to exclude given a path for a file list
    private function seperateFilesAndDirectories($fileListPathString)
    {
        $fileListPathString = trim($fileListPathString);
        $fileListContents = file_get_contents($fileListPathString);
        $fileList = explode("\n", $fileListContents);
        $fileList = array_filter($fileList, 'strlen');
        $directoryList = [];
        foreach ($fileList as &$path)
        {
            $path = trim($path);
            $path = rtrim($path, '/*');
            if (is_dir($path))
            {
                $directoryList[] = $path . '*';
            }
        }
        return [$directoryList, $fileList];
    }

    // Helper function to do an array diff between associative array and normal array. Used to filter exclude list and sparseness
    private function udiffCompare($outputFileList, $skipFileList, $option = 0)
    {
        if ($option == 0)
        {     // Match skip list paths
            return $outputFileList['path'] == $skipFileList ? 0 : 1;    // Will REMOVE if both path strings match | REMOVE = take off move list
        } elseif ($option == 1)
        { // Match sparseness settings
            if ($this->sparseness_UseLessThan)
            {
                return $outputFileList['sparseness'] >= $this->sparseness_Value ? 0 : 1; // Will REMOVE if sparseness value is greater than or equal to user setting
            } else
            {
                return $outputFileList['sparseness'] <= $this->sparseness_Value ? 0 : 1; // Will REMOVE if sparseness value is less than or equal to user setting
            }
        } else
        {  // Match skip list paths and sparseness
            $diff = $outputFileList['path'] == $skipFileList ? 0 : 1;
            if ($this->sparseness_UseLessThan)
            {
                $diff += $outputFileList['sparseness'] >= $this->sparseness_Value ? 0 : 1;
            } else
            {
                $diff += $outputFileList['sparseness'] <= $this->sparseness_Value ? 0 : 1;
            }

            return $diff <= 1 ? 0 : 1;  // Will REMOVE if path and/or sparseness matches
        }
    }

    // Helper function to execute find command and save in multi-dimensional array
    private function executeFindCommand($bashFindString)
    {
        exec($bashFindString, $output, $returnStatus);
        $tunedFilesList = [];

        // Check the command executed correctly
        if ($returnStatus === 0) 
        {
            $filesList = $this->buildAssociativeArray($output);             // Builds an associative array for path:size(:sparseness)
            $tunedFilesList = $this->filterPathOrSparseness($filesList);    // Applies the skip list and/or sparseness filtering
        } else
        {
            // Print out error message and return empty list, maybe null.
            $tunedFilesList = null;
        }
        return $tunedFilesList;
    }

    private function buildAssociativeArray($findCommandOutput)
    {
        $filesList = [];
        if ($this->sparseness_Enabled)
        {
            // Put each path into associative array
            foreach ($findCommandOutput as $line)
            {
                list($path, $size, $sparseness) = explode(':', $line);
                $filesList[] = array('path' => $path, 'size' => $size, 'sparseness' => $sparseness);
            }
        } else
        {
            // Does not include column for sparseness
            foreach ($findCommandOutput as $line)
            {
                list($path, $size) = explode(':', $line);
                $filesList[] = array('path' => $path, 'size' => $size);
            }
        }
        return $filesList;
    }

    private function filterPathOrSparseness($filesList) {
        if ($this->sparseness_Enabled)
        {
            // Filter for sparseness and/or exclude list
            $tunedFilesList = array_udiff($filesList, $this->skipList_FileList, function ($a, $b) {
                return $this->skipList_Enabled ? $this->udiffCompare($a, $b, 2) : $this->udiffCompare($a, $b, 1); // If skipList is enabled, filter sparseness AND paths.
            });

            // Remove sparseness column
            foreach ($tunedFilesList as &$subArray) {
                unset($subArray['sparseness']);
            }
        } elseif ($this->skipList_Enabled)
        {
            // Filter exceluded paths if skip list is enabled
            $tunedFilesList = array_udiff($filesList, $this->skipList_FileList, [$this, 'udiffCompare']);
        } else 
        {
            // Don't filter the list
            $tunedFilesList = $filesList;
        }
        return $tunedFilesList;
    }





}
