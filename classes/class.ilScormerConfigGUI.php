<?php

#include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
 
/**
 * Scormer configuration user interface class
 *
 * @author Aresch Yavari <ay@databay.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilScormerConfigGUI: ilObjComponentSettingsGUI
 *
 */
class ilScormerConfigGUI extends ilPluginConfigGUI
{
    private const DEFAULT_CONFIG = [
        'scormer_base_url' => 'https://scormer.invorbereitung.de',
        'scormer_preview_api_key' => '',
        'scormer_editor_api_key' => '',
    ];

	/**
	* Handles all commmands, default is "configure"
	*/
	function performCommand(string $cmd): void
	{

		switch ($cmd)
		{
			case "configure":
			case "save":
				$this->$cmd();
				break;

		}
	}

	/**
	 * Configure screen
	 */
	function configure()
	{
		global $tpl;

		$form = $this->initConfigurationForm();
		$tpl->setContent($form->getHTML());
	}
	
	//
	// From here on, this is just an Scormer implementation using
	// a standard form (without saving anything)
	//
	
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		global $lng, $ilCtrl;
		
		$pl = $this->getPluginObject();
	
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

        $config = $this->readConfiguration();

        $baseUrl = new ilTextInputGUI($pl->txt("scormer_base_url"), "scormer_base_url");
        $baseUrl->setRequired(true);
        $form->addItem($baseUrl);

        $previewApiKey = new ilTextInputGUI($pl->txt("scormer_preview_api_key"), "scormer_preview_api_key");
        $form->addItem($previewApiKey);

        $editorApiKey = new ilTextInputGUI($pl->txt("scormer_editor_api_key"), "scormer_editor_api_key");
        $form->addItem($editorApiKey);

		$form->addCommandButton("save", $lng->txt("save"));
	                
		$form->setTitle($pl->txt("Scormer_plugin_configuration"));
		$form->setFormAction($ilCtrl->getFormAction($this));

        $form->setValuesByArray($config);

		return $form;
	}
	
    private function getProjectDataPath(): string
    {
        return 'Scormer/Scormer_config.json';
    }

    private function readConfiguration(): array
    {
        global $DIC;

        $storage = $DIC->filesystem()->storage();
        $filePath = $this->getProjectDataPath();

        if (!$storage->has($filePath)) {
            return self::DEFAULT_CONFIG;
        }

        $decoded = json_decode($storage->read($filePath), true);
        if (!is_array($decoded)) {
            return self::DEFAULT_CONFIG;
        }

        return array_merge(self::DEFAULT_CONFIG, array_intersect_key($decoded, self::DEFAULT_CONFIG));
    }

	/**
	 * Save form input (currently does not save anything to db)
	 *
	 */
	public function save()
	{
		global $tpl, $lng, $ilCtrl;
        global $DIC;
        $storage = $DIC->filesystem()->storage();
        $filePath = $this->getProjectDataPath();
	
		$pl = $this->getPluginObject();
		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
            $config = [
                'scormer_base_url' => rtrim((string) $form->getInput("scormer_base_url"), "/"),
                'scormer_preview_api_key' => (string) $form->getInput("scormer_preview_api_key"),
                'scormer_editor_api_key' => (string) $form->getInput("scormer_editor_api_key"),
            ];

            $storage->put(
                $filePath,
                json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
			
			$tpl->setOnScreenMessage("success", $pl->txt("saving_invoked"), true);
			$ilCtrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}

}
?>
