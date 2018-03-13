<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2012-2016 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2015-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 *       \file       htdocs/adherents/card.php
 *       \ingroup    member
 *       \brief      Page of member
 */

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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/adherentsplus/class/adherent.class.php');
dol_include_once('/adherentsplus/class/adherent_type.class.php');
dol_include_once('/adherentsplus/class/subscription.class.php');
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");
$langs->load('other');

$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');
$backtopage=GETPOST('backtopage','alpha');
$confirm=GETPOST('confirm','alpha');
$rowid=GETPOST('rowid','int');
$id=GETPOST('id')?GETPOST('id','int'):$rowid;
$typeid=GETPOST('typeid','int');
$userid=GETPOST('userid','int');
$socid=GETPOST('socid','int');
$link=GETPOST('link','int');

if (! empty($conf->mailmanspip->enabled))
{
	include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';

	$langs->load('mailmanspip');

	$mailmanspip = new MailmanSpip($db);
}

$object = new Adherentplus($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('adherent', 'membercard', $canvas);
}

// Security check
$result=restrictedArea($user, 'adherent', $id, '', '', 'fk_soc', 'rowid', $objcanvas);

if ($id > 0)
{
	// Load member
	$result = $object->fetch($id);

	// Define variables to know what current user can do on users
	$canadduser=($user->admin || $user->rights->user->user->creer);
	// Define variables to know what current user can do on properties of user linked to edited member
	if ($object->user_id)
	{
		// $ User is the user who edits, $ object->user_id is the id of the related user in the edited member
		$caneditfielduser=((($user->id == $object->user_id) && $user->rights->user->self->creer)
				|| (($user->id != $object->user_id) && $user->rights->user->user->creer));
		$caneditpassworduser=((($user->id == $object->user_id) && $user->rights->user->self->password)
				|| (($user->id != $object->user_id) && $user->rights->user->user->password));
	}
}

// Define variables to determine what the current user can do on the members
$canaddmember=$user->rights->adherent->creer;
// Define variables to determine what the current user can do on the properties of a member
if ($id)
{
	$caneditfieldmember=$user->rights->adherent->creer;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('membercard','globalcard'));



/*
 * 	Actions
 */

if ($cancel) $action='';

