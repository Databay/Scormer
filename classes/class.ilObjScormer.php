<?php

/**
* Application class for Scormer repository object.
*
* @author Aresch Yavari <ay@databay.de>
*
* $Id$
*/
class ilObjScormer extends ilObjectPlugin
{
	/**
	* Constructor
	*
	* @access	public
	*/
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}
	

	/**
	* Get type.
	* The initType() method must set the same ID as the plugin ID.
	*/
	final function initType(): void
	{
		$this->setType("xsco");
	}
	
	/**
	* Create object
	* This method is called, when a new repository object is created.
	* The Object-ID of the new object can be obtained by $this->getId().
	* You can store any properties of your object that you need.
	* It is also possible to use multiple tables.
	* Standard properites like title and description are handled by the parent classes.
	*/
	function doCreate(bool $clone_mode = false): void
	{
		// $myID = $this->getId();

	}
	
	/**
	* Read data from db
	* This method is called when an instance of a repository object is created and an existing Reference-ID is provided to the constructor.
	* All you need to do is to read the properties of your object from the database and to call the corresponding set-methods.
	*/
	function doRead(): void
	{
		// $myID = $this->getId();

	}
	
	/**
	* Update data
	* This method is called, when an existing object is updated.
	*/
	function doUpdate(): void
	{
		// $myID = $this->getId();

	}
	
	/**
	* Delete data from db
	* This method is called, when a repository object is finally deleted from the system.
	* It is not called if an object is moved to the trash.
	*/
	function doDelete(): void
	{
		// $myID = $this->getId();
		
	}
	
	/**
	* Do Cloning
	* This method is called, when a repository object is copied.
	*/
	function doClone($a_target_id,$a_copy_id,$new_obj)
	{
	}
	

}
?>
