<?php

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Library dependencies
jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

class plgSystemSEOPlugin extends JPlugin
{
	/**
     * Constructor
     *
     * @param object $subject
     * The object to observe
     *
     * @param bool $params
     * An optional associative array of configuration settings. Recognized key values 
     * include 'name', 'group', 'params', 'language' (this list is not meant to be comprehensive).
     */
    function plgSystemSEOPlugin(&$subject, $params)
    {
		parent::__construct($subject, $params);
    }

    // This event is triggered after the framework has dispatched the application.
    function onAfterDispatch()
    {
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		$docType = $document->getType();
 
    	// Not(admin, install, etc.)
      	if(!$app->isSite()) 
      		return ;

    	if($docType != 'html') 
    		return ;
		
		// Check to see if this is the front page and if front page title layout feature is disabled
		$titleLayout = $this->params->def('l_title_layout', 0);
		if($this->isFrontPage() && $titleLayout == 0) 
			return;

		// Check to see if this is not the front page and if Page title layout feature is disabled
		$titOrder = $this->params->def('titorder', 0);
		if (!$this->isFrontPage() && $titOrder == 0) 
			return;

		// Changing stuff
		$customTitle = html_entity_decode($this->params->def('custom_title', 'Home'));
		$pageTitle = html_entity_decode($document->getTitle());
		$sitename = html_entity_decode($app->getCfg('sitename'));
		$sep = $this->params->def('separator', '|');
		
		if($this->isFrontPage())
		{
			if($titleLayout == 1)
				$newPageTitle = $customTitle . ' ' . $sep . ' ' . $sitename;
			elseif($titleLayout == 2)
				$newPageTitle = $sitename . ' ' . $sep . ' ' . $customTitle;
			elseif($titleLayout == 3)
				$newPageTitle = $customTitle;
			elseif($titleLayout == 4)
				$newPageTitle = $sitename;
			elseif($titleLayout == 5)
				$newPageTitle = $sitename . ' ' . $sep . ' ' . $pageTitle;
			elseif($titleLayout == 6)
				$newPageTitle = $pageTitle . ' ' . $sep . ' ' . $sitename;
			elseif($titleLayout == 7)
				$newPageTitle = $customTitle . ' ' . $sep . ' ' . $pageTitle;
			elseif($titleLayout == 8)
				$newPageTitle = $pageTitle . ' ' . $sep . ' ' . $customTitle;
		}
		else
		{
			if($titOrder == 1)
				$newPageTitle = $pageTitle . ' ' . $sep . ' ' . $sitename;
			elseif($titOrder == 2)
				$newPageTitle = $sitename . ' ' . $sep . ' ' . $pageTitle;
		}

		// Set the new page title
		$document->setTitle($newPageTitle);
	}

	function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		$app = JFactory::getApplication();
		
		if(!$app->isSite())
			return;
		
		$document = JFactory::getDocument();
		$view = JRequest::getVar('view');
		$length = $this->params->def('t_length', 155);
		$thecontent = $article->text;
		$rContent = $this->params->def('r_content', 0);
		$rDesc = $this->params->def('r_desc', 0);

		// Checks to see whether front page should use standard desc or auto-generated one.
		if($this->isFrontPage() && $rContent == 0)
		{
			$document->setDescription($app->getCfg('MetaDesc'));
			return;
		}

		// Grab only the first content item in category list.
		if($document->getDescription() != '')
		{
			if ($document->getDescription() != $app->getCfg('MetaDesc')) 
				return;
		}

		if($view == 'category' && $rDesc == 0)
		{ 

			$db = JFactory::getDBO();
			$catId = JRequest::getVar('id');
			$db->setQuery('SELECT cat.description FROM #__categories cat WHERE cat.id=' . $catId);   
      		$rDesc = $db->loadResult();

			if($rDesc)
			{ 
				$thecontent = $rDesc;
			}
		}

		// Rounding to nearest word
		$thecontent = $thecontent . ' ';
		$thecontent = substr($thecontent, 0, $length);
		$thecontent = rtrim(substr($thecontent,0,strrpos($thecontent,' ')), ' ');

		// Set the description
		$document->setDescription($thecontent);
	}

	function isFrontPage()
	{
		$app = JFactory::getApplication();
		$menu = $app->getMenu();
		$lang = JFactory::getLanguage();

		if($menu->getActive() == $menu->getDefault($lang->getTag()))
			return true;

		return false;
	}

	/**
     * Clean Text
     *
     * @param string $text
     * @return string
     */
	function cleanText($text)
	{
		$text = preg_replace( "'<script[^>]*>.*?</script>'si", '', $text );
		$text = preg_replace( '/<!--.+?-->/', '', $text );
		$text = preg_replace( '/{.+?}/', '', $text );

		// Convert html entities to chars 
		$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

		return $text;
	}
}