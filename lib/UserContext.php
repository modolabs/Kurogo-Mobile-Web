<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */
Kurogo::includePackage('UserContext');
class UserContext
{
    const CONTEXT_DEFAULT = '*';
    const RESOLUTION_TYPE_AUTO = 'auto';
    const RESOLUTION_TYPE_MANUAL = 'manual';
	protected $id;
	protected $title;
	protected $description;
	protected $rule;
	protected $active;
	
	public function isManual() {
	    return $this->rule->getResolution() == self::RESOLUTION_TYPE_MANUAL;
	}

	public function isAuto() {
	    return $this->rule->getResolution() == self::RESOLUTION_TYPE_AUTO;
	}

	public function getContextArgs() {
	    return $this->rule->getContextArgs();
	}
	
	public function __toString() {
	    return sprintf("%s (%s)", $this->id, get_class($this->rule));
	}
	
	public function setID($id) {
	    if ($id == self::CONTEXT_DEFAULT) {
	        //ok
	    } elseif (!preg_match("/^[a-z0-9_-]+$/i", $id)) {
			throw new KurogoConfigurationException("Invalid context id $id");
		}
		$this->id = $id;
		return true;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function isActive() {
	    if (!isset($this->active)) {
	        $this->active = $this->rule->evaluate();
	    }
	    
		return $this->active;
	}
	
	public function setContext($bool) {
		return $this->rule->setContext($bool);
	}
	
	public static function factory($id, $args) {
	    $context = new UserContext();
	    $context->setID($id);
	    $context->init($args);
	    return $context;
	}
	
	public function init($args) {
		$args['ID'] = $this->id;
		$this->title = Kurogo::arrayVal($args, 'TITLE', $this->id);
		$this->description = Kurogo::arrayVal($args, 'DESCRIPTION');
		$this->rule = UserContextRule::factory($args);
	}
	
	public function setDescription($description) {
		$this->description = $description;
	}
	
	public function getDescription() {
		return $this->description;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

}
