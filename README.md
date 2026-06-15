# Scormer ILIAS Plugin

ILIAS repository object plugin for integrating [Scormer](https://www.databay.de/) into ILIAS. Scormer lets you create AI-assisted SCORM learning modules from text, PDFs, and media — edit them directly from the ILIAS repository, preview them, and import them as SCORM learning modules in ILIAS.

| | |
|---|---|
| **Plugin ID** | `xsco` |
| **Version** | 1.1.0 |
| **ILIAS** | 9.0 – 10.x |
| **Author** | [Databay AG](https://www.databay.de/) |
| **License** | See [license.txt](license.txt) |

## Features

- Create and manage **Scormer objects** in the ILIAS repository
- **AI support** via Databay-hosted AI or an OpenAI-compatible endpoint
- **Preview** and **editing** through the embedded Scormer interface
- **SCORM export** as an ILIAS SCORM learning module (SCORM 1.2 or 2004) into a course or folder

## Requirements

- A working **ILIAS installation** (version 9.0 through 10.x)
- A reachable **Scormer backend instance**
- **API keys** for preview and editing — issued for your Scormer backend instance and required for plugin configuration
- Optional: access to an **OpenAI-compatible API endpoint** for custom AI models (if you choose not to use Databay-hosted AI)


To use the Databay-hosted Scormer backend and obtain the necessary API keys (preview and editor), contact the [Databay AG](https://www.databay.de/) sales team. They will provide access to a Scormer instance and the corresponding keys for your ILIAS installation.



## Installation

### 1. Create the plugin directory

Change to your ILIAS root directory and clone the repository into the designated plugin path. The folder name **must** be `Scormer` (matching the plugin name in ILIAS).

The path to the `Customizing` directory depends on your ILIAS version:

| ILIAS version | Customizing location | Full plugin path |
|---|---|---|
| **9.x** | `<ilias-root>/Customizing/` | `<ilias-root>/Customizing/global/plugins/Services/Repository/RepositoryObject/Scormer` |
| **10.x** | `<ilias-root>/public/Customizing/` | `<ilias-root>/public/Customizing/global/plugins/Services/Repository/RepositoryObject/Scormer` |

Starting with ILIAS 10, the web-accessible document root was moved into `public/`. Customizations that previously lived directly under the ILIAS root (including plugins) now belong under `public/Customizing/`. When upgrading from ILIAS 9 to 10, move your existing `Customizing` folder into `public/` and adjust plugin paths accordingly.

#### ILIAS 9

```bash
cd /path/to/ilias
mkdir -p Customizing/global/plugins/Services/Repository/RepositoryObject
cd Customizing/global/plugins/Services/Repository/RepositoryObject
git clone https://github.com/DatabayAG/Scormer.git Scormer
```

#### ILIAS 10

```bash
cd /path/to/ilias
mkdir -p public/Customizing/global/plugins/Services/Repository/RepositoryObject
cd public/Customizing/global/plugins/Services/Repository/RepositoryObject
git clone https://github.com/DatabayAG/Scormer.git Scormer
composer du
```

Alternatively, download the repository as a ZIP archive, rename the extracted folder to `Scormer`, and place it in the path that matches your ILIAS version (see table above).

### 2. Activate the plugin in ILIAS

1. Log in to ILIAS as an administrator
2. Go to **Administration** → **Extending ILIAS** → **Plugins**
3. Find **Scormer** and **activate** the plugin
4. If needed, clear the ILIAS cache (**Administration** → **System Settings and Tools** → **Cache**)

### 3. Set up permissions

Make sure the relevant roles are allowed to create and edit Scormer objects. Permissions are assigned through the role administration, as with other repository objects.

## Configuration

After activation, plugin settings are available under **Administration** → **Extending ILIAS** → **Plugins** → **Scormer** → **Configure**.

| Setting | Description |
|---|---|
| **Scormer backend URL** | Base URL of your Scormer instance (required), e.g. `https://scormer.iliasnet.de` |
| **API key for preview** | Key for read/preview access |
| **API key for editing** | Key for write/editor access |
| **AI provider** | `Databay-hosted AI` or `OpenAI-compatible endpoint` |
| **Endpoint URL / API key / model** | Only for OpenAI-compatible providers: settings for text and image generation |

Configuration is stored in `Scormer/Scormer_config.json` in the ILIAS file storage.

## Usage

1. In the desired course or folder, choose **Add** → **Scormer**
2. Enter a title and description, then save the object
3. Open the mind map editor and Scormer interface via **Edit**
4. Structure learning content and enhance it with AI assistance
5. Use **Export** to import the finished SCORM package as an ILIAS SCORM learning module into a target folder

## Project structure

```
Scormer/
├── plugin.php              # Plugin metadata and version information
├── classes/                # PHP classes (GUI, configuration, access control)
├── lang/                   # Language files (DE, EN)
├── templates/              # ILIAS templates and icons
├── Scormer/                # Embedded editor resources and configuration
└── license.txt
```

## Uninstallation

1. Delete all Scormer objects in the repository
2. Deactivate and uninstall the plugin under **Administration** → **Extending ILIAS** → **Plugins**
3. Optionally remove the plugin directory:
   - ILIAS 9: `Customizing/global/plugins/Services/Repository/RepositoryObject/Scormer`
   - ILIAS 10: `public/Customizing/global/plugins/Services/Repository/RepositoryObject/Scormer`

## Support

For questions about installation, licensing, or the Scormer backend, contact [Databay AG](https://www.databay.de/).

**Contact:** Aresch Yavari — [ay@databay.de](mailto:ay@databay.de)

## License

Use of this plugin is governed by the terms in [LICENSE](LICENSE). By downloading, installing, or using the software, you agree to these terms.
