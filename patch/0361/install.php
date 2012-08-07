<?php
/**
 * projectcare_patch_0361
 * @package modules.projectcare
 */
class projectcare_patch_0361 extends patch_BasePatch
{

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript("init.xml");
	}
}