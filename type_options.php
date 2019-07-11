<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2017      Ari Elbaz (elarifr)	<github@accedinfo.com>
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
 *      \file       htdocs/adherentsex/type.php
 *      \ingroup    member
 *      \brief      Member's type setup
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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';    
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("adherentsplus@adherentsplus");

$rowid  = GETPOST('rowid','int');
$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');

$search_lastname	= GETPOST('search_lastname','alpha');
$search_login		= GETPOST('search_login','alpha');
$search_email		= GETPOST('search_email','alpha');
$type				= GETPOST('type','alpha');
$status				= GETPOST('status','alpha');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="d.lastname"; }

$label=GETPOST("label","alpha");
$statut=GETPOST("statut","int");
$morphy=GETPOST("morphy","alpha");
$subscription=GETPOST("subscription","int");
$family=GETPOST("family","int");
$vote=GETPOST("vote","int");
$comment=GETPOST("comment");
$mail_valid=GETPOST("mail_valid");
$welcome=GETPOST("welcome","alpha");
$price=GETPOST("price","alpha");
$price_level=GETPOST("price_level","int");
$duration_value = GETPOST('duration_value', 'int');
$duration_unit = GETPOST('duration_unit', 'alpha');
$automatic=GETPOST("automatic","int");
$automatic_renew=GETPOST("automatic_renew","int")
;
// Security check
$result=restrictedArea($user, 'adherent', $rowid, 'adherent_type');

$object = new AdherentTypePlus($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('adherent_type');

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_lastname="";
    $search_login="";
    $search_email="";
    $type="";
    $sall="";
}


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membertypecard','globalcard'));


/*
 *	Actions
 */

if ($cancel) {

	$action='';

	if (! empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
}

if ($action == 'add' && $user->rights->adherent->configurer) {

    $object->welcome     = price2num($welcome);
    $object->price       = price2num($price);
    $object->price_level       = trim($price_level?$price_level:'1');
    $object->automatic   = (boolean) trim($automatic);
    $object->automatic_renew   = (boolean) trim($automatic_renew);
    $object->family   = (boolean) trim($family);
		$object->label			= trim($label);
    $object->statut         = trim($statut);
    $object->morphy         = trim($morphy);
		$object->subscription	= (int) trim($subscription);
    $object->duration_value     	 = $duration_value;
    $object->duration_unit      	 = $duration_unit;
		$object->note			= trim($comment);
		$object->mail_valid		= (boolean) trim($mail_valid);
		$object->vote			= (boolean) trim($vote);

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
	if ($ret < 0) $error++;

	if (empty($object->label)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	else {
		$sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."adherent_type WHERE libelle='".$db->escape($object->label)."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLabelAlreadyExists", $login), null, 'errors');
		}
	}

	if (! $error)
	{
		$id=$object->create($user);
		if ($id > 0)
		{
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	}
	else
	{
		$action = 'create';
	}
}

if ($action == 'update' && $user->rights->adherent->configurer)
{
	$object->fetch($rowid);

	$object->oldcopy = clone $object;

		$object->label        = trim($label);
    $object->statut         = trim($statut);
    $object->morphy         = trim($morphy);
		$object->subscription   = (int) trim($subscription);
		$object->note           = trim($comment);
		$object->mail_valid     = (boolean) trim($mail_valid);
		$object->vote           = (boolean) trim($vote);
    $object->family           = (boolean) trim($family);
    $object->welcome     = price2num($welcome);
    $object->price       = price2num($price);
    $object->price_level       = trim($price_level?$price_level:'1');
    $object->duration_value     	 = $duration_value;
    $object->duration_unit      	 = $duration_unit;
    $object->automatic   = (boolean) trim($automatic);
    $object->automatic_renew   = (boolean) trim($automatic_renew);

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
	if ($ret < 0) $error++;

	$ret=$object->update($user);

	if ($ret >= 0 && ! count($object->errors))
	{
		setEventMessages($langs->trans("MemberTypeModified"), null, 'mesgs');
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$object->id);
	exit;
}

