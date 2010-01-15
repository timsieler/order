<?php
/*
 * @version $Id: HEADER 1 2009-09-21 14:58 Tsmr $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 
// ----------------------------------------------------------------------
// Original Author of file: NOUH Walid & Benjamin Fontan
// Purpose of file: plugin order v1.1.0 - GLPI 0.72
// ----------------------------------------------------------------------
 */

define('GLPI_ROOT', '../../..');
include (GLPI_ROOT . "/inc/includes.php");

if (!isset ($_GET["id"]))
	$_GET["id"] = "";
if (!isset ($_GET["withtemplate"]))
	$_GET["withtemplate"] = "";

$PluginOrderOrder = new PluginOrderOrder();
$PluginOrderConfig = new PluginOrderConfig();
$PluginOrderOrder_Item = new PluginOrderOrder_Item();
$PluginOrderOrder_Supplier = new PluginOrderOrder_Supplier();

/* add order */
if (isset ($_POST["add"])) {
	if (plugin_order_HaveRight("order", "w"))
      $newID = $PluginOrderOrder->add($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
/* delete order */
else if (isset ($_POST["delete"])) {
   if (plugin_order_HaveRight("order", "w"))
      $PluginOrderOrder->delete($_POST);
   glpi_header($CFG_GLPI["root_doc"] . "/plugins/front/menu.php");
}
/* restore order */
else if (isset ($_POST["restore"])) {
   if (plugin_order_HaveRight("order", "w"))
      $PluginOrderOrder->restore($_POST);
   glpi_header($CFG_GLPI["root_doc"] . "/plugins/front/menu.php");
}
/* purge order */
else if (isset ($_POST["purge"])) {
   if (plugin_order_HaveRight("order", "w"))
      $PluginOrderOrder->delete($_POST, 1);
   glpi_header($CFG_GLPI["root_doc"] . "/plugins/front/menu.php");
}
/* update order */
else if (isset ($_POST["update"])) {
   if (plugin_order_HaveRight("order", "w"))
      $PluginOrderOrder->update($_POST);
   glpi_header($_SERVER['HTTP_REFERER']);
} 
//Status update & order workflow
/* validate order */
else if (isset ($_POST["validate"])) {
   
   $config = $PluginOrderConfig->getConfig();
		
   if (plugin_order_HaveRight("order", "w") && ( plugin_order_HaveRight("validation", "w") || !$config["use_validation"]))
   {
      $PluginOrderOrder->updateOrderStatus($_POST["id"],ORDER_STATUS_APPROVED,$_POST["comment"]);
      $PluginOrderOrder_Item->updateDelivryStatus($_POST["id"]);
      $PluginOrderOrder->getFromDB($_POST["id"]);
      $PluginOrderOrder->sendNotification("validation",$_POST["id"],$PluginOrderOrder->fields["entities_id"],$_SESSION["glpiID"],$_POST["comment"]);
      addMessageAfterRedirect($LANG['plugin_order']['validation'][10]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset ($_POST["waiting_for_approval"])) {
   if (plugin_order_HaveRight("order", "w"))
   {
      $PluginOrderOrder->updateOrderStatus($_POST["id"],ORDER_STATUS_WAITING_APPROVAL,$_POST["comment"]);
      $PluginOrderOrder->getFromDB($_POST["id"]);
      $PluginOrderOrder->sendNotification("ask",$_POST["id"],$PluginOrderOrder->fields["entities_id"],$_SESSION["glpiID"],$_POST["comment"]);
      addMessageAfterRedirect($LANG['plugin_order']['validation'][7]);
   }
   
   glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset ($_POST["cancel_waiting_for_approval"])) {
   if (plugin_order_HaveRight("order", "w") && plugin_order_HaveRight("cancel", "w"))
   {
      $PluginOrderOrder->updateOrderStatus($_POST["id"],ORDER_STATUS_DRAFT,$_POST["comment"]);
      addMessageAfterRedirect($LANG['plugin_order']['validation'][14]);
   }
   
   glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset ($_POST["cancel_order"])) {
   if (plugin_order_HaveRight("order", "w") && plugin_order_HaveRight("cancel", "w"))
   {
      $PluginOrderOrder->updateOrderStatus($_POST["id"],ORDER_STATUS_CANCELED,$_POST["comment"]);
      $PluginOrderOrder->deleteAllLinkWithItem($_POST["id"]);
      $PluginOrderOrder->getFromDB($_POST["id"]);
      $PluginOrderOrder->sendNotification("cancel",$_POST["id"],$PluginOrderOrder->fields["entities_id"],$_SESSION["glpiID"],$_POST["comment"]);
      addMessageAfterRedirect($LANG['plugin_order']['validation'][5]);
   }
   
   glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset ($_POST["undovalidation"])) {
   if (plugin_order_HaveRight("order", "w") && plugin_order_HaveRight("undo_validation", "w"))
   {
      $PluginOrderOrder->updateOrderStatus($_POST["id"],ORDER_STATUS_DRAFT,$_POST["comment"]);
      $PluginOrderOrder->getFromDB($_POST["id"]);
      $PluginOrderOrder->sendNotification("undovalidation",$_POST["id"],$PluginOrderOrder->fields["entities_id"],$_SESSION["glpiID"],$_POST["comment"]);
      addMessageAfterRedirect($LANG['plugin_order']['validation'][8]);
   }
   
   glpi_header($_SERVER['HTTP_REFERER']);
}
//Details management
else if (isset ($_POST["add_item"])) {
   if ($_POST["discount"] < 0 || $_POST["discount"] > 100)
      addMessageAfterRedirect($LANG['plugin_order']['detail'][33],false,ERROR);
   else
   {
      $PluginOrderOrder->getFromDB($_POST["plugin_order_orders_id"]);
      $taxes = $PluginOrderOrder->fields["plugin_order_ordertaxes_id"];
      $new_value = $LANG['plugin_order']['detail'][34]." ".Dropdown::getDropdownName("glpi_plugin_order_references",$_POST["plugin_order_references_id"]);
      $new_value.= " (".$LANG['plugin_order']['detail'][7]." : ".$_POST["quantity"];
      $new_value.= " ".$LANG['plugin_order']['detail'][25]." : ".$_POST["discount"].")";
      $PluginOrderOrder->addHistory("PluginOrderOrder","",$new_value,$_POST["plugin_order_orders_id"]);
      $PluginOrderOrder_Item->addDetails($_POST["plugin_order_references_id"], $_POST["itemtype"], $_POST["plugin_order_orders_id"], $_POST["quantity"], $_POST["price"], $_POST["discount"], $taxes);
   }
      
   glpi_header($_SERVER['HTTP_REFERER']);
} 
else if (isset ($_POST["delete_item"])) {
   
   if (isset($_POST["plugin_order_orders_id"]) && $_POST["plugin_order_orders_id"] > 0 && isset($_POST["item"]))
   {
      foreach ($_POST["item"] as $ID => $val)
         if ($val==1)
         {
            $new_value = $LANG['plugin_order']['detail'][35]." ".Dropdown::getDropdownName("glpi_plugin_order_references",$ID);
            $PluginOrderOrder->addHistory("PluginOrderOrder","",$new_value,$_POST["plugin_order_orders_id"]);
            $PluginOrderOrder_Item->delete(array('id'=>$ID));
         }
   }elseif(!isset($_POST["item"]))
      addMessageAfterRedirect($LANG['plugin_order']['detail'][29],false,ERROR);
      
   glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["add_supplier_infos"]))
{
	if(plugin_order_HaveRight("order","w"))
	{
		if (isset($_POST["plugin_order_orders_id"]) && $_POST["plugin_order_orders_id"] > 0)
		{
			$newID=$PluginOrderOrder_Supplier->add($_POST);
			$new_value = $LANG['plugin_order']['history'][2]. " ";
         if ($_POST["num_quote"])
            $new_value.= $LANG['plugin_order'][30]." ".$_POST["num_quote"];
         if ($_POST["num_order"])
            $new_value.= " - ".$LANG['plugin_order'][31]." : ".$_POST["num_order"];
         if ($_POST["num_bill"])
            $new_value.= " - ".$LANG['plugin_order'][28]." : ".$_POST["num_bill"];
         $PluginOrderOrder->addHistory('PluginOrderOrder',"",$new_value,$_POST["plugin_order_orders_id"]);
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete_supplier_infos"]))
{
	if(plugin_order_HaveRight("order","w"))
	{
		foreach ($_POST["check"] as $ID => $value) {
			$PluginOrderOrder_Supplier->delete(array("id"=>$ID),0,0);
			$new_value = $LANG['plugin_order']['history'][4]. " ".$LANG['plugin_order'][4]." : ".$ID;
         $PluginOrderOrder->addHistory('PluginOrderOrder',"",$new_value,$_POST["plugin_order_orders_id"]);
      }
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else 
{
	PluginOrderProfile::checkRight("order","r");

	if (!isset ($_SESSION['glpi_tab']))
		$_SESSION['glpi_tab'] = 1;
	if (isset ($_GET['onglet'])) 
		$_SESSION['glpi_tab'] = $_GET['onglet'];

	commonHeader($LANG['plugin_order']['title'][1], $_SERVER["PHP_SELF"], "plugins", "order", "order");
	
	$PluginOrderOrder->showForm($_SERVER["PHP_SELF"], $_GET["id"]);
	
	commonFooter();
}

?>