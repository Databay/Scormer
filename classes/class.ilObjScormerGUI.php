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
class ilObjScormerGUI extends ilObjectPluginGUI {
	protected $activeCmd = "projects";
	protected $ScormerBaseUrl = "https://tools.databay.de/scormer";
	protected $proxyTarget = "";
	protected $listTarget = "";
	protected $apiTarget = "";
	protected $indexTarget = "";
	/**
	 * Initialisation
	 */
	protected function afterConstructor(): void {
	}

	/**
	 * Get type.
	 */
	final function getType(): string {
		return "xsco";
	}

	/**
	 * Handles all commmands of this class, centralizes permission checks
	 */
	function performCommand(string $cmd): void {
		global $ilCtrl;

		$this->proxyTarget = $ilCtrl->getLinkTarget($this, "proxy");
		$this->listTarget = $ilCtrl->getLinkTarget($this, "projects");
		$this->indexTarget = $ilCtrl->getLinkTarget($this, "project");
		$this->apiTarget = $ilCtrl->getLinkTarget($this, "api");

		$this->activeCmd = $cmd;
		switch($cmd) {
			case "showContent":
			case "api":
			case "proxy":
			case "projects":
			case "project":
			case "projectMetadata":
			case "textablage":
			case "medien":
			case "slides":
			case "themes":
			case "export":
				$this->checkPermission("write");
				$this->$cmd();
				break;

			case "editProperties":
			case "updateProperties":

				$this->checkPermission("write");
				$this->$cmd();
				break;

		}
	}

	/**
	 * After object has been created -> jump to this command
	 */
	function getAfterCreationCmd(): string {
		return "projects";
	}

	/**
	 * Get standard command
	 */
	function getStandardCmd(): string {
		return "projects";
	}

