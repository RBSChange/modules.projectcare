<?php
/**
 * projectcare_patch_0360
 * @package modules.projectcare
 */
class projectcare_patch_0360 extends patch_BasePatch
{
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript("init.xml");
	}
	
}