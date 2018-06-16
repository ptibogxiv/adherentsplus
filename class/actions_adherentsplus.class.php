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
 *	\file       htdocs/multicompany/actions_multicompany.class.php
 *	\ingroup    multicompany
 *	\brief      File Class multicompany
 */
 
dol_include_once('/adherentsplus/class/adherent.class.php');
$langs->load("adherentsplus@adherentsplus");

/**
 *	\class      ActionsMulticompany
 *	\brief      Class Actions of the module multicompany
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
	 *
	 */
	function formObjectOptions($parameters=false, &$object, &$action='')
	{
		global $db,$conf,$user,$langs,$form;

		if (is_array($parameters) && ! empty($parameters))
		{
			foreach($parameters as $key=>$value)
			{
				$key=$value;
			}
		}

		if (is_object($object) && $object->element == 'societe')
		{ 
			if ($action == 'create' || $action == 'editparentwordpress')
			{
				$this->resprints.= '<tr><td>'.fieldLabel('LinkedToWordpress','linked_entity').'</td><td colspan="3" class="maxwidthonsmartphone">';
//				$s = $this->select_entities('', 'linked_entity', '', 0, array($conf->entity), true);
				$this->resprints.= $form->textwithpicto($s,$langs->trans("LinkedToDolibarrMember"),1);
				$this->resprints.= '</td></tr>';
			}
			else
			{
				$this->resprints.= '<tr><td>';
				$this->resprints.= '<table width="100%" class="nobordernopadding"><tr><td>';
				$this->resprints.= $langs->trans("LinkedToDolibarrMember");
				$this->resprints.= '<td><td align="right">';
				$this->resprints.= '<a href="'.$dolibarr_main_url_root.dol_buildpath('/adhrentsplus/card.php', 1).'">'.img_edit().'</a>';
				$this->resprints.= '</td></tr></table>';
				$this->resprints.= '</td>';
				$this->resprints.= '<td colspan="3">';
            $adh=new AdherentPlus($db);
            $result=$adh->fetch('','',$object->id);
            if ($result > 0)
            {
                $adh->ref=$adh->getFullName($langs);
$this->resprints.= ''.$adh->getNomUrl(1);
            }
            else
            {
$this->resprints.= '<span class="opacitymedium">'.$langs->trans("ThirdpartyNotLinkedToMember").'</span>';
            }
				$this->resprints.= '</td></tr>';
			}
		} 

		return 0;
	}

}
