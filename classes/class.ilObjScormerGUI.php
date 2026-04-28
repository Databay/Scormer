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
    protected $activeCmd = "projects";
    protected $ScormerBaseUrl = "https://tools.databay.de/scormer";
    protected $proxyTarget = "";
    protected $listTarget = "";
    protected $apiTarget = "";
    protected $indexTarget = "";
    /**
     * Initialisation
     */
    protected function afterConstructor(): void {}

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
        global $ilCtrl;

        $this->apiTarget = $ilCtrl->getLinkTarget($this, "api");

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
                $this->checkPermission("write");
                $this->$cmd();
                break;
        }
    }

    function targetSelect(): void
    {
        global $DIC;

        $ilTabs = $DIC->tabs();
        $ilTabs->activateTab("showEdit");

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

        $DIC->ui()->mainTemplate()->setOnScreenMessage(
            "success",
            "Ziel ausgewählt: " . ilObject::_lookupTitle(ilObject::_lookupObjId($target_ref_id))
                . " (Ref-ID: " . $target_ref_id . ")",
            true
        );
        $DIC->ctrl()->redirect($this, "showEdit");
    }

    function cancelTarget(): void
    {
        global $DIC;
        $DIC->ctrl()->redirect($this, "showEdit");
    }

    function myOutput(): void
    {
        global $tpl, $ilTabs;
        $ilTabs->activateTab("showContent"); // optional
        $html = "<h2>Hallo aus dem Deeplink</h2>";
        $tpl->setContent($html);
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
        global $ilAccess, $ilUser, $lng, $ilCtrl, $tpl, $ilTabs;

        $ilTabs->setTabActive("info_short");

        $this->checkPermission("visible");

        include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
        $info = new ilInfoScreenGUI($this);

        $info->addSection($this->txt("plugininfo"));
        $info->addProperty('Name', 'Scormer');
        $info->addProperty('Version', xsco_version);
        $info->addProperty('Developer', 'Aresch Yavari');
        $info->addProperty('Kontakt', 'ay@databay.de');
        $info->addProperty('&nbsp;', 'Databay AG');
        $info->addProperty('&nbsp;', '<img src="http://www.iliasnet.de/Pluginmanager/logo.php?plug=Scormer" alt="Databay AG" title="Databay AG" />');
        $info->addProperty('&nbsp;', "http://www.iliasnet.de");

        $info->enablePrivateNotes();

        $lng->loadLanguageModule("meta");

        $this->addInfoItems($info);

        $ret = $ilCtrl->forwardCommand($info);
    }

    /**
     * Set tabs
     */
    function setTabs(): void
    {
        global $ilTabs, $ilCtrl, $ilAccess;

        $ilTabs->addTab("showContent", "Vorschau", $ilCtrl->getLinkTarget($this, "showContent"));
        $ilTabs->addTab("showEdit", "Bearbeiten", $ilCtrl->getLinkTarget($this, "showEdit"));

        if ($ilAccess->checkAccess("write", "", $this->object->getRefId())) {
            $ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
        }

        $this->addPermissionTab();
    }


    /**
     * Edit Properties. This commands uses the form class to display an input form.
     */
    function editProperties()
    {
        global $tpl, $ilTabs;

        $ilTabs->activateTab("properties");
        $this->initPropertiesForm();
        $this->getPropertiesValues();
        $tpl->setContent($this->form->getHTML());
    }

    /**
     * Init  form.
     *
     * @param int $a_mode Edit Mode
     */
    public function initPropertiesForm()
    {
        global $ilCtrl;

        include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
        $this->form = new ilPropertyFormGUI();

        $ti = new ilTextInputGUI($this->txt("title"), "title");
        $ti->setRequired(true);
        $this->form->addItem($ti);

        $ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
        $this->form->addItem($ta);

        $this->form->addCommandButton("updateProperties", $this->txt("save"));

        $this->form->setTitle($this->txt("edit_properties"));
        $this->form->setFormAction($ilCtrl->getFormAction($this));
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
        global $tpl, $lng, $ilCtrl;

        $this->initPropertiesForm();
        if ($this->form->checkInput()) {
            $this->object->setTitle($this->form->getInput("title"));
            $this->object->setDescription($this->form->getInput("desc"));
            $this->object->update();
            $tpl->setOnScreenMessage("success", $lng->txt("msg_obj_modified"), true);
            $ilCtrl->redirect($this, "editProperties");
        }

        $this->form->setValuesByPost();
        $tpl->setContent($this->form->getHtml());
    }


    /**
     * Show content
     */
    function showContent()
    {
        global $tpl, $ilTabs;

        $ilTabs->activateTab("showContent");

        $this->dataDir = ilFileUtils::getDataDir() . '/Scormer';
        if (!file_exists($this->dataDir)) {
            ilFileUtils::makeDirParents($this->dataDir);
        }

        $fn = $this->dataDir . '/Scormer_' . $this->object->getRefId() . '.json';
        $data = "";
        if (file_exists($fn)) {
            $data = json_decode(file_get_contents($fn), true);
        }
        if ($data === "") {
            $data = [];
            $data["uuid"] = $this->uuidv4();
            file_put_contents($fn, json_encode($data, JSON_PRETTY_PRINT));
        }

        // Rolle: 'editor' für Vollzugriff, 'preview' für reine Vorschau
        $role = 'preview';
        $accessKey = 'dev-preview-key'; // Passender Key für die Rolle aus config/app.php

        $token = $this->getToken($role, $accessKey);


        $scormerUrl = 'https://scormer.invorbereitung.de';
        $projectUuid = $data["uuid"];

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

        $tpl->setContent($html);
    }

    /**
     * Show content
     */
    function showEdit()
    {
        global $tpl, $ilTabs;

        $ilTabs->activateTab("showEdit");

        $this->dataDir = ilFileUtils::getDataDir() . '/Scormer';
        if (!file_exists($this->dataDir)) {
            ilFileUtils::makeDirParents($this->dataDir);
        }

        $fn = $this->dataDir . '/Scormer_' . $this->object->getRefId() . '.json';
        $data = "";
        if (file_exists($fn)) {
            $data = json_decode(file_get_contents($fn), true);
        }
        if ($data === "") {
            $data = [];
            $data["uuid"] = $this->uuidv4();
            file_put_contents($fn, json_encode($data, JSON_PRETTY_PRINT));
        }

        // Rolle: 'editor' für Vollzugriff, 'preview' für reine Vorschau
        $role = 'editor';
        $accessKey = 'dev-editor-key'; // Passender Key für die Rolle aus config/app.php

        $token = $this->getToken($role, $accessKey);
        #$tpl->setContent($token);return;

        $scormerUrl = 'https://scormer.invorbereitung.de';
        $projectUuid = $data["uuid"];

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
                        onclick='event.preventDefault(); window.open(\"" . $go . "\", \"scormereditor\", \"resizable=yes\");'>SCORMer - Editor öffnen</a>";
            $html .= "<div style='display:none;margin: 20px;padding: 20px;border: solid 1px gray;' class='scormereditorlinkrefresh'>Bitte neu laden.</div>";

            $html .= "<script>
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

        $tpl->setContent($html);
    }

    private function getToken($role, $accessKey)
    {

        $scormerUrl = 'https://scormer.invorbereitung.de';

        $myUserId = '5';
        $myUserName = 'JohnDoe';


        // --- Token vom Scormer anfordern ---
        $ch = curl_init($scormerUrl . '/api/auth/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'access_key' => $accessKey,
                'role' => $role,
                'user_id' => $myUserId,
                'user_name' => $myUserName,
                "session_id" => session_id(),
                "ref_id" => $_GET['ref_id'],
                "goto_link" => "https://" . $_SERVER['HTTP_HOST'] . "/goto.php?target=xsco_" . $_GET['ref_id'],
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);

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
