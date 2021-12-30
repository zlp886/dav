<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Afterlogic\DAV\FS\Shared;

use Afterlogic\DAV\Constants;
use Afterlogic\DAV\Server;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Directory extends \Afterlogic\DAV\FS\Directory
{
    use PropertyStorageTrait;
    use NodeTrait;

    public function __construct($name, $node)
    {
        $this->name = $name;
        $this->node = $node;
    }

    public function getChild($path)
    {
        if ($this->node) {
            $oChild = $this->node->getChild($path);
            $aExtendedProps = $oChild->getProperty('ExtendedProps');
            if (!(is_array($aExtendedProps) && isset($aExtendedProps['InitializationVector']))) {
                if ($oChild)
                {
                    if ($oChild instanceof \Afterlogic\DAV\FS\File)
                    {
                        $oChild = new File($oChild->getName(), $oChild);
                    }
                    else if ($oChild instanceof \Afterlogic\DAV\FS\Directory)
                    {
                        $oChild = new Directory($oChild->getName(), $oChild);
                    }
                    $oPdo = new \Afterlogic\DAV\FS\Backend\PDO();
                    $aSharedFile = $oPdo->getSharedFile(Constants::PRINCIPALS_PREFIX . $this->getUser(), $oChild->getNode()->getRelativePath() . '/' . $oChild->getNode()->getName());
                    if ($aSharedFile) {
                        $oChild->setAccess($aSharedFile['access']);    
                    }
                    else {
                        $oChild->setInherited(true);
                        $oChild->setAccess($this->getAccess());    
                    }
                }
                return $oChild;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getChildren()
    {
        $aResult = [];
        if ($this->node) {
            $aChildren = $this->node->getChildren();
            foreach ($aChildren as $oChild) {
                $oResult = false;
                if ($oChild instanceof \Afterlogic\DAV\FS\File) {
                    $aExtendedProps = $oChild->getProperty('ExtendedProps');
                    if (!(is_array($aExtendedProps) && isset($aExtendedProps['InitializationVector']))) {
                        $oResult = new File($oChild->getName(), $oChild);
                    }
                } else if ($oChild instanceof \Afterlogic\DAV\FS\Directory) {
                    $oResult = new Directory($oChild->getName(), $oChild);
                }
                if ($oResult) {
                    $oPdo = new \Afterlogic\DAV\FS\Backend\PDO();
                    $aSharedFile = $oPdo->getSharedFile(Constants::PRINCIPALS_PREFIX . $this->getUser(), $oResult->getNode()->getRelativePath() . '/' . $oResult->getNode()->getName());
                    if ($aSharedFile) {
                        $oResult->setAccess($aSharedFile['access']);    
                    } else {
                        $oResult->setAccess($this->node->getAccess());
                        $oResult->setInherited(true);
                    }
                    $aResult[] = $oResult;
                }
            }
        }

        return $aResult;
    }

    public function childExists($name)
    {
        return $this->node && $this->node->childExists($name);
    }

	public function createDirectory($name)
	{
        if ($this->node) {
            $sPath = 'files/' . $this->getStorage() . $this->getRelativePath() . '/' . $this->getName();
            Server::checkPrivileges($sPath, '{DAV:}write');
            $this->node->createDirectory($name);
        }
    }

	public function createFile($name, $data = null, $rangeType = 0, $offset = 0, $extendedProps = [])
	{
        if ($this->node) {
            $sPath = 'files/' . $this->getStorage() . $this->getRelativePath() . '/' . $this->getName();
            if ($this->node->childExists($name)) {
                $sPath = $sPath . '/' . $name;
            }
            Server::checkPrivileges($sPath, '{DAV:}write');

            if (!(is_array($extendedProps) && isset($extendedProps['InitializationVector']))) {
                return $this->node->createFile($name, $data, $rangeType, $offset, $extendedProps);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
