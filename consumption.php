<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Frederic France         <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/adherents/note.php
 *      \ingroup    member
 *      \brief      Tab for note of a member
*/

// require '../../main.inc.php';
// Dolibarr environment
$res = 0;
if (! $res && file_exists("../main.inc.php"))
{
	$res = @include "../main.inc.php";
}
if (! $res && file_exists("../../main.inc.php"))
{
	$res = @include "../../main.inc.php";
}
if (! $res && file_exists("../../../main.inc.php"))
{
	$res = @include "../../../main.inc.php";
}
if (! $res)
{
	die("Main include failed");
}

dol_include_once('/adherentsplus/lib/member.lib.php');
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->loadLangs(array('products', 'companies', 'members', 'bills', 'other', 'adherentsplus@adherentsplus'));

$action = GETPOST('action', 'alpha');
$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'); // $place is id of table for Bar or Restaurant
$invoiceid = GETPOST('invoiceid', 'int');
$cancel = GETPOST('cancel','alpha');
$id=GETPOST('rowid','int');
$lineid=GETPOST('lineid','int');
$productid  = GETPOST('productid','int');
$qty  = GETPOST('quantity','int');
if (!empty(GETPOST('date_start_month', 'int')) && !empty(GETPOST('date_start_day', 'int')) && !empty(GETPOST('date_start_year', 'int'))) $date_start = dol_mktime(0, 0, 0, GETPOST('date_start_month', 'int'), GETPOST('date_start_day', 'int'), GETPOST('date_start_year', 'int'));
if (!empty(GETPOST('date_end_month', 'int')) && !empty(GETPOST('date_end_day', 'int')) && !empty(GETPOST('date_end_year', 'int'))) $date_end = dol_mktime(0, 0, 0, GETPOST('date_end_month', 'int'), GETPOST('date_end_day', 'int'), GETPOST('date_end_year', 'int'));
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'thirdpartylist';

if ($contextpage == 'takepos')
{
	$_GET['optioncss'] = 'print';
$constforcompanyid = $conf->global->{'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"]};  
$invoice = new Facture($db);
if ($invoiceid > 0)
{
    $invoice->fetch($invoiceid);
}

if ($constforcompanyid != $invoice->socid && !empty($invoice->socid)) { 
$adh = new AdherentPlus($db);
$result = $adh->fetch('', '', $invoice->socid, '', '', '', 1);
$id = $adh->id;
}
}

// Security check
if (!$id || (!$user->rights->adherent-creer && !$user->rights->takepos->run))
{
$message = null;
if ($constforcompanyid == $invoice->socid)  $message = $langs->trans('MembershipNotAllowedForGenericCustomer');
	accessforbidden($message);
}

// Security check
$result=restrictedArea($user, 'adherent', $id);

$object = new AdherentPlus($db);
$result=$object->fetch($id, '', '', '', '', '', 1);
if ($result > 0)
{
    $adht = new AdherentTypePlus($db);
    $result=$adht->fetch($object->typeid);
}
$consumption = new Consumption($db);
$permissionnote=$user->rights->adherent->creer;  // Used by the include of actions_setnotes.inc.php

/*
 *	Actions
 */

if ($action == 'confirm_create' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{ 
		$consumption->fk_adherent= $id;
		$consumption->fk_type    = $rowid;
    $consumption->fk_product = $productid;
		$consumption->qty        = $qty;
    $consumption->date_start = $date_start;
    $consumption->date_end = $date_end;

			$result = $consumption->create($user);
			if ($result > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$id);
				exit;
			}
			else
			{
				$mesg=$consumption->error;
				$action = 'create';
			}

	}
}
  
if ($action == 'update' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{
		$consumption->id     = $lineid;
		$consumption->fk_adherent     = $id;
		$consumption->qty        = $qty;
    $consumption->date_start = $date_start;
    $consumption->date_end = $date_end;

		$result = $consumption->update($user);

				header("Location: consumption.php?rowid=".$id);
				exit;
	}
}
  
	if ($user->rights->adherent->configurer && $action == 'confirm_delete' && GETPOST('confirm', 'alpha') == 'yes')
	{
    $consumption->fetch($lineid);
		$result = $consumption->delete($lineid, $user);
		if ($result > 0)
		{
			if (! empty($backtopage))
			{
				header("Location: ".$backtopage);
				exit;
			}
			else
			{
				header("Location: consumption.php?rowid=".$id);
				exit;
			}
		}
		else
		{
			setEventMessages($consumption->error, $consumption->errors, 'errors');
		}
	}

/*
 *	View
 */

$title=$langs->trans("Member") . " - " . $langs->trans("Consumptions");
$helpurl="EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
llxHeader("",$title,$helpurl);

$form = new Form($db);

