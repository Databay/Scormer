<?php


#include_once "./Services/Repository/classes/class.ilObjectPluginListGUI.php";

/**
* ListGUI implementation for Scormer object plugin. This one
* handles the presentation in container items (categories, courses, ...)
* together with the corresponfing ...Access class.
*
* PLEASE do not create instances of larger classes here. Use the
* ...Access class to get DB data and keep it small.
*
* @author 		Aresch Yavari <ay@databay.de>
*/
class ilObjScormerListGUI extends ilObjectPluginListGUI
{
	
	/**
	* Init type
	*/
	function initType()
	{
		$this->setType("xsco");
	}
	
	/**
	* Get name of gui class handling the commands
	*/
	function getGuiClass(): string
	{
		return "ilObjScormerGUI";
	}
	
	/**
	* Get commands
	*/
	function initCommands(): array
	{
		return array
		(
			array(
				"permission" => "read",
				"cmd" => "showContent",
				"default" => true),
			array(
				"permission" => "write",
				"cmd" => "editProperties",
				"txt" => $this->txt("edit"),
				"default" => false),
		);
	}

	/**
	* Get item properties
	*
	* @return	array		array of property arrays:
	*						"alert" (boolean) => display as an alert property (usually in red)
	*						"property" (string) => property name
	*						"value" (string) => property value
	*/
	function getProperties(): array
	{
		$props = array();
		
		#$this->plugin->includeClass("class.ilObjScormerAccess.php");

		return $props;
	}
}
?>
