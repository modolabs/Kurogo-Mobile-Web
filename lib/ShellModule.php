<?php

abstract class ShellModule extends Module {

    /**
     * Factory method
     * @param string $id the module id to load
     * @param string $command the command to execute
     * @param array $args an array of arguments
     * @return APIModule 
     */
     
    public static function factory($id, $command='', $args=array()) {

        if (!$module = parent::factory($id, 'shell')) {
            return false;
        }
        if ($command) {
            $module->init($command, $args);
        }

        return $module;
    }
    
    /**
     * Initialize the request
     */
    protected function init($command='', $args=array()) {
        parent::init();
        $this->setArgs($args);
        $this->setRequestedVersion($this->getArg('v', null), $this->getArg('vmin', null));
        if ($context = $this->getArg('context', null)) {
            $this->setContext($context);
        }
        $this->setCommand($command);
    }
    
    /**
     * All modules must implement this method to handle the logic of each command.
     */
    abstract protected function initializeForCommand();

}



