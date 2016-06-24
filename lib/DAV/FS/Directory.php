<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Afterlogic\DAV\FS;

class Directory extends \Sabre\DAV\FSExt\Directory {
    
	/**
	 * @var \CApiTenantsManager
	 */
	protected $oApiTenants = null;	
	
	/**
	 * @var \CApiUsersManager
	 */
	protected $oApiUsers = null;	
	
	/**
	 * @var int $iUserId
	 */
	protected $iUserId = null;	
	
	/**
	 * @var \CTenant
	 */
	protected $oTenant = null;
	
	public function getTenantsMan()
	{
		if ($this->oApiTenants === null) {
			
			$this->oApiTenants = \CApi::GetSystemManager('tenants');
		}
		
		return $this->oApiTenants;
	}

	public function getUser()
	{
		if ($this->iUserId === null) {
			
			$this->iUserId = \Afterlogic\DAV\Server::getInstance()->getUser();
		}
		return $this->iUserId;
	}
	
	public function setUser($iUserId)
	{
		$this->iUserId = $iUserId;
	}
	
	public function getTenant()
	{
		if ($this->oTenant == null) {
			// TODO: 
/*			$oAccount = $this->getAccount();
			if ($oAccount !== null) {
				
				$oApiTenants = $this->getTenantsMan();
				if ($oApiTenants) {
					
					$this->oTenant = $oApiTenants->getTenantById($oAccount->IdTenant);
				}
			}
 * 
 */
		}
		
		return $this->oTenant;
	}
	
	public function initPath() {
		
    }

	public function getPath() {

        return $this->path;

    }

    public function createDirectory($name) {

		$this->initPath();
		
        if ($name=='.' || $name=='..') {
			
			throw new DAV\Exception\Forbidden('Permission denied to . and ..');
		}
        $newPath = $this->path . '/' . $name;
		
		if (!is_dir($newPath)) {
			
			mkdir($newPath, 0777, true);
		}
    }

	public function createFile($name, $data = null) {

		$this->initPath();
		
		parent::createFile($name, $data);

		$oFile = $this->getChild($name);
		$aProps = $oFile->getProperties(array('Owner'));
		
		if (!isset($aProps['Owner'])) {
			
			$aProps['Owner'] = $this->getUser();
		}
		
		$oFile->updateProperties($aProps);

		if (!$this->updateQuota()) {
			
			$oFile->delete();
			throw new \Sabre\DAV\Exception\InsufficientStorage();
		}
    }

    public function getChild($name) {

		$this->initPath();
		
        $path = $this->path . '/' . trim($name, '/');

        if (!file_exists($path)) {
			
			throw new \Sabre\DAV\Exception\NotFound(
					'File with name ' . $path . ' could not be located'
			);
		}

		return is_dir($path) ? new Directory($path) : new File($path);
    }	
	
	public function getChildren() {

		$this->initPath();
		
		$nodes = array();
		
		if(!file_exists($this->path)) {
			
			mkdir($this->path);
		}
		
        foreach(scandir($this->path) as $node) {
			
			if($node!='.' && $node!='..' && $node!== '.sabredav' && 
					$node!== API_HELPDESK_PUBLIC_NAME)  {
				
				$nodes[] = $this->getChild($node);
			}
		}
        return $nodes;

    }
	
    public function childExists($name) {

		$this->initPath();
		
		return parent::childExists($name);

    }

    public function delete() {

		$this->initPath();
		
		parent::delete();
		
		$this->updateQuota();
    }	
	
	public function Search($pattern, $path = null) 
	{
		$aResult = array();
		
		$this->initPath();
		
		$path = ($path === null) ? $this->path : $path;
		$aItems = \api_Utils::SearchFiles($path, $pattern);
		if ($aItems) {
			
			foreach ($aItems as $sItem) {
				
				$aResult[] = is_dir($sItem) ? new Directory($sItem) : new File($sItem);
			}
		}
		
		return $aResult;
	}
	
