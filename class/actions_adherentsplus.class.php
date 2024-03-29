<?php
/* Copyright (C) 2009-2016 Regis Houssin  <regis@dolibarr.fr>
 * Copyright (C) 2011      Herve Prot     <herve.prot@symeos.com>
 * Copyright (C) 2014      Philippe Grand <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/adherentsplus/actions_adherentsplus.class.php
 *	\ingroup    multicompany
 *	\brief      File Class Adherentsplus
 */
 
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
$langs->loadLangs(array("members", 'adherentsplus@adherentsplus'));

/**
 *	\class      ActionsAdherentsplus
 *	\brief      Class Actions of the module Adherentsplus
 */
class Actionsadherentsplus
{
	/** @var DoliDB */
	var $db;

	private $config=array();

	// For Hookmanager return
	var $resprints;
	var $results=array();


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}
  
  	/**
	 * addMoreActionsButtons
	 *
	 * @param array	 	$parameters	Parameters
	 * @param Object	$object		Object
	 * @param string	$action		action
	 * @return int					0
	 */
	function addMoreActionsButtons($parameters, &$object, &$action)
	{
		global $langs, $conf, $user;
		if (is_object($object) && $object->element == 'societe' && (float)DOL_VERSION < 11.0 ){

    $adh = new Adherent($this->db);
    $result=$adh->fetch('','',$object->id);
    if ($result == 0 && ($object->client == 1 || $object->client == 3) && ! empty($conf->global->MEMBER_CAN_CONVERT_CUSTOMERS_TO_MEMBERS))
{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/adherents/card.php?&action=create&socid='.$object->id.'" title="'.dol_escape_htmltag($langs->trans("NewMember")).'">'.$langs->trans("NewMember").'</a>';
}    
    
	}
  return 0;
  }
  
  	/**
	 * ActionButtons
	 *
	 * @param array	 	$parameters	Parameters
	 * @return int					0
	 */
	function ActionButtons($parameters)
	{
       global $conf, $user, $langs;

        $error = 0; // Error counter

        /* print_r($parameters); print_r($object); echo "action: " . $action; */
        if (in_array($parameters['currentcontext'], array('takeposfrontend')))		// do something only for the context 'somecontext1' or 'somecontext2'
        {
			?><script>var invoiceid = $("#invoiceid").val();</script><?php
			$langs->loadLangs(array("members", 'adherentsplus@adherentsplus'));
			$buttons['subscription'] = array('title'=>'<span class="fas fa-users paddingrightonly"></span><div class="trunc">'.$langs->trans("Subscription").'</div>', 'action'=>'$.colorbox({href:\'../custom/adherentsplus/takepos_subscription.php?place=\'+place+\'&invoiceid=\'+invoiceid+\'&contextpage=takepos\', width:\'80%\', height:\'90%\', transition:\'none\', iframe:\'true\', title:\''.$langs->trans("Subscription").'\'});');
        if (!empty($conf->global->ADHERENT_CONSUMPTION)) {
			$buttons['consumption'] = array('title'=>'<span class="fas fa-users paddingrightonly"></span><div class="trunc">'.$langs->trans("Consumptions").'</div>', 'action'=>'$.colorbox({href:\'../custom/adherentsplus/consumption.php?place=\'+place+\'&invoiceid=\'+invoiceid+\'&contextpage=takepos\', width:\'80%\', height:\'90%\', transition:\'none\', iframe:\'true\', title:\''.$langs->trans("Consumptions").'\'});');
        }       

        }

        if (! $error) {
            $this->results = array($buttons);
        //    $this->resprints = 'A text to show';
            return 0; // or return 1 to replace standard code
        } else {
            $this->errors[] = 'Error message';
            return -1;
        }
  }

}
