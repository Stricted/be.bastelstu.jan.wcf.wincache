<?php
namespace wcf\system\event\listener;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\SystemException;
use wcf\system\Regex;
use wcf\system\WCF;

/**
 * @author      Jan Altensen (Stricted)
 * @copyright   2013-2014 Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     be.bastelstu.jan.wcf.wincache
 * @category    Community Framework
 */
class WinCacheListener implements IParameterizedEventListener {
	/**
	 * @see \wcf\system\event\listener\IParameterizedEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		switch ($className) {
			case "wcf\acp\page\CacheListPage":
				if ($eventObj->cacheData['source'] == 'wcf\system\cache\source\WinCacheSource') {
					
					// set version
					$eventObj->cacheData['version'] = phpversion('wincache');
					
					$prefix = new Regex('^'.WCF_UUID.'_');
					$data = array();
					$info = wincache_ucache_info();
					foreach ($info['ucache_entries'] as $cache) {
						if (!$prefix->match($cache['key_name'])) continue;
						
						// get additional cache information
						$data['data']['WinCache'][] = array(
							'filename' => $prefix->replace($cache['key_name'], ''),
							'filesize' => $cache['value_size'],
							'mtime' => TIME_NOW - $cache['age_seconds']
						);
						$eventObj->cacheData['files']++;
						$eventObj->cacheData['size'] += $cache['value_size'];
					}
					$eventObj->caches = array_merge($data, $eventObj->caches);
				}
				break;
			
			case "wcf\system\option\OptionHandler":
				$eventObj->cachedOptions['cache_source_type']->modifySelectOptions($eventObj->cachedOptions['cache_source_type']->selectOptions . "\nwin:wcf.acp.option.cache_source_type.wincache");
				$eventObj->cachedOptions['cache_source_type']->modifyEnableOptions($eventObj->cachedOptions['cache_source_type']->enableOptions . "\nwin:!cache_source_memcached_host");
				break;
			
			case "wcf\acp\action\UninstallPackageAction":
				$packageID = 0;
				if (isset($_POST['packageID']) && !empty($_POST['packageID'])) $packageID = intval($_POST['packageID']);
				
				if ($packageID) {
					$sql = "SELECT * FROM wcf".WCF_N."_package where package = ? LIMIT 1";
					$statement = WCF::getDB()->prepareStatement($sql);
					$statement->execute(array("be.bastelstu.jan.wcf.wincache"));
					$row = $statement->fetchArray();
					if ($packageID == $row['packageID']) {
						// set cache to disk if wincache is enabled
						$sql = "UPDATE	wcf".WCF_N."_option
							SET	optionValue = ?
							WHERE	optionName = ?
								AND optionValue = ?";
						$statement = WCF::getDB()->prepareStatement($sql);
						$statement->execute(array(
							'disk',
							'cache_source_type',
							'win'
						));
					}
				}
				break;
		}
	}
}