	public function getRootPath($sType = \EFileStorageTypeStr::Personal)
	{
		$sRootPath = '';
		$iUserId = $this->getUser();
		
		if ($sType === \EFileStorageTypeStr::Corporate) {

			$sRootPath = \CApi::DataPath() . \Afterlogic\DAV\Constants::FILESTORAGE_PATH_ROOT . 
				\Afterlogic\DAV\Constants::FILESTORAGE_PATH_CORPORATE . '/' . 0;
		} else if ($sType === \EFileStorageTypeStr::Shared) {

			$sRootPath = \CApi::DataPath() . \Afterlogic\DAV\Constants::FILESTORAGE_PATH_ROOT . 
					\Afterlogic\DAV\Constants::FILESTORAGE_PATH_SHARED . '/' . $iUserId;
		} else {

			$sRootPath = \CApi::DataPath() . \Afterlogic\DAV\Constants::FILESTORAGE_PATH_ROOT . 
					\Afterlogic\DAV\Constants::FILESTORAGE_PATH_PERSONAL . '/' . $iUserId;
		}
		
		return $sRootPath;
	}
	
	public function getFullQuotaInfo()
	{
		$iFreeSize = 0;

		$sRootPath = $this->getRootPath(\EFileStorageTypeStr::Personal);
		$aSize = \api_Utils::GetDirectorySize($sRootPath);
		$iUsageSize = (int) $aSize['size'];

		$sRootPath = $this->getRootPath(\EFileStorageTypeStr::Corporate);
		$aSize = \api_Utils::GetDirectorySize($sRootPath);
		$iUsageSize += (int) $aSize['size'];

		$iUserId = $this->getUser();
		if ($iUserId) {
			
			$oTenant = $this->getTenant();
			if ($oTenant) {
				
				$iFreeSize = ($oTenant->FilesUsageDynamicQuotaInMB * 1024 * 1024) - $iUsageSize;
			}
		}
		
		return array($iUsageSize, $iFreeSize);
	}
	
	public function updateQuota()
	{
		if (isset($GLOBALS['__FILESTORAGE_MOVE_ACTION__']) && 
				$GLOBALS['__FILESTORAGE_MOVE_ACTION__']) {
			
			return true;
		}
		
		$iSizeUsage = 0;
		$aQuota = $this->getFullQuotaInfo();
		if (isset($aQuota[0])) {
			
			$iSizeUsage = $aQuota[0];
		}
		$oTenant = $this->getTenant();
		if (!isset($oTenant)) {
			
			return true;
		} else {
			
			$oTenantsMan = $this->getTenantsMan();
			if ($oTenantsMan) {
				
				return $oTenantsMan->allocateFileUsage($oTenant, $iSizeUsage);
				
			}
		}
	}
	
    /**
     * Returns the path to the resource file
     *
     * @return string
     */
    protected function getResourceInfoPath() {

		$this->initPath();

		return $this->path . '/.sabredav';

    }

    /**
     * Returns all the stored resource information
     *
     * @return array
     */
    protected function getResourceData() {

		$aResult = array();
		$path = $this->getResourceInfoPath();
        if (!file_exists($path)) {
			
			return array('properties' => array());
		}

        // opening up the file, and creating a shared lock
        $handle = fopen($path,'r');
        flock($handle,LOCK_SH);
        $sData = '';

        // Reading data until the eof
        while(!feof($handle)) {
            $sData.=fread($handle,8192);
        }

        // We're all good
        fclose($handle);

        // Unserializing and checking if the resource file contains data for this file
        $aData = unserialize($sData);
		foreach ($aData as $sName => $aValue) {
			
			$aResultItem = array('@Name' => $sName);
			if (isset($aValue['properties']) && is_array($aValue['properties'])) {
				
				$aResultItem = array_merge($aResultItem, $aValue['properties']);
			}
			$aResult[] = $aResultItem;
		}

        return $aResult;

    }	
	
	public function getChildrenProperties()
	{
		return $this->getResourceData();
	}
	
}