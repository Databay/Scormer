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
        'scormer_base_url' => 'https://scormer.iliasnet.de',
        'scormer_preview_api_key' => '',
        'scormer_editor_api_key' => '',
        'ai_text_provider' => 'databay',
        'ai_image_provider' => 'databay',
        'ai_endpoint_url' => 'https://api.openai.com/v1/chat/completions',
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

        switch ($cmd) {
            case "configure":
            case "save":
            case "openConfig":
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
        $DIC->ui()->mainTemplate()->setContent($form->getHTML());
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

        $backendConfigSection = new ilFormSectionHeaderGUI();
        $backendConfigSection->setTitle($pl->txt("scormer_backend_config_section"));
        $form->addItem($backendConfigSection);

        $backendConfigAction = new ilCustomInputGUI();
        if (trim((string) $config['scormer_editor_api_key']) === '') {
            $backendConfigAction->setHtml(
                '<p class="ilFormInfo">' . $pl->txt("scormer_editor_api_key_required") . '</p>'
            );
        } else {
            $openConfigLink = $DIC->ctrl()->getLinkTarget($this, "openConfig");
            $backendConfigAction->setHtml(
                '<a href="' . $openConfigLink . '" class="btn btn-default" target="scormerconfig" rel="noopener noreferrer">'
                    . $pl->txt("scormer_open_config")
                    . '</a>'
            );
        }
        $form->addItem($backendConfigAction);

        $aiTextSection = new ilFormSectionHeaderGUI();
        $aiTextSection->setTitle($pl->txt("ai_text_section_header"));
        $form->addItem($aiTextSection);

        $aiTextProvider = new ilRadioGroupInputGUI($pl->txt("ai_text_provider"), "ai_text_provider");

        $optTextDatabay = new ilRadioOption($pl->txt("ai_provider_databay"), "databay");
        $aiTextProvider->addOption($optTextDatabay);

        $optTextOpenai = new ilRadioOption($pl->txt("ai_provider_openai"), "openai");

        $aiEndpointUrl = new ilTextInputGUI($pl->txt("ai_endpoint_url"), "ai_endpoint_url");
        $optTextOpenai->addSubItem($aiEndpointUrl);

        $aiApiKey = new ilTextInputGUI($pl->txt("ai_api_key"), "ai_api_key");
        $optTextOpenai->addSubItem($aiApiKey);

        $aiModel = new ilTextInputGUI($pl->txt("ai_model"), "ai_model");
        $optTextOpenai->addSubItem($aiModel);

        $aiTextProvider->addOption($optTextOpenai);
        $form->addItem($aiTextProvider);

        $aiImageSection = new ilFormSectionHeaderGUI();
        $aiImageSection->setTitle($pl->txt("ai_image_section"));
        $form->addItem($aiImageSection);

        $aiImageProvider = new ilRadioGroupInputGUI($pl->txt("ai_image_provider"), "ai_image_provider");

        $optImageDatabay = new ilRadioOption($pl->txt("ai_provider_databay"), "databay");
        $aiImageProvider->addOption($optImageDatabay);

        $optImageOpenai = new ilRadioOption($pl->txt("ai_provider_openai"), "openai");

        $aiImageEndpointUrl = new ilTextInputGUI($pl->txt("ai_image_endpoint_url"), "ai_image_endpoint_url");
        $optImageOpenai->addSubItem($aiImageEndpointUrl);

        $aiImageApiKey = new ilTextInputGUI($pl->txt("ai_image_api_key"), "ai_image_api_key");
        $optImageOpenai->addSubItem($aiImageApiKey);

        $aiImageModel = new ilTextInputGUI($pl->txt("ai_image_model"), "ai_image_model");
        $optImageOpenai->addSubItem($aiImageModel);

        $aiImageProvider->addOption($optImageOpenai);
        $form->addItem($aiImageProvider);

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

        $config = array_merge(
            self::DEFAULT_CONFIG,
            array_intersect_key($decoded, self::DEFAULT_CONFIG)
        );

        return $this->migrateLegacyAiProvider($config, $decoded);
    }

    /**
     * Maps legacy ai_provider to separate text/image providers.
     */
    private function migrateLegacyAiProvider(array $config, array $decoded): array
    {
        if (!isset($decoded['ai_text_provider']) && isset($decoded['ai_provider'])) {
            $legacy = (string) $decoded['ai_provider'];
            if (in_array($legacy, ['databay', 'openai'], true)) {
                $config['ai_text_provider'] = $legacy;
                $config['ai_image_provider'] = $legacy;
            }
        }

        return $config;
    }

    private function normalizeAiProvider(string $provider, string $defaultKey): string
    {
        if (!in_array($provider, ['databay', 'openai'], true)) {
            return self::DEFAULT_CONFIG[$defaultKey];
        }

        return $provider;
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
        if ($form->checkInput()) {
            $existing = $this->readConfiguration();
            $aiTextProvider = $this->normalizeAiProvider(
                (string) $form->getInput("ai_text_provider"),
                'ai_text_provider'
            );
            $aiImageProvider = $this->normalizeAiProvider(
                (string) $form->getInput("ai_image_provider"),
                'ai_image_provider'
            );

            $config = [
                'scormer_base_url' => rtrim((string) $form->getInput("scormer_base_url"), "/"),
                'scormer_preview_api_key' => (string) $form->getInput("scormer_preview_api_key"),
                'scormer_editor_api_key' => (string) $form->getInput("scormer_editor_api_key"),
                'ai_text_provider' => $aiTextProvider,
                'ai_image_provider' => $aiImageProvider,
                'ai_endpoint_url' => rtrim(
                    $this->getAiConfigValue($form, "ai_endpoint_url", $existing, $aiTextProvider),
                    "/"
                ),
                'ai_api_key' => $this->getAiConfigValue($form, "ai_api_key", $existing, $aiTextProvider),
                'ai_model' => $this->getAiConfigValue($form, "ai_model", $existing, $aiTextProvider),
                'ai_image_endpoint_url' => rtrim(
                    $this->getAiConfigValue($form, "ai_image_endpoint_url", $existing, $aiImageProvider),
                    "/"
                ),
                'ai_image_api_key' => $this->getAiConfigValue($form, "ai_image_api_key", $existing, $aiImageProvider),
                'ai_image_model' => $this->getAiConfigValue($form, "ai_image_model", $existing, $aiImageProvider),
            ];

            $storage->put(
                $filePath,
                json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $DIC->ui()->mainTemplate()->setOnScreenMessage("success", $pl->txt("saving_invoked"), true);
            $DIC->ctrl()->redirect($this, "configure");
        } else {
            $form->setValuesByPost();
            $DIC->ui()->mainTemplate()->setContent($form->getHtml());
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

    /**
     * Opens the Scormer backend /config route in a new browser tab.
     */
    public function openConfig(): void
    {
        global $DIC;

        $pl = $this->getPluginObject();
        $config = $this->readConfiguration();

        if (
            trim((string) $config['scormer_base_url']) === ''
            || trim((string) $config['scormer_editor_api_key']) === ''
        ) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                $pl->txt("scormer_editor_api_key_required"),
                true
            );
            $DIC->ctrl()->redirect($this, "configure");
        }

        $token = $this->requestConfigToken($config);
        if ($token === '') {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                $pl->txt("scormer_config_token_error"),
                true
            );
            $DIC->ctrl()->redirect($this, "configure");
        }

        $url = rtrim((string) $config['scormer_base_url'], '/')
            . '/config?token='
            . urlencode($token);

        ilUtil::redirect($url);
    }

    private function buildAiFieldsForToken(array $config): array
    {
        $aiTextProvider = (string) ($config['ai_text_provider'] ?? 'databay');
        $aiImageProvider = (string) ($config['ai_image_provider'] ?? 'databay');

        $fields = [
            'ai_provider' => $aiTextProvider === 'openai' ? 'openai' : 'default',
            'ai_image_provider' => $aiImageProvider === 'openai' ? 'openai' : 'default',
        ];

        if ($aiTextProvider === 'openai') {
            $fields['ai_endpoint_url'] = rtrim((string) ($config['ai_endpoint_url'] ?? ''), '/');
            $fields['ai_api_key'] = (string) ($config['ai_api_key'] ?? '');
            $fields['ai_model'] = (string) ($config['ai_model'] ?? '');
        }

        if ($aiImageProvider === 'openai') {
            $fields['ai_image_endpoint_url'] = rtrim((string) ($config['ai_image_endpoint_url'] ?? ''), '/');
            $fields['ai_image_api_key'] = (string) ($config['ai_image_api_key'] ?? '');
            $fields['ai_image_model'] = (string) ($config['ai_image_model'] ?? '');
        }

        return $fields;
    }

    private function requestConfigToken(array $config): string
    {
        global $DIC;

        $scormerUrl = rtrim((string) $config['scormer_base_url'], '/');
        $ilUser = $DIC->user();

        $postFields = array_merge([
            'access_key' => (string) $config['scormer_editor_api_key'],
            'role' => 'config',
            'user_id' => (string) $ilUser->getId(),
            'user_name' => $ilUser->getLogin(),
            'session_id' => session_id(),
        ], $this->buildAiFieldsForToken($config));

        $ch = curl_init($scormerUrl . '/api/auth/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($postFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201 || $response === false) {
            return '';
        }

        $result = json_decode($response, true);

        return (string) ($result['data']['token'] ?? '');
    }
}
