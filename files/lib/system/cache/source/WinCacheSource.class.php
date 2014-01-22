<?php
namespace wcf\system\cache\source;
use wcf\system\exception\SystemException;
use wcf\system\Regex;
use wcf\util\StringUtil;

/**
 * WinCacheSource is an implementation of CacheSource that uses wincache for windows to store data.
 *
 * @author      Jan Altensen (Stricted)
 * @copyright   2013-2014 Jan Altensen (Stricted)
 * @license     GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package     be.bastelstu.jan.wcf.wincache
 * @category    Community Framework
 */
class WinCacheSource implements ICacheSource {
	/**
	 * key prefix
	 * @var	string
	 */
	protected $prefix = '';
	
	/**
	 * Creates a new WinCacheSource object.
	 */
	public function __construct() {
		if (!extension_loaded('wincache')) {
			throw new SystemException('The php extension "wincache" is not loaded');
			exit;
		}
		
		// set variable prefix to prevent collision
		$this->prefix = 'WCF_'.substr(sha1(WCF_DIR), 0, 10) . '_';
	}
	
	/**
	 * Flushes a specific cache, optionally removing caches which share the same name.
	 * 
	 * @param	string		$cacheName
	 * @param	boolean		$useWildcard
	 */
	public function flush($cacheName, $useWildcard) {
		if ($useWildcard) $this->removeKeys($this->prefix . $cacheName . '(\-[a-f0-9]+)?');
		else wincache_ucache_delete($this->prefix . $cacheName);
	}
	
	/**
	 * Clears the cache completely.
	 */
	public function flushAll() {
		$this->removeKeys();
	}
	
	/**
	 * Returns a cached variable.
	 * 
	 * @param	string		$cacheName
	 * @param	integer		$maxLifetime
	 * @return	mixed
	 */
	public function get($cacheName, $maxLifetime) {
		if (!wincache_ucache_exists($this->prefix . $cacheName)) return null;
		return wincache_ucache_get($this->prefix . $cacheName);
	}
	
	/**
	 * Stores a variable in the cache.
	 * 
	 * @param	string		$cacheName
	 * @param	mixed		$value
	 * @param	integer		$maxLifetime
	 */
	public function set($cacheName, $value, $maxLifetime) {
		wincache_ucache_set($this->prefix . $cacheName, $value, $this->getTTL($maxLifetime));
	}
	
	/**
	 * Returns time to live in seconds, defaults to 3 days.
	 * 
	 * @param	integer		$maxLifetime
	 * @return	integer
	 */
	protected function getTTL($maxLifetime = 0) {
		if ($maxLifetime && ($maxLifetime <= (60 * 60 * 24 * 30) || $maxLifetime >= TIME_NOW)) {
			return $maxLifetime;
		}
		
		// default TTL: 3 days
		return (60 * 60 * 24 * 3);
	}
	
	/**
	 * remove cache data
	 *
	 * @param	string	$pattern	<optional>
	 */
	public function removeKeys($pattern = null) {
		$regex = null;
		if ($pattern !== null) $regex = new Regex('^'.$pattern.'$');
		
		$info = wincache_ucache_info();
		foreach ($info['ucache_entries'] as $cache) {
			if ($regex === null) {
				if (StringUtil::startsWith($cache['key_name'], $this->prefix)) {
					wincache_ucache_delete($cache['key_name']);
				}
			}
			else if ($regex->match($cache['key_name'])) {
				wincache_ucache_delete($cache['key_name']);
			}
		}
	}
}