$parameters=array('id'=>$id, 'rowid'=>$id, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'setuserid' && ($user->rights->user->self->creer || $user->rights->user->user->creer))
	{
		$error=0;
		if (empty($user->rights->user->user->creer))	// If can edit only itself user, we can link to itself only
		{
			if ($userid != $user->id && $userid != $object->user_id)
			{
				$error++;
				setEventMessages($langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly"), null, 'errors');
			}
		}

		if (! $error)
		{
			if ($userid != $object->user_id)	// If link differs from currently in database
			{
				$result=$object->setUserId($userid);
				if ($result < 0) dol_print_error($object->db,$object->error);
				$action='';
			}
		}
	}

	if ($action == 'setsocid')
	{
		$error=0;
		if (! $error)
		{
			if ($socid != $object->fk_soc)	// If link differs from currently in database
			{
				$sql ="SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
				$sql.=" WHERE fk_soc = '".$socid."'";
				$sql.=" AND entity = ".$conf->entity;
				$resql = $db->query($sql);
				if ($resql)
				{
					$obj = $db->fetch_object($resql);
					if ($obj && $obj->rowid > 0)
					{
						$othermember=new AdherentPlus($db);
						$othermember->fetch($obj->rowid);
						$thirdparty=new Societe($db);
						$thirdparty->fetch($socid);
						$error++;
						setEventMessages($langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty",$othermember->getFullName($langs),$othermember->login,$thirdparty->name), null, 'errors');
					}
				}

				if (! $error)
				{
					$result=$object->setThirdPartyId($socid);
					if ($result < 0) dol_print_error($object->db,$object->error);
					$action='';
				}
			}
		}
	}

	// Create user from a member
	if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->rights->user->user->creer)
	{
		if ($result > 0)
		{
			// Creation user
			$nuser = new User($db);
			$result=$nuser->create_from_member($object,GETPOST('login'));

			if ($result < 0)
			{
				$langs->load("errors");
				setEventMessages($langs->trans($nuser->error), null, 'errors');
			}
		}
		else
		{
			setEventMessages($object->errors, $object->error, 'errors');
		}
	}

	// Create third party from a member
	if ($action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->rights->societe->creer)
	{
		if ($result > 0)
		{
			// User creation
			$company = new Societe($db);
			$result=$company->create_from_member($object,GETPOST('companyname'));

			if ($result < 0)
			{
				$langs->load("errors");
				setEventMessages($langs->trans($company->error), null, 'errors');
				setEventMessages($company->error, $company->errors, 'errors');
			}
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_sendinfo' && $confirm == 'yes')
	{
		if ($object->email)
		{
			$from=$conf->email_from;
			if (! empty($conf->global->ADHERENT_MAIL_FROM)) $from=$conf->global->ADHERENT_MAIL_FROM;

			$result=$object->send_an_email($langs->transnoentitiesnoconv("ThisIsContentOfYourCard")."\n\n%INFOS%\n\n",$langs->transnoentitiesnoconv("CardContent"));

			$langs->load("mails");
			setEventMessages($langs->trans("MailSuccessfulySent", $from, $object->email), null, 'mesgs');
		}
	}

	if ($action == 'update' && ! $cancel && $user->rights->adherent->creer)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$birthdate='';
		if (isset($_POST["birthday"]) && $_POST["birthday"]
				&& isset($_POST["birthmonth"]) && $_POST["birthmonth"]
				&& isset($_POST["birthyear"]) && $_POST["birthyear"])
		{
			$birthdate=dol_mktime(12, 0, 0, $_POST["birthmonth"], $_POST["birthday"], $_POST["birthyear"]);
		}
		$lastname=$_POST["lastname"];
		$firstname=$_POST["firstname"];
		$societe=$_POST["societe"];
		$morphy=$_POST["morphy"];
		$login=$_POST["login"];
		if ($morphy != 'mor' && empty($lastname)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname")), null, 'errors');
		}
		if ($morphy != 'mor' && (!isset($firstname) || $firstname=='')) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Firstname")), null, 'errors');
		}
		if ($morphy == 'mor' && empty($societe)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Company")), null, 'errors');
		}
		// Test si le login existe deja
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			if (empty($login)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Login")), null, 'errors');
			}
		}
		// Create new object
		if ($result > 0 && ! $error)
		{
			$object->oldcopy = clone $object;

			// Change values
			$object->civility_id = trim($_POST["civility_id"]);
			$object->firstname   = trim($_POST["firstname"]);
			$object->lastname    = trim($_POST["lastname"]);
			$object->login       = trim($_POST["login"]);
			$object->pass        = trim($_POST["pass"]);

			$object->societe     = trim($_POST["societe"]);
			$object->company     = trim($_POST["societe"]);

			$object->address     = trim($_POST["address"]);
			$object->zip         = trim($_POST["zipcode"]);
			$object->town        = trim($_POST["town"]);
			$object->state_id    = $_POST["state_id"];
			$object->country_id  = $_POST["country_id"];

			$object->phone       = trim($_POST["phone"]);
			$object->phone_perso = trim($_POST["phone_perso"]);
			$object->phone_mobile= trim($_POST["phone_mobile"]);
			$object->email       = trim($_POST["member_email"]);
			$object->skype       = trim($_POST["skype"]);
			$object->birth       = $birthdate;

			$object->typeid      = $_POST["typeid"];
			//$object->note        = trim($_POST["comment"]);
			$object->morphy      = $_POST["morphy"];

			if (GETPOST('deletephoto')) $object->photo='';
			elseif (! empty($_FILES['photo']['name'])) $object->photo  = dol_sanitizeFileName($_FILES['photo']['name']);

			// Get status and public property
			$object->statut      = $_POST["statut"];
			$object->public      = $_POST["public"];

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;

			// Check if we need to also synchronize user information
			$nosyncuser=0;
			if ($object->user_id)	// If linked to a user
			{
				if ($user->id != $object->user_id && empty($user->rights->user->user->creer)) $nosyncuser=1;		// Disable synchronizing
			}

			// Check if we need to also synchronize password information
			$nosyncuserpass=0;
			if ($object->user_id)	// If linked to a user
			{
				if ($user->id != $object->user_id && empty($user->rights->user->user->password)) $nosyncuserpass=1;	// Disable synchronizing
			}

			$result=$object->update($user,0,$nosyncuser,$nosyncuserpass);

			if ($result >= 0 && ! count($object->errors))
			{
				$categories = GETPOST('memcats', 'array');
				$object->setCategories($categories);

				// Logo/Photo save
				$dir= $conf->adherent->dir_output . '/' . get_exdir(0,0,0,1,$object,'member').'/photos';
				$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
				if ($file_OK)
				{
					if (GETPOST('deletephoto'))
					{
						require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
						$fileimg=$conf->adherent->dir_output.'/'.get_exdir(0,0,0,1,$object,'member').'/photos/'.$object->photo;
						$dirthumbs=$conf->adherent->dir_output.'/'.get_exdir(0,0,0,1,$object,'member').'/photos/thumbs';
						dol_delete_file($fileimg);
						dol_delete_dir_recursive($dirthumbs);
					}

					if (image_format_supported($_FILES['photo']['name']) > 0)
					{
						dol_mkdir($dir);

						if (@is_dir($dir))
						{
							$newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
							if (! dol_move_uploaded_file($_FILES['photo']['tmp_name'],$newfile,1,0,$_FILES['photo']['error']) > 0)
							{
								setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
							}
							else
							{
							    // Create thumbs
							    $object->addThumbs($newfile);
							}
						}
					}
					else
					{
						setEventMessages("ErrorBadImageFormat", null, 'errors');
					}
				}
				else
				{
					switch($_FILES['photo']['error'])
					{
						case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
						case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
							$errors[] = "ErrorFileSizeTooLarge";
							break;
						case 3: //uploaded file was only partially uploaded
							$errors[] = "ErrorFilePartiallyUploaded";
							break;
					}
				}

	            $rowid=$object->id;
	            $id=$object->id;
				$action='';

				if (! empty($backtopage))
				{
					header("Location: ".$backtopage);
					exit;
				}
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$action='';
			}
		}
		else
		{
			$action='edit';
		}
	}

	if ($action == 'add' && $user->rights->adherent->creer)
	{
		if ($canvas) $object->canvas=$canvas;
		$birthdate='';
		if (isset($_POST["birthday"]) && $_POST["birthday"]
				&& isset($_POST["birthmonth"]) && $_POST["birthmonth"]
				&& isset($_POST["birthyear"]) && $_POST["birthyear"])
		{
			$birthdate=dol_mktime(12, 0, 0, $_POST["birthmonth"], $_POST["birthday"], $_POST["birthyear"]);
		}
		$datesubscription='';
		if (isset($_POST["reday"]) && isset($_POST["remonth"]) && isset($_POST["reyear"]))
		{
			$datesubscription=dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
		}

		$typeid=$_POST["typeid"];
		$civility_id=$_POST["civility_id"];
		$lastname=$_POST["lastname"];
		$firstname=$_POST["firstname"];
		$societe=$_POST["societe"];
		$address=$_POST["address"];
		$zip=$_POST["zipcode"];
		$town=$_POST["town"];
		$state_id=$_POST["state_id"];
		$country_id=$_POST["country_id"];

		$phone=$_POST["phone"];
		$phone_perso=$_POST["phone_perso"];
		$phone_mobile=$_POST["phone_mobile"];
		$skype=$_POST["member_skype"];
		$email=$_POST["member_email"];
		$login=$_POST["member_login"];
		$pass=$_POST["password"];
		$photo=$_POST["photo"];
		//$comment=$_POST["comment"];
		$morphy=$_POST["morphy"];
		$subscription=$_POST["subscription"];
		$public=$_POST["public"];

		$userid=$_POST["userid"];
		$socid=$_POST["socid"];

		$object->civility_id = $civility_id;
		$object->firstname   = $firstname;
		$object->lastname    = $lastname;
		$object->societe     = $societe;
		$object->address     = $address;
		$object->zip         = $zip;
		$object->town        = $town;
		$object->state_id    = $state_id;
		$object->country_id  = $country_id;
		$object->phone       = $phone;
		$object->phone_perso = $phone_perso;
		$object->phone_mobile= $phone_mobile;
		$object->skype       = $skype;
		$object->email       = $email;
		$object->login       = $login;
		$object->pass        = $pass;
		$object->birth       = $birthdate;
		$object->photo       = $photo;
		$object->typeid      = $typeid;
		//$object->note        = $comment;
		$object->morphy      = $morphy;
		$object->user_id     = $userid;
		$object->fk_soc      = $socid;
		$object->public      = $public;

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		// Check parameters
		if (empty($morphy) || $morphy == "-1") {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Nature")), null, 'errors');
		}
		// Tests if the login already exists
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			if (empty($login)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Login")), null, 'errors');
			}
			else {
				$sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$db->escape($login)."'";
				$result = $db->query($sql);
				if ($result) {
					$num = $db->num_rows($result);
				}
				if ($num) {
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorLoginAlreadyExists",$login), null, 'errors');
				}
			}
			if (empty($pass)) {
				$error++;
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Password")), null, 'errors');
			}
		}
		if ($morphy == 'mor' && empty($societe)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Company")), null, 'errors');
		}
		if ($morphy != 'mor' && empty($lastname)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname")), null, 'errors');
		}
		if ($morphy != 'mor' && (!isset($firstname) || $firstname=='')) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Firstname")), null, 'errors');
		}
		if (! ($typeid > 0)) {	// Keep () before !
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
		}
		if ($conf->global->ADHERENT_MAIL_REQUIRED && ! isValidEMail($email)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadEMail",$email), null, 'errors');
		}
		$public=0;
		if (isset($public)) $public=1;

		if (! $error)
		{
			$db->begin();

			// Email about right and login does not exist
			$result=$object->create($user);
			if ($result > 0)
			{
				// Foundation categories
				$memcats = GETPOST('memcats', 'array');
				$object->setCategories($memcats);

				$db->commit();
				$rowid=$object->id;
				$id=$object->id;
				$action='';
			}
			else
			{
				$db->rollback();

				if ($object->error) {
					setEventMessages($object->error, $object->errors, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}

				$action = 'create';
			}
		}
		else {
			$action = 'create';
		}
	}

	if ($user->rights->adherent->supprimer && $action == 'confirm_delete' && $confirm == 'yes')
	{
		$result=$object->delete($id, $user);
		if ($result > 0)
		{
			if (! empty($backtopage))
			{
				header("Location: ".$backtopage);
				exit;
			}
			else
			{
				header("Location: list.php");
				exit;
			}
		}
		else
		{
			$errmesg=$object->error;
		}
	}

	if ($user->rights->adherent->creer && $action == 'confirm_valid' && $confirm == 'yes')
	{
		$error=0;

		$db->begin();

		$adht = new AdherentTypePlus($db);
		$adht->fetch($object->typeid);

		$result=$object->validate($user);

		if ($result >= 0 && ! count($object->errors))
		{
			// Send confirmation email (according to parameters of member type. Otherwise generic)
			if ($object->email && GETPOST("send_mail"))
			{
				$result=$object->send_an_email($adht->getMailOnValid(),$conf->global->ADHERENT_MAIL_VALID_SUBJECT,array(),array(),array(),"","",0,2);
				if ($result < 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
		else
		{
			$error++;
			if ($object->error) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if (! $error)
		{
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
		$action='';
	}

	if ($user->rights->adherent->supprimer && $action == 'confirm_resign')
	{
		$error = 0;

		if ($confirm == 'yes')
		{
			$adht = new AdherentTypePlus($db);
			$adht->fetch($object->typeid);

			$result=$object->resiliate($user);

			if ($result >= 0 && ! count($object->errors))
			{
				if ($object->email && GETPOST("send_mail"))
				{
					$result=$object->send_an_email($adht->getMailOnResiliate(),$conf->global->ADHERENT_MAIL_RESIL_SUBJECT,array(),array(),array(),"","",0,-1);
				}
				if ($result < 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
			else
			{
				$error++;

				if ($object->error) {
					setEventMessages($object->error, $object->errors, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
				$action='';
			}
		}
		if (! empty($backtopage) && ! $error)
		{
			header("Location: ".$backtopage);
			exit;
		}
	}

	// SPIP Management
	if ($user->rights->adherent->supprimer && $action == 'confirm_del_spip' && $confirm == 'yes')
	{
		if (! count($object->errors))
		{
			if (!$mailmanspip->del_to_spip($object))
			{
				setEventMessages($langs->trans('DeleteIntoSpipError').': '.$mailmanspip->error, null, 'errors');
			}
		}
	}

	if ($user->rights->adherent->creer && $action == 'confirm_add_spip' && $confirm == 'yes')
	{
		if (! count($object->errors))
		{
			if (!$mailmanspip->add_to_spip($object))
			{
				setEventMessages($langs->trans('AddIntoSpipError').': '.$mailmanspip->error, null, 'errors');
			}
		}
	}

  if ($action == 'confirm_deleteparent' && $confirm == 'yes' && $user->rights->adherent->creer)
	{
 		$result=$object->delete_parent($link);
		if ($result > 0)
		{

				header("Location: ".$dolibarr_main_url_root.dol_buildpath('/adherentsplus/card.php?rowid='.$rowid, 1));
				exit;
		}
		else
		{
			$errmesg=$object->error;
		}
  }
  
    if ($action == 'confirm_addparent' && $confirm == 'yes' && $user->rights->adherent->creer)
	{
 		$result=$object->add_parent($link);
		if ($result > 0)
		{

				header("Location: ".$dolibarr_main_url_root.dol_buildpath('/adherentsplus/card.php?rowid='.$rowid, 1));
				exit;
		}
		else
		{
			$errmesg=$object->error;
		}
  }
	// Actions to build doc
	$upload_dir = $conf->adherent->dir_output;
	$permissioncreate=$user->rights->adherent->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formcompany = new FormCompany($db);

$title=$langs->trans("Member") . " - " . $langs->trans("Card");
$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
llxHeader('',$title,$help_url);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id)
	{
		$object = new AdherentPlus($db);
		$result=$object->fetch($id);
		if ($result <= 0) dol_print_error('',$object->error);
	}
   	$objcanvas->assign_values($action, $object->id, $object->ref);	// Set value for templates
    $objcanvas->display_canvas($action);							// Show template
}
else
{
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	if ($action == 'create')
	{
		/* ************************************************************************** */
		/*                                                                            */
		/* Creation mode                                                              */
		/*                                                                            */
		/* ************************************************************************** */
		$object->canvas=$canvas;
		$object->state_id = GETPOST('state_id', 'int');

		// We set country_id, country_code and country for the selected country
		$object->country_id=GETPOST('country_id','int')?GETPOST('country_id','int'):$mysoc->country_id;
		if ($object->country_id)
		{
			$tmparray=getCountry($object->country_id,'all');
			$object->country_code=$tmparray['code'];
			$object->country=$tmparray['label'];
		}

		$adht = new AdherentTypePlus($db);

		print load_fiche_titre($langs->trans("NewMember"));

		if ($conf->use_javascript_ajax)
		{
			print "\n".'<SCRIPT type="text/javascript" language="javascript">';
			print 'jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="create";
							document.formsoc.submit();
						});
						function initfieldrequired()
						{
							jQuery("#tdcompany").removeClass("fieldrequired");
							jQuery("#tdlastname").removeClass("fieldrequired");
							jQuery("#tdfirstname").removeClass("fieldrequired");
							if (jQuery("#morphy").val() == \'mor\')
							{
								jQuery("#tdcompany").addClass("fieldrequired");
							}
							if (jQuery("#morphy").val() == \'phy\')
							{
								jQuery("#tdlastname").addClass("fieldrequired");
								jQuery("#tdfirstname").addClass("fieldrequired");
							}
						}
						jQuery("#morphy").change(function() {
							initfieldrequired();
						});
						initfieldrequired();
					})';
			print '</SCRIPT>'."\n";
		}

		print '<FORM name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		print '<INPUT type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<INPUT type="hidden" name="action" value="add">';

        dol_fiche_head('');

		print '<TABLE class="border" width="100%">';
		print '<TBODY>';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<TR><TD><SPAN class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</SPAN></TD><TD><INPUT type="text" name="member_login" class="maxwidth200" value="'.(isset($_POST["member_login"])?GETPOST("member_login", 'alpha', 2):$object->login).'"></TD></TR>';
		}

		// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$generated_password=getRandomPassword(false);
			print '<TR><TD><SPAN class="fieldrequired">'.$langs->trans("Password").'</SPAN></TD><TD>';
			print '<INPUT size="30" maxsize="32" type="text" name="password" value="'.$generated_password.'">';
			print '</TD></TR>';
		}

		// Type
		print '<TR><TD class="fieldrequired">'.$langs->trans("MemberType").'</TD><TD>';
		$listetype=$adht->liste_array();
		if (count($listetype))
		{
			print $form->selectarray("typeid", $listetype, GETPOST('typeid','int')?GETPOST('typeid','int'):$typeid, count($listetype)>1?1:0);
		} else {
			print '<FONT class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</FONT>';
		}
		print "</TD>\n";

		// Morphy
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<TR><TD class="fieldrequired">'.$langs->trans("Nature")."</TD><TD>\n";
		print $form->selectarray("morphy", $morphys, GETPOST('morphy','alpha')?GETPOST('morphy','alpha'):$object->morphy, 1);
		print "</TD>\n";

		// Company
		print '<TR><TD id="tdcompany">'.$langs->trans("Company").'</TD><TD><INPUT type="text" name="societe" size="40" value="'.(GETPOST('societe','alpha')?GETPOST('societe','alpha'):$object->societe).'"></TD></TR>';

		// Civility
		print '<TR><TD>'.$langs->trans("UserTitle").'</TD><TD>';
		print $formcompany->select_civility(GETPOST('civility_id','int')?GETPOST('civility_id','int'):$object->civility_id,'civility_id').'</TD>';
		print '</TR>';

		// Lastname
		print '<TR><TD id="tdlastname">'.$langs->trans("Lastname").'</TD><TD><INPUT type="text" name="lastname" value="'.(GETPOST('lastname','alpha')?GETPOST('lastname','alpha'):$object->lastname).'" size="40"></TD>';
		print '</TR>';

		// Firstname
		print '<TR><TD id="tdfirstname">'.$langs->trans("Firstname").'</TD><TD><INPUT type="text" name="firstname" size="40" value="'.(GETPOST('firstname','alpha')?GETPOST('firstname','alpha'):$object->firstname).'"></TD>';
		print '</TR>';

		// EMail
		print '<TR><TD>'.($conf->global->ADHERENT_MAIL_REQUIRED?'<SPAN class="fieldrequired">':'').$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED?'</SPAN>':'').'</TD><TD><INPUT type="text" name="member_email" size="40" value="'.(GETPOST('member_email','alpha')?GETPOST('member_email','alpha'):$object->email).'"></TD></TR>';

		// Address
		print '<TR><TD class="tdtop">'.$langs->trans("Address").'</TD><TD>';
		print '<TEXTAREA name="address" wrap="soft" class="quatrevingtpercent" rows="2">'.(GETPOST('address','alpha')?GETPOST('address','alpha'):$object->address).'</TEXTAREA>';
		print '</TD></TR>';

		// Zip / Town
		print '<TR><TD>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</TD><TD>';
		print $formcompany->select_ziptown((GETPOST('zipcode','alpha')?GETPOST('zipcode','alpha'):$object->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
		print ' ';
		print $formcompany->select_ziptown((GETPOST('town','alpha')?GETPOST('town','alpha'):$object->town),'town',array('zipcode','selectcountry_id','state_id'));
		print '</TD></TR>';

		// Country
		$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;
		print '<TR><TD width="25%">'.$langs->trans('Country').'</TD><TD>';
		print $form->select_country(GETPOST('country_id','alpha')?GETPOST('country_id','alpha'):$object->country_id,'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</TD></TR>';

		// State
		if (empty($conf->global->MEMBER_DISABLE_STATE))
		{
			print '<TR><TD>'.$langs->trans('State').'</TD><TD>';
			if ($object->country_id)
			{
				print $formcompany->select_state(GETPOST('state_id','int')?GETPOST('state_id','int'):$object->state_id,$object->country_code);
			}
			else
			{
				print $countrynotdefined;
			}
			print '</TD></TR>';
		}

		// Pro phone
		print '<TR><TD>'.$langs->trans("PhonePro").'</TD><TD><INPUT type="text" name="phone" size="20" value="'.(GETPOST('phone','alpha')?GETPOST('phone','alpha'):$object->phone).'"></TD></TR>';

		// Personal phone
		print '<TR><TD>'.$langs->trans("PhonePerso").'</TD><TD><INPUT type="text" name="phone_perso" size="20" value="'.(GETPOST('phone_perso','alpha')?GETPOST('phone_perso','alpha'):$object->phone_perso).'"></TD></TR>';

		// Mobile phone
		print '<TR><TD>'.$langs->trans("PhoneMobile").'</TD><TD><INPUT type="text" name="phone_mobile" size="20" value="'.(GETPOST('phone_mobile','alpha')?GETPOST('phone_mobile','alpha'):$object->phone_mobile).'"></TD></TR>';

	    // Skype
	    if (! empty($conf->skype->enabled))
	    {
			print '<TR><TD>'.$langs->trans("Skype").'</TD><TD><INPUT type="text" name="member_skype" size="40" value="'.(GETPOST('member_skype','alpha')?GETPOST('member_skype','alpha'):$object->skype).'"></TD></TR>';
	    }

		// Birthday
		print "<TR><TD>".$langs->trans("Birthday")."</TD><TD>\n";
		$form->select_date(($object->birth ? $object->birth : -1),'birth','','',1,'formsoc');
		print "</TD></TR>\n";

		// Public profil
		print "<TR><TD>".$langs->trans("Public")."</TD><TD>\n";
		print $form->selectyesno("public",$object->public,1);
		print "</TD></TR>\n";

		// Categories
		if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
		{
			print '<TR><TD>' . fieldLabel('Categories', 'memcars') . '</TD><TD>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, 'parent', null, null, 1);
			print $form->multiselectarray('memcats', $cate_arbo, GETPOST('memcats', 'array'), null, null, null, null, '100%');
			print "</TD></TR>";
		}

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields,'edit');
		}

        print '<TBODY>';
		print "</TABLE>\n";

        dol_fiche_end();

	    print '<DIV class="center">';
	    print '<INPUT type="submit" name="button" class="button" value="'.$langs->trans("AddMember").'">';
	    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	    print '<INPUT type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'" onclick="history.go(-1)" />';
	    print '</DIV>';

		print "</FORM>\n";
	}

	if ($action == 'edit')
	{
		/********************************************
		*
		* Edition mode
		*
		********************************************/

		$res=$object->fetch($id);
		if ($res < 0) {
			dol_print_error($db,$object->error); exit;
		}
		$res=$object->fetch_optionals($object->id,$extralabels);
		if ($res < 0) {
			dol_print_error($db); exit;
		}

		$adht = new AdherentTypePlus($db);
		$adht->fetch($object->typeid);

		// We set country_id, and country_code, country of the chosen country
		$country=GETPOST('country','int');
		if (!empty($country) || $object->country_id)
		{
			$sql = "SELECT rowid, code, label from ".MAIN_DB_PREFIX."c_country where rowid = ".(!empty($country)?$country:$object->country_id);
			$resql=$db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
			}
			else
			{
				dol_print_error($db);
			}
			$object->country_id=$obj->rowid;
			$object->country_code=$obj->code;
			$object->country=$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->label;
		}

		$head = member_prepare_head($object);


		if ($conf->use_javascript_ajax)
		{
			print "\n".'<SCRIPT type="text/javascript" language="javascript">';
			print 'jQuery(document).ready(function () {
				jQuery("#selectcountry_id").change(function() {
					document.formsoc.action.value="edit";
					document.formsoc.submit();
				});
				function initfieldrequired()
				{
					jQuery("#tdcompany").removeClass("fieldrequired");
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
					if (jQuery("#morphy").val() == \'mor\')
					{
						jQuery("#tdcompany").addClass("fieldrequired");
					}
					if (jQuery("#morphy").val() == \'phy\')
					{
						jQuery("#tdlastname").addClass("fieldrequired");
						jQuery("#tdfirstname").addClass("fieldrequired");
					}
				}
				jQuery("#morphy").change(function() {
					initfieldrequired();
				});
				initfieldrequired();
			})';
			print '</SCRIPT>'."\n";
		}

		print '<FORM name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		print '<INPUT type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
		print '<INPUT type="hidden" name="action" value="update" />';
		print '<INPUT type="hidden" name="rowid" value="'.$id.'" />';
		print '<INPUT type="hidden" name="statut" value="'.$object->statut.'" />';
		if ($backtopage) print '<INPUT type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';

		dol_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');

		print '<TABLE class="border" width="100%">';

		// Ref
		print '<TR><TD class="titlefieldcreate">'.$langs->trans("Ref").'</TD><TD class="valeur">'.$object->id.'</TD></TR>';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<TR><TD><SPAN class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</SPAN></TD><TD><INPUT type="text" name="login" class="maxwidth200" value="'.(isset($_POST["login"])?GETPOST("login",'alpha',2):$object->login).'"></TD></TR>';
		}

	// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<TR><TD class="fieldrequired">'.$langs->trans("Password").'</TD><TD><INPUT type="password" name="pass" class="maxwdith200" value="'.(isset($_POST["pass"])?GETPOST("pass",'',2):$object->pass).'"></TD></TR>';
		}
		// Morphy
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Morale");
		print '<TR><TD><SPAN class="fieldrequired">'.$langs->trans("Nature").'</SPAN></TD><TD>';
		print $form->selectarray("morphy", $morphys, isset($_POST["morphy"])?$_POST["morphy"]:$object->morphy);
		print "</TD></TR>";

		// Type
		print '<TR><TD class="fieldrequired">'.$langs->trans("Type").'</TD><TD>';
		if ($user->rights->adherent->creer)
		{
			print $form->selectarray("typeid", $adht->liste_array(), (isset($_POST["typeid"])?$_POST["typeid"]:$object->typeid));
		}
		else
		{
			print $adht->getNomUrl(1);
			print '<INPUT type="hidden" name="typeid" value="'.$object->typeid.'">';
		}
		print "</TD></TR>";

		// Company
		print '<TR><TD id="tdcompany">'.$langs->trans("Company").'</TD><TD><INPUT type="text" name="societe" class="minwidth100" value="'.(isset($_POST["societe"])?GETPOST("societe",'',2):$object->societe).'"></TD></TR>';

		// Civility
		print '<TR><TD>'.$langs->trans("UserTitle").'</TD><TD>';
		print $formcompany->select_civility(isset($_POST["civility_id"])?$_POST["civility_id"]:$object->civility_id)."\n";
		print '</TD>';
		print '</TR>';

		// Lastname
		print '<TR><TD id="tdlastname">'.$langs->trans("Lastname").'</TD><TD><INPUT type="text" name="lastname" class="minwidth100" value="'.(isset($_POST["lastname"])?GETPOST("lastname",'',2):$object->lastname).'"></TD>';
		print '</TR>';

		// Firstname
		print '<TR><TD id="tdfirstname">'.$langs->trans("Firstname").'</TD><TD><INPUT type="text" name="firstname" class="minwidth100" value="'.(isset($_POST["firstname"])?GETPOST("firstname",'',3):$object->firstname).'"></TD>';
		print '</TR>';

		// Photo
		print '<TR><TD>'.$langs->trans("Photo").'</TD>';
		print '<TD class="hideonsmartphone" valign="middle">';
		print $form->showphoto('memberphoto',$object)."\n";
		if ($caneditfieldmember)
		{
			if ($object->photo) print "<BR>\n";
			print '<TABLE class="nobordernopadding">';
			if ($object->photo) print '<TR><TD><INPUT type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<BR><BR></TD></TR>';
			print '<TR><TD>'.$langs->trans("PhotoFile").'</TD></TR>';
			print '<TR><TD><INPUT type="file" class="flat" name="photo" id="photoinput"></TD></TR>';
			print '</TABLE>';
		}
		print '</TD></TR>';

		// EMail
		print '<TR><TD>'.($conf->global->ADHERENT_MAIL_REQUIRED?'<SPAN class="fieldrequired">':'').$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED?'</SPAN>':'').'</TD><TD><INPUT type="text" name="member_email" size="40" value="'.(isset($_POST["member_email"])?GETPOST("member_email",'',2):$object->email).'"></TD></TR>';

		// Address
		print '<TR><TD>'.$langs->trans("Address").'</TD><TD>';
		print '<TEXTAREA name="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_2.'">'.(isset($_POST["address"])?GETPOST("address",'',2):$object->address).'</TEXTAREA>';
		print '</TD></TR>';

		// Zip / Town
		print '<TR><TD>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</TD><TD>';
		print $formcompany->select_ziptown((isset($_POST["zipcode"])?GETPOST("zipcode",'',2):$object->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
		print ' ';
		print $formcompany->select_ziptown((isset($_POST["town"])?GETPOST("town",'',2):$object->town),'town',array('zipcode','selectcountry_id','state_id'));
		print '</TD></TR>';

		// Country
		//$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;    // In edit mode we don't force to company country if not defined
		print '<TR><TD>'.$langs->trans('Country').'</TD><TD>';
		print $form->select_country(isset($_POST["country_id"])?$_POST["country_id"]:$object->country_id,'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</TD></TR>';

		// State
		if (empty($conf->global->MEMBER_DISABLE_STATE))
		{
			print '<TR><TD>'.$langs->trans('State').'</TD><TD>';
			print $formcompany->select_state($object->state_id,isset($_POST["country_id"])?GETPOST("country_id"):$object->country_id);
			print '</TD></TR>';
		}

		// Pro phone
		print '<TR><TD>'.$langs->trans("PhonePro").'</TD><TD><INPUT type="text" name="phone" size="20" value="'.(isset($_POST["phone"])?GETPOST("phone"):$object->phone).'"></TD></TR>';

		// Personal phone
		print '<TR><TD>'.$langs->trans("PhonePerso").'</TD><TD><INPUT type="text" name="phone_perso" size="20" value="'.(isset($_POST["phone_perso"])?GETPOST("phone_perso"):$object->phone_perso).'"></TD></TR>';

		// Mobile phone
		print '<TR><TD>'.$langs->trans("PhoneMobile").'</TD><TD><INPUT type="text" name="phone_mobile" size="20" value="'.(isset($_POST["phone_mobile"])?GETPOST("phone_mobile"):$object->phone_mobile).'"></TD></TR>';

	    // Skype
	    if (! empty($conf->skype->enabled))
	    {
			    print '<TR><TD>'.$langs->trans("Skype").'</TD><TD><INPUT type="text" name="skype" class="minwidth100" value="'.(isset($_POST["skype"])?GETPOST("skype"):$object->skype).'"></TD></TR>';
	    }

		// Birthday
		print "<TR><TD>".$langs->trans("Birthday")."</TD><TD>\n";
		$form->select_date(($object->birth ? $object->birth : -1),'birth','','',1,'formsoc');
		print "</TD></TR>\n";

		// Public profil
		print "<TR><TD>".$langs->trans("Public")."</TD><TD>\n";
		print $form->selectyesno("public",(isset($_POST["public"])?GETPOST("public",'',2):$object->public),1);
		print "</TD></TR>\n";

		// Categories
		if (! empty( $conf->categorie->enabled ) && !empty( $user->rights->categorie->lire ))
		{
			print '<TR><TD>' . fieldLabel('Categories', 'memcats') . '</TD>';
			print '<TD>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, null, null, null, 1);
			$c = new Categorie($db);
			$cats = $c->containing($object->id, Categorie::TYPE_MEMBER);
			foreach ($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
			print $form->multiselectarray('memcats', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
			print "</TD></TR>";
		}

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields,'edit',$parameters);
		}

		// Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
			print '<TR><TD>'.$langs->trans("LinkedToDolibarrThirdParty").'</TD><TD colspan="2" class="valeur">';
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
			print '</TD></TR>';
		}

		// Login Dolibarr
		print '<TR><TD>'.$langs->trans("LinkedToDolibarrUser").'</TD><TD colspan="2" class="valeur">';
		if ($object->user_id)
		{
			$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'none');
		}
		else print $langs->trans("NoDolibarrAccess");
		print '</TD></TR>';

		print '</TABLE>';

		dol_fiche_end();

		print '<DIV class="center">';
		print '<INPUT type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<INPUT type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'" onclick="history.go(-1)" />';
		print '</DIV>';

		print '</FORM>';

	}

	if ($id > 0 && $action != 'edit')
	{
		/* ************************************************************************** */
		/*                                                                            */
		/* View mode                                                                  */
		/*                                                                            */
		/* ************************************************************************** */

		$res=$object->fetch($id);
		if ($res < 0) {
			dol_print_error($db,$object->error); exit;
		}
		$res=$object->fetch_optionals($object->id,$extralabels);
		if ($res < 0) {
			dol_print_error($db); exit;
		}

		$adht = new AdherentTypePlus($db);
		$res=$adht->fetch($object->typeid);
		if ($res < 0) {
			dol_print_error($db); exit;
		}


		/*
		 * Show tabs
		 */
		$head = member_prepare_head($object);

		dol_fiche_head($head, 'general', $langs->trans("Member"), -1, 'user');

		// Confirm create user
		if ($action == 'create_user')
		{
			$login=$object->login;
			if (empty($login))
			{
				// Full firstname and name separated with a dot : firstname.name
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$login=dol_buildlogin($object->lastname,$object->firstname);
			}
			if (empty($login)) $login=strtolower(substr($object->firstname, 0, 4)) . strtolower(substr($object->lastname, 0, 4));

			// Create a form array
			$formquestion=array(
					array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login)
			);
			$text=$langs->trans("ConfirmCreateLogin").'<BR>';
			if (! empty($conf->societe->enabled))
			{
				if ($object->fk_soc > 0) $text.=$langs->trans("UserWillBeExternalUser");
				else $text.=$langs->trans("UserWillBeInternalUser");
			}
			print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("CreateDolibarrLogin"),$text,"confirm_create_user",$formquestion,'yes');
		}

		// Confirm create third party
		if ($action == 'create_thirdparty')
		{
			$name = $object->getFullName($langs);
			if (! empty($name))
			{
				if ($object->societe) $name.=' ('.$object->societe.')';
			}
			else
			{
				$name=$object->societe;
			}

			// Create a form array
			$formquestion=array(		array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $name));

			print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("CreateDolibarrThirdParty"),$langs->trans("ConfirmCreateThirdParty"),"confirm_create_thirdparty",$formquestion,1);
		}

		// Confirm validate member
		if ($action == 'valid')
		{
			$langs->load("mails");

			$adht = new AdherentTypePlus($db);
			$adht->fetch($object->typeid);

			$subjecttosend=$object->makeSubstitution($conf->global->ADHERENT_MAIL_VALID_SUBJECT);
			$texttosend=$object->makeSubstitution($adht->getMailOnValid());

			$tmp=$langs->trans("SendAnEMailToMember");
			$tmp.='<BR>('.$langs->trans("MailFrom").': <B>'.$conf->global->ADHERENT_MAIL_FROM.'</B>, ';
			$tmp.=$langs->trans("MailRecipient").': <B>'.$object->email.'</B>)';
			$helpcontent='';
			$helpcontent.='<B>'.$langs->trans("MailFrom").'</B>: '.$conf->global->ADHERENT_MAIL_FROM.'<BR>'."\n";
			$helpcontent.='<B>'.$langs->trans("MailRecipient").'</B>: '.$object->email.'<BR>'."\n";
			$helpcontent.='<B>'.$langs->trans("Subject").'</B>:<BR>'."\n";
			$helpcontent.=$subjecttosend."\n";
			$helpcontent.="<BR>";
			$helpcontent.='<B>'.$langs->trans("Content").'</B>:<BR>';
			$helpcontent.=dol_htmlentitiesbr($texttosend)."\n";
			$label=$form->textwithpicto($tmp,$helpcontent,1,'help');

			// Create form popup
			$formquestion=array();
			if ($object->email) $formquestion[]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label,  'value' => ($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL?true:false));
			if (! empty($conf->mailman->enabled) && ! empty($conf->global->ADHERENT_USE_MAILMAN)) {
				$formquestion[]=array('type'=>'other','label'=>$langs->transnoentitiesnoconv("SynchroMailManEnabled"),'value'=>'');
			}
			if (! empty($conf->mailman->enabled) && ! empty($conf->global->ADHERENT_USE_SPIP))    {
				$formquestion[]=array('type'=>'other','label'=>$langs->transnoentitiesnoconv("SynchroSpipEnabled"),'value'=>'');
			}
			print $form->formconfirm("card.php?rowid=".$id,$langs->trans("ValidateMember"),$langs->trans("ConfirmValidateMember"),"confirm_valid",$formquestion,1,1);
		}

		// Confirm send card by mail
		if ($action == 'sendinfo')
		{
			print $form->formconfirm("card.php?rowid=".$id,$langs->trans("SendCardByMail"),$langs->trans("ConfirmSendCardByMail",$object->email),"confirm_sendinfo",'',0,1);
		}

		// Confirm terminate
		if ($action == 'resign')
		{
			$langs->load("mails");

			$adht = new AdherentTypePlus($db);
			$adht->fetch($object->typeid);

			$subjecttosend=$object->makeSubstitution($conf->global->ADHERENT_MAIL_RESIL_SUBJECT);
			$texttosend=$object->makeSubstitution($adht->getMailOnResiliate());

			$tmp=$langs->trans("SendAnEMailToMember");
			$tmp.='<BR>('.$langs->trans("MailFrom").': <B>'.$conf->global->ADHERENT_MAIL_FROM.'</B>, ';
			$tmp.=$langs->trans("MailRecipient").': <B>'.$object->email.'</B>)';
			$helpcontent='';
			$helpcontent.='<B>'.$langs->trans("MailFrom").'</B>: '.$conf->global->ADHERENT_MAIL_FROM.'<BR>'."\n";
			$helpcontent.='<B>'.$langs->trans("MailRecipient").'</B>: '.$object->email.'<BR>'."\n";
			$helpcontent.='<B>'.$langs->trans("Subject").'</B>:<BR>'."\n";
			$helpcontent.=$subjecttosend."\n";
			$helpcontent.="<BR>";
			$helpcontent.='<B>'.$langs->trans("Content").'</B>:<BR>';
			$helpcontent.=dol_htmlentitiesbr($texttosend)."\n";
			$label=$form->textwithpicto($tmp,$helpcontent,1,'help');

			// Create an array
			$formquestion=array();
			if ($object->email) $formquestion[]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL)?'true':'false'));
			if ($backtopage)    $formquestion[]=array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			print $form->formconfirm("card.php?rowid=".$id,$langs->trans("ResiliateMember"),$langs->trans("ConfirmResiliateMember"),"confirm_resign",$formquestion,'no',1);
		}

		// Confirm remove member
		if ($action == 'delete')
		{
			$formquestion=array();
			if ($backtopage) $formquestion[]=array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			print $form->formconfirm("card.php?rowid=".$id,$langs->trans("DeleteMember"),$langs->trans("ConfirmDeleteMember"),"confirm_delete",$formquestion,0,1);
		}

		// Confirm add in spip
		if ($action == 'add_spip')
		{
			print $form->formconfirm("card.php?rowid=".$id, $langs->trans('AddIntoSpip'), $langs->trans('AddIntoSpipConfirmation'), 'confirm_add_spip');
		}
		// Confirm removed from spip
		if ($action == 'del_spip')
		{
			print $form->formconfirm("card.php?rowid=$id", $langs->trans('DeleteIntoSpip'), $langs->trans('DeleteIntoSpipConfirmation'), 'confirm_del_spip');
		}

		$rowspan=17;
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $rowspan++;
		if (! empty($conf->societe->enabled)) $rowspan++;

		$linkback = '<A href="'.dol_buildpath('/adherentsplus/list.php', 1).'">'.$langs->trans("BackToList").'</A>';

		dol_banner_tab($object, 'rowid', $linkback);

        print '<DIV class="fichecenter">';
        print '<DIV class="fichehalfleft">';

        print '<DIV class="underbanner clearboth"></DIV>';
		print '<TABLE class="border centpercent">';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<TR><TD class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</TD><TD class="valeur">'.$object->login.'&nbsp;</TD></TR>';
		}

		// Type
		print '<TR><TD class="titlefield">'.$langs->trans("Type").'</TD><TD class="valeur">'.$adht->getNomUrl(1)."</TD></TR>\n";

		// Morphy
		print '<TR><TD>'.$langs->trans("Nature").'</TD><TD class="valeur" >'.$object->getmorphylib().'</TD>';
		print '</TR>';

		// Company
		print '<TR><TD>'.$langs->trans("Company").'</TD><TD class="valeur">'.$object->societe.'</TD></TR>';

		// Civility
		print '<TR><TD>'.$langs->trans("UserTitle").'</TD><TD class="valeur">'.$object->getCivilityLabel().'&nbsp;</TD>';
		print '</TR>';

		// Password
		if (!empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<TR><TD>'.$langs->trans("Password").'</TD><TD>'.preg_replace('/./i','*',$object->pass);
			if ($object->pass) print preg_replace('/./i','*',$object->pass);
			else
			{
			    if ($user->admin) print $langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
			    else print $langs->trans("Hidden");
			}
			if ((! empty($object->pass) || ! empty($object->pass_crypted)) && empty($object->user_id))
			{
			    $langs->load("errors");
			    $htmltext=$langs->trans("WarningPasswordSetWithNoAccount");
			    print ' '.$form->textwithpicto('', $htmltext,1,'warning');
			}
			print '</TD></TR>';
		}

        print '</TABLE>';

        print '</DIV>';
        print '<DIV class="fichehalfright"><DIV class="ficheaddleft">';

        print '<DIV class="underbanner clearboth"></DIV>';
        print '<TABLE class="border tableforfield" width="100%">';

		// Birthday
		print '<TR><TD class="titlefield">'.$langs->trans("Birthday").'</TD><TD class="valeur">'.dol_print_date($object->birth,'day').'</TD></TR>';

		// Public
		print '<TR><TD>'.$langs->trans("Public").'</TD><TD class="valeur">'.yn($object->public).'</TD></TR>';

		// Categories
		if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
		{
			print '<TR><TD>' . $langs->trans("Categories") . '</TD>';
			print '<TD colspan="2">';
			print $form->showCategories($object->id, 'member', 1);
			print '</TD></TR>';
		}

    	// Other attributes
    	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        // Date end subscription
        print '<TR><TD>'.$langs->trans("SubscriptionEndDate").'</TD><TD class="valeur">';
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
        print '</TD></TR>';

		// Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
			print '<TR><TD>';
			print '<TABLE class="nobordernopadding" width="100%"><TR><TD>';
			print $langs->trans("LinkedToDolibarrThirdParty");
			print '</TD>';
			if ($action != 'editthirdparty' && $user->rights->adherent->creer) print '<TD align="right"><A href="'.$_SERVER["PHP_SELF"].'?action=editthirdparty&amp;rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToThirdParty'),1).'</A></TD>';
			print '</TR></TABLE>';
			print '</TD><TD colspan="2" class="valeur">';
			if ($action == 'editthirdparty')
			{
				$htmlname='socid';
				print '<FORM method="POST" action="'.$_SERVER['PHP_SELF'].'" name="form'.$htmlname.'">';
				print '<INPUT type="hidden" name="rowid" value="'.$object->id.'">';
				print '<INPUT type="hidden" name="action" value="set'.$htmlname.'">';
				print '<INPUT type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<TABLE class="nobordernopadding" cellpadding="0" cellspacing="0">';
				print '<TR><TD>';
				print $form->select_company($object->fk_soc,'socid','',1);
				print '</TD>';
				print '<TD align="left"><INPUT type="submit" class="button" value="'.$langs->trans("Modify").'"></TD>';
				print '</TR></TABLE></FORM>';
			}
			else
			{
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
			}
			print '</TD></TR>';
		}

		// Login Dolibarr
		print '<TR><TD>';
		print '<TABLE class="nobordernopadding" width="100%"><TR><TD>';
		print $langs->trans("LinkedToDolibarrUser");
		print '</TD>';
		if ($action != 'editlogin' && $user->rights->adherent->creer)
		{
			print '<TD align="right">';
			if ($user->rights->user->user->creer)
			{
				print '<A href="'.$_SERVER["PHP_SELF"].'?action=editlogin&amp;rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToUser'),1).'</A>';
			}
			print '</TD>';
		}
		print '</TR></TABLE>';
		print '</TD><TD colspan="2" class="valeur">';
		if ($action == 'editlogin')
		{
			$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'userid','');
		}
		else
		{
			if ($object->user_id)
			{
				$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'none');
			}
			else print $langs->trans("NoDolibarrAccess");
		}
		print '</TD></TR>';

		print "</TABLE>\n";

		print "</DIV></DIV></DIV>\n";
        print '<DIV style="clear:both"></DIV>';

        dol_fiche_end();


		/*
		 * Hotbar
		 */

		print '<DIV class="tabsAction">';
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		if (empty($reshook)) {
			if ($action != 'valid' && $action != 'editlogin' && $action != 'editthirdparty')
			{
				// Modify
				if ($user->rights->adherent->creer)
				{
					print '<DIV class="inline-block divButAction"><A class="butAction" href="card.php?rowid='.$id.'&action=edit">'.$langs->trans("Modify")."</A></DIV>";
				}
				else
				{
					print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Modify").'</FONT></DIV>';
				}

				// Validate
				if ($object->statut == -1)
				{
					if ($user->rights->adherent->creer)
					{
						print '<DIV class="inline-block divButAction"><A class="butAction" href="card.php?rowid='.$id.'&action=valid">'.$langs->trans("Validate")."</A></DIV>\n";
					}
					else
					{
						print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Validate").'</FONT></DIV>';
					}
				}

				// Reactivate
				if ($object->statut == 0)
				{
					if ($user->rights->adherent->creer)
					{
						print '<DIV class="inline-block divButAction"><A class="butAction" href="card.php?rowid='.$id.'&action=valid">'.$langs->trans("Reenable")."</A></DIV>\n";
					}
					else
					{
						print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Reenable")."</FONT></DIV>";
					}
				}

				// Send card by email
				if ($user->rights->adherent->creer)
				{
					if ($object->statut >= 1)
					{
						if ($object->email) print '<DIV class="inline-block divButAction"><A class="butAction" href="card.php?rowid='.$object->id.'&action=sendinfo">'.$langs->trans("SendCardByMail")."</A></DIV>\n";
						else print '<DIV class="inline-block divButAction"><A class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendCardByMail")."</A></DIV>\n";
					}
					else
					{
						print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("SendCardByMail")."</FONT></DIV>";
					}
				}
				else
				{
					print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("SendCardByMail")."</FONT></DIV>";
				}

				// Terminate
				if ($object->statut >= 1)
				{
					if ($user->rights->adherent->supprimer)
					{
						print '<DIV class="inline-block divButAction"><A class="butAction" href="card.php?rowid='.$id.'&action=resign">'.$langs->trans("Resiliate")."</A></DIV>\n";
					}
					else
					{
						print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Resiliate")."</FONT></DIV>";
					}
				}

				// Create third party
				if (! empty($conf->societe->enabled) && ! $object->fk_soc)
				{
					if ($user->rights->societe->creer)
					{
						if ($object->statut != -1) print '<DIV class="inline-block divButAction"><A class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_thirdparty">'.$langs->trans("CreateDolibarrThirdParty").'</A></DIV>';
						else print '<DIV class="inline-block divButAction"><A class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrThirdParty").'</A></DIV>';
					}
					else
					{
						print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrThirdParty")."</FONT></DIV>";
					}
				}

				// Create user
				if (! $user->societe_id && ! $object->user_id)
				{
					if ($user->rights->user->user->creer)
					{
						if ($object->statut != -1) print '<DIV class="inline-block divButAction"><A class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</A></DIV>';
						else print '<DIV class="inline-block divButAction"><A class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrLogin").'</A></DIV>';
					}
					else
					{
						print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrLogin")."</FONT></DIV>";
					}
				}

				// Delete
				if ($user->rights->adherent->supprimer)
				{
					print '<DIV class="inline-block divButAction"><A class="butActionDelete" href="card.php?rowid='.$object->id.'&action=delete">'.$langs->trans("Delete")."</A></DIV>\n";
				}
				else
				{
					print '<DIV class="inline-block divButAction"><FONT class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Delete")."</FONT></DIV>";
				}

				// Action SPIP
				if (! empty($conf->mailmanspip->enabled) && ! empty($conf->global->ADHERENT_USE_SPIP))
				{
					$isinspip = $mailmanspip->is_in_spip($object);

					if ($isinspip == 1)
					{
						print '<DIV class="inline-block divButAction"><A class="butAction" href="card.php?rowid='.$object->id.'&action=del_spip">'.$langs->trans("DeleteIntoSpip")."</A></DIV>\n";
					}
					if ($isinspip == 0)
					{
						print '<DIV class="inline-block divButAction"><A class="butAction" href="card.php?rowid='.$object->id.'&action=add_spip">'.$langs->trans("AddIntoSpip")."</A></DIV>\n";
					}
				}

			}
		}
		print '</DIV>';

		if ($isinspip == -1)
		{
			print '<BR><BR><FONT class="error">'.$langs->trans('SPIPConnectionFailed').': '.$mailmanspip->error.'</FONT>';
		}



		print '<DIV class="fichecenter"><DIV class="fichehalfleft">';
		print '<A name="builddoc"></A>'; // ancre

		// Documents generes
		$filename = dol_sanitizeFileName($object->ref);
		//$filename =  'tmp_cards.php';
		//$filedir = $conf->adherent->dir_output . '/' . get_exdir($object->id, 2, 0, 0, $object, 'member') . dol_sanitizeFileName($object->ref);
		$filedir = $conf->adherent->dir_output . '/' . get_exdir(0, 0, 0, 0, $object, 'member');
		$urlsource = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
		$genallowed = $user->rights->adherent->lire;
		$delallowed = $user->rights->adherent->creer;

		print $formfile->showdocuments('member', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $object->default_lang, '', $object);
		$somethingshown = $formfile->numoffiles;

		// Show links to link elements
		//$linktoelem = $form->showLinkToObjectBlock($object, null, array('subscription'));
		//$somethingshown = $form->showLinkedObjectBlock($object, '');

		// Show links to link elements
		/*$linktoelem = $form->showLinkToObjectBlock($object,array('order'));
		 if ($linktoelem) print ($somethingshown?'':'<BR>').$linktoelem;

		 // Link for paypal payment
		 /*
		 if (! empty($conf->paypal->enabled) && $object->statut != 0) {
		 include_once DOL_DOCUMENT_ROOT . '/paypal/lib/paypal.lib.php';
		 print showPaypalPaymentUrl('invoice', $object->ref);
		 }
		 */
		print '</DIV><DIV class="fichehalfright"><DIV class="ficheaddleft">';

		// List of actions on element
        /* Already in tab Agenda/Events
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'member', $socid);
		*/