	/**
	 * show information screen
	 */
	function infoScreen(): void {
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
	function setTabs(): void {
		global $ilTabs, $ilCtrl, $ilAccess;

		$ilTabs->addTab("projects", "Projekte", $ilCtrl->getLinkTarget($this, "projects"));
		if($_GET["cmd"]!="projects") {
			$ilTabs->addTab("project", "Projekt-Metadaten", $ilCtrl->getLinkTarget($this, "project")."&view=meta&project=".$_GET["project"]);
			$ilTabs->addTab("themes", "Themes", $ilCtrl->getLinkTarget($this, "project")."&view=themes&project=".$_GET["project"]);
			$ilTabs->addTab("textablage", "Textablage", $ilCtrl->getLinkTarget($this, "project")."&view=textablage&project=".$_GET["project"]);
			$ilTabs->addTab("medien", "Medien", $ilCtrl->getLinkTarget($this, "project")."&view=medien&project=".$_GET["project"]);
			$ilTabs->addTab("slides", "Slides", $ilCtrl->getLinkTarget($this, "project")."&view=slides&project=".$_GET["project"]);
			$ilTabs->addTab("export", "Vorschau/Export", $ilCtrl->getLinkTarget($this, "project")."&view=export&project=".$_GET["project"]);
		}

		if ($ilAccess->checkAccess("write", "", $this->object->getRefId())) {
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		$this->addPermissionTab();
	}

	function api() {
		global $DIC;

		// Request-Objekt holen
		$request = $DIC->http()->request();

// Alle Query-Parameter als Array
		$queryParams = $request->getQueryParams();
		$postParams = $request->getParsedBody();

		$file = $_GET["apifile"];
		if ($file != "") {
			$url = $this->ScormerBaseUrl . "/api" . $file;

			#$queryParams = json_decode(json_encode($_GET),true);
			#unset($queryParams['file']);

			if (!empty($queryParams)) {
				$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
			}
			#if(stristr($url, "media.php"))  {echo $url;exit;}
			$options = [
				'http' => [
					'method' => $_SERVER['REQUEST_METHOD'],
					'ignore_errors' => true
				]
			];

 		if ($_SERVER['REQUEST_METHOD'] === 'POST'
				|| $_SERVER['REQUEST_METHOD'] === 'PUT'
				|| $_SERVER['REQUEST_METHOD'] === 'DELETE'
			) {
				$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : 'application/x-www-form-urlencoded';

 			// multipart/form-data via cURL weiterleiten (auch ohne Dateien, da php://input bei multipart leer ist)
				if (strpos($contentType, 'multipart/form-data') !== false) {
					//$postFields = json_decode(json_encode($_POST),true);
					$postFields = $postParams;
					foreach ($_FILES as $key => $fileInfo) {
						if ($fileInfo['error'] === UPLOAD_ERR_OK) {
							$postFields[$key] = new \CURLFile(
								$fileInfo['tmp_name'],
								$fileInfo['type'],
								$fileInfo['name']
							);
						}
					}

					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
					curl_setopt($ch, CURLOPT_HEADER, true);
					$response = curl_exec($ch);
					$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
					$responseHeaders = substr($response, 0, $headerSize);
					$res = substr($response, $headerSize);
					curl_close($ch);

					foreach (explode("\r\n", $responseHeaders) as $header) {
						if (stripos($header, 'Content-Type:') === 0) {
							header($header);
						}
					}

					echo $res;
					exit;
				}

				$options['http']['header'] = "Content-Type: " . $contentType . "\r\n";
				$options['http']['content'] = file_get_contents('php://input');
			}

			$context = stream_context_create($options);
			$res = file_get_contents($url, false, $context);

			if (isset($http_response_header)) {
				foreach ($http_response_header as $header) {
					if (stripos($header, 'Content-Type:') === 0) {
						header($header);
					}
				}
			}

			echo $res;
		}
		exit;
	}

	function proxy() {
		$file = $_GET["apifile"];
		if ($file != "") {
			$url = $this->ScormerBaseUrl . "/" . $file;
			$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

			$mime_types = [
				'css' => 'text/css',
				'js' => 'application/javascript',
				'json' => 'application/json',
				'png' => 'image/png',
				'jpg' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'gif' => 'image/gif',
				'svg' => 'image/svg+xml'
			];

			if (isset($mime_types[$ext])) {
				header('Content-Type: ' . $mime_types[$ext]);
			}

			$res = file_get_contents($url);
			$res = str_replace(".php?", ".php&", $res);

			$res = str_replace('index.php?', '', $res);

			echo $res;
		}
		exit;
	}

	function projects() {
		global $tpl, $ilTabs;
		$ilTabs->activateTab("projects");

		$this->dataDir = ilFileUtils::getDataDir().'/Scormer';
		if (!file_exists($this->dataDir)) {
			ilFileUtils::makeDirParents($this->dataDir);
		}

		$html = file_get_contents($this->ScormerBaseUrl."/list.php");

		$html = str_replace('project.php?project', 'project.php&project', $html);
		$html = str_replace('href="list.php?', 'href="' . $this->listTarget . '&', $html);
		$html = str_replace('href="list.php"', 'href="' . $this->listTarget . '"', $html);
		$html = str_replace('href="index.php?', 'href="' . $this->indexTarget . '&', $html);
		$html = str_replace('href="index.php"', 'href="' . $this->indexTarget . '"', $html);
		$html = str_replace("href = 'index.php?", "href = '" . $this->indexTarget . "&", $html);
		$html = str_replace('href="assets/', 'href="' . $this->proxyTarget . '&apifile=assets/', $html);
		$html = str_replace('src="assets/', 'src="' . $this->proxyTarget . '&apifile=assets/', $html);

		$html = str_replace('##ILIASAPIBASE##', $this->apiTarget."&project=".$_GET["project"]."&apifile=", $html);
		$html = str_replace('##ILIASAPIBASENOUUID##', $this->apiTarget."&apifile=", $html);

		$tpl->setContent($html);
	}

	function project() {
		global $tpl, $ilTabs;
		#$this->projectMetadata();

		$html = file_get_contents($this->ScormerBaseUrl."/index.php?project=".$_GET["project"]);

		$html = str_replace('href="list.php?', 'href="'.$this->listTarget.'&', $html);
		$html = str_replace('href="list.php"', 'href="'.$this->listTarget.'"', $html);
		$html = str_replace('href="index.php?', 'href="'.$this->indexTarget.'&', $html);
		$html = str_replace('href="index.php"', 'href="'.$this->indexTarget.'"', $html);
		$html = str_replace("href = 'index.php?", "href = '" . $this->indexTarget . "&", $html);
		$html = str_replace('href="assets/', 'href="'.$this->proxyTarget.'&apifile=assets/', $html);
		$html = str_replace('src="assets/', 'src="'.$this->proxyTarget.'&apifile=assets/', $html);

		$html = str_replace('##ILIASAPIBASE##', $this->apiTarget."&project=".$_GET["project"]."&apifile=", $html);
		$html = str_replace('##ILIASAPIBASENOUUID##', $this->apiTarget."&apifile=", $html);

		$tpl->setContent($html);
		if(isset($_GET["view"])) {
			$view = $_GET["view"];
			$ilTabs->activateTab($view);
		} else {
			$ilTabs->activateTab("project");
		}
//		if (method_exists($this, $view)) {
//		}
//		$this->$view();

	}

	function projectMetadata() {
		global $tpl, $ilTabs;
		$ilTabs->activateTab("projectMetadata");

	}
	function textablage() {
		global $tpl, $ilTabs;
		$ilTabs->activateTab("textablage");
	}
	function medien() {
		global $tpl, $ilTabs;
		$ilTabs->activateTab("medien");
	}
	function themes() {
		global $tpl, $ilTabs;
		$ilTabs->activateTab("themes");
	}
	function slides() {
		global $tpl, $ilTabs;
		$ilTabs->activateTab("slides");
	}
	function export() {
		global $tpl, $ilTabs;
		$ilTabs->activateTab("export");
	}

	/**
	 * Edit Properties. This commands uses the form class to display an input form.
	 */
	function editProperties() {
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
	public function initPropertiesForm() {
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
	function getPropertiesValues() {
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$this->form->setValuesByArray($values);
	}

	/**
	 * Update properties
	 */
	public function updateProperties() {
		global $tpl, $lng, $ilCtrl;

		$this->initPropertiesForm();
		if ($this->form->checkInput()) {
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}

	/**
	 * Show content
	 */
	function showContent() {

		$_GET["view"] = "project";
		$this->projects();
		return;

		global $tpl, $ilTabs;

		$ilTabs->activateTab("content");

		$this->dataDir = ilFileUtils::getDataDir().'/Scormer';
		if (!file_exists($this->dataDir)) {
			ilFileUtils::makeDirParents($this->dataDir);
		}

		$fn = $this->dataDir.'/Scormer_'.$this->object->getRefId().'.json';

		$data = "";
		if (file_exists($fn)) {
			$data = file_get_contents($fn);
		}

		if ($data == "") {
			$data = '{"edges": [], "nodes": {"root": {"id": "root", "title":"Scormer", "x":0, "y":0}}}';
		}

		$D = json_decode($data, true);
		$intern = array();
		foreach ($D['nodes'] as $key => $node) {
			if (isset($node["linktype"]) && $node["linktype"] == "intern") {
				if (is_file("./classes/class.ilLink.php")) {
					include_once("./classes/class.ilLink.php");
				} else {
					include_once("./Services/Link/classes/class.ilLink.php");
				}
				$interlink = ilLink::_getLink($node['linktarget']);

				$intern[$node['linktarget']] = $interlink;
			}
		}

		$html = file_get_contents(dirname(__FILE__)."/../templates/mm_lang.html");
		$html .= file_get_contents(dirname(__FILE__)."/../templates/mm.html");
		$html = str_replace("#ScormerDATA#", $data, $html);
		$html = str_replace("#INTERNLINKS#", json_encode($intern), $html);

		$html = $this->translate($html);

		$tpl->setContent($html);
	}

	/**
	 * Show content
	 */
	function showEdit() {
		global $tpl, $ilTabs;

		$ilTabs->activateTab("edit");

		$this->dataDir = ilFileUtils::getDataDir().'/Scormer';
		if (!file_exists($this->dataDir)) {
			ilFileUtils::makeDirParents($this->dataDir);
		}

		$fn = $this->dataDir.'/Scormer_'.$this->object->getRefId().'.json';



		$html = file_get_contents(dirname(__FILE__)."/../templates/mm_lang.html");
		$html .= file_get_contents(dirname(__FILE__)."/../templates/mm_edit.html");

		#$html = str_replace("#ScormerDATA#", $data, $html);

		#$html = $this->translate($html);

		$tpl->setContent($html);
	}

	private function translate($html) {
		$anz = preg_match_all("/#!(.*?)!#/", $html, $matches);

		for ($i = 0; $i < $anz; $i++) {
			$html = str_replace($matches[0][$i], $this->txt($matches[1][$i]), $html);
		}

		return $html;
	}

}

?>
