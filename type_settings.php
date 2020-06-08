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

$family=GETPOST("family","int");
$welcome=GETPOST("welcome","alpha");
$price=GETPOST("price","alpha");
$federal=GETPOST("federal","alpha");
$price_level=GETPOST("price_level","int");
$duration_value = GETPOST('duration_value', 'int');
$duration_unit = GETPOST('duration_unit', 'alpha');
$automatic=GETPOST("automatic","int");
$automatic_renew=GETPOST("automatic_renew","int");

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

if ($action == 'update' && $user->rights->adherent->configurer)
{
	$object->fetch($rowid);

	$object->oldcopy = clone $object;

    $object->family           = (boolean) trim($family);
    $object->welcome     = price2num($welcome);
    $object->price       = price2num($price);
    $object->federal       = price2num($federal);
    $object->price_level       = trim($price_level?$price_level:'1');
    if ((float) DOL_VERSION < 11.0) $object->duration_value     	 = $duration_value;
    if ((float) DOL_VERSION < 11.0) $object->duration_unit      	 = $duration_unit;
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
		print '<input type="hidden" name="token" value="'.newToken().'">';
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
    print '<th class="center">'.$langs->trans("MemberNature").'</th>';
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

		dol_fiche_head($head, 'settings', $langs->trans("MemberType"), -1, 'group');

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'rowid', $linkback);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans("GroupSubscription").'</td><td>';
		print yn($object->family);
		print '</tr>';

    if (! empty($object->subscription))
    {        
    print '<tr><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
		print price($object->welcome);
    print ' '.$langs->trans("Currency".$conf->currency);
		print '</tr>';
    
    print '<tr><td>'.$langs->trans("SubscriptionPrice").'</td><td>';
		print price($object->price);
    print ' '.$langs->trans("Currency".$conf->currency);
		print '</tr>';
    
if (! empty($conf->global->ADHERENT_FEDERAL_PART)){    
    print '<tr><td>'.$langs->trans("FederalPart");
		print $form->textwithpicto($s,$langs->trans("IncludeInSubscritionPrice"),1);
    print '</td><td>';
		print price($object->federal);
    print ' '.$langs->trans("Currency".$conf->currency);
		print '</tr>';
}    
    }

