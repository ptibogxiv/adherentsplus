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
 *      \file       htdocs/adherentsex/type_package.php
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
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';    
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("adherentsplus@adherentsplus");

$rowid  = GETPOST('rowid','int');
$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');
$lineid  = GETPOST('lineid','int');
$productid  = GETPOST('productid','int');
$qty  = GETPOST('quantity','int');
$date_start      = dol_mktime(0, 0, 0, GETPOST('date_start_month', 'int'), GETPOST('date_start_day', 'int'), GETPOST('date_start_year', 'int'));
if (!empty(GETPOST('date_end_month', 'int')) && !empty(GETPOST('date_end_day', 'int')) && !empty(GETPOST('date_end_year', 'int'))) $date_end = dol_mktime(0, 0, 0, GETPOST('date_end_month', 'int'), GETPOST('date_end_day', 'int'), GETPOST('date_end_year', 'int'));

$search_ref	= GETPOST('search_ref','alpha');
$search_label		= GETPOST('search_label','alpha');
$search_qty		= GETPOST('search_qty','int');
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
//if (! $sortfield) {  $sortfield="d.lastname"; }

// Security check
$result=restrictedArea($user,'adherent',$rowid,'adherent_type');

$object = new AdherentTypePlus($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('adherent_type_package');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membertypecard','globalcard'));


/*
 *	Actions
 */

if ($action == 'create' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{ 
		$object->fk_type    = $rowid;
    $object->fk_product = $productid;
		$object->qty        = $qty;
    $object->date_start = $date_start;
    $object->date_end = $date_end;

		// Fill array 'array_options' with data from add form
		//$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		//if ($ret < 0) $error++;

		if ($object->fk_product && $object->qty)
		{
			$result = $object->create_package($user);
			if ($result > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$rowid);
				exit;
			}
			else
			{
				$mesg=$object->error;
				$action = 'create';
			}
		}
		else
		{
			$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			$action = 'create';
		}
	}
}

if ($action == 'update' && $user->rights->adherent->configurer)
{
	if (! $cancel)
	{
		$object->lineid     = $lineid;
		$object->qty        = $qty;
    $object->date_start = $date_start;
    $object->date_end = $date_end;

		// Fill array 'array_options' with data from updateform
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		$object->update_package($user);

		header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$rowid);
		exit;
	}
}

if ($action == 'delete' && $user->rights->adherent->configurer)
{
		$object->fetch_package($lineid);
    $object->delete_package($user);

		header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$rowid);
		exit;
}


