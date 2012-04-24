<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

/**
 * FuelPHP LessCSS package implementation. This namespace controls all Google
 * package functionality, including multiple sub-namespaces for the various
 * tools.
 *
 * @author     Kriansa
 * @version    1.0
 * @package    Fuel
 * @subpackage Less
 */
namespace Less;

class Asset_Instance extends \Fuel\Core\Asset_Instance
{
	/**
	 * Less
	 *
	 * Compile a Less file and load it as a CSS asset.
	 *
	 * @access	public
	 * @param	mixed	       The file name, or an array files.
	 * @param	array	       An array of extra attributes
	 * @param	string	       The asset group name
	 * @return	string|object  Rendered asset or current instance when adding to group
	 */
	public function less($stylesheets = array(), $attr = array(), $group = null, $raw = false)
	{
		if ( ! is_array($stylesheets))
		{
			$stylesheets = array($stylesheets);
		}
		
		foreach ($stylesheets as &$less_file) {
			$less_file = \Config::get('asset.less_source_dir').$less_file;
			
			if( ! is_file($less_file))
			{
				throw new \Fuel_Exception('Could not find less source file: '.$less_file);
			}
		}
		
		$combined_result = static::combine_less($stylesheets);
		$combined_less = $combined_result['content'];
		$css_file_name = $combined_result['name'] . '.css';
		$css_file_path = \Config::get('asset.less_output_dir') . $css_file_name;
		
		if (! is_file($css_file_path) ) // if the combined file has never been compiled yet
		{ 
			require_once PKGPATH.'less'.DS.'vendor'.DS.'lessphp'.DS.'lessc.inc.php';
				
			$handle = new \lessc();
			$handle->indentChar = \Config::get('asset.indent_with');
			
			$compile_path = dirname($css_file_path);
			
			\File::create($compile_path, $css_file_name, $handle->parse($combined_less));
		}
		
		return static::css($css_file_name, $attr, $group, $raw);
	}
	
	protected function combine_less(array $files) {
		$last_modified = 0;
		
		foreach ($files as $file)
		{	
			$file_last_modified = filemtime($file);
			
			if ($file_last_modified > $last_modified) $last_modified = $file_last_modified;
		}
		
		$combination_name = md5(implode('', $files) . $last_modified);
		
		
		try
		{
			$combined_content = \Cache::get($combination_name);
		}
		catch (\CacheNotFoundException $e)
		{
			$combined_content = '';
			
			foreach ($files as $file)
			{
				$combined_content .= file_get_contents($file).PHP_EOL;
			}
			
			\Cache::set($combination_name, $combined_content, \Config::get('asset.less_combined_cache_expiration'));
		}
		
		return array('name' => $combination_name, 'content' => $combined_content);
	}
}