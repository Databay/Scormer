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
        'ai_provider' => 'databay',
        'ai_endpoint_url' => 'https://api.openai.com/v1',
        'ai_api_key' => '',
        'ai_model' => '',
        'ai_image_endpoint_url' => '',
        'ai_image_api_key' => '',
        'ai_image_model' => '',
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
		global $DIC;

		$form = $this->initConfigurationForm();
		$DIC['tpl']->setContent($form->getHTML());
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
		global $DIC;

		$pl = $this->getPluginObject();
	
		$form = new ilPropertyFormGUI();

        $config = $this->readConfiguration();

        $baseUrl = new ilTextInputGUI($pl->txt("scormer_base_url"), "scormer_base_url");
        $baseUrl->setRequired(true);
        $form->addItem($baseUrl);

        $previewApiKey = new ilTextInputGUI($pl->txt("scormer_preview_api_key"), "scormer_preview_api_key");
        $form->addItem($previewApiKey);

        $editorApiKey = new ilTextInputGUI($pl->txt("scormer_editor_api_key"), "scormer_editor_api_key");
        $form->addItem($editorApiKey);

        $aiSection = new ilFormSectionHeaderGUI();
        $aiSection->setTitle($pl->txt("ai_section_header"));
        $form->addItem($aiSection);

        $aiProvider = new ilRadioGroupInputGUI($pl->txt("ai_provider"), "ai_provider");

        $optDatabay = new ilRadioOption($pl->txt("ai_provider_databay"), "databay");
        $aiProvider->addOption($optDatabay);

        $optOpenai = new ilRadioOption($pl->txt("ai_provider_openai"), "openai");

        $aiEndpointUrl = new ilTextInputGUI($pl->txt("ai_endpoint_url"), "ai_endpoint_url");
        $optOpenai->addSubItem($aiEndpointUrl);

        $aiApiKey = new ilTextInputGUI($pl->txt("ai_api_key"), "ai_api_key");
        $optOpenai->addSubItem($aiApiKey);

        $aiModel = new ilTextInputGUI($pl->txt("ai_model"), "ai_model");
        $optOpenai->addSubItem($aiModel);

        $aiImageSection = new ilFormSectionHeaderGUI();
        $aiImageSection->setTitle($pl->txt("ai_image_section"));
        $optOpenai->addSubItem($aiImageSection);

        $aiImageEndpointUrl = new ilTextInputGUI($pl->txt("ai_image_endpoint_url"), "ai_image_endpoint_url");
        $optOpenai->addSubItem($aiImageEndpointUrl);

        $aiImageApiKey = new ilTextInputGUI($pl->txt("ai_image_api_key"), "ai_image_api_key");
        $optOpenai->addSubItem($aiImageApiKey);

        $aiImageModel = new ilTextInputGUI($pl->txt("ai_image_model"), "ai_image_model");
        $optOpenai->addSubItem($aiImageModel);

        $aiProvider->addOption($optOpenai);
        $form->addItem($aiProvider);

		$form->addCommandButton("save", $DIC->language()->txt("save"));
	                
		$form->setTitle($pl->txt("Scormer_plugin_configuration"));
		$form->setFormAction($DIC->ctrl()->getFormAction($this));

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
        global $DIC;
        $storage = $DIC->filesystem()->storage();
        $filePath = $this->getProjectDataPath();
	
		$pl = $this->getPluginObject();
		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
            $existing = $this->readConfiguration();
            $aiProvider = (string) $form->getInput("ai_provider");
            if (!in_array($aiProvider, ["databay", "openai"], true)) {
                $aiProvider = self::DEFAULT_CONFIG["ai_provider"];
            }

            $config = [
                'scormer_base_url' => rtrim((string) $form->getInput("scormer_base_url"), "/"),
                'scormer_preview_api_key' => (string) $form->getInput("scormer_preview_api_key"),
                'scormer_editor_api_key' => (string) $form->getInput("scormer_editor_api_key"),
                'ai_provider' => $aiProvider,
                'ai_endpoint_url' => rtrim(
                    $this->getAiConfigValue($form, "ai_endpoint_url", $existing, $aiProvider),
                    "/"
                ),
                'ai_api_key' => $this->getAiConfigValue($form, "ai_api_key", $existing, $aiProvider),
                'ai_model' => $this->getAiConfigValue($form, "ai_model", $existing, $aiProvider),
                'ai_image_endpoint_url' => rtrim(
                    $this->getAiConfigValue($form, "ai_image_endpoint_url", $existing, $aiProvider),
                    "/"
                ),
                'ai_image_api_key' => $this->getAiConfigValue($form, "ai_image_api_key", $existing, $aiProvider),
                'ai_image_model' => $this->getAiConfigValue($form, "ai_image_model", $existing, $aiProvider),
            ];

            $storage->put(
                $filePath,
                json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
			
			$DIC['tpl']->setOnScreenMessage("success", $pl->txt("saving_invoked"), true);
			$DIC->ctrl()->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$DIC['tpl']->setContent($form->getHtml());
		}
	}

    /**
     * Reads AI field from form; when Databay is active, hidden OpenAI sub-fields
     * may be absent from POST — then existing JSON values are preserved.
     */
    private function getAiConfigValue(
        ilPropertyFormGUI $form,
        string $key,
        array $existing,
        string $provider
    ): string {
        $input = $form->getInput($key);

        if ($provider === "databay" && ($input === null || $input === false)) {
            return (string) ($existing[$key] ?? self::DEFAULT_CONFIG[$key] ?? "");
        }

        if ($input === null || $input === false) {
            return (string) ($existing[$key] ?? self::DEFAULT_CONFIG[$key] ?? "");
        }

        return (string) $input;
    }

}
?>
