<?php
/**
 * @package modules.projectcare.lib.services
 *
 * find localizable.strings across the FileSystem
 */
class projectcare_LocalesServiceIndexer extends ModuleBaseService
{
	/**
     * Singleton
     *
     * @var projectcare_LocalesService
     */
	private static $instance;
	protected $override = true;
	
	/**
     * @return projectcare_LocalesService
     */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		
		return self::$instance;
	}
	
	/**
     *
     */
	protected function __construct()
	{
		$this->override = true;
	}
	
	/**
     * @param boolean
     */
	public function setOverride($b)
	{
		$this->override = (bool) $b;
	}
	
	/**
	 * return results from getStrings after exclusions
	 *
	 * @return array
	 */
	public function getLocalizables()
	{
		$localizables = $this->getStrings();
		$excluder = projectcare_LocalesExclusions::getInstance();
		
		return $excluder->cleanArray($localizables, 'localizables');
	}
	
	/**
	 * supports none of the optional parameters supported by extractLocalizedKeys
	 * return results after exclusions
	 *
	 * @return array
	 */
	public function getLocalizeds()
	{
		$localizeds = $this->extractLocalizedKeys();
		$excluder = projectcare_LocalesExclusions::getInstance();
		
		return $excluder->cleanArray($localizeds, 'localizeds');
	}
	
	/**
     * return multidimensional array of filepaths ordered by filetype (lowercase
     * extensions)
     *
     * @return array
     */
	public function walk()
	{
		$files = array('php' => $this->findPhpFiles(array('framework', 'build')), 'xml' => $this->findXmlFiles(array('themes', 'framework')), 
			'html' => $this->findHtmlFiles());
		ksort($files);
		return $files;
	}
	
	/**
     * return multidimensional array of filepaths and localizable.strings
     * perspective.xml files are processed here (too)
     * add other filetypes before the general processing (else)
     * 
     * @internal array $matches should be a simple list of localizable strings 
     * @return array
     */
	public function getKeysByFiles()
	{
		$filesAll = $this->walk();
		$filematches = array();
		
		$expressions = array('php' => array('[\'"](m|t|f)(\.[a-zA-Z0-9_\-]+)+[\'"]', '&(modules|themes|framework)(\.[a-zA-Z0-9_\-]+)+;'), 
			'xml' => array('\$\{trans(ui)?:\s*(m|t|f)(\.[a-zA-Z0-9_\-]+)+[,}]'), 'html' => array('\$\{trans(ui)?:\s*(m|t|f)(\.[a-zA-Z0-9_\-]+)+[,}]'));
		
		foreach ($filesAll as $filetype => $files)
		{
			foreach ($files as $file)
			{
				if (basename($file) == 'perspective.xml')
				{
					# continue; # 2537 -> 2348 +10% on a ecommercecore version
					$parser = new projectcare_PerspectiveParser($file, true);
					$matches = $parser->getMatches();
					if ($matches)
					{
						if (!isset($filematches[$file]))
						{
							$filematches[$file] = array();
						}
						$filematches[$file] = array_merge($filematches[$file], $matches);
						sort($filematches[$file]);
					}
				}
				else
				{
					# general processing
					$content = file_get_contents($file);
					foreach ($expressions[$filetype] as $pattern)
					{
						preg_match_all("#$pattern#", $content, $matches);
						$matches = array_filter($matches[0]);
						foreach ($matches as $i => $v)
						{
							$matches[$i] = strtolower($v);
						}
						if ($matches)
						{
							if (!isset($filematches[$file]))
							{
								$filematches[$file] = array();
							}
							$filematches[$file] = array_merge($filematches[$file], $matches);
							sort($filematches[$file]);
						}
					}
				}
			}
		}
		ksort($filematches);
		$filematches = $this->cleanKeys($filematches);
		
		return $filematches;
	}
	
	/**
     * return multidimensional array of localizable.strings (as key) and filepaths
     *
     * @return array
     */
	public function getStrings()
	{
		return $this->reverseKeysByFiles($this->getKeysByFiles());
	}
	
	/**
     * return an array of packages directories (using absolute paths),
     * $additions can be set in order
     * to inspect additional paths
     *
     * @param array $additions
     * @return array
     */
	public function getPackagesPaths($additions = array())
	{
		$results = array();
		$packageNames = ModuleService::getInstance()->getPackageNames();
		
		$packageNames = array_merge($packageNames, $additions);
		$packageNames = array_unique($packageNames);
		sort($packageNames);
		
		foreach ($packageNames as $packageName)
		{
			$packagePath = str_replace('_', '/', $packageName);
			
			$overpath = f_util_FileUtils::buildOverridePath($packagePath);
			if ($this->override && is_dir($overpath) && is_readable($overpath))
			{
				$results[] = $overpath;
			}
			$results[] = f_util_FileUtils::buildWebeditPath($packagePath);
		}
		return $results;
	}
	
	/**
     * find filepaths
     */
	public function findGenKeys($additions = array('framework'))
	{
		$files = $this->findPhpFiles($additions);
		$pattern = '.*[\->|::](translate|translateUI|trans[F|B]O|formatKey)\((.*)\)*.*';
		$position = 2;
		$results = array();
		$result = array('filepath' => null, 'match' => null, 'lineno' => null);
		
		foreach ($files as $filepath)
		{
			$content = file_get_contents($filepath);
			preg_match_all("#$pattern#", $content, $matches);
			if (!$matches[0])
			{
				continue;
			}
			
			foreach (explode("\n", $content) as $lineno => $sentence)
			{
				preg_match("#$pattern#", $sentence, $matches);
				if (!($matches && $matches[0]))
				{
					continue;
				}
				
				$parts = explode(',', $matches[$position]);
				$trans = null;
				if ($matches[1] == 'transFO' || $matches[1] == 'transBO')
				{
					// if trans[F|B]O has only one argument then we use residual parenthese
					if ($parts[0] == $matches[$position])
					{
						$parts = explode(')', $matches[$position]);
					}
					$trans = trim($parts[0]);
				}
				elseif ($matches[1] == 'translate' || $matches[1] == 'translateUI')
				{
					$p = '[\'|"]&(.*);[\'|"]';
					preg_match_all("#$p#", $matches[$position], $sm);
					if ($m[0])
					{
						$trans = trim($m[1]);
					}
				}
				elseif ($matches[1] == 'formatKey')
				{
					$trans = trim($parts[1]);
					// formatKey used on a signle line, ended using semicolon
					if (strpos($parts[1], ');') !== false)
					{
						$parts = explode(');', $parts[1]);
						$trans = trim($parts[0]);
					}
				}
				
				$ltstr = new projectcare_LocaleTransString($trans);
				if (!$trans || ($trans && $ltstr->isConsecutive()))
				{
					continue;
				}
				
				$results[$filepath][] = (object) array('file' => $filepath, 'matches' => $matches, 'lineno' => $lineno + 1, 'trans' => $trans, 
					'type' => $matches[1]);
			}
		}
		ksort($results);
		return $results;
	}
	
	/**
     * return array of xml locale files from modules and $additions
     *
     * @param array $additions
     * @return array
     */
	public function findLocales($additions = array('themes'))
	{
		$files = $this->findXmlFiles($additions);
		$results = array();
		$pattern = '.*/i18n/.*[a-z]{2}_[A-Z]{2}.xml';
		
		foreach ($files as $filepath)
		{
			preg_match_all("#$pattern#", $filepath, $matches);
			if ($matches[0])
			{
				$results[] = $filepath;
			}
		}
		return $results;
	}
	
	/**
     * extract localiz(ed|ables) strings defined in XML locale files
     *
     * @param boolean $processIncludes
     * @param array $additions
     * @return array
     */
	public function extractLocalizedKeys($processInclude = false, $additions = array('themes'))
	{
		$files = $this->findLocales($additions);
		$results = array();
		
		foreach ($files as $filepath)
		{
			try
			{
				$parser = new projectcare_LocaleParser($filepath, true);
				$result = $parser->getContent();
				foreach ($result->keys as $key => $value)
				{
					$results[] = strtolower($result->baseKey . '.' . $key);
				}
			}
			catch (Exception $e)
			{
				if (Framework::isWarnEnabled()) Framework::warn($e);
			}
		}
		sort($results);
		return array_unique($results);
	}
	
	/**
     * return array of html files from modules and $additions
     *
     * @param string[] $additions
     * @return string[]
     */
	public function findHtmlFiles($additions = array())
	{
		return $this->findFilteredFiles('html', $additions);
	}
	
	/**
     * return array of xml files from modules and $additions
     *
     * @param array $additions
     * @return array
     */
	public function findXmlFiles($additions = array())
	{
		return $this->findFilteredFiles('xml', $additions);
	}
	
	/**
     * return array of php files from modules and $additions
     *
     * @param string[] $additions
     * @return string[]
     */
	public function findPhpFiles($additions = array())
	{
		return $this->findFilteredFiles('php', $additions);
	}
	
	/**
     * return an array of filenames filtered by $extension from modules and
     * $additions directories
     *
     * @param array $additions
     * @return array
     */
	protected function findFilteredFiles($extension, $additions = array())
	{
		$packagePaths = $this->getPackagesPaths($additions);
		$files = array();
		
		foreach ($packagePaths as $path)
		{
			$di = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
			projectcare_FileFilter::setFilters($extension);
			$fi = new projectcare_FileFilter($di);
			$it = new RecursiveIteratorIterator($fi, RecursiveIteratorIterator::CHILD_FIRST);
			
			foreach ($it as $file => $info)
			{
				/* @var $info SplFileInfo */
				if ($info->isFile())
				{
					$files[] = $file;
				}
			}
		}
		return $files;
	}
	
	/**
     * intended to clean matches in $this->getKeysByFiles() and to remove
     * duplicate localizable.strings file by file
     *
     * @param array $localizables
     * @return array
     */
	protected function cleanKeys($localizables)
	{
		$pattern = '([a-z]+\.[a-zA-Z0-9_\-]+(\.[a-zA-Z0-9_\-]+)+)';
		foreach ($localizables as $file => $strings)
		{
			foreach ($strings as $i => $string)
			{
				preg_match_all("#$pattern#", $localizables[$file][$i], $matches);
				$match = $matches[0][0];
				
				$ls = new projectcare_LocalizableString($match);
				$localizables[$file][$i] = $ls->key;
			}
		}
		
		return $localizables;
	}
	
	/**
     *
     * @param array $localizables
     * @return array
     */
	protected function reverseKeysByFiles($localizables)
	{
		$results = array();
		foreach ($localizables as $file => $strings)
		{
			foreach ($strings as $i => $string)
			{
				$ls = new projectcare_LocalizableString($string);
				if (!isset($results[$string]))
				{
					$results[$ls->key] = $ls;
				}
				# use old syntax (backward compatibility)
				# should be:
				# $results[$ls->key]->add_file($file);
				#
				$locale = $results[$ls->key];
				$locale->add_file($file);
				$results[$ls->key] = $locale;
			}
		}
		return $results;
	}
}