llxHeader('',$langs->trans("MembersTypeSetup"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form=new Form($db);
$formother=new FormOther($db);
$formproduct = new FormProduct($db);

$object = new AdherentTypePlus($db);
$object->fetch($rowid);
$object->fetch_optionals($rowid,$extralabels);

$head = memberplus_type_prepare_head($object);

dol_fiche_head($head, 'package', $langs->trans("MemberType"), -1, 'group');

$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
    
print '</div>';

dol_fiche_end();

if ($rowid && $action == 'create' && $user->rights->adherent->creer)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforadd='create';
	print '<input type="hidden" name="action" value="'.$actionforadd.'">';
}

if ($rowid && $action == 'edit' && $user->rights->adherent->creer)
{
	print '<form action="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$actionforedit='update';
	print '<input type="hidden" name="action" value="'.$actionforedit.'">';
}
    
/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */
	if ($action != 'create' && $action != 'edit')
	{

		$now=dol_now();

		$sql = "SELECT t.rowid as id, t.fk_type as type, t.fk_product as product, t.qty, t.date_start, t.date_end";
		$sql .= ", p.ref as ref, p.label as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type_package as t";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = t.fk_product";
		$sql.= " WHERE t.entity IN (".getEntity('member_type').")";
		$sql.= " AND t.fk_type = ".$rowid;

		$resql = $db->query($sql);

		if ($resql)
		{
		    $num = $db->num_rows($resql);
		    $i = 0;

		    $titre=$langs->trans("ProductsList");

		    $param="&rowid=".$rowid;
		    if (! empty($status))			$param.="&status=".$status;
		    if (! empty($search_ref))	$param.="&search_ref=".$search_ref;
		    if (! empty($search_label))		$param.="&search_label=".$search_label;
		    if (! empty($search_email))		$param.="&search_email=".$search_email;
		    if (! empty($filter))			$param.="&filter=".$filter;

		    if ($sall)
		    {
		        print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
		    }

			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input class="flat" type="hidden" name="rowid" value="'.$rowid.'" size="12"></td>';

			print '<br>';

  $morehtmlright= dolGetButtonTitle($langs->trans('AddAPackage'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=create');

      print load_fiche_titre($langs->trans("ListOfProductsServices"), $morehtmlright, '');

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

			// Lignes des champs de filtre
			print '<tr class="liste_titre_filter">';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="7"></td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_label" value="'.dol_escape_htmltag($search_label).'" size="12"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td class="liste_titre" align="left">';
			print '<input class="flat" type="text" name="search_qty" value="'.dol_escape_htmltag($search_qty).'" size="5"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td align="right" colspan="2" class="liste_titre">';
			print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		    print '&nbsp; ';
		    print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
			print '</td>';

			print "</tr>";

			print '<tr class="liste_titre">';
		    print_liste_field_titre( $langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Label",$_SERVER["PHP_SELF"],"p.label",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Description",$_SERVER["PHP_SELF"],"",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("Qty",$_SERVER["PHP_SELF"],"t.qty",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("DateStart",$_SERVER["PHP_SELF"],"t.date_start",$param,"","",$sortfield,$sortorder);
		    print_liste_field_titre("DateEnd",$_SERVER["PHP_SELF"],"t.date_end",$param,"",'align="center"',$sortfield,$sortorder);
		    print_liste_field_titre("Action",$_SERVER["PHP_SELF"],"",$param,"",'width="60" align="center"',$sortfield,$sortorder);
		    print "</tr>\n";

		    while ($i < $num && $i < $conf->liste_limit)
		    {
		        $objp = $db->fetch_object($resql);

		        $datefin=$db->jdate($objp->datefin);

	$product_static = new Product($db);
		$product_static->id = $objp->fk_product;
		$product_static->ref = $objp->ref;
		        // Lastname
		        print '<tr class="oddeven">';
			print '<td class="tdoverflowmax200">';
			print $product_static->getNomUrl(1);
			print "</td>";

		        // Login
		        print '<td class="tdoverflowmax200">'.dol_trunc($objp->label, 80).'</td>';

		        // Moral/Physique
		        print "<td>".dol_trunc($objp->label, 80)."</td>";

		        // Qty
            if (!empty($objp->qty)) {
 		        print "<td>".$objp->qty."</td>";           
            } else {
		        print "<td>".$langs->trans("unlimited")."</td>";
            }

		        // Date begin
		        print '<td class="nowrap">';
		        print dol_print_date($objp->date_start,'day');
		        print "</td>";

		        // Date end
		        if ($objp->date_end)
		        {
			        print '<td align="center" class="nowrap">';
		          print dol_print_date($objp->date_end,'day');
		          print '</td>';
		        }
		        else
		        {
			        print '<td align="left" class="nowrap">';
			        print '&nbsp;';
		          print '</td>';
		        }

		        // Actions
		        print '<td align="center">';
				if ($user->rights->adherent->creer)
        {
				print '<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&lineid='.$objp->id.'&action=edit">';
				print img_picto($langs->trans("Modify"), 'edit');
				print '</a>';
        }
		   	print '&nbsp;';
				if ($user->rights->adherent->supprimer)
        {
		   	print '<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&lineid='.$objp->id.'&action=delete">';
		   	print img_picto($langs->trans("Delete"), 'delete');
		   	print '</a>';
		    }
				print "</td>";
				print "</td>";

		        print "</tr>\n";
		        $i++;
		    }

		    print "</table>\n";
            print '</div>';
            print '</form>';

			if ($num > $conf->liste_limit)
			{
			    print_barre_liste('',$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'');
			}
		}
		else
		{
		    dol_print_error($db);
		}

	}
  
// Create Card
if ($rowid && $action == 'create' && $user->rights->adherent->creer)
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
if ($rowid && $action == 'edit' && $user->rights->adherent->creer)
{

  $object->fetch_package($lineid);  

	print '<div class="nofichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PredefinedProductsAndServicesToSell").'</td>';
	  $product_static = new Product($db);
		$product_static->id = $object->fk_product;
		$product_static->ref = $object->ref;
    $product_static->label = $object->label;
    $product_static->type = $object->fk_product_type;
	print '<td>'.$product_static->getNomUrl(1)." - ".$object->label.'</td></tr>';
  print '<tr><td class="fieldrequired">'.$langs->trans("Qty").'</td>';
	print '<td><input class="minwidth50" type="text" name="quantity" value="'.($qty?$qty:$object->qty).'"></td></tr>';
    print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("DateStart").'</td><td>';
    $form->select_date($object->date_start, 'date_start_', '', '', '', "date_start", 1, 1);
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("DateEnd").'</td><td>';
    $form->select_date($object->date_end, 'date_end_', '', '', '', "date_end", 1, 1);
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