if (! empty($conf->global->PRODUIT_MULTIPRICES)){
    print '<tr><td>'.$langs->trans("PriceLevel").'</td><td>';
    print $object->price_level;
	  $keyforlabel='PRODUIT_MULTIPRICES_LABEL'.$object->price_level;
		if (! empty($conf->global->$keyforlabel)) print ' - '.$langs->trans($conf->global->$keyforlabel);
    print '</td></tr>';
}
if ((float) DOL_VERSION < 11.0) {
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
}

    print '<tr><td class="titlefield">'.$langs->trans("Commitment").'</td><td colspan="2">';
    if (empty($object->commitment_value)) {
    print $langs->trans("None");
    } else {
    print $object->commitment_value.'&nbsp;';
    if ($object->commitment_value > 1)
    {
    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
    }
    elseif ($object->commitment_value > 0)
    {
    $dur=array("i"=>$langs->trans("Minute"),"h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    }
    print (! empty($object->commitment_unit) && isset($dur[$object->commitment_unit]) ? $langs->trans($dur[$object->commitment_unit]) : '')."&nbsp;";
    }
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Validation").'</td><td>';
		print autoOrManual($object->automatic);
		print '</tr>';
    
    print '<tr><td>'.$langs->trans("Renewal").'</td><td>';
		print autoOrManual($object->automatic_renew);
		print '</tr>';

    	// Other attributes
    	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';
    print '</div>';
        
        // Create a new DateTime object
$abo = null;
//$abo = "2020-04-03 15:46:24";
$date = new DateTime($abo);  
$monthName = date("F", mktime(0, 0, 0, $conf->global->SOCIETE_SUBSCRIBE_MONTH_START, 10));
$date->modify('FIRST DAY OF '.$monthName.' MIDNIGHT');
if ($date->getTimestamp() > dol_now()) {
$date->modify('LAST YEAR');
}
$prestart = 12 - $conf->global->SOCIETE_SUBSCRIBE_MONTH_PRESTART;
$date->modify(' + '.$prestart.' MONTHS'); 
//print 'renew: '.$date->format('Y-m-d H:i:s').'<br>'; 
$daterenew = $date->getTimestamp();
if ($date->getTimestamp() <= dol_now()) {
$date->modify('NEXT YEAR');
} 
$date->modify(' - '.$prestart.' MONTHS'); 
$datefrom = $date->format('Y-m-d H:i:s');
$date = new DateTime($datefrom);
print 'from '.$date->format('Y-m-d H:i:s');
$date->modify('NEXT YEAR');
$date->modify('-1 SECONDS');
$dateto = $date->format('Y-m-d H:i:s');
print ' to '.$date->format('Y-m-d H:i:s').'<br>';

$date = new DateTime($dateto);
$date->modify('NEXT DAY MIDNIGHT');
$date->modify('- '.$conf->global->SOCIETE_SUBSCRIBE_MONTH_PRESTART.' MONTHS');  
print 'date_renew: '.$date->format('Y-m-d H:i:s').'<br>';

if (!empty($abo) && $abo < $dateto) { 
$datewf = $abo;
$date = new DateTime($datewf);
$date->modify('+1 SECONDS');
//$date->modify('NEXT DAY MIDNIGHT');
$date->modify('+ '.$conf->global->ADHERENT_WELCOME_MONTH.' MONTHS');     
} else {
$datewf = null;
$date = new DateTime();
$date->modify('+1 SECONDS');
//$date->modify('NEXT DAY MIDNIGHT');
$date->modify('- '.$conf->global->ADHERENT_WELCOME_MONTH.' MONTHS');       
}
print 'date_welcomefee: '.$date->format('Y-m-d H:i:s').'<br>';
$datewf = $date->getTimestamp();
print '<hr>';

if (!empty($abo) && $abo > $datefrom) { 
$date = new DateTime($abo);
$date->modify('+1 SECONDS');   
} else {
$date = new DateTime();   
}

if (!empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA)) {
//forced date
if ($daterenew > dol_now()) {
$date = new DateTime(); 
$date->modify('NOW');
} elseif ($daterenew <= dol_now() && $abo > $datefrom) {
$date = new DateTime(); 
$date->modify('NOW');
} elseif ($object->duration_unit == 'd') { 
$date->modify('MIDNIGHT');
} elseif ($object->duration_unit == 'w') { 
$date->modify('LAST MONDAY MIDNIGHT');
} elseif ($object->duration_unit == 'm') {
$date->modify('FIRST DAY OF THIS MONTH MIDNIGHT');
} else {
 $monthName = date("F", mktime(0, 0, 0, $conf->global->SOCIETE_SUBSCRIBE_MONTH_START, 10));
$date->modify('FIRST DAY OF '.$monthName.' MIDNIGHT');
if ($date->getTimestamp() > dol_now() && $daterenew > dol_now()) {
$date->modify('LAST YEAR');
}
}
}

// current dates
print 'begin: '.$date->format('Y-m-d H:i:s').'<br>';
$datebegin = $date->getTimestamp();
if (!empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA)) {
//forced date
if ($object->duration_unit == 'd') { 
$date->modify('NEXT DAY MIDNIGHT');
} elseif ($object->duration_unit == 'w') { 
$date->modify('NEXT MONDAY MIDNIGHT');
} elseif ($object->duration_unit == 'm') {
$date->modify('FIRST DAY OF NEXT MONTH MIDNIGHT');
} else {
$date->modify('FIRST DAY OF NEXT YEAR MIDNIGHT');
if ($date->format('Y-m-d H:i:s') > $dateto) {
$date->modify($dateto);
$date->modify('+1 SECONDS');
}
}
} else {
if ($object->duration_unit == 'd') { 
$date->modify('NEXT DAY MIDNIGHT');
} elseif ($object->duration_unit == 'w') { 
$date->modify('+1 WEEK MIDNIGHT');
} elseif ($object->duration_unit == 'm') {
$date->modify('NEXT MONTH MIDNIGHT');
} else {
$date->modify('NEXT YEAR MIDNIGHT');
}
}

