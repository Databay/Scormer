<?php

#include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
 * User Interface class for Scormer repository object.
 *
 * User interface classes process GET and POST parameter and call
 * application classes to fulfill certain tasks.
 *
 * @author            Aresch Yavari <ay@databay.de>
 *
 * $Id$
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @ilCtrl_isCalledBy ilObjScormerGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjScormerGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 *
 */
class ilObjScormerGUI extends ilObjectPluginGUI
{
    private const DEFAULT_SCORMER_CONFIG = [
        'scormer_base_url' => 'https://scormer.ilianet.de',
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

    protected $activeCmd = "projects";
    protected $ScormerBaseUrl = "https://scormer.iliasnet.de";
    protected $ScormerAccessKeyEditor = "";
    protected $ScormerAccessKeyPreview = "";
    protected $AiTextProvider = "databay";
    protected $AiImageProvider = "databay";
    protected $AiEndpointUrl = "https://api.openai.com/v1/chat/completions";
    protected $AiApiKey = "";
    protected $AiModel = "";
    protected $AiImageEndpointUrl = "";
    protected $AiImageApiKey = "";
    protected $AiImageModel = "";
    protected $proxyTarget = "";
    protected $listTarget = "";
    protected $apiTarget = "";
    protected $indexTarget = "";
    /**
     * Initialisation
     */
    protected function afterConstructor(): void
    {
        $this->loadScormerConfiguration();
    }

    /**
     * Get type.
     */
    final function getType(): string
    {
        return "xsco";
    }

    /**
     * Handles all commmands of this class, centralizes permission checks
     */
    function performCommand(string $cmd): void
    {
        $this->apiTarget = $this->ctrl->getLinkTarget($this, "api");

        $this->activeCmd = $cmd;
        switch ($cmd) {
            case "showEdit":
            case "showContent":
                $this->checkPermission("write");
                $this->$cmd();
                break;

            case "editProperties":
            case "updateProperties":

                $this->checkPermission("write");
                $this->$cmd();
                break;
            case "myOutput":
                $this->checkPermission("read");   // oder "write", je nach Bedarf
                $this->myOutput();
                break;
            case "targetSelect":
            case "saveTarget":
            case "cancelTarget":
            case "showSuccess":
                $this->checkPermission("write");
                $this->$cmd();
                break;
        }
    }

    function targetSelect(): void
    {
        global $DIC;

        $ilTabs = $DIC->tabs();
        $ilTabs->activateTab("export");

        $ui_factory = $DIC['ui.factory'];

        $exp = new ilRepositorySelectorExplorerGUI($this, "targetSelect");
        $exp->setTypeWhiteList(["root", "cat", "grp", "crs", "fold"]);
        $exp->setSelectMode("target", false);

        if ($exp->handleCommand()) {
            return;
        }

        $output = $exp->getHTML();

        $t = new ilToolbarGUI();
        $t->setFormAction($DIC->ctrl()->getFormAction($this, "saveTarget"));

        $primary_button = $ui_factory->button()->primary(
            $DIC->language()->txt('select'),
            ''
        )->withOnLoadCode(
            function ($id) {
                return "document.getElementById('$id')"
                    . '.addEventListener("click", '
                    . '(e) => {e.preventDefault();'
                    . 'e.target.setAttribute("name", "cmd[saveTarget]");'
                    . 'e.target.form.requestSubmit(e.target);});';
            }
        );
        $t->addComponent($primary_button);

        $cancel_btn = $ui_factory->button()->standard(
            $DIC->language()->txt('cancel'),
            ''
        )->withOnLoadCode(
            function ($id) {
                return "document.getElementById('$id')"
                    . '.addEventListener("click", '
                    . '(e) => {e.preventDefault();'
                    . 'e.target.setAttribute("name", "cmd[cancelTarget]");'
                    . 'e.target.form.requestSubmit(e.target);});';
            }
        );
        $t->addComponent($cancel_btn);

        $t->setCloseFormTag(false);
        $t->setLeadingImage(ilUtil::getImagePath("nav/arrow_upright.svg"), " ");
        $output = $t->getHTML() . $output;

        $t->setLeadingImage(ilUtil::getImagePath("nav/arrow_downright.svg"), " ");
        $t->setCloseFormTag(true);
        $t->setOpenFormTag(false);
        $output .= "<br />" . $t->getHTML();

        $info = "<p>Bitte wählen Sie das Ziel für das SCORMer-Lernmodul.</p>";

        $DIC->ui()->mainTemplate()->setContent($info . $output);
    }

    function saveTarget(): void
    {
        global $DIC;

        $target_ref_id = (int) ($_POST["target"] ?? 0);

        if ($target_ref_id <= 0) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage("failure", $DIC->language()->txt("select_one"), true);
            $DIC->ctrl()->redirect($this, "targetSelect");
            return;
        }

        $target_type = ilObject::_lookupType($target_ref_id, true);
        $target_obj_id = ilObject::_lookupObjId($target_ref_id);
        $target_class_name = ilObjectFactory::getClassByType($target_type);
        $target_object = new $target_class_name($target_ref_id);
        $possible_subtypes = $target_object->getPossibleSubObjects();

        if (!array_key_exists('sahs', (array) $possible_subtypes)) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                sprintf(
                    $DIC->language()->txt('msg_obj_may_not_contain_objects_of_type'),
                    ilObject::_lookupTitle($target_obj_id),
                    $DIC->language()->txt('obj_sahs')
                ),
                true
            );
            $DIC->ctrl()->redirect($this, "targetSelect");
            return;
        }

        if (!$DIC->access()->checkAccess("create", "", $target_ref_id, "sahs")) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                $DIC->language()->txt("no_create_permission"),
                true
            );
            $DIC->ctrl()->redirect($this, "targetSelect");
            return;
        }

        $projectUuid = $this->getProjectUuid();
        if ($projectUuid === '') {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                "Kein Scormer-Projekt gefunden. Bitte zuerst ein Projekt anlegen.",
                true
            );
            $DIC->ctrl()->redirect($this, "showEdit");
            return;
        }

        $buildResult = $this->requestScormBuild($projectUuid);
        if ($buildResult === null) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                "Fehler beim Erzeugen des SCORM-Pakets. Bitte versuchen Sie es erneut.",
                true
            );
            $DIC->ctrl()->redirect($this, "targetSelect");
            return;
        }

        $downloadUrl = $buildResult['download_url'] ?? '';
        $scormVersion = $buildResult['scorm_version'] ?? '1.2';
        $title = $buildResult['settings_title'] ?? $this->object->getTitle();

        if ($downloadUrl === '') {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                "Keine Download-URL vom Scormer erhalten.",
                true
            );
            $DIC->ctrl()->redirect($this, "targetSelect");
            return;
        }

        $tmpZipPath = ilFileUtils::ilTempnam() . '.zip';
        $zipContent = $this->downloadFile($downloadUrl);
        if ($zipContent === false) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                "Fehler beim Herunterladen des SCORM-Pakets. " . $downloadUrl,
                true
            );
            $DIC->ctrl()->redirect($this, "targetSelect");
            return;
        }
        file_put_contents($tmpZipPath, $zipContent);

        $newObj = $this->createScormObject($scormVersion, $title, $target_ref_id, $tmpZipPath);

        @unlink($tmpZipPath);

        if ($newObj === null) {
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                "failure",
                "Fehler beim Anlegen des SCORM-Lernmoduls.",
                true
            );
            $DIC->ctrl()->redirect($this, "targetSelect");
            return;
        }

        $DIC->ctrl()->setParameter($this, 'new_ref_id', $newObj->getRefId());
        $DIC->ctrl()->redirect($this, "showSuccess");
    }

    private function getProjectDataPath(): string
    {
        return 'Scormer/Scormer_' . $this->object->getRefId() . '.json';
    }

    private function getConfigurationDataPath(): string
    {
        return 'Scormer/Scormer_config.json';
    }

    private function readScormerConfiguration(): array
    {
        global $DIC;

        $storage = $DIC->filesystem()->storage();
        $filePath = $this->getConfigurationDataPath();

        if (!$storage->has($filePath)) {
            return self::DEFAULT_SCORMER_CONFIG;
        }

        $decoded = json_decode($storage->read($filePath), true);
        if (!is_array($decoded)) {
            return self::DEFAULT_SCORMER_CONFIG;
        }

        $config = array_merge(
            self::DEFAULT_SCORMER_CONFIG,
            array_intersect_key($decoded, self::DEFAULT_SCORMER_CONFIG)
        );

        if (trim((string) $config['scormer_base_url']) === '') {
            $config['scormer_base_url'] = self::DEFAULT_SCORMER_CONFIG['scormer_base_url'];
        }

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

    private function loadScormerConfiguration(): void
    {
        $config = $this->readScormerConfiguration();

        $this->ScormerBaseUrl = rtrim((string) $config['scormer_base_url'], '/');
        $this->ScormerAccessKeyPreview = (string) $config['scormer_preview_api_key'];
        $this->ScormerAccessKeyEditor = (string) $config['scormer_editor_api_key'];

        $aiTextProvider = (string) $config['ai_text_provider'];
        $this->AiTextProvider = in_array($aiTextProvider, ['databay', 'openai'], true)
            ? $aiTextProvider
            : self::DEFAULT_SCORMER_CONFIG['ai_text_provider'];

        $aiImageProvider = (string) $config['ai_image_provider'];
        $this->AiImageProvider = in_array($aiImageProvider, ['databay', 'openai'], true)
            ? $aiImageProvider
            : self::DEFAULT_SCORMER_CONFIG['ai_image_provider'];
        $this->AiEndpointUrl = rtrim((string) $config['ai_endpoint_url'], '/');
        $this->AiApiKey = (string) $config['ai_api_key'];
        $this->AiModel = (string) $config['ai_model'];
        $this->AiImageEndpointUrl = rtrim((string) $config['ai_image_endpoint_url'], '/');
        $this->AiImageApiKey = (string) $config['ai_image_api_key'];
        $this->AiImageModel = (string) $config['ai_image_model'];
    }

    private function getOrCreateProjectData(): array
    {
        global $DIC;
        $storage = $DIC->filesystem()->storage();
        $filePath = $this->getProjectDataPath();

        if ($storage->has($filePath)) {
            $decoded = json_decode($storage->read($filePath), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $data = ['uuid' => $this->uuidv4()];
        $storage->put($filePath, json_encode($data, JSON_PRETTY_PRINT));
        return $data;
    }

    private function getProjectUuid(): string
    {
        global $DIC;
        $storage = $DIC->filesystem()->storage();
        $filePath = $this->getProjectDataPath();

        if (!$storage->has($filePath)) {
            return '';
        }

        $data = json_decode($storage->read($filePath), true);
        return $data['uuid'] ?? '';
    }

    private function requestScormBuild(string $uuid): ?array
    {
        $scormerUrl = $this->ScormerBaseUrl;
        $accessKey = $this->ScormerAccessKeyEditor;

        $ch = curl_init($scormerUrl . '/api/export/build');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'access_key' => $accessKey,
                'uuid' => $uuid,
            ]),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $result = json_decode($response, true);
        if (!is_array($result) || empty($result['success']) || !isset($result['data'])) {
            return null;
        }

        return $result['data'];
    }

    /**
     * @return string|false
     */
    private function downloadFile(string $url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($content === false || $httpCode !== 200) {
            return false;
        }

        return $content;
    }

    private function createScormObject(
        string $scormVersion,
        string $title,
        int $targetRefId,
        string $zipPath
    ): ?ilObjSAHSLearningModule {
        global $DIC;

        try {
            if ($scormVersion === '2004') {
                $newObj = new ilObjSCORM2004LearningModule();
                $newObj->setSubType('scorm2004');
            } else {
                $newObj = new ilObjSCORMLearningModule();
                $newObj->setSubType('scorm');
            }

            $newObj->setTitle($title);
            $newObj->setDescription('');
            $newObj->create(true);
            $newObj->createReference();
            $newObj->putInTree($targetRefId);
            $newObj->setPermissions($targetRefId);
            $newObj->setOfflineStatus(true);

            $newObj->createDataDirectory();

            $destZip = $newObj->getDataDirectory() . '/' . basename($zipPath);
            copy($zipPath, $destZip);

            $archives = $DIC->legacyArchives();
            $archives->unzip($destZip, $newObj->getDataDirectory(), false, false, false);

            ilFileUtils::renameExecutables($newObj->getDataDirectory());

            $manifestTitle = $newObj->readObject();
            if ($manifestTitle !== '') {
                ilObject::_writeTitle($newObj->getId(), $manifestTitle);
            }

            $newObj->setLearningProgressSettingsAtUpload();

            return $newObj;
        } catch (\Exception $e) {
            ilLoggerFactory::getLogger('sahs')->error(
                'Scormer SCORM import failed: ' . $e->getMessage()
            );
            return null;
        }
    }

    function cancelTarget(): void
    {
        global $DIC;
        $DIC->ctrl()->redirect($this, "showEdit");
    }

    function showSuccess(): void
    {
        global $DIC;

        $ilTabs = $DIC->tabs();
        $ilTabs->activateTab("showEdit");

        $newRefId = (int) ($_GET["new_ref_id"] ?? 0);

        if ($newRefId <= 0) {
            $DIC->ctrl()->redirect($this, "showEdit");
            return;
        }

        $objId = ilObject::_lookupObjId($newRefId);
        $title = ilObject::_lookupTitle($objId);

        $settingsLink = ilLink::_getStaticLink($newRefId, 'sahs');
        $editorLink = $DIC->ctrl()->getLinkTarget($this, "showEdit");

        $html = '<div style="margin: 20px; padding: 30px; background: #f0f9f0; border: 1px solid #4caf50; border-radius: 8px;">'
            . '<h3 style="color: #2e7d32; margin-top: 0;">SCORM-Lernmodul erfolgreich angelegt</h3>'
            . '<p>Das SCORM-Lernmodul <strong>&bdquo;' . htmlspecialchars($title) . '&ldquo;</strong> wurde erfolgreich erstellt.</p>'
            . '<p style="margin-top: 20px;">'
            . '<a href="' . $settingsLink . '" class="btn btn-default btn-primary" style="margin-right: 10px;">Einstellungen des Lernmoduls &ouml;ffnen</a>'
            . '<a href="' . $editorLink . '" class="btn btn-default">Zur&uuml;ck zum Editor</a>'
            . '</p>'
            . '</div>';

        $DIC->ui()->mainTemplate()->setContent($html);
    }

    function myOutput(): void
    {
        $this->tabs->activateTab("showContent"); // optional
        #$html = "<h2>Hallo aus dem Deeplink</h2>";
        #$this->tpl->setContent($html);
	$this->showContent();
    }

    /**
     * After object has been created -> jump to this command
     */
    function getAfterCreationCmd(): string
    {
        return "projects";
    }

    /**
     * Get standard command
     */
    function getStandardCmd(): string
    {
        return "projects";
    }

    /**
     * show information screen
     */
    function infoScreen(): void
    {
        $this->tabs->setTabActive("info_short");

        $this->checkPermission("visible");

        $info = new ilInfoScreenGUI($this);

        $info->addSection($this->txt("plugininfo"));
        $info->addProperty('Name', 'Scormer');
        $info->addProperty('Version', xsco_version);
        $info->addProperty('Developer', 'Aresch Yavari');
        $info->addProperty('Kontakt', 'ay@databay.de');
        $info->addProperty('&nbsp;', 'Databay AG');
        $info->addProperty('&nbsp;', '<img src="http://www.iliasnet.de/Pluginmanager/logo.php?plug=Scormer" alt="Databay AG" title="Databay AG" />');
        $info->addProperty('&nbsp;', "http://www.databay.de");

        $info->enablePrivateNotes();

        $this->lng->loadLanguageModule("meta");

        $this->addInfoItems($info);

        $this->ctrl->forwardCommand($info);
    }

    /**
     * Set tabs
     */
    function setTabs(): void
    {
        $this->tabs->addTab("showContent", "Vorschau", $this->ctrl->getLinkTarget($this, "showContent"));
        if ($this->access->checkAccess("write", "", $this->object->getRefId())) {
            $this->tabs->addTab("showEdit", "Bearbeiten", $this->ctrl->getLinkTarget($this, "showEdit"));
            $this->tabs->addTab("export", $this->txt("export"), $this->ctrl->getLinkTarget($this, "targetSelect"));
            $this->tabs->addTab("properties", $this->txt("properties"), $this->ctrl->getLinkTarget($this, "editProperties"));
        }

        $this->addPermissionTab();
    }


    /**
     * Edit Properties. This commands uses the form class to display an input form.
     */
    function editProperties()
    {
        $this->tabs->activateTab("properties");
        $this->initPropertiesForm();
        $this->getPropertiesValues();
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * Init  form.
     *
     * @param int $a_mode Edit Mode
     */
    public function initPropertiesForm()
    {
        $this->form = new ilPropertyFormGUI();

        $ti = new ilTextInputGUI($this->txt("title"), "title");
        $ti->setRequired(true);
        $this->form->addItem($ti);

        $ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
        $this->form->addItem($ta);

        $this->form->addCommandButton("updateProperties", $this->txt("save"));

        $this->form->setTitle($this->txt("edit_properties"));
        $this->form->setFormAction($this->ctrl->getFormAction($this));
    }

    /**
     * Get values for edit properties form
     */
    function getPropertiesValues()
    {
        $values["title"] = $this->object->getTitle();
        $values["desc"] = $this->object->getDescription();
        $this->form->setValuesByArray($values);
    }

    /**
     * Update properties
     */
    public function updateProperties()
    {
        $this->initPropertiesForm();
        if ($this->form->checkInput()) {
            $this->object->setTitle($this->form->getInput("title"));
            $this->object->setDescription($this->form->getInput("desc"));
            $this->object->update();
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            $this->ctrl->redirect($this, "editProperties");
        }

        $this->form->setValuesByPost();
        $this->tpl->setContent($this->form->getHtml());
    }


    /**
     * Show content
     */
    function showContent()
    {
        $this->tabs->activateTab("showContent");

        $data = $this->getOrCreateProjectData();

        // Rolle: 'editor' für Vollzugriff, 'preview' für reine Vorschau
        $role = 'preview';
        $accessKey = $this->ScormerAccessKeyPreview; // Passender Key für die Rolle aus config/app.php



        $scormerUrl = $this->ScormerBaseUrl;
        $projectUuid = $data["uuid"];
        $token = $this->getToken($role, $accessKey, $projectUuid);

        $myUserId = '5';
        $myUserName = 'JohnDoe';

        if ($token === '') {
            $html = 'Kein Token in der Antwort erhalten';
        } else {

            #$html = $data["uuid"]."<br>".$response;

            $go = $scormerUrl . '/' . $projectUuid . '?token=' . $token;
            #$html = "<a style='display:inline-block;margin: 20px;padding: 20px;border: solid 1px gray;' href='".$go."' target='_blank'>SCORMer - Preview öffnen</a>";
            $html = "<iframe src='" . $go . "' style='width: 100%;height: 700px;'></iframe>";
        }

        $this->tpl->setContent($html);
    }

    /**
     * Show content
     */
    function showEdit()
    {
        $this->tabs->activateTab("showEdit");

        $data = $this->getOrCreateProjectData();

        // Rolle: 'editor' für Vollzugriff, 'preview' für reine Vorschau
        $role = 'editor';
        $accessKey = $this->ScormerAccessKeyEditor; // Passender Key für die Rolle aus config/app.php

        #$tpl->setContent($token);return;

        $scormerUrl = $this->ScormerBaseUrl;
        $projectUuid = $data["uuid"];
        $token = $this->getToken($role, $accessKey, $projectUuid);

        $myUserId = '5';
        $myUserName = 'JohnDoe';

        if ($token === '') {
            $html = 'Kein Token in der Antwort erhalten';
        } else {

            #$html = $data["uuid"]."<br>".$response;

            $go = $scormerUrl . '/' . $projectUuid . '?token=' . $token;
            $html = "<a style='display:inline-block;margin: 20px;padding: 20px;border: solid 1px gray;' 
                        href='" . $go . "' 
                        target='scormereditor' 
                        class='scormereditorlink' 
						>SCORMer - Editor öffnen</a>";
            $html .= "<div style='display:none;margin: 20px;padding: 20px;border: solid 1px gray;' class='scormereditorlinkrefresh'>Bitte neu laden.</div>";

            $html .= "<script>


function openScormerEditor(e) {
	e.preventDefault(); 
	const childWindow = window.open('" . $go . "', 'scormereditor', 'resizable=yes');
		
	// Auf Nachrichten vom Kind-Fenster hören
	window.addEventListener('message', (event) => {
		console.log(event);
		// Wichtig: Origin prüfen!
		if (event.origin !== 'https://scormer.iliasnet.de' && event.origin !== 'https://scormer.invorbereitung.de') return;
	
		console.log('Nachricht vom Kind:', event.data);
		
		// Beispiel: auf Daten reagieren
		if (event.data.type === 'EXPORT_DONE') {
			// ... verarbeiten
			console.log(event.data);
			window.location = event.data.payload.goto;
		}
	});
}


document.querySelectorAll('.scormereditorlink').forEach(function(el) {
    el.addEventListener('click', openScormerEditor);
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.scormereditorlink').forEach(function (el) {
        el.addEventListener('click', function () {
            var self = this;
            setTimeout(function () {
                self.style.display = 'none';
                
                 // andere Elemente einblenden
                document.querySelectorAll('.scormereditorlinkrefresh').forEach(function (refreshEl) {
                    refreshEl.style.display = 'inline-block';
                });
                
            }, 1000);
        });
    });
});
</script>";
        }

        $this->tpl->setContent($html);
    }

    private function getAiFieldsForToken(): array
    {
        $fields = [
            'ai_provider' => $this->AiTextProvider === 'openai' ? 'openai' : 'default',
            'ai_image_provider' => $this->AiImageProvider === 'openai' ? 'openai' : 'default',
        ];

        if ($this->AiTextProvider === 'openai') {
            $fields['ai_endpoint_url'] = $this->AiEndpointUrl;
            $fields['ai_api_key'] = $this->AiApiKey;
            $fields['ai_model'] = $this->AiModel;
        }

        if ($this->AiImageProvider === 'openai') {
            $fields['ai_image_endpoint_url'] = $this->AiImageEndpointUrl;
            $fields['ai_image_api_key'] = $this->AiImageApiKey;
            $fields['ai_image_model'] = $this->AiImageModel;
        }

        return $fields;
    }

    private function getToken($role, $accessKey, $projectUuid)
    {

        global $DIC;

        $scormerUrl = $this->ScormerBaseUrl;
        $ilUser = $DIC->user();

        $postFields = array_merge([
            'access_key' => $accessKey,
            'role' => $role,
            'user_id' => (string) $ilUser->getId(),
            'user_name' => $ilUser->getLogin(),
            'project_uuid' => $projectUuid,
            'session_id' => session_id(),
            'ref_id' => $_GET['ref_id'],
            'title' => $this->object->getTitle(),
            'goto_link' => 'https://' . $_SERVER['HTTP_HOST'] . '/goto.php?target=' . $this->getType() . '_' . $_GET['ref_id'],
        ], $this->getAiFieldsForToken());

        // --- Token vom Scormer anfordern ---
        $ch = curl_init($scormerUrl . '/api/auth/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($postFields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        #mail("ay@databay.de", "the response", print_r($response, 1));

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $token = "";
        if ($httpCode !== 201 || $response === false) {
            $html = 'Fehler beim Anfordern des Tokens (HTTP ' . $httpCode . ')';
        } else {

            $result = json_decode($response, true);
            $token = $result['data']['token'] ?? '';

            if ($token === '') {
                $html = 'Kein Token in der Antwort erhalten';
            }
        }
        return $token;
    }

    private function uuidv4()
    {
        $data = random_bytes(16);

        // Version auf 0100 setzen (UUID v4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

        // Variant auf 10xxxxxx setzen
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function translate($html)
    {
        $anz = preg_match_all("/#!(.*?)!#/", $html, $matches);

        for ($i = 0; $i < $anz; $i++) {
            $html = str_replace($matches[0][$i], $this->txt($matches[1][$i]), $html);
        }

        return $html;
    }

    public static function _goto(array $a_target): void
    {
        global $DIC;
        $ilCtrl = $DIC->ctrl();
        $ilAccess = $DIC->access();
        $lng = $DIC->language();
        $main_tpl = $DIC->ui()->mainTemplate();

        $parts      = explode('_', $a_target[0]);
        $ref_id     = (int) $parts[0];
        $class_name = $a_target[1] ?? 'ilObjScormerGUI';

        $cmd = $parts[1] ?? 'myOutput';

        $isApiRequest = ($_GET['api'] ?? '') === 'json';

        if ($isApiRequest) {
            self::handleApiRequest($ref_id, $cmd, $ilAccess, $DIC);
            return;
        }

        if ($ilAccess->checkAccess('read', '', $ref_id)) {
            $ilCtrl->setTargetScript('ilias.php');
            $ilCtrl->setParameterByClass($class_name, 'ref_id', $ref_id);
            if ($cmd !== '') {
                $ilCtrl->redirectByClass(
                    ['ilobjplugindispatchgui', $class_name],
                    $cmd
                );
            }
            $ilCtrl->redirectByClass(
                ['ilobjplugindispatchgui', $class_name],
                ''
            );
        } elseif ($ilAccess->checkAccess('visible', '', $ref_id)) {
            $ilCtrl->setTargetScript('ilias.php');
            $ilCtrl->setParameterByClass($class_name, 'ref_id', $ref_id);
            $ilCtrl->redirectByClass(
                ['ilobjplugindispatchgui', $class_name],
                'infoScreen'
            );
        } else {
            $main_tpl->setOnScreenMessage('failure', sprintf(
                $lng->txt('msg_no_perm_read_item'),
                ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id))
            ));
            ilObjectGUI::_gotoRepositoryRoot();
        }
    }

    private static function sendJson(array $data, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function handleApiRequest(int $ref_id, string $cmd, $ilAccess, $DIC): void
    {
        if (!$ilAccess->checkAccess('read', '', $ref_id)) {
            self::sendJson(['status' => 'error', 'message' => 'Kein Zugriff'], 403);
            return;
        }

        $ilUser = $DIC->user();

        switch ($cmd) {
            case 'userInfo':
                self::sendJson([
                    'status' => 'ok',
                    'data' => [
                        'user_id'    => $ilUser->getId(),
                        'login'      => $ilUser->getLogin(),
                        'firstname'  => $ilUser->getFirstname(),
                        'lastname'   => $ilUser->getLastname(),
                        'email'      => $ilUser->getEmail(),
                    ],
                ]);
                break;

            case 'objectInfo':
                $obj_id = ilObject::_lookupObjId($ref_id);
                self::sendJson([
                    'status' => 'ok',
                    'data' => [
                        'ref_id'      => $ref_id,
                        'obj_id'      => $obj_id,
                        'title'       => ilObject::_lookupTitle($obj_id),
                        'description' => ilObject::_lookupDescription($obj_id),
                        'type'        => ilObject::_lookupType($obj_id),
                    ],
                ]);
                break;

            case 'sessionCheck':
                self::sendJson([
                    'status' => 'ok',
                    'data' => [
                        'session_valid' => true,
                        'user_id'       => $ilUser->getId(),
                        'login'         => $ilUser->getLogin(),
                    ],
                ]);
                break;

            default:
                self::sendJson([
                    'status' => 'error',
                    'message' => 'Unbekannter API-Befehl: ' . $cmd,
                ], 400);
                break;
        }
    }
}