if ($id && $contextpage != 'takepos') 
{  
	$head = memberplus_prepare_head($object);

	dol_fiche_head($head, 'consumption', $langs->trans("Member"), -1, 'user');

  $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	
	dol_banner_tab($object, 'rowid', $linkback);
    
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

    // Login
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
    {
        print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';
    }

		// Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans("LinkedToDolibarrThirdParty");
			print '</td>';
			if ($action != 'editthirdparty' && $user->rights->adherent->creer) print '<td align="right"></td>';
			print '</tr></table>';
			print '</td><td colspan="2" class="valeur">';
				if ($object->fk_soc)
				{
					$company=new Societe($db);
					$result=$company->fetch($object->fk_soc);
					print $company->getNomUrl(1);
				}
				else
				{
					print $langs->trans("NoThirdPartyAssociatedToMember");
				}
			print '</td></tr>';
		}
    
    // Type
    print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1).'</td></tr>';

    // Company
    print '<tr><td>'.$langs->trans("SubscriptionEndDate").'</td><td class="valeur">';
    if ($object->datefin)
		{
			print dol_print_date($object->datefin,'day');
			if ($object->hasDelay()) {
				print " ".img_warning($langs->trans("Late"));
			}
		}
		else
		{
			if (! $adht->subscription)
			{
				print $langs->trans("SubscriptionNotRecorded");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
			else
			{
				print $langs->trans("SubscriptionNotReceived");
				if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft and not terminated
			}
		}
    print'</td></tr>';

    // Commitment
    print '<tr><td>'.$langs->trans("Commitment").'</td><td class="valeur">';
		if ($object->datecommitment)
		{
			print dol_print_date($object->datecommitment,'day');
			if ($object->hasDelay()) {
				print " ".img_warning($langs->trans("Late"));
			}
		}
		else
		{
				print $langs->trans("None");
		}     
    print '</td>';
    print '</tr>';

    print "</table>";

    print '</div>';


    $cssclass='titlefield';
    $permission = $user->rights->adherent->creer;  // Used by the include of notes.tpl.php
    //include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

    dol_fiche_end();

}

if ($id && $action == 'create' && $user->rights->adherent->creer)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?rowid='.$id.'" method="post">';
if ($contextpage == 'takepos') print '<input type="hidden" name="contextpage" value="takepos">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforadd='confirm_create';
	print '<input type="hidden" name="action" value="'.$actionforadd.'">';
} elseif ($id && $action == 'edit' && $user->rights->adherent->creer)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?rowid='.$id.'" method="post">';
if ($contextpage == 'takepos') print '<input type="hidden" name="contextpage" value="takepos">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforedit='update';
	print '<input type="hidden" name="action" value="'.$actionforedit.'">';
} else {
	print '<form action="'.$_SERVER["PHP_SELF"].'?rowid='.$id.'" method="post">';
if ($contextpage == 'takepos') print '<input type="hidden" name="contextpage" value="takepos">'; 
	print '<input type="hidden" name="token" value="'.newToken().'">';
}

	// Confirm delete ban
	if ($action == 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$id."&lineid=".$lineid, $langs->trans("DeleteAConsumption"), $langs->trans("ConfirmDeleteConsumption", ''), "confirm_delete", '', 0, 1);
	}
  
