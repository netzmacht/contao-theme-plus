<?php

/**
 * Theme+ - Theme extension for the Contao Open Source CMS
 *
 * Copyright (C) 2013 bit3 UG <http://bit3.de>
 *
 * @package    Theme+
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @link       http://www.themeplus.de
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

define('TL_MODE', 'FE');
require(dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/system/initialize.php');

use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Asset\StringAsset;
use Assetic\Filter\FilterCollection;
use ContaoAssetic\AsseticFactory;
use Bit3\Contao\ThemePlus\ThemePlusEnvironment;
use Bit3\Contao\ThemePlus\Model\StylesheetModel;
use Bit3\Contao\ThemePlus\Model\JavaScriptModel;

class proxy
{
	public function run()
	{
		if (ThemePlusEnvironment::isDesignerMode()) {
			$user = FrontendUser::getInstance();
			$user->authenticate();

			$pathInfo = \Environment::get('pathInfo');

			list($type, $sourceDescriptor) = explode('/', substr($pathInfo, 1));

			// output headers
			header("Cache-Control: public");
			switch ($type) {
				case 'css':
					header('Content-Type: text/css; charset=utf-8');
					break;

				case 'js':
					header('Content-Type: text/javascript; charset=utf-8');
					break;
			}

			if (preg_match('~^(.*)\.(.*)\.' . preg_quote($type) . '$~', $sourceDescriptor, $matches)) {
				$id = $matches[2];

				if (isset($_SESSION['THEME_PLUS_ASSETS'][$id])) {
					$session = unserialize($_SESSION['THEME_PLUS_ASSETS'][$id]);

					// load asset from session
					/** @var AssetInterface $asset */
					$asset = $session->asset;

					if ($asset instanceof StringAsset) {
						header('X-Theme-Plus-Rendering: cached');
						echo $asset->getContent();
						ob_flush();
						return;
					}

					header('X-Theme-Plus-Rendering: live');

					// load page from session
					$GLOBALS['objPage'] = \PageModel::findWithDetails($session->page);

					// load filters from session
					$defaultFilters = $session->filters;

					// update the target path
					$asset->setTargetPath('assets/proxy.php/:type/:descriptor');

					// dump the asset
					$buffer =  $asset->dump($defaultFilters);

					$cachedAsset = new StringAsset($buffer, array(), $asset->getSourceRoot(), $asset->getSourcePath());
					$cachedAsset->setTargetPath($asset->getTargetPath());
					$cachedAsset->setLastModified($asset->getLastModified());
					$cachedAsset->load();

					$session->asset   = $cachedAsset;
					$session->filters = array();
					$_SESSION['THEME_PLUS_ASSETS'][$id] = serialize($session);

					echo $buffer;

					return;
				}
			}
		}

		header('HTTP/1.1 403 Forbidden');
		header('Status: 403 Forbidden');
	}
}

$proxy = new proxy();
$proxy->run();