if ($action == 'confirm_delete' && $user->rights->adherent->configurer)
{
	$object->fetch($rowid);
	$res=$object->delete();

	if ($res > 0)
	{
		setEventMessages($langs->trans("MemberTypeDeleted"), null, 'mesgs');
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		setEventMessages($langs->trans("MemberTypeCanNotBeDeleted"), null, 'errors');
		$action='';
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("MembersTypeSetup"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form=new Form($db);
$formother=new FormOther($db);
$formproduct = new FormProduct($db);

// List of members type
if (! $rowid && $action != 'create' && $action != 'edit')
{
	//dol_fiche_head('');

	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.vote, d.welcome, d.price, d.vote, d.automatic, d.automatic_renew, d.family, d.statut, d.morphy";
	$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql.= " WHERE d.entity IN (".getEntity('adherent').")";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	    print_barre_liste($langs->trans("MembersTypes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

		$moreforfilter = '';

		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Label").'</th>';
    print '<th class="center">'.$langs->trans("Nature").'</th>';
    print '<th class="center">'.$langs->trans("GroupSubscription").'</th>';
		print '<th class="center">'.$langs->trans("SubscriptionRequired").'</th>';
		print '<th class="center">'.$langs->trans("VoteAllowed").'</th>';
    print '<th class="center">'.$langs->trans("Validation").'</th>';
    print '<th class="center">'.$langs->trans("Renewal").'</th>';
    print '<th class="center">'.$langs->trans("Status").'</th>';
		print '<th>&nbsp;</th>';
		print "</tr>\n";

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			print '<tr class="oddeven">';
			print '<td><a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a></td>';
			print '<td><a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.dol_escape_htmltag($objp->label).'</a></td>';
      print '<td align="center">';
		if ($objp->morphy == 'phy') { print $langs->trans("Physical"); }
		elseif ($objp->morphy == 'mor') { print $langs->trans("Moral"); } 
    else print $langs->trans("Physical & Morale");    
      print '</td>'; //'.$objp->getmorphylib($objp->morphy).'
      print '<td class="center">'.yn($objp->family).'</td>';
			print '<td class="center">'.yn($objp->subscription).'</td>';
			print '<td class="center">'.yn($objp->vote).'</td>';
      print '<td class="center">'.autoOrManual($objp->automatic).'</td>';
      print '<td class="center">'.autoOrManual($objp->automatic_renew).'</td>';
      print '<td class="center">';
if ( !empty($objp->statut) ) print img_picto($langs->trans("InActivity"),'statut4');
else print img_picto($langs->trans("ActivityCeased"),'statut5');     
      print '</td>';
			if ($user->rights->adherent->configurer)
				print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
			else
				print '<td class="right">&nbsp;</td>';
			print "</tr>";
			$i++;
		}
		print "</table>";
		print '</div>';

		print '</form>';
	}
	else
	{
		dol_print_error($db);
	}
}


/* ************************************************************************** */
/*                                                                            */
/* Creation mode                                                              */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'create')
{
	$object = new AdherentTypePlus($db);

	print load_fiche_titre($langs->trans("NewMemberType"));

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

    dol_fiche_head('');

	print '<table class="border" width="100%">';
	print '<tbody>';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" size="40"></td></tr>';

  print '<tr><td>'.$langs->trans("Status").'</td><td>';
  print $form->selectarray('statut', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),1);
  print '</td></tr>';
  
  // Morphy
  $morphys[] = $langs->trans("Physical & Morale");
  $morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Morale");
	print '<tr><td><span>'.$langs->trans("Nature").'</span></td><td>';
	print $form->selectarray("morphy", $morphys, isset($_POST["morphy"])?$_POST["morphy"]:$object->morphy);
	print "</td></tr>";
  
  print '<tr><td>'.$langs->trans("GroupSubscription").'</td><td>';
	print $form->selectyesno("family",0,1);
  print '</td></tr>';
	
	print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
	print $form->selectyesno("subscription",1,1);
	print '</td></tr>';
  
  print '<tr ><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
	print '<input size="10" type="text" value="' . price($object->welcome) . '" name="welcome">';
  print ' '.$langs->trans("Currency".$conf->currency);    
	print '</td></tr>';
    
  print '<tr ><td>'.$langs->trans("SubscriptionPrice").'</td><td>';
	print '<input size="10" type="text" value="' . price($object->price) . '" name="price">';   
  print ' '.$langs->trans("Currency".$conf->currency);    
	print '</td></tr>';
if (! empty($conf->global->PRODUIT_MULTIPRICES)){
  print '<tr><td>';
	print $langs->trans("PriceLevel").'</td><td colspan="2">';
	print '<select name="price_level" class="flat">';
	for($i=1;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
	{
		print '<option value="'.$i.'"' ;
		if($i == $object->price_level)
		print 'selected';
		print '>'.$i;
		$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
		if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
		print '</option>';
	}
	print '</select>';
	print '</td></tr>';
}

  print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
  print '<input name="surface" size="4" value="1">';
  print $formproduct->selectMeasuringUnits("duration_unit", "time", "y", 0, 1);
  print '</td></tr>';

	print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	print $form->selectyesno("vote",0,1);
	print '</td></tr>';
  
  print '<tr><td>'.$langs->trans("Validation").'</td><td>';
	print $formother->selectAutoManual("automatic",$object->automatic,1);
	print '</td></tr>';
    
  print '<tr><td>'.$langs->trans("Renewal").'</td><td>';
	print $formother->selectAutoManual("automatic_renew",$object->automatic_renew,1);
	print '</td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="comment" wrap="soft" class="centpercent" rows="3"></textarea></td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,'90%');
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields,'edit');
	}
	print '<tbody>';
	print "</table>\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" name="button" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'" onclick="history.go(-1)" />';
	print '</div>';

	print "</form>\n";
}

