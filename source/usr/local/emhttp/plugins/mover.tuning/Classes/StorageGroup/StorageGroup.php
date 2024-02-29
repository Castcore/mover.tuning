<?php

class StorageGroup {
    private $name;
    private $totalCapacity;
    private $usedCapacity;
    private $fileSystemType;
    private $mountPoint;

    // Constructor method
    public function __construct($name, $totalCapacity, $usedSpace, $fileSystemType, $mountPoint) {
        $this->name = $name;
        $this->totalCapacity = $totalCapacity;
        $this->usedSpace = $usedSpace;
        $this->fileSystemType = $fileSystemType;
        $this->mountPoint = $mountPoint;
    }

    // Getter methods
    public function getName() {
        return $this->name;
    }

    public function getTotalCapacity() {
        return $this->totalCapacity;
    }

    public function getUsedSpace() {
        return $this->usedCapacity;
    }

    public function getFileSystemType() {
        return $this->fileSystemType;
    }

    public function getMountPoint() {
        return $this->mountPoint;
    }

    // Class methods
    // calculate total capcity
    // calculate used capacity
    // 

}