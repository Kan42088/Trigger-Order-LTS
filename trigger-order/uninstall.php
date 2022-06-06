<?php
/**
 * Trigger this file on Plugin uninstall
 * 
 * @package triggerOrderPlugin
 * 
 */

 if(! defined('WP_UNINSTALL_PLUGIN')){
     die;
 }

 
 //Clear

 //trelo param
 delete_option('to-trello-key');
 delete_option('to-trello-secret');
 delete_option('to-trello-boardId');
 delete_option('to-trello-source-listId');

 //email param
 delete_option('to-email-username');
 delete_option('to-email-password');
 delete_option('to-email-sender-name');
 delete_option('to-email-subject-oc');
 delete_option('to-email-content-oc');
 delete_option('to-email-subject-pu');
 delete_option('to-email-content-pu');