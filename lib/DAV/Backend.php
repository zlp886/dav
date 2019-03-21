<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Afterlogic\DAV;

class Backend
{
	public static $aBackends = array();

	public static function __callStatic($sMethod, $aArgs)
	{
		$oResult = null;
		if (!method_exists('Backend', $sMethod)) {
			
			$oResult = self::getBackend(strtolower($sMethod));
		}
		return $oResult;
	}	
	
	public static function getBackend($sName)
	{
		if (!isset(self::$aBackends[$sName])) {
			$oBackend = null;
			switch ($sName) {
				case 'auth':
					$oBackend = Auth\Backend::getInstance();
					break;
				case 'principal':
					$oBackend = new Principal\Backend\PDO();
					break;
				case 'caldav':
					$oBackend = new CalDAV\Backend\PDO();
					break;
				case 'carddav':
					$oBackend = new CardDAV\Backend\PDO();
					break;
				case 'carddav-owncloud':
					$oBackend = new CardDAV\Backend\OwnCloudPDO();
					break;
				case 'lock':
					$oBackend = new Locks\Backend\PDO();
					break;
				case 'reminders':
					$oBackend = new Reminders\Backend\PDO();
					break;
				case 'fs':
					$oBackend = new FS\Backend\PDO();
					break;
			}
			if (isset($oBackend)) {
				
				self::$aBackends[$sName] = $oBackend;
			}
		}
		return self::$aBackends[$sName];
	}
	
}