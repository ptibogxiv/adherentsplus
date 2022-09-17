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

$langs->loadLangs(array('members', 'adherentsplus@adherentsplus'));

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
$federal=!empty(GETPOST("federal","alpha"))?GETPOST("federal","alpha"):0;
$prorata=GETPOST("prorata","alpha");
$prorata_date=GETPOST("prorata_date","int");
$price_level=GETPOST("price_level","int");
$commitment_value = GETPOST('commitment_value', 'int');
$commitment_unit = GETPOST('commitment_unit', 'alpha');
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
    $object->prorata       = trim($prorata?$prorata:null);
    $object->prorata_date  = (int) $prorata_date;
    $object->price_level       = trim($price_level?$price_level:'1');
    $object->commitment_value     	 = $commitment_value;
    $object->commitment_unit      	 = $commitment_unit;
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
		$object->subscription_calculator();

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
		print '</td></tr>';

    if (! empty($object->subscription))
    {        
    print '<tr><td>'.$langs->trans("SubscriptionWelcome").'</td><td>';
		print price($object->welcome);
    print ' '.$langs->trans("Currency".$conf->currency);
		print '</td></tr>';
    
if (! empty($conf->global->ADHERENT_FEDERAL_PART)){        
    print '<tr><td>'.$langs->trans("FederalPart");
		print $form->textwithpicto('', $langs->trans("IncludeInSubscritionPrice"),1);
    print '</td><td>';
	if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED)) {
		if (!$conf->entity == 1) {
			$adht = new AdherentTypePlus($db);
			$adht->fetch($object->federal);
			print $adht->getNomUrl(1);
		} else { print $langs->trans("no"); }
	} else {
		print price($object->federal).' '.$langs->trans("Currency".$conf->currency);
	}
		print '</td></tr>';
}    
    }

if (!empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA) && $conf->global->ADHERENT_SUBSCRIPTION_PRORATA == '2') {    
    print '<tr><td>'.$langs->trans("BeginningFixedDate").'</td><td>';
    print yn($object->prorata_date);
		print '</td></tr>';
    }
    
    print '<tr><td>'.$langs->trans("Prorata");
		print $form->textwithpicto('',$langs->trans("IncludeInSubscritionPrice"),1);
    print '</td><td>';
		print $langs->trans((!empty($object->prorata)?$object->prorata:'None'));
		print '</td></tr>';

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
    $dur=array("d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
    }
    elseif ($object->commitment_value > 0)
    {
    $dur=array("d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    }
    print (! empty($object->commitment_unit) && isset($dur[$object->commitment_unit]) ? $langs->trans($dur[$object->commitment_unit]) : '')."&nbsp;";
    }
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("AutomaticValidation").'</td><td>';
		print yn($object->automatic);
		print '</tr>';
    
    print '<tr><td>'.$langs->trans("AutomaticRenewal").'</td><td>';
		print yn($object->automatic_renew);
		print '</tr>';

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
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{    
print 'from '.dol_print_date($object->date_from, 'dayhour').' to '.dol_print_date($object->date_to, 'dayhour').'<br>'; 
print 'season: '.$object->season.'<br>';
print 'date_renew: '.dol_print_date($object->date_renew, 'dayhour').'<br>';
print 'date_welcomefee: '.dol_print_date($object->date_welcomefee, 'dayhour');
print '<hr>';

// current dates
print 'begin: '.dol_print_date($object->date_begin, 'dayhour').'<br>';
print 'end: '.dol_print_date($object->date_end, 'dayhour').'<br>';
print 'commitment: '.dol_print_date($object->date_commitment, 'dayhour').'<br>';

//print 'timestamp_prorata: '.$object->timestamp_prorata.'% <br>';
if (!empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA) && $conf->global->ADHERENT_SUBSCRIPTION_PRORATA == '2') { 
$prorata = $object->prorata_date;  
} else {
$prorata = $conf->global->ADHERENT_SUBSCRIPTION_PRORATA;  
} 

if (!empty($prorata)) {
$year = $object->date_to-$object->date_from;
$month = cal_days_in_month(CAL_GREGORIAN, dol_print_date($object->date_begin, '%m'), dol_print_date($object->date_begin, '%Y'))*86400;
} else {
if ($object->duration_unit == 'y') {
$year = $object->date_end-$object->date_begin;
} else {
$year = $object->date_to-$object->date_from;
}
$month = ceil((ceil(($object->date_end-$object->date_begin)/86400)*86400)/(!empty($object->duration_value)?$object->duration_value:1));
}
print 'daily_prorata: '.ceil(($object->date_end-$object->date_begin)/86400).'/'.ceil($object->duration_timestamp/86400).'<br>';
if ($object->duration_timestamp >= 604800) print 'weekly_prorata: '.ceil(($object->date_end-$object->date_begin)/604800).'/'.ceil($object->duration_timestamp/604800).'<br>';
if ($object->duration_timestamp >= ($month)) print 'monthly_prorata: '.ceil(($object->date_end-$object->date_begin)/($month)).'/'.ceil($object->duration_timestamp/($month)).'<br>';
if ($object->duration_timestamp >= ($year/4)) print 'quarterly_prorata: '.ceil(($object->date_end-$object->date_begin)/($year/4)).'/'.ceil($object->duration_timestamp/($year/4)).'<br>'; 
if ($object->duration_timestamp >= ($year/3)) print 'semester_prorata: '.ceil(($object->date_end-$object->date_begin)/($year/3)).'/'.ceil($object->duration_timestamp/($year/3)).'<br>';
if ($object->duration_timestamp >= ($year/2)) print 'biannual_prorata: '.ceil(($object->date_end-$object->date_begin)/($year/2)).'/'.ceil($object->duration_timestamp/($year/2)).'<br>';
if ($object->duration_timestamp >= $year) print 'annual_prorata: '.ceil(($object->date_end-$object->date_begin)/$year).'/'.ceil($object->duration_timestamp/$year).'<br>';

print 'price: '.price($object->price_prorata);
print ' '.$langs->trans("Currency".$conf->currency);
    
print '<hr>';
// next dates
print 'nextbegin: '.dol_print_date($object->date_nextbegin, 'dayhour').'<br>'; 
print 'nextend: '.dol_print_date($object->date_nextend, 'dayhour').'<br>';
print 'nextprice: '.price($object->nextprice);
print ' '.$langs->trans("Currency".$conf->currency);
}
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
    
