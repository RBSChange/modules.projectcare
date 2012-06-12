<?php
/**
 * @package modules.projectcare.lib.services
 */
class projectcare_CommandCheckService extends ModuleBaseService
{
	
	private $resultStrings = array();
	
	/**
	 * Singleton
	 * @var projectcare_CommandCheckService
	 */
	private static $instance = null;
	
	/**
	 * @return projectcare_CommandCheckService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	public function execute()
	{
		$this->checkRequiredPanel();
		
		$this->checkPropertiesTabInResume();
		
		$this->checkRedirectTabInResume();
		
		$this->checkLocalizationTabInResume();
		
		$this->checkResumeOrderSection();
		
		$this->checkResumeLinkedTab();
		
		$this->checkRequiredRootFolderEditor();
		
		$this->findSpecificPanelsWithoutBindings();
		
		$this->findJS();
		
		$this->checkActivationIsAvailable();
		
		echo implode(PHP_EOL, $this->resultStrings), PHP_EOL;
	}
	
	/**
	 * Check that module has at least Resume, Publication and History panels
	 */
	public function checkRequiredPanel()
	{
		$this->resultStrings[] = 'Start : Check that the required panels are defined';
		$resultStrings = array();
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/panels.xml');
		
		$requiredPanels = array('resume', 'publication', 'history');
		$error = false;
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			$fileError = false;
			$fileResult = array();
			
			if (!$doc->documentElement->hasAttribute('hidden') && !$doc->documentElement->hasAttribute('use'))
			{
				foreach ($requiredPanels as $requiredPanel)
				{
					if ($doc->findUnique('//panel[@name="' . $requiredPanel . '"]') === null)
					{
						$fileError = true;
						$error = true;
						$fileResult[] = '-- Missing panel : ' . $requiredPanel;
					}
				}
			}
			
			if ($fileError)
			{
				$resultStrings[] = $file;
				$resultStrings = array_merge($resultStrings, $fileResult);
			}
		
		}
		
		if ($error)
		{
			$this->resultStrings = array_merge($this->resultStrings, $resultStrings);
		}
		
