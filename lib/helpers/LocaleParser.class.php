<?php
/**
 * Intended to parse locales XML files, as seen in sample below
 * @package modules.projectcare.lib.services
 * @example
 * &lt;?xml version="1.0" encoding="utf-8"?&gt;<br />
 * &lt;i18n baseKey="m.brand.bo.useractionlogger" lcid="fr_FR"&gt;<br />
 *   &lt;key id="update-brand"&gt;update.brand {lang} {documentlabel}&lt;/key&gt;<br />
 *   &lt;key id="log-update-brand"&gt;log update brand&lt;/key&gt;<br />
 *   &lt;key id="insert-space"&gt;insert.space {documentlabel}&lt;/key&gt;<br />
 *   &lt;key id="log-insert-space"&gt;log insert space&lt;/key&gt;<br />
 * &lt;/i18n&gt;<br />
 */
class projectcare_LocaleParser
{
	protected $file;
	/**
	 * @property $_entities used to store entities extracted from document $file
	 */
	protected $_entities;
	protected $_content;
	
	/**
	 * 
	 */
	public function __construct($file, $parse = false)
	{
		$this->file = $file;
		if ($parse)
		{
			$this->process();
		}
	}
	
	/**
	 * process a locale XML file in order to output an object oriented easy and usable structure
	 * 
	 * @param boolean $processInclude
	 * @return array with keys: baseKey, lcid and keys (representation of the processed XML document)
	 */
	public function process($processInclude = false)
	{
		$this->reset();
		
		$lcid = basename($this->file, '.xml');
		$attrs = $this->getRootAttributes();
		$content = $this->processFile($this->file, $processInclude);
		$fpath = substr(realpath($this->file), f_util_StringUtils::strlen(realpath(f_util_FileUtils::buildWebeditPath())), f_util_StringUtils::strlen(realpath($this->file)));
		$keys = array();
		
		if (isset($content[$lcid]) && $content[$lcid])
		{
			foreach ($content[$lcid] as $k => $v)
			{
				$keys[$k] = $v[0];
			}
		}
		
		$this->_content = (object) array_merge($attrs, array('lcid' => $lcid, 'keys' => $keys, 'path' => $this->file));
		return $this;
	}
	
	/**
	 */
	public function parse($processInclude = false)
	{
		return $this->process($processInclude);
	}
	
	/**
	 */
	public function getContent()
	{
		return $this->_content;
	}
	
	/**
	 */
	protected function getRootAttributes()
	{
		$root = f_util_DOMUtils::fromPath($this->file)->documentElement;
		
		return array('baseKey' => $root->getAttribute('baseKey'), 'lcid' => $root->getAttribute('lcid'));
	}
	
	/**
	 * 
	 */
	protected function reset()
	{
		$this->_entities = array(); # reset entities
		$this->_content = array();
	}
	
	/**
	 * 
	 * see processFile in ./repository/framework/service/LocaleService.class.php
	 * @param string $file filepath to XML locale file
	 * @param boolean
	 */
	protected function processFile($file, $processInclude = true)
	{
		$lcid = basename($file, '.xml');
		$dom = f_util_DOMUtils::fromPath($file);
		
		foreach ($dom->documentElement->childNodes as $node)
		{
			if ($node->nodeType == XML_ELEMENT_NODE)
			{
				if ($node->nodeName == 'include' && $processInclude)
				{
					$id = $node->getAttribute('id');
					$subPath = $this->getI18nFilePath($id, $lcid);
					if (file_exists($subPath))
					{
						$this->processFile($subPath);
					}
					$subPath = $this->getI18nFilePath($id, $lcid, true);
					if (file_exists($subPath))
					{
						$this->processFile($subPath);
					}
				}
				
				if ($node->nodeName == 'key')
				{
					$id = $node->getAttribute('id');
					$content = $node->textContent;
					$format = $node->getAttribute('format') === 'html' ? 'HTML' : 'TEXT';
					$this->_entities[$lcid][$id] = array($content, $format);
				}
			}
		}
		return $this->_entities;
	}
	
	/**
	 *
	 * see getI18nFilePath in ./repository/framework/service/LocaleService.class.php
	 */
	private function getI18nFilePath($baseKey, $lcid, $override = false)
	{
		$parts = explode('.', $baseKey);
		$parts[] = $lcid . '.xml';
		switch ($parts[0])
		{
			case 'f' :
			case 'framework' :
				$parts[0] = '/framework/i18n';
				break;
			case 'm' :
			case 'modules' :
				$parts[0] = '/modules';
				$parts[1] .= '/i18n';
				break;
			case 't' :
			case 'themes' :
				$parts[0] = '/themes';
				$parts[1] .= '/i18n';
				break;
		}
		if ($override)
		{
			return PROJECT_OVERRIDE . implode('/', $parts);
		}
		
		return WEBEDIT_HOME . implode('/', $parts);
	}
}