/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0)
{
	if ($action != 'edit')
	{
		$object = new AdherentTypePlus($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?rowid=".$object->id, $langs->trans("DeleteAMemberType"), $langs->trans("ConfirmDeleteMemberType", $object->label), "confirm_delete", '', 0, 1);
		}

		$head = memberplus_type_prepare_head($object);

		dol_fiche_head($head, 'options', $langs->trans("MemberType"), -1, 'group');

		$linkback = '<a href="'.dol_buildpath('/adherentsplus/type.php', 1).'">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'rowid', $linkback);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans("Status").'</td><td>';
if ( !empty($object->statut) ) print img_picto($langs->trans('TypeStatusActive'),'statut4').' '.$langs->trans("InActivity");
else print img_picto($langs->trans('TypeStatusInactive'),'statut5').' '.$langs->trans("ActivityCeased");   
		print '</tr>';
    
    // Morphy
		print '<tr><td>'.$langs->trans("Nature").'</td><td class="valeur" >'.$object->getmorphylib($object->morphy).'</td>';
		print '</tr>';

    print '<tr><td class="titlefield">'.$langs->trans("GroupSubscription").'</td><td>';
		print yn($object->family);
		print '</tr>';

		print '<tr><td class="titlefield">'.$langs->trans("SubscriptionRequired").'</td><td>';
		print yn($object->subscription);
		print '</tr>';
    if ($object->subscription == '1')
	{        
    print '<tr><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
		print price($object->welcome);
    print ' '.$langs->trans("Currency".$conf->currency);
		print '</tr>';
    
    print '<tr><td>'.$langs->trans("SubscriptionPrice").'</td><td>';
		print price($object->price);
    print ' '.$langs->trans("Currency".$conf->currency);
		print '</tr>';               
}
if (! empty($conf->global->PRODUIT_MULTIPRICES)){
    print '<tr><td>';
	  print $langs->trans("PriceLevel").'</td><td colspan="2">'.$object->price_level."</td></tr>";
}

    print '<tr><td class="titlefield">'.$langs->trans("Duration").'</td><td colspan="2">'.$object->duration_value.'&nbsp;';
    if ($object->duration_value > 1)
    {
    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
    }
    elseif ($object->duration_value > 0)
    {
    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    }
    print (! empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? $langs->trans($dur[$object->duration_unit]) : '')."&nbsp;";
    print '</td></tr>';
                
		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print yn($object->vote);
		print '</tr>';

    print '<tr><td>'.$langs->trans("Validation").'</td><td>';
		print autoOrManual($object->automatic);
		print '</tr>';
    
    print '<tr><td>'.$langs->trans("Renewal").'</td><td>';
		print autoOrManual($object->automatic_renew);
		print '</tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($object->note)."</td></tr>";

		print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
		print nl2br($object->mail_valid)."</td></tr>";

    	// Other attributes
    	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';
        print '</div>';

		dol_fiche_end();

		/*
		 * Buttons
		 */

		print '<div class="tabsAction">';

		// Edit
		if ($user->rights->adherent->configurer)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;rowid='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
		}

		// Add
    //    if ( $user->rights->adherent->configurer && !empty($object->statut) )
		//{
    //        print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=create&typeid='.$object->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.$langs->trans("AddMember").'</a></div>';
    //    } else {
		//    print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoAddMember")).'">'.$langs->trans("AddMember").'</a></div>';
    //    }

		// Delete
		//if ($user->rights->adherent->configurer)
		//{
		//	print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$object->id.'">'.$langs->trans("DeleteType").'</a></div>';
		//}

		print "</div>";

	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Edition mode                                                               */
	/*                                                                            */
	/* ************************************************************************** */

	if ($action == 'edit')
	{
		$object = new AdherentTypePlus($db);
		$object->id = $rowid;
		$object->fetch($rowid);
		$object->fetch_optionals($rowid,$extralabels);

		$head = memberplus_type_prepare_head($object);

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="action" value="update">';

		dol_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');

		print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" size="40" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

    print '<tr><td>'.$langs->trans("Status").'</td><td>';
    print $form->selectarray('statut', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')), $object->statut);
    print '</td></tr>';
    
    // Morphy
    $morphys[null] = $langs->trans("Physical & Morale");
    $morphys["phy"] = $langs->trans("Physical");
    $morphys["mor"] = $langs->trans("Morale");
    print '<tr><td><span>'.$langs->trans("Nature").'</span></td><td>';
    print $form->selectarray("morphy", $morphys, isset($_POST["morphy"])?$_POST["morphy"]:$object->morphy);
    print "</td></tr>";
  
    print '<tr><td>'.$langs->trans("GroupSubscription").'</td><td>';
		print $form->selectyesno("family",$object->family,1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print $form->selectyesno("subscription",$object->subscription,1);
		print '</td></tr>';

    print '<tr ><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
		print '<input size="10" type="text" value="' . price($object->welcome) . '" name="welcome">';
    print ' '.$langs->trans("Currency".$conf->currency);    
		print '</td></tr>';
    
    print '<tr ><td>'.$langs->trans("SubscriptionPrice").'</td><td>';
		print '<input size="10" type="text" value="' . price($object->price) . '" name="price">';   
    print ' '.$langs->trans("Currency".$conf->currency);    
		print '</td></tr>';
if (! empty($conf->global->PRODUIT_MULTIPRICES)){    
    print '<tr><td>';
	  print $langs->trans("PriceLevel").'</td><td colspan="2">';
	  print '<select name="price_level" class="flat">';
	  for($i=1;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
  	{
		print '<option value="'.$i.'"' ;
		if($i == $object->price_level)
		print 'selected';
		print '>'.$i;
		$keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$i;
		if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
		print '</option>';
	  }
	  print '</select>';
	  print '</td></tr>';
 }
 
    print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
    print '<input name="duration_value" size="5" value="'.$object->duration_value.'"> ';
    print $formproduct->selectMeasuringUnits("duration_unit", "time", $object->duration_unit, 0, 1);
    print '</td></tr>';
                 
		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print $form->selectyesno("vote",$object->vote,1);
		print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("Validation").'</td><td>';
		print $formother->selectAutoManual("automatic",$object->automatic,1);
		print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("Renewal").'</td><td>';
		print $formother->selectAutoManual("automatic_renew",$object->automatic_renew,1);
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print '<textarea name="comment" wrap="soft" class="centpercent" rows="3">'.$object->note.'</textarea></td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('mail_valid',$object->mail_valid,'',280,'dolibarr_notes','',false,true,$conf->fckeditor->enabled,15,'90%');
		$doleditor->Create();
		print "</td></tr>";

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$act,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
		    print $object->showOptionals($extrafields,'edit');
		}

		print '</table>';

		// Extra field
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print '<br><br><table class="border" width="100%">';
			foreach($extrafields->attribute_label as $key=>$label)
			{
				if (isset($_POST["options_" . $key])) {
					if (is_array($_POST["options_" . $key])) {
						// $_POST["options"] is an array but following code expects a comma separated string
						$value = implode(",", $_POST["options_" . $key]);
					} else {
						$value = $_POST["options_" . $key];
					}
				} else {
					$value = $adht->array_options["options_" . $key];
				}
				print '<tr><td width="30%">'.$label.'</td><td>';
				print $extrafields->showInputField($key,$value);
				print "</td></tr>\n";
			}
			print '</table><br><br>';
		}

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print "</form>";
	}
}


llxFooter();

$db->close();
