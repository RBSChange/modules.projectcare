<?php
/**
 *class intended to easify reading and analyze of perspective.xml files
 *
 * most properties are public for debug and inspection purposes, please do not use instance variables whose names begin with an underscore, or use them at your own risks

 * @example
 * 
 * # grep -Rin 'createroot_' .<br>
 * ./repository/modules/brand/brand-3.6.1/config/perspective.xml:8:               <groupactions name="createRoot_"><br>
 * ./repository/modules/brand/brand-3.6.1/config/perspective.xml:75:              <action name="createRoot_" single="true" icon="add" /><br>
 * # ch4 projectcare.locales_unuseds | grep -i createroot_ # false positive results<br>
 * m.brand.bo.actions.createroot_<br>
 * m.form.bo.actions.createroot_<br><br>
 *
 * @package modules.projectcare.lib.services
 */
class projectcare_PerspectiveParser
{
	public $filepath;
	public $_elements = array(); // public for debug purpose
	/**
	 * 
	 */
	public function __construct($filepath, $parse = false)
	{
		$this->filepath = $filepath;
		$this->__reset();
		if ($parse)
		{
			$this->parse();
		}
	}
	
	/**
	 * reset some properties to their default values
	 * @return $this;
	 */
	public function __reset()
	{
		$this->_elements = array();
		$this->_name = $this->getModuleName();
		
		return $this;
	}
	
	/**
	 * return module name based on filepath (property)
	 * 
	 * @return string 
	 */
	public function getModuleName()
	{
		$parts = explode(DIRECTORY_SEPARATOR, realpath($this->filepath));
		foreach ($parts as $i => $value)
		{
			if (!$value)
			{
				unset($parts[$i]);
			}
		}
		
		$parts = array_reverse(array_values($parts));
		return $parts[3];
	}
	
	/**
	 * @return self
	 */
	public function parse()
	{
		$actions = $this->getActionsNodeFrom($this->filepath);
		$this->__reset();
		
		if ($actions === null)
		{
			return $this;
		}
		
		foreach ($actions->childNodes as $node)
		{
			if ($node->nodeName != 'action') {
				continue; 
			}
			$node = $this->parseActionNode($node);
			$this->_elements[] = $node;
		}
		
		return $this;
	}
	
	/*
	 * 
	 */
	public function getNodes()
	{
		return $this->_elements;
	}
	
	/**
	 * return all labeli18n found in file $filepath as a list
	 * 
	 * @return array
	 */
	public function getMatches() {
		$matches = array();
		foreach($this->getNodes() as $element) {
			$matches[] = strtolower($element->labeli18n);
		}
		return $matches;
	}
	
	/**
	 * return first (seen) actions node from file $file
	 * 
	 * @param string $filepath
	 * @return object(DOMElement)
	 */
	protected function getActionsNodeFrom($filepath)
	{
		$dom = f_util_DOMUtils::fromPath($filepath);
		
		foreach ($dom->documentElement->childNodes as $node)
		{
			if ($node->nodeName == 'actions')
			{
				return $node;
			}
		}
		return null;
	}
	
	/**
	 * intended to return simplified action node, containing a subset of available attributes as properties
	 * labeli18n is always defined (purpose of this function) in returned structure, if not defined in XML file, it will be calculated
	 * 
	 * @throws Exception if attribute name is not set or is empty
	 * @return object(Object) having name and labeli18n properties
	 */
	protected function parseActionNode($node)
	{
		$structure = (object) array('name' => null, 'labeli18n' => null, '_node' => $node);
		
		$structure->name = $node->getAttribute('name');
		if (!$node->getAttribute('name'))
		{
			throw new Exception('Can not find attribute `name\' found in file `' . $filepath . "' at line " . $node->getLineNo());
		}
		
		if ($node->getAttribute('labeli18n'))
		{
			$structure->labeli18n = $node->getAttribute('labeli18n');
		}
		else
		{
			# construct labeli18n
			# example: m.brand.bo.actions.createroot_
			$labeli18n = 'm.' . $this->getModuleName() . '.bo.actions.' . $node->getAttribute('name');
			$structure->labeli18n = $labeli18n;
		}
		
		return $structure;
	}
}