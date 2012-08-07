<?php
/**
 * @package modules.projectcare.lib.services
 * 
 * Usage:
 * 
 *	$di = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
 *	projectcare_FileFilter::setFilters($extension);
 *	$fi = new projectcare_FileFilter($di);
 *	$it = new RecursiveIteratorIterator($fi, RecursiveIteratorIterator::CHILD_FIRST);
 *	
 *	foreach ($it as $file => $info) { }
 */
class projectcare_FileFilter extends RecursiveFilterIterator
{
	public static $excludeDirs;
	public static $recursive;
	public static $extension;
	public static function setFilters($extension, $recursive = true, $exludeDirs = null)
	{
		self::$extension = $extension;
		self::$recursive = ($recursive == true);
		if (self::$recursive && is_array($exludeDirs))
		{
			self::$excludeDirs = array_merge(array('.svn', '.git'), $exludeDirs);
		}
		else
		{
			self::$excludeDirs = array('.svn', '.git');
		}
	}
	public function accept()
	{
		$c = $this->current();
		if ($c->isDir())
		{
			if (!self::$recursive || in_array($c->getFilename(), self::$excludeDirs))
			{
				return false;
			}
			return true;
		}
		elseif ($c->isFile() && self::$extension && substr($c->getFilename(), (strlen(self::$extension) + 1) * -1) == '.' . self::$extension)
		{
			return true;
		}
		return false;
	}
}