if (! empty($conf->global->ADHERENT_FEDERAL_PART)){    
    print '<tr><td>'.$langs->trans("FederalPart");
	print $form->textwithpicto('', $langs->trans("IncludeInSubscritionPrice"),1);
    print '</td><td>';
	if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_MEMBER_SHARING_ENABLED)) {
		if (!$conf->entity == 1) {
			print $form->selectarray("federal", $object->liste_array(1), (GETPOSTISSET("federal") ? GETPOST("federal", 'int') : $object->federal), 0, 0, 0, '', 0, 0, 0, '', '', 1);
		} else { print $langs->trans("no").' <input size="10" type="hidden" value="0" name="federal">'; }
	} else {
		print '<input size="10" type="text" value="' . price($object->federal) . '" name="federal"> '.$langs->trans("Currency".$conf->currency);  
	}
	print '</td></tr>';
} 
    } else {
    print '<input size="10" type="text" value="0" name="welcome">';
    }
    
if (!empty($conf->global->ADHERENT_SUBSCRIPTION_PRORATA) && $conf->global->ADHERENT_SUBSCRIPTION_PRORATA == '2') {    
    print '<tr><td>'.$langs->trans("BeginningFixedDate").'</td><td>';
    print $form->selectyesno("prorata_date", $object->prorata_date, 1);
		print '</tr>';
    }
    
    print '<tr><td>'.$langs->trans("Prorata");
		print $form->textwithpicto('',$langs->trans("IncludeInSubscritionPrice"),1);
    print '</td><td>';
    
if ($object->duration_unit == 'd') { 
$duration = 86400*(!empty($object->duration_value)?$object->duration_value:1);
} elseif ($object->duration_unit == 'w') { 
$duration = 604800*(!empty($object->duration_value)?$object->duration_value:1);
} elseif ($object->duration_unit == 'm') {
$duration = 2629872*(!empty($object->duration_value)?$object->duration_value:1);
} else {
$duration = 31558464*(!empty($object->duration_value)?$object->duration_value:1);
}
    
    $rate = null;
    $rate[''] = $langs->trans("None");
if ($duration >= 86400) $rate['daily'] = $langs->trans("daily");    
if ($duration >= 604800) $rate['weekly'] = $langs->trans("weekly");
if ($duration >= 2629872) $rate['monthly'] = $langs->trans("monthly");
if ($duration >= (2629872*3)) $rate['quaterly'] = $langs->trans("quaterly");
if ($duration >= (2629872*4)) $rate['semester'] = $langs->trans("semester");
if ($duration >= (2629872*6)) $rate['biannual'] = $langs->trans("biannual");
    print $form->selectarray('prorata', $rate, (!empty($object->prorata)?$object->prorata:null), null);
		print '</tr>';

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
    print "<input type='text' name='commitment_value' value='".$object->commitment_value."' size='4' />&nbsp;".$form->selectarray('commitment_unit', array('d'=>$langs->trans('Day'), 'w'=>$langs->trans('Week'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), $object->commitment_unit);
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("AutomaticValidation").'</td><td>';
		print $formother->selectAutoManual("automatic",$object->automatic,1);
		print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("AutomaticRenewal").'</td><td>';
		print $formother->selectAutoManual("automatic_renew",$object->automatic_renew,1);
		print '</td></tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

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