$value = (!empty($object->duration_value)?$object->duration_value:0) - 1;
if ($value>0) {
if ($object->duration_unit == 'd') { 
$date->modify('+'.$value.' DAY');
} elseif ($object->duration_unit == 'w') { 
$date->modify('+'.$value.' WEEK');
} elseif ($object->duration_unit == 'm') {
$date->modify('+'.$value.' MONTH');
} else {
$date->modify('+'.$value.' YEAR');
}
}

if ($object->duration_unit == 'd') { 
$duration = 86400*(!empty($object->duration_value)?$object->duration_value:1);
} elseif ($object->duration_unit == 'w') { 
$duration = 604800*(!empty($object->duration_value)?$object->duration_value:1);
} elseif ($object->duration_unit == 'm') {
$duration = 2629872*(!empty($object->duration_value)?$object->duration_value:1);
} else {
$duration = 31558464*(!empty($object->duration_value)?$object->duration_value:1);
}

if ($daterenew <= dol_now() && (empty($object->duration_unit) || $object->duration_unit == 'y')) {
$date->modify($dateto);
} else {
$date->modify('-1 SECONDS');
}

print 'end: '.$date->format('Y-m-d H:i:s').'<br>';
$dateend = $date->getTimestamp();

if (!empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA)) { 
$rate = 100*(round((($dateend-$datebegin)/$duration)*$conf->global->ADHERENT_SUBSCRIPTION_PRORATA, 2)/$conf->global->ADHERENT_SUBSCRIPTION_PRORATA);
} else {
$rate = 100;
}
print 'prorata: '.$rate.'%<br>';
print 'daily_prorata: '.ceil(($dateend-$datebegin)/86400).'/'.round($duration/86400).'<br>';
if ($duration >= 604800) print 'weekly_prorata: '.ceil(($dateend-$datebegin)/604800).'/'.round($duration/604800).'<br>';
if ($duration >= 2629872) print 'monthly_prorata: '.ceil(($dateend-$datebegin)/2629872).'/'.round($duration/2629872).'<br>';
if ($duration >= (2629872*3)) print 'quarterly_prorata: '.ceil(($dateend-$datebegin)/(2629872*3)).'/'.round($duration/(2629872*3)).'<br>'; 
if ($duration >= (2629872*4)) print 'semester_prorata: '.ceil(($dateend-$datebegin)/(2629872*4)).'/'.round($duration/(2629872*4)).'<br>';
if ($duration >= (2629872*6)) print 'biannual_prorata: '.ceil(($dateend-$datebegin)/(2629872*6)).'/'.round($duration/(2629872*6)).'<br>';

if ( $datewf <= $datebegin) {
$price = $object->welcome + ($object->price * $rate / 100);
} else {
$price = ($object->price * $rate / 100);
}
if ($price < 0) $price = 0;
print 'price: '.price($price);
print ' '.$langs->trans("Currency".$conf->currency);
    