if ($adht->family=='1' && $action=='deleteparent' && $user->rights->adherent->creer){
$form = new Form($db);
$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?rowid='.$object->id.'&link='.$link, $langs->trans('Confirm'), $langs->trans('ConfirmDeleteParent'), 'confirm_deleteparent', '', 0, 1);
print $formconfirm;	
}
if ($adht->family=='1' && $action=='addparent' && $user->rights->adherent->creer){
$form = new Form($db);
$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?rowid='.$object->id.'&link='.$link, $langs->trans('Confirm'), $langs->trans('ConfirmAddParent'), 'confirm_addparent', '', 0, 1);
print $formconfirm;	
}
if ($adht->family=='1' && $object->fk_parent>'0') {
print load_fiche_titre($langs->trans("PrincipalMember"), '', '');
print '<TABLE class="noborder" summary="listofdocumentstable" id="'.$modulepart.'_table" width="100%">'."\n";
$objp = new Adherentplus($db);
$objp->fetch($object->fk_parent);
print '<TR class="oddeven"><TD align="left"><A href="'.$dolibarr_main_url_root.dol_buildpath('/adherentsplus/card.php?rowid='.$object->fk_parent, 1).'">'.img_picto('', 'object_user').' '.$objp->firstname.' '.$objp->lastname.'</A></TD>';
if ($user->rights->adherent->creer) {print '<TD align="right"><A href="'. $_SERVER['PHP_SELF'] .'?action=deleteparent&rowid=' . $object->id . '&link=' . $object->id . '" class="deletefilelink">' . img_delete() . '</A></TD>';}
print '</TR></TABLE>'."\n";     
} elseif ($adht->family=='1'){
print load_fiche_titre($langs->trans("SecondaryMembers"), '', ''); 
print '<TABLE class="noborder" summary="listofdocumentstable" id="'.$modulepart.'_table" width="100%">'."\n";
print '<TR class="liste_titre">';
print '<TH align="center" colspan="'.(3+($addcolumforpicto?'2':'1')).'" class="formdoc liste_titre maxwidthonsmartphone">';
print '<form action="'. $_SERVER['PHP_SELF'] .'?action=addparent&rowid=' . $object->id . '" id="'.$forname.'_form" method="post">';
print '<SELECT name="link">';  
        
        $sql = "SELECT c.rowid, c.firstname, c.lastname";               
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as c";
        $sql.= " WHERE c.entity IN (" . getEntity('adherentsplus') . ") AND c.rowid!=$object->id AND ISNULL(c.fk_parent)";
        $sql.= " ORDER BY c.firstname ASC";
        $sql.= " LIMIT 0,5";
        
        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            $var=True;
            print '<OPTION value="" disabled selected>'.$langs->trans('Members').'</OPTION>';  
            while ($i < $num)
            {            
                $objp = $db->fetch_object($result);
                $var=!$var;               
             
                print '<OPTION value="'.$objp->rowid.'">'.$objp->firstname.' '.$objp->lastname.'</OPTION>';   
                            
                $i++;
            }
        }
        else
        {
            dol_print_error($db);
        }

