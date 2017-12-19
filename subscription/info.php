<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/adherentsex/subscription/info.php
 *      \ingroup    member
 *      \brief      Page with information of subscriptions of a member
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/lib/member.lib.php');
dol_include_once('/adherentsplus/class/subscription.class.php');

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

if (!$user->rights->adherent->lire)
	accessforbidden();

$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];



/*
 * View
 */

llxHeader();

$form = new Form($db);

$object = new SubscriptionPlus($db);
$result = $object->fetch($rowid);

$head = subscription_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("Subscription"), -1, 'payment');

$linkback = '<a href="'.dol_include_once('/adherentsplus/subscription/list.php').'">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback, 1);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';

print '<br>';

$object->info($rowid);

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';


dol_fiche_end();

llxFooter();
$db->close();
