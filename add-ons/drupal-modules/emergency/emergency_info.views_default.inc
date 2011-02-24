<?php
   function emergency_info_views_default_views() {

   $views = array();

   require "emergency_contact_lists.view.export";
   $views[$view->name] = $view;

   require "emergency_notifications.view.export";
   $views[$view->name] = $view;

   return $views;   
}