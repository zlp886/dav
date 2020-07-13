<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Afterlogic\DAV\FS\Shared;

use LogicException;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Root extends \Afterlogic\DAV\FS\Root implements \Sabre\DAVACL\IACL {

//    use NodeTrait;

	protected $pdo = null;

	public function __construct() {

		$this->getUser();

		$this->pdo = new \Afterlogic\DAV\FS\Backend\PDO();
	}

	public function getName() {

        return \Aurora\System\Enums\FileStorageType::Shared;

	}

    /**
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
	public function getOwner()
	{
		return \Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . $this->UserPublicId;
	}

	protected function populateItem($aSharedFile)
	{
		$mResult = false;

		if (is_array($aSharedFile))
		{
			$oServer = \Afterlogic\DAV\Server::createInstance();
			$sCurrentUser = $oServer->getUser();
			$oServer->setUser(basename($aSharedFile['owner']));
			$oItem = null;
			try
			{
				$oItem = $oServer->tree->getNodeForPath('files/' . $aSharedFile['storage'] . '/' .  trim($aSharedFile['path'], '/'));
			}
			catch (\Exception $oEx)
			{
				\Aurora\Api::LogException($oEx);
			}
			$oServer->setUser($sCurrentUser);

			if ($oItem instanceof \Sabre\DAV\FS\Node)
			{
				$oItem->setAccess((int) $aSharedFile['access']);
			}

			if ($oItem instanceof \Afterlogic\DAV\FS\File)
			{
				$mResult = new File($oItem);
			}
			else if ($oItem instanceof \Afterlogic\DAV\FS\Directory)
			{
				$mResult = new Directory($oItem);
			}

			if ($mResult)
			{
				$sRelativeNodePath = \str_replace(
					$oItem->getName(),
					'',
					$aSharedFile['path']
				);
				$mResult->setOwnerPublicId(basename($aSharedFile['owner']));
				$mResult->setRelativeNodePath($sRelativeNodePath);
			}
		}
		return $mResult;
	}

	public function getChild($name)
	{
		$aSharedFile = $this->pdo->getSharedFileByUid(\Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . $this->UserPublicId, $name);

		return $this->populateItem($aSharedFile);
    }

	public function getChildren()
	{
		$aResult = [];

		$aSharedFiles = $this->pdo->getSharedFilesForUser(\Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . $this->UserPublicId);

		foreach ($aSharedFiles as $aSharedFile)
		{
			$oSharedItem = $this->populateItem($aSharedFile);
			if ($oSharedItem)
			{
				$aResult[] = $oSharedItem;
			}
		}

		return $aResult;
	}

	public function createFile($name, $data = null, $rangeType = 0, $offset = 0, $extendedProps = [])
	{
		throw new \Sabre\DAV\Exception\Forbidden();
	}

}
