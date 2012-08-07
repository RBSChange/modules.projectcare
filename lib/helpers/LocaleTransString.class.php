<?php
/**
 * @package modules.projectcare.lib.services
 * 
 * naive class intended to have a simple representation of a localizable string 
 * based on its key and their files
 */
class projectcare_LocaleTransString
{
	protected $content;
	
	/**
	 * @param string $content
	 */
	public function __construct($content)
	{
		$this->content = $content;
	}
	
	/**
	 * @return boolean true if $this is consecutive
	 */
	public function isConsecutive()
	{
		return $this->_isConsecutive((string)$this);
	}
	
	/**
	 */
	public function __toString()
	{
		return $this->content;
	}
	
	/**
	 * does not support unicode or UTF-8, but localizable strings are not intended to use unicode chars
	 * examples of expected strings:
	 *	'm.brand.bo.actions.create-brand'
	 *	"m.brand.bo.actions.create-brand"
	 *	'm.brand.bo.actions.create_'.$var
	 *	"m.brand.bo.actions.create_.$var"
	 *	'm.brand.bo.'.$var
	 *	$var
	 *
	 * @param string $input string to analyse
	 * @return boolean true if $input is consecutive
	 */
	protected function _isConsecutive($input)
	{
		$input = trim($input);

		$firstchar = $input[0];
		$lastchar = substr($input, -1);
		$chars = str_split($input);
		$nlastpos = count($chars) - 1;
		
		if ($lastchar != $firstchar) {
			return false;
		}
		foreach ($chars as $i => $char)
		{
			if ($i > 0 && $char == $firstchar && $i < $nlastpos) {
				return false;
			}
			
			if ($char == '$' && $firstchar == '"') { //  && $chars[$i-1] != '\'
				return false;
			}
		}
		return true;
	}
}