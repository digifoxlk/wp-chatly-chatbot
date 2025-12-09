# Chatly

Chatly provides a simple chat bubble widget that offers configurable branding and a list of quick, predefined question → answer responses. It is designed to be lightweight and easy to configure from the WordPress admin.

## Features
- Easy admin UI to add predefined question → answer pairs
- Lightweight front-end widget with configurable branding and position
- Non-blocking, script-based integration (no external AI required)

## Installation
1. Upload the `chatly` plugin folder to the `/wp-content/plugins/` directory, or upload the ZIP via the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the Chatly menu in the admin sidebar to configure branding, quick responses and appearance.

## Usage
- Add short question/answer entries in the admin area under Chatly → Responses. These appear as quick-action chips in the chat widget.
- Customize `Branding Name`, `Brand Logo`, `Chat Avatar`, `Primary Color`, and `Bubble Position` in Chatly → Settings.

## Developer notes
- Plugin main file: `chatly.php`
- Front-end scripts & styles: `includes/js/main.js`, `includes/css/styles.css`
- Images: `includes/img/`

## License
This plugin is distributed under the `GNU General Public License v3.0 (GPL-3.0)`. You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/gpl-3.0.en.html>.

Copyright (C) 2025 DigiFox Technologies