print '</SELECT>';
print '<input class="button buttongen" id="addsecondarymemeber" name="addsecondarymemeber" type="submit" value="'.$langs->trans('Add').'">';
print '</FORM></TH></TR>';
$sql = "SELECT d.rowid, d.login, d.lastname, d.firstname, d.societe as company, d.fk_soc,";
$sql.= " d.datefin, d.fk_adherent_type as type_id, d.morphy, d.statut, d.datec as date_creation, d.tms as date_update";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d";
$sql.= " WHERE d.fk_parent = $id ";
        
$result = $db->query($sql);
if ($result){
$num = $db->num_rows($result);
$i = 0;

$var=True;
while ($i < $num){            
$objp = $db->fetch_object($result);
$var=!$var;
                      
print "<TR ".$bc[$var].">";
print '<TD>'.$objp->rowid;              
print '</TD>';
print '<TD align="left"><A href="'.$dolibarr_main_url_root.dol_buildpath('/adherentsplus/card.php?rowid='.$objp->rowid, 1).'">'.img_picto('', 'object_user').' '.$objp->firstname.' '.$objp->lastname.'</A></TD>';
if ($user->rights->adherent->creer) {print '<TD align="right"><A href="'. $_SERVER['PHP_SELF'] .'?action=deleteparent&rowid=' . $object->id . '&link=' . $objp->rowid . '" class="deletefilelink">' . img_delete() . '</A></TD>';}
print "</TR>";
$i++;
}
if ($num=='0'){
print '<TR class="oddeven"><TD colspan="3" class="opacitymedium">'.$langs->trans("None").'</TD></TR>'."\n";
}
}
print '</TABLE>'."\n";   
}  
		print '</DIV></DIV></DIV>';

	}
}

llxFooter();

$db->close();