/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */
	if ($action != 'create' && $action != 'edit')
	{

     //print var_dump($object->overview_consumptions($id, $object->typeid));

			print '<input class="flat" type="hidden" name="rowid" value="'.$socid.'" size="12">';
      
      print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
    /*
    * List of consumptions
    */
    $sql = "SELECT entity, fk_product, sum(qty) as package";    
    $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type_package as c";
		$sql.= " WHERE entity IN (".getEntity('adherent').")";
		$sql.= " AND (fk_type = '".$object->typeid."' or fk_member = '".$object->id."')";
    $sql.= " GROUP BY fk_product";
		//dol_syslog(get_class($this)."::fetch_consumptions", LOG_DEBUG);

		$resql=$db->query($sql);
		if ($resql)
		{
			$package=array();

			$i=0;
            while ($obj = $db->fetch_object($resql))
            {
                $package[$obj->fk_product]=$obj->package;
                $i++;
            }

		} 
  print var_dump($package);
  
    
  $urlcreation =  $_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=create'; 
  if ($contextpage == 'takepos') $urlcreation .= "&contextpage=takepos";
  $morehtmlright= dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $urlcreation);

      print load_fiche_titre($langs->trans("ListOfProductsServices"), $morehtmlright, '');

      print '<div class="div-table-responsive">';
      print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

            print '<tr class="liste_titre">';
            print '<td align="left">'.$langs->trans("DateCreation").'</td>';
            print '<td align="left">'.$langs->trans("Date").'</td>';
            print '<td align="center">'.$langs->trans("Description").'</td>';
            print '<td align="center">'.$langs->trans("Quantity").'</td>';
            print '<td align="right">'.$langs->trans("Price").'</td>';
            print '<td align="right">'.$langs->trans('Invoice').'</td>';
            print '<td align="center">'.$langs->trans('Action').'</td>';
            print "</tr>\n";

            foreach ($object->consumptions as $consumption)
            {

                print "<tr ".$bc[$var].">";

                print '<td>'.dol_print_date($consumption->date_creation,'dayhour')."</td>\n";
                print '<td>'.dol_print_date($consumption->date_start,'day')."</td>\n";
                print '<td align="center">';
                $prodtmp=new Product($db);
                $prodtmp->fetch($consumption->fk_product);
                print $prodtmp->getNomUrl(1);	// must use noentitiesnoconv to avoid to encode html into getNomUrl of product
                print ' - '.$prodtmp->label.'</td>';
                print '<td align="center">'; 
                             
                if ($prodtmp->isService() && $prodtmp->duration_value> 0)
            {
                print $consumption->qty." "; 
                if ($prodtmp->duration_value > 1)
                {
                    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
                }
                else if ($prodtmp->duration_value > 0)
                {
                    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
                }
                print (! empty($prodtmp->duration_unit) && isset($dur[$prodtmp->duration_unit]) ? $langs->trans($dur[$prodtmp->duration_unit]) : '')."/</td>\n";                  
            } else {
                print $consumption->qty."/</td>\n";  
            }   
        
                print '<td align="right">'.$consumption->amount.'</td>';
                print '<td align="right">'.dol_print_date($consumption->date_validation,'day').'</td>';
                
		        // Actions
		        print '<td align="center">';
				if ($user->rights->adherent->configurer && empty($consumption->fk_facture))
        {
				print '<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$id.'&lineid='.$consumption->id.'&action=edit">';
				print img_picto($langs->trans("Modify"), 'edit');
				print '</a>';

		   	print '&nbsp;';

		   	print '<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$id.'&lineid='.$consumption->id.'&action=delete">';
		   	print img_picto($langs->trans("Delete"), 'delete');
		   	print '</a>';
		    } else {
		   	print '&nbsp;';
        }
				print "</td>";
                
                print "</tr>";

            }
            print "</table></div>";
            
 }   
 
// Create Card
if ($id && $action == 'create' && $user->rights->adherent->creer)
{

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PredefinedProductsAndServicesToSell").'</td>';
	print '<td>';
  			if (! empty($conf->global->ENTREPOT_EXTRA_STATUS))
			{
				// hide products in closed warehouse, but show products for internal transfer
				$form->select_produits(GETPOST('productid', 'int'), 'productid', $filtertype, $conf->product->limit_size, $object->price_level, 1, 2, '', 1, array(), $object->id, '1', 0, 'maxwidth300', 0, 'warehouseopen,warehouseinternal', GETPOST('combinations', 'array'));
			}
			else
			{
				$form->select_produits(GETPOST('productid', 'int'), 'productid', $filtertype, $conf->product->limit_size, $object->price_level, 1, 2, '', 1, array(), $object->id, '1', 0, 'maxwidth300', 0, '', GETPOST('combinations', 'array'));
			}
  print '</td></tr>';

  print '<tr><td class="fieldrequired">'.$langs->trans("Qty").'</td>';
	print '<td><input class="minwidth50" type="text" name="quantity" value="'.($qty?$qty:1).'"></td></tr>';
  print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("DateStart").'</td><td>';
  $form->select_date($date_start, 'date_start_', '', '', '', "date_start", 1, 1);
  print '</td></tr>';
  print '</tr>';
  print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
  $form->select_date($date_end, 'date_end_', '', '', '', "date_end", 1, 1);
  print '</td>';
  print '</tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	dol_set_focus('#label');

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Add").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

// Create Card
if ($id && $action == 'edit' && $user->rights->adherent->creer)
{

  $consumption->fetch($lineid);  

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PredefinedProductsAndServicesToSell").'</td>';
	  $product_static = new Product($db);
		$product_static->id = $consumption->fk_product;
		$product_static->ref = $consumption->ref;
    $product_static->label = $consumption->label;
    $product_static->type = $consumption->fk_product_type;
	print '<td>'.$product_static->getNomUrl(1)." - ".$consumption->label.'</td></tr>';
  print '<tr><td class="fieldrequired">'.$langs->trans("Qty").'</td>';
	print '<td><input class="minwidth50" type="text" name="quantity" value="'.($qty?$qty:$consumption->qty).'"></td></tr>';
  print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("DateStart").'</td><td>';
  $form->select_date($consumption->date_start, 'date_start_', '', '', '', "date_start", 1, 1);
  print '</td></tr>';
  print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
  $form->select_date($consumption->date_end, 'date_end_', '', '', '', "date_end", 1, 1);
  print '</td>';
  print '</tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	dol_set_focus('#label');

	print '<div class="center"><input type="hidden" name="lineid" value="'.$lineid.'">';
	print '<input class="button" value="'.$langs->trans("Edit").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';
}

print '</form>';    
            
llxFooter();
$db->close();