print '<hr>';
// next dates
$date = new DateTime($date->format('Y-m-d H:i:s'));
$date->modify('NEXT DAY MIDNIGHT');
print 'nextbegin: '.$date->format('Y-m-d H:i:s').'<br>';
if (!empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA)) {
//forced date
if ($object->duration_unit == 'd') { 
$date->modify('NEXT DAY MIDNIGHT');
} elseif ($object->duration_unit == 'w') { 
$date->modify('NEXT MONDAY MIDNIGHT');
} elseif ($object->duration_unit == 'm') {
$date->modify('FIRST DAY OF NEXT MONTH MIDNIGHT');
} else {
$date->modify('FIRST DAY OF NEXT YEAR MIDNIGHT');
}
} else {
if ($object->duration_unit == 'd') { 
$date->modify('NEXT DAY MIDNIGHT');
} elseif ($object->duration_unit == 'w') { 
$date->modify('+1 WEEK MIDNIGHT');
} elseif ($object->duration_unit == 'm') {
$date->modify('NEXT MONTH MIDNIGHT');
} else {
$date->modify('NEXT YEAR MIDNIGHT');
}
}

if ($value>0) {
if ($object->duration_unit == 'd') { 
$date->modify('+'.$value.' DAY');
} elseif ($object->duration_unit == 'w') { 
$date->modify('+'.$value.' WEEK');
} elseif ($object->duration_unit == 'm') {
$date->modify('+'.$value.' MONTH');
} else {
$date->modify('+'.$value.' YEAR');
}
}

$date->modify('-1 SECONDS');
 
print 'nextend: '.$date->format('Y-m-d H:i:s').'<br>';
print 'nextprice: '.price($object->price);
print ' '.$langs->trans("Currency".$conf->currency);
//print $date2->getTimestamp();
		
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
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="rowid" value="'.$rowid.'">';
		print '<input type="hidden" name="action" value="update">';

		dol_fiche_head($head, 'settings', $langs->trans("MemberType"), 0, 'group');

		print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->id.' '.dol_escape_htmltag($object->label).'</td></tr>';
  
    print '<tr><td>'.$langs->trans("GroupSubscription").'</td><td>';
		print $form->selectyesno("family",$object->family,1);
		print '</td></tr>';

    if (! empty($object->subscription))
    {  
    print '<tr ><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
		print '<input size="10" type="text" value="' . price($object->welcome) . '" name="welcome">';
    print ' '.$langs->trans("Currency".$conf->currency);    
		print '</td></tr>';
    
    print '<tr ><td>'.$langs->trans("SubscriptionPrice").'</td><td>';
		print '<input size="10" type="text" value="' . price($object->price) . '" name="price">';   
    print ' '.$langs->trans("Currency".$conf->currency);    
		print '</td></tr>';
    
if (! empty($conf->global->ADHERENT_FEDERAL_PART)){    
    print '<tr><td>'.$langs->trans("FederalPart");
		print $form->textwithpicto($s,$langs->trans("IncludeInSubscritionPrice"),1);
    print '</td><td>';
		print '<input size="10" type="text" value="' . price($object->federal) . '" name="federal">';   
    print ' '.$langs->trans("Currency".$conf->currency);    
		print '</td></tr>';
} 
    } else {
    print '<input size="10" type="text" value="0" name="welcome"><input size="10" type="text" value="0" name="price">';
    }

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
 
if ((float) DOL_VERSION < 11.0) {
    print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
    print '<input name="duration_value" size="5" value="'.$object->duration_value.'"> ';
    print $formproduct->selectMeasuringUnits("duration_unit", "time", $object->duration_unit, 0, 1);
    print '</td></tr>';
}
    
    print '<tr><td>'.$langs->trans("Commitment").'</td><td colspan="3">';
    print "<input type='text' name='commitment_value' value='".GETPOST('commitment_value', 'int')."' size='4' />&nbsp;".$form->selectarray('commitment_unit', array(''=>$langs->trans('None'), 'd'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), (GETPOST('commitment_unit') ?GETPOST('commitment_unit') : null));
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("Validation").'</td><td>';
		print $formother->selectAutoManual("automatic",$object->automatic,1);
		print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("Renewal").'</td><td>';
		print $formother->selectAutoManual("automatic_renew",$object->automatic_renew,1);
		print '</td></tr>';

		print '</table>';

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