		$this->resultStrings[] = 'End : Check that the required panels are defined';
	}
	
	/**
	 * Check if all modules have rootfolder editor
	 */
	public function checkRequiredRootFolderEditor()
	{
		$this->resultStrings[] = 'Start : Check that the rootfolder editor exist';
		
		$packages = ModuleService::getInstance()->getPackageNames();
		foreach ($packages as $package)
		{
			$package = ModuleService::getInstance()->getShortModuleName($package);
			
			if (ModuleService::getInstance()->getModule($package)->isVisible())
			{
				$path = WEBEDIT_HOME . '/modules/' . $package . '/forms/editor/rootfolder/';
				if (!file_exists($path))
				{
					$this->resultStrings[] = 'Add file : ' . $path . 'empty.txt';
				}
			}
		
		}
		
		$this->resultStrings[] = 'End : Check that the rootfolder editor exist';
	
	}
	
	public function checkResumeLinkedTab()
	{
		$this->resultStrings[] = 'Start : Check that all panels exists for the resume section with link';
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			$sections = array();
			foreach ($doc->documentElement->childNodes as $node)
			{
				if ($node->localName == 'section')
				{
					if ($node->hasAttribute('linkedtab'))
					{
						$name = $node->getAttribute('linkedtab');
						$sections[] = $name;
					}
				}
			}
			
			$filePanel = str_replace('resume', 'panels', $file);
			
			try
			{
				$docPanel = f_util_DOMUtils::fromPath($filePanel);
				$panels = array();
				foreach ($sections as $section)
				{
					if ($docPanel->findUnique('//panel[@name="' . $section . '"]') !== null)
					{
						$panels[] = $section;
					}
					else
					{
						$jsContentNode = $docPanel->findUnique('//xul/javascript/constructor');
						if ($jsContentNode != null)
						{
							$jsContent = $jsContentNode->nodeValue;
							$panelSpe = array();
							if (preg_match('/.*addTab\([\'"]' . $section . '[\'"],[^,]+,[^,]+\);.*/', $jsContent) >= 1)
							{
								$panels[] = $section;
							}
						}
					}
				}
				
				if ($sections != $panels)
				{
					$this->resultStrings[] = $file;
					$this->resultStrings[] = "Sections : ";
					foreach ($sections as $section)
					{
						$this->resultStrings[] = " -- " . $section;
					}
					$this->resultStrings[] = $filePanel;
					$this->resultStrings[] = "Panels : ";
					foreach ($panels as $panel)
					{
						$this->resultStrings[] = " -- " . $panel;
					}
				}
			}
			catch (Exception $e)
			{
				$this->resultStrings[] = 'No File found : ' . $filePanel;
			}
		
		}
		
		$this->resultStrings[] = 'End : Check that all panels exists for the resume section with link';
	}
	
	public function checkLocalizationTabInResume()
	{
		$this->resultStrings[] = 'Start : Check that the localization section is in resume panel if document has localized properties';
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			$tmp = explode('/', $file);
			$tmpCount = count($tmp);
			
			try
			{
				$model = f_persistentdocument_PersistentDocumentModel::getInstance($tmp[$tmpCount - 5], $tmp[$tmpCount - 2]);
				
				$doc = f_util_DOMUtils::fromPath($file);
				if (!$doc->documentElement->hasAttribute('use'))
				{
					$localizationNode = $doc->findUnique('//section[@name="localization"]');
					
					if ($localizationNode != null && !$model->isLocalized())
					{
						$this->resultStrings[] = $model->getName() . ' not localized but section is present. Section must be deleted.';
					}
					else if ($localizationNode == null && $model->isLocalized())
					{
						$this->resultStrings[] = $model->getName() . ' localized but section is missing. Section must be added.';
					}
				}
			
			}
			catch (Exception $e)
			{
				$this->resultStrings[] = 'Model modules_' . $tmp[$tmpCount - 5] . '/' . $tmp[$tmpCount - 2] . ' not exist';
			}
		
		}
		
		$this->resultStrings[] = 'End : Check that the localization section is in resume panel if document has localized properties';
	}
	
	public function checkPropertiesTabInResume()
	{
		
		$this->resultStrings[] = 'Start : Check that the properties section is in resume panel';
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			$tmp = explode('/', $file);
			$tmpCount = count($tmp);
			
			$doc = f_util_DOMUtils::fromPath($file);
			if (!$doc->documentElement->hasAttribute('use'))
			{
				$propertiesNode = $doc->findUnique('//section[@name="properties"]');
				
				if ($propertiesNode == null)
				{
					$this->resultStrings[] = $file . ' has not properties section.';
				}
			}
		
		}
		
		$this->resultStrings[] = 'End : Check that the properties section is in resume panel';
	
	}
	
	public function checkRedirectTabInResume()
	{
		$this->resultStrings[] = 'Start : Check that the redirect section is in resume panel if document has an url and not in other case';
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		foreach ($files as $file)
		{
			$tmp = explode('/', $file);
			$tmpCount = count($tmp);
			
			try
			{
				$model = f_persistentdocument_PersistentDocumentModel::getInstance($tmp[$tmpCount - 5], $tmp[$tmpCount - 2]);
				
				$doc = f_util_DOMUtils::fromPath($file);
				if (!$doc->documentElement->hasAttribute('use'))
				{
					$redirectNode = $doc->findUnique('//section[@name="urlrewriting"]');
					
					if ($redirectNode != null && !$model->hasURL())
					{
						$this->resultStrings[] = $model->getName() . ' has  not url but section is present. Section must be deleted.';
					}
					else if ($redirectNode == null && $model->hasURL())
					{
						$this->resultStrings[] = $model->getName() . ' has url but section is missing. Section must be added.';
					}
				}
			
			}
			catch (Exception $e)
			{
				$this->resultStrings[] = 'Model modules_' . $tmp[$tmpCount - 5] . '/' . $tmp[$tmpCount - 2] . ' not exist';
			}
		
		}
		
		$this->resultStrings[] = 'End : Check that the redirect section is in resume panel if document has an url and not in other case';
	
	}
	
	public function findSpecificPanelsWithoutBindings()
	{
		$this->resultStrings[] = 'Start : Check the configuration of specifics panels. Attributes and bindings';
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/panels.xml');
		
		$default = array('resume', 'properties', 'localization', 'publication', 'redirect', 'permission', 'history', 'create');
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			
			$bindingBasePath = str_replace('forms', 'lib/bindings', $file);
			$bindingBasePath = str_replace('panels.xml', '', $bindingBasePath);
			
			$error = false;
			$message = $file . PHP_EOL;
			foreach ($doc->documentElement->childNodes as $node)
			{
				if ($node->localName == 'panel')
				{
					$name = $node->getAttribute('name');
					
					if (array_search($name, $default) === false)
					{
						
						if (!$node->hasAttribute('icon'))
						{
							$error = true;
							$message .= $name . ' has no defined icon' . PHP_EOL;
						}
						
						if (!$node->hasAttribute('labeli18n'))
						{
							$error = true;
							$message .= $name . ' has no defined label' . PHP_EOL;
						}
						
						$bindingFile = $bindingBasePath . $name . '.xml';
						
						if (!file_exists($bindingFile))
						{
							$error = true;
							$message .= $name . ' has no binding file : ' . $bindingFile . PHP_EOL;
						}
					
					}
				
				}
			}
			
			if ($error)
			{
				$this->resultStrings[] = $message;
			}
		
		}
		
		$this->resultStrings[] = 'End : Check the configuration of specifics panels. Attributes and bindings';
	}
	
	public function findJS()
	{
		$this->resultStrings[] = 'Start : Check JS code in panels';
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/panels.xml');
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			
			$el = $doc->findUnique('//xul/javascript');
			
			if ($el !== null)
			{
				$this->resultStrings[] = 'Use JS : ' . $file;
				
				$jsContentNode = $doc->findUnique('//xul/javascript/constructor');
				$jsContent = $jsContentNode->nodeValue;
				
				if (preg_match('/.*addTab\([^,]+,[^,]+,[^,]+\);.*/', $jsContent) >= 1)
				{
					$this->resultStrings[] = 'Add tab without specific place. You may set the fourth argument';
				}
				
				if (preg_match('/.*checkModuleVersion.*/', $jsContent) >= 1)
				{
					$this->resultStrings[] = 'checkModuleVersion must be replaced by hasModule';
				}
			
			}
		
		}
		
		$this->resultStrings[] = 'End : Check JS code in panels';
	}
	
	public function checkResumeOrderSection()
	{
		$this->resultStrings[] = 'Start : Check that the order of resume section is ok';
		
		function cmp($a, $b)
		{
			$value = 0;
			switch ($a)
			{
				case 'properties' :
					$value = -1;
					break;
				case 'localization' :
					if ($b == 'properties')
					{
						$value = 1;
					}
					else
					{
						$value = -1;
					}
					break;
				case 'publication' :
					if ($b == 'properties' || $b == 'localization')
					{
						$value = 1;
					}
					else
					{
						$value = -1;
					}
					break;
				case 'redirect' :
					if ($b == 'permission' || $b == 'history')
					{
						$value = -1;
					}
					else
					{
						$value = 1;
					}
					break;
				case 'permission' :
					if ($b == 'history')
					{
						$value = -1;
					}
					else
					{
						$value = 1;
					}
					break;
				case 'history' :
					$value = 1;
					break;
				default :
					$value = 0;
			}
			return $value;
		}
		
		$files = array();
		$files = glob(WEBEDIT_HOME . '/modules/*/forms/editor/*/resume.xml');
		
		$default = array('properties', 'localization', 'publication', 'redirect', 'permission', 'history');
		
		foreach ($files as $file)
		{
			
			$doc = f_util_DOMUtils::fromPath($file);
			
			$originNodeNames = array();
			$currentNodeName = array();
			
			foreach ($doc->documentElement->childNodes as $node)
			{
				if ($node->localName == 'section')
				{
					$name = $node->getAttribute('name');
					if (in_array($name, $default))
					{
						$originNodeNames[] = $name;
						$currentNodeName[] = $name;
					}
				}
			}
			
			usort($currentNodeName, 'cmp');
			
			if ($originNodeNames != $currentNodeName)
			{
				$this->resultStrings[] = 'Sections in ' . $file . ' must be sort';
			}
		
		}
		
		$this->resultStrings[] = 'End : Check that the order of resume section is ok';
	}
	
	public function checkActivationIsAvailable()
	{
		$this->resultStrings[] = 'Start : Check that document with default status DRAFT has action activate';
		
		$models = f_persistentdocument_PersistentDocumentModel::getDocumentModels();
		foreach ($models as $model)
		{
			/* @var $model f_persistentdocument_PersistentDocumentModel */
			if ($model->getDefaultNewInstanceStatus() == 'DRAFT')
			{
				$path = WEBEDIT_HOME . '/modules/' . $model->getModuleName() . '/config/perspective.xml';
				$doc = f_util_DOMUtils::fromPath($path);
				
				$nodeList = $doc->find('//model[@name="' . $model->getName() . '"]//contextaction');
				
				$actions = array();
				for ($i = 0; $i < $nodeList->length; $i++)
				{
					$node = $nodeList->item($i);
					$attributes = $node->attributes;
					$actions[] = $attributes->item(0)->nodeValue;
				}
				
				$name = split('\/', $model->getName());
				$editorPath = WEBEDIT_HOME . '/modules/' . $model->getModuleName() . '/forms/editor/' . $name[1] . '/';
				
				if (is_dir($editorPath))
				{
					if (!in_array('activate', $actions))
					{
						$this->resultStrings[] = 'Action activate missing on ' . $model->getName();
					}
					
					if (!in_array('deactivated', $actions) || !in_array('reactivate', $actions))
					{
						$this->resultStrings[] = 'Action deactivated or reactivate missing on ' . $model->getName();
					}
				}
			
			}
		}
		
		$this->resultStrings[] = 'End : Check that document with default status DRAFT has action activate';
	}

}