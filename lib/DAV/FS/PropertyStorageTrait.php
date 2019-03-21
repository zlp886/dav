<?php

namespace Afterlogic\DAV\FS;

trait PropertyStorageTrait
{

    public function getProperty($sName)
    {
        $aData = $this->getResourceData();
        return isset($aData[$sName]) ? $aData[$sName] : null;
    }

    public function setProperty($sName, $mValue)
    {
        $aData = $this->getResourceData();
        $aData[$sName] = $mValue;
        $this->putResourceData($aData);
    }	

    /**
     * Updates properties on this node,
     *
     * @param array $properties
     * @see Sabre\DAV\IProperties::updateProperties
     * @return bool|array
     */
    public function updateProperties($properties) 
    {
        $resourceData = $this->getResourceData();

        foreach($properties as $propertyName=>$propertyValue) 
        {
            // If it was null, we need to delete the property
            if (is_null($propertyValue)) 
            {
                if (isset($resourceData['properties'][$propertyName])) 
                {
                    unset($resourceData['properties'][$propertyName]);
                }
            } 
            else 
            {
                $resourceData['properties'][$propertyName] = $propertyValue;
            }

        }

        $this->putResourceData($resourceData);
        return true;
    }

    /**
     * Returns a list of properties for this nodes.;
     *
     * The properties list is a list of propertynames the client requested, encoded as xmlnamespace#tagName, for example: http://www.example.org/namespace#author
     * If the array is empty, all properties should be returned
     *
     * @param array $properties
     * @return array
     */
    function getProperties($properties) 
    {
        $resourceData = $this->getResourceData();

        // if the array was empty, we need to return everything
        if (!$properties) return $resourceData['properties'];

        $props = [];
        foreach($properties as $property) 
        {
            if (isset($resourceData['properties'][$property])) $props[$property] = $resourceData['properties'][$property];
        }

        return $props;
    }

    /**
     * Returns the path to the resource file
     *
     * @return string
     */
    protected function getResourceInfoPath() 
    {
        list($parentDir) = \Sabre\Uri\split($this->path);
        return $parentDir . '/.sabredav';
    }

    /**
     * Returns all the stored resource information
     *
     * @return array
     */
    protected function getResourceData() 
    {
        $path = $this->getResourceInfoPath();
        if (!file_exists($path)) return ['properties' => []];

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'r');
    //        flock($handle,LOCK_SH);
        $data = '';

        // Reading data until the eof
        while(!feof($handle)) 
        {
            $data.=fread($handle,8192);
        }

        // We're all good
        fclose($handle);

        // Unserializing and checking if the resource file contains data for this file
        $data = unserialize($data);
        if (!isset($data[$this->getName()])) 
        {
            return ['properties' => []];
        }

        $data = $data[$this->getName()];
        if (!isset($data['properties'])) $data['properties'] = [];
        return $data;
    }

    /**
     * Updates the resource information
     *
     * @param array $newData
     * @return void
     */
    protected function putResourceData(array $newData) 
    {
        $path = $this->getResourceInfoPath();

        $handle1 = @fopen($path,'r');

        $data = [];
        if (is_resource($handle1))
        {
            $data = '';
            rewind($handle1);
            // Reading data until the eof
            while(!feof($handle1)) 
            {
                $data.=fread($handle1,8192);
            }
            $data = unserialize($data);
            fclose($handle1);
        }

        $handle2 = fopen($path,'w');
        $data[$this->getName()] = $newData;

        rewind($handle2);
        fwrite($handle2,serialize($data));
        fclose($handle2);
    }

    /**
     * Renames the node
     *
     * @param string $name The new name
     * @return void
     */
    public function setName($name) 
    {
        list($parentPath, $oldName) = \Sabre\Uri\split($this->path);
        list(, $newName) = \Sabre\Uri\split($name);
        $newPath = $parentPath . '/' . $newName;
        
        $sRelativePath = $this->getRelativePath();

        $oldPathForShare = $sRelativePath . '/' .$oldName;
        $newPathForShare = $sRelativePath . '/' .$newName;

        $pdo = new \Afterlogic\DAV\FS\Backend\PDO();
        $pdo->updateShare($this->getOwner(), $this->getStorage(), $oldPathForShare, $newPathForShare);

        // We're deleting the existing resourcedata, and recreating it
        // for the new path.
        $resourceData = $this->getResourceData();
        $this->deleteResourceData();

        rename($this->path, $newPath);
        $this->path = $newPath;
        $this->putResourceData($resourceData);
    }

    /**
     * @return bool
     */
    public function deleteResourceData() 
    {
        // When we're deleting this node, we also need to delete any resource information
        $path = $this->getResourceInfoPath();
        if (!file_exists($path)) return true;

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'a+');
        flock($handle,LOCK_EX);
        $data = '';

        rewind($handle);

        // Reading data until the eof
        while(!feof($handle)) 
        {
            $data.=fread($handle,8192);
        }

        // Unserializing and checking if the resource file contains data for this file
        $data = unserialize($data);
        if (isset($data[$this->getName()])) unset($data[$this->getName()]);
        ftruncate($handle,0);
        rewind($handle);
        fwrite($handle,serialize($data));
        fclose($handle);

        return true;
    }
}