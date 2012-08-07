<?php
/**
 * @package modules.projectcare.lib.services
 */
class projectcare_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var projectcare_ModuleService
	 */
	private static $instance = null;
	
	/**
	 * @return projectcare_ModuleService
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
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkLinksInPageContents($offset, $chunkSize, $report)
	{
		// Get all pages.
		$pages = website_PageService::getInstance()->createQuery()->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_LinksReportGenerator');
		$rc = RequestContext::getInstance();
		foreach ($pages as $page)
		{
			foreach ($page->getI18nInfo()->getLangs() as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					
					$doc = new DOMDocument('1.0', 'utf-8');
					// Test if document is published in the working lang
					if ($page->isPublished() && $doc->loadXML($page->getContent()) !== false)
					{
						$resultXPath = new DOMXPath($doc);
						$resultXPath->registerNamespace('change', 'http://www.rbs.fr/change/1.0/schema');
						$contentList = $resultXPath->query('//change:richtextcontent');
						foreach ($contentList as $content)
						{
							$links = $this->checkLinksInFragment($content->textContent, $page, $lang, 'content');
							if (count($links) > 0)
							{
								$prg->appendRowsToCsv($report, $links);
							}
						}
					}
					
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return count($pages) < $chunkSize;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param string[] $propertyNames
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkLinksInXHTMLFragments($model, $propertyNames, $offset, $chunkSize, $report)
	{
		$documents = $this->getPersistentProvider()->createQuery($model->getName(), false)->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_LinksReportGenerator');
		$rc = RequestContext::getInstance();
		foreach ($documents as $document)
		{
			foreach ($document->getI18nInfo()->getLangs() as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					
					foreach ($propertyNames as $propertyName)
					{
						// Test if document is published in the working lang
						if (!$document->isPublished())
						{
							continue;
						}
						$getter = 'get' . ucfirst($propertyName);
						$links = $this->checkLinksInFragment($document->$getter(), $document, $lang, $propertyName);
						if (count($links) > 0)
						{
							$prg->appendRowsToCsv($report, $links);
						}
					}
					
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return count($documents) < $chunkSize;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param string[] $propertyNames
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkLinksInUrlProperty($model, $propertyNames, $offset, $chunkSize, $report)
	{
		$documents = $this->getPersistentProvider()->createQuery($model->getName(), false)->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_LinksReportGenerator');
		$rc = RequestContext::getInstance();
		foreach ($documents as $document)
		{
			foreach ($document->getI18nInfo()->getLangs() as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					
					foreach ($propertyNames as $propertyName)
					{
						// Test if document is published in the working lang
						if (!$document->isPublished())
						{
							continue;
						}
						$getter = 'get' . ucfirst($propertyName);
						
						$link = $this->testExternalLink($document->$getter(), $document, $lang, $propertyName);
						
						if ($link != null)
						{
							$prg->appendRowsToCsv($report, array($link));
						}
					}
					
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return count($documents) < $chunkSize;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @param string[] $propertyNames
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkLinksInBbCodeProperties($model, $propertyNames, $offset, $chunkSize, $report)
	{
		return true;
	}
	
	/**
	 * @param string $fragment
	 * @param f_persistentdocument_PersistentDocument $container
	 * @param string $lang
	 * @param string $propertyName
	 */
	protected function checkLinksInFragment($fragment, $container, $lang, $propertyName)
	{
		$links = array();
		$externalBrokenLinks = array();
		$externalForbiddenLinks = array();
		$otherLinks = array();
		
		$newXml = '<?xml version="1.0" encoding="UTF-8" ?><root>' . $fragment . '</root>';
		$docContent = new DOMDocument('1.0', 'utf-8');
		if ($docContent->loadXML($newXml) === false)
		{
			Framework::error(__METHOD__ . ' Invalid XML in XHTMLFragment on document ' . $container->__toString() . ': ' . PHP_EOL . $newXml);
			return array();
		}
		
		$contentXPath = new DOMXPath($docContent);
		$aList = $contentXPath->query('//a');
		foreach ($aList as $a)
		{
			// Ignore anchors.
			if ($a->hasAttribute('name'))
			{
				continue;
			}
			
			// If link href != # call link and wait the response.
			if ($a->getAttribute('href') != '#' && f_util_StringUtils::beginsWith($a->getAttribute('href'), 'http'))
			{
				$link = $this->testExternalLink($a->getAttribute('href'), $container, $lang, $propertyName);
				
				if ($link != null)
				{
					$links[] = $link;
				}
			}
			// If link href == # and rel cmpref exists, try to get the link.
			else if ($a->getAttribute('href') == '#' && $a->hasAttribute('rel'))
			{
				$documentId = null;
				foreach (explode(',', $a->getAttribute('rel')) as $rel)
				{
					if (strpos($rel, 'cmpref:') === 0)
					{
						$documentId = intval(substr($rel, 7));
						break;
					}
				}
				
				try
				{
					$document = DocumentHelper::getDocumentInstance($documentId);
					LinkHelper::getDocumentUrl($document);
				}
				catch (Exception $e)
				{
					$links[] = array('httpStatus' => '404', 'curlError' => 'no error', 'containerId' => $container->getId(), 
						'containerUrl' => LinkHelper::getDocumentUrl($container), 'containerLabel' => $container->getLabel(), 
						'containerModel' => $container->getDocumentModelName(), 'containerLang' => $lang, 'linkType' => 'internal', 
						'propertyName' => $propertyName, 'targetId' => $documentId, 'targetUrl' => null);
				}
			}
			// Else return link.
			else
			{
				$links[] = array('httpStatus' => 'not tested', 'curlError' => 'not tested', 'containerId' => $container->getId(), 
					'containerUrl' => LinkHelper::getDocumentUrl($container), 'containerLabel' => $container->getLabel(), 
					'containerModel' => $container->getDocumentModelName(), 'containerLang' => $lang, 'linkType' => 'special', 
					'propertyName' => $propertyName, 'targetId' => null, 'targetUrl' => $a->getAttribute('href'));
			}
		}
		return $links;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return string[]
	 */
	public function getRichtextPropertyNamesByModel($model)
	{
		$propertyNames = array();
		
		foreach ($model->getPropertiesInfos() as $property)
		{
			/* @var $property PropertyInfo */
			if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
			{
				$propertyNames[] = $property->getName();
			}
		}
		foreach ($model->getSerializedPropertiesInfos() as $property)
		{
			/* @var $property PropertyInfo */
			if ($property->getType() == f_persistentdocument_PersistentDocument::PROPERTYTYPE_XHTMLFRAGMENT)
			{
				$propertyNames[] = $property->getName();
			}
		}
		
		$moduleKey = $model->getModuleName() . '_' . $model->getDocumentName();
		$excludes = Framework::getConfigurationValue('modules/projectcare/xhtmlfragmentsExcludes/' . $moduleKey);
		if (is_array($excludes))
		{
			$propertyNames = array_diff($propertyNames, $excludes);
		}
		
		return $propertyNames;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return string[]
	 */
	public function getUrlPropertyNamesByModel($model)
	{
		$moduleKey = $model->getModuleName() . '_' . $model->getDocumentName();
		$urlProperties = Framework::getConfigurationValue('modules/projectcare/urlProperties/' . $moduleKey);
		
		return $urlProperties;
	}
	
	/**
	 * @param f_persistentdocument_PersistentDocumentModel $model
	 * @return string[]
	 */
	public function getBbCodePropertyNamesByModel($model)
	{
		$moduleKey = $model->getModuleName() . '_' . $model->getDocumentName();
		$properties = Framework::getConfigurationValue('modules/projectcare/bbCodeProperties/' . $moduleKey);
		
		return $properties;
	}
	
	/**
	 * 
	 * @param String $url
	 * @param f_persistentdocument_PersistentDocument $container
	 * @param String $lang
	 * @param String $propertyName
	 */
	private function testExternalLink($url, $container, $lang, $propertyName)
	{
		$httpClient = HTTPClientService::getInstance()->getNewHTTPClient();
		$httpClient->setOption(CURLOPT_NOBODY, true);
		$httpClient->setTimeOut(10);
		$httpClient->get($url);
		$returnCode = $httpClient->getHTTPReturnCode();
		$curlError = $httpClient->getCurlError();
		if ($returnCode != 200)
		{
			return array('httpStatus' => $returnCode ? $returnCode : 'error', 'curlError' => $curlError ? $curlError : 'no error', 
				'containerId' => $container->getId(), 'containerUrl' => LinkHelper::getDocumentUrl($container), 
				'containerLabel' => $container->getLabel(), 'containerModel' => $container->getDocumentModelName(), 'containerLang' => $lang, 
				'linkType' => 'external', 'propertyName' => $propertyName, 'targetId' => null, 'targetUrl' => $url);
		}
		
		return null;
	}
	
	/**
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkDocumentInBlockInPageContents($offset, $chunkSize, $report)
	{
		// Get all pages.
		$pages = website_PageService::getInstance()->createQuery()->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_DocumentInBlockReportGenerator');
		$rc = RequestContext::getInstance();
		foreach ($pages as $page)
		{
			foreach ($page->getI18nInfo()->getLangs() as $lang)
			{
				try
				{
					$rc->beginI18nWork($lang);
					
					$doc = new DOMDocument('1.0', 'utf-8');
					// Test if document is published in the working lang
					if ($page->isPublished() && $doc->loadXML($page->getContent()) !== false)
					{
						$this->testXmlContent($doc, $prg, $report, $page);
					}
					
					$rc->endI18nWork();
				}
				catch (Exception $e)
				{
					$rc->endI18nWork($e);
				}
			}
		}
		return count($pages) < $chunkSize;
	}
	
	/**
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkDocumentInBlockInUserDashboard($offset, $chunkSize, $report)
	{
		$users = users_BackenduserService::getInstance()->createQuery()->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_DocumentInBlockReportGenerator');
		
		/* @var $user users_persistentdocument_backenduser */
		foreach ($users as $user)
		{
			$content = $user->getDashboardcontent();
			
			if ($content != null)
			{
				$doc = new DOMDocument('1.0', 'utf-8');
				// Test if document is published in the working lang
				if ($doc->loadXML($content) !== false)
				{
					$this->testXmlContent($doc, $prg, $report, $user);
				}
			}
		
		}
		return count($users) < $chunkSize;
	}
	
	/**
	 * @param integer $offset
	 * @param integer $chunkSize
	 * @param projectcare_persistentdocument_report $report
	 * @return boolean true if all links are checked, false if there are other links to check.
	 */
	public function checkDocumentInBlockInTemplate($offset, $chunkSize, $report)
	{
		$templates = website_TemplateService::getInstance()->createQuery()->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_DocumentInBlockReportGenerator');
		
		/* @var $template website_persistentdocument_template*/
		foreach ($templates as $template)
		{
			$doc = new DOMDocument('1.0', 'utf-8');
			// Test if document is published in the working lang
			if ($doc->loadXML($template->getContent()) !== false)
			{
				$this->testXmlContent($doc, $prg, $report, $template);
			}
		
		}
		return count($templates) < $chunkSize;
	}
	
	public function checkDocumentInBlockInPageTemplate($offset, $chunkSize, $report)
	{
		$templates = theme_PagetemplateService::getInstance()->createQuery()->add(Restrictions::ne('publicationstatus', 'DEPRECATED'))->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_DocumentInBlockReportGenerator');
		$rc = RequestContext::getInstance();
		foreach ($templates as $template)
		{
			/* @var $template theme_persistentdocument_pagetemplate */
			$configuredBlocks = $template->getConfiguredBlocks();
			
			if (count($configuredBlocks) > 0)
			{
				foreach ($configuredBlocks as $configuredBlock)
				{
					$id = $configuredBlock['parameters']['cmpref'];
					
					if ($id != null)
					{
						$blockName = $configuredBlock['type'];
						$row = $this->checkDocumentId($id, $template, $blockName, "cmpref");
						if ($row !== null)
						{
							$prg->appendRowsToCsv($report, array($row));
						}
					}
				
				}
			}
		}
		return count($templates) < $chunkSize;
	}
	
	/**
	 * @param DOMDocument $doc
	 * @param projectcare_DocumentInBlockReportGenerator $prg
	 * @param projectcare_persistentdocument_report $report
	 * @param f_persistentdocument_PersistentDocument $container
	 */
	private function testXmlContent($doc, $prg, $report, $container)
	{
		
		$resultXPath = new DOMXPath($doc);
		$resultXPath->registerNamespace('change', 'http://www.rbs.fr/change/1.0/schema');
		$nodeList = $resultXPath->query("//change:block");
		
		foreach ($nodeList as $node)
		{
			
			$blockType = $node->getAttribute("type");
			$rows = $this->checkBlockParameters($node, $container, $blockType);
			
			if (count($rows) > 0)
			{
				$prg->appendRowsToCsv($report, $rows);
			}
		
		}
	
	}
	
	/**
	 * @param $node DOMElement
	 * @param f_persistentdocument_PersistentDocument $container
	 * @param String $blockName
	 */
	private function checkBlockParameters($node, $container, $blockName)
	{
		$rows = array();
		$checkCmpRef = false;
		
		$blockInfo = block_BlockService::getInstance()->getBlockInfo($blockName);
		
		$paramsInfo = $blockInfo->getParametersInfoArray();
		
		foreach ($paramsInfo as $paramInfo)
		{
			/* @var $paramInfo block_BlockPropertyInfo */
			if ($paramInfo->isDocument())
			{
				$paramName = "__" . $paramInfo->getName();
				if ($paramName == "__cmpref")
				{
					$checkCmpRef = true;
				}
				
				$value = $node->getAttribute($paramName);
				
				if ($paramInfo->isArray())
				{
					$ids = explode(",", $value);
				}
				else
				{
					$ids = array($value);
				}
				
				foreach ($ids as $id)
				{
					$row = $this->checkDocumentId($id, $container, $blockName, $paramName);
					if ($row !== null)
					{
						$rows[] = $row;
					}
				}
			
			}
		
		}
		
		if (!$checkCmpRef)
		{
			
			if ($node->hasAttribute("__cmpref"))
			{
				$id = $node->getAttribute("__cmpref");
				
				$row = $this->checkDocumentId($id, $container, $blockName, "__cmpref");
				if ($row !== null)
				{
					$rows[] = $row;
				}
			}
		}
		
		return $rows;
	
	}
	
	/**
	 * 
	 * @param Integer $id
	 * @param f_persistentdocument_PersistentDocument $container
	 * @param String $blockName
	 * @param String $paramName
	 */
	private function checkDocumentId($id, $container, $blockName, $paramName)
	{
		
		if ($id != null)
		{
			// Test the id of document
			$modelName = f_persistentdocument_PersistentProvider::getInstance()->getDocumentModelName($id);
			
			if ($modelName === false)
			{
				return array('containerId' => $container->getId(), 'containerUrl' => LinkHelper::getDocumentUrl($container), 
					'containerLabel' => $container->getLabel(), 'containerModel' => $container->getDocumentModelName(), 
					'containerLang' => $container->getLang(), 'targetId' => $id, 'blockType' => $blockName, 'paramName' => $paramName);
			}
		}
		
		return null;
	}
	
	public function checkOrphanPageReference($offset, $chunkSize, $report)
	{
		$prs = website_PagereferenceService::getInstance();
		$pageReferences = $prs->createQuery()->setFirstResult($offset)->setMaxResults($chunkSize)->find();
		$prg = projectcare_ReportGenerator::getInstance('projectcare_OrphanPageReferenceReportGenerator');
		$rc = RequestContext::getInstance();
		foreach ($pageReferences as $pageReference)
		{
			
			/* @var $pageReference website_persistentdocument_pagereference */
			if (!$prs->hasOriginPage($pageReference))
			{
				$row = array('id' => $pageReference->getId(), 'path' => $prs->getPathOf($pageReference));
				$prg->appendRowsToCsv($report, array($row));
			}
		
		}
		return count($pageReferences) < $chunkSize;
	}

}