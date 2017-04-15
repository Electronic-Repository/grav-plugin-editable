# This is a fork of grav-plugin-editable

It contains a fix to allow content to be editable when nested deep in folder hierarchy.

```
/pages
+	/01.home
+		/page1
+			default.md
+		/page2
+			default.md
+		/page3
+			/sub-category-1
+				default.md
+			/sub-category-2
+				default.md
```

default.md should now be editable, no matter how nested it may be.

## Old-school hacker style explaination:

removed :

```
default:
		throw new \Exception('Unsupported action: "' . $target . '"');
```

I did not read, nor did I spend the time to accurately gauge the original authors intent. It could prove to be an issue later, but all it currently did was throw an exception for an unsupported action, where supported actions are 'pages', 'images', 'files?', 'approve'.

removed page case and transitioned it to default :

```
		$output = $resource->saveContent($page);
		$this->setHeaders();
		echo json_encode($output);
		break;
```

then fixed

```
$page = $pages->dispatch('/' . $this->getIdentifier(), false);
```

using another solution.

This fix was brought to you by being unemployed. PS. I am not maintaining this fork. I have not extensively tested it, but I am using it. If you happen to fix it properly (professionally)? I'd be happy to know about it. Here's my couple of hours back to the community. PS I'm not a php programmer, just a miscellaneous programmer.

Thank you 'bleutzinn' for this excellent plugin.

# Editable Plugin

The **Editable** Plugin for [Grav CMS](http://github.com/getgrav/grav) enables users to edit page content in the front-end. So called 'editable content' can either be a full page or one or more regions on a page.

All page content is stored in normal Grav pages. Any uploaded media is stored in the same folder as the corresponding page.

The Editable plugin functions as a generic core which provides all functionality for a range of front-end editors which can be installed as [add-ons](#add-ons).   
At the moment two editors are available as add-ons: SimpleMDE and ContentTools.

## Demo's

- [SimpleMDE demo](https://wardenier.eu/simplemde-demo/)
- [ContentTools demo](https://wardenier.eu/contenttools-demo/)

Uername: 'john', password: 'Demo0123'. Note that the default content is re-loaded every half hour.


## Installation

The plugin can be installed from within the [Admin Plugin](http://learn.getgrav.org/admin-panel/plugins) by selecting it from the available plugins.

Alternatively it can be installed on the command line via [GPM](http://learn.getgrav.org/advanced/grav-gpm) (Grav Package Manager):

```
$ bin/gpm install editable
```

A third option is to manualy install the plugin by downloading the plugin as a [zip file](https://github.com/bleutzinn/grav-plugin-editable/archive/master.zip) from it's GitHub repository. Copy the zip file to your `/user/plugins` folder, unzip it there and rename the folder to `editable`.

Note that other plugins upon which this plugin depends must be installed also if not installed already. See Dependencies.

At least one Editor Add-on must be installed as well. Read about how to download and install add-ons in the section about [add-ons](#add-ons).   


## Dependencies

This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error), [Problems](https://github.com/getgrav/grav-plugin-problems), [Login](https://github.com/getgrav/grav-plugin-login) and [Shortcode Core](https://github.com/getgrav/grav-plugin-shortcode-core) plugins to operate.

## Configuration

To edit the configuration, first copy `editable.yaml` from the `user/plugins/editable` folder to your `user/config/plugins` folder and only edit that copy.

The default `editable.yaml` file looks like:

```
enabled: true
active_admin: false
editor: simplemde
editable_self: false
```
### Caching

Editable does, unfortunately, not work with caching enabled. **Caching must be disabled at the system level.**

Either turn Caching Off in the Admin panel or use this setting in the `system.yaml` configuration file:

```
cache:
  enabled: false
```


## Add-ons <a id="add-ons"></a>

An Editor Add-on consists of the editor application plus all additional files needed for using that editor with the Editable plugin.

### Installation

Download the [SimpleMDE Add-on](https://github.com/bleutzinn/editable-simplemde-add-on) or the [ContentTools Add-on](https://github.com/bleutzinn/editable-contenttools-add-on) and unzip the downloaded file. Rename the resulting folder to `simplemde` or `contenttools`.

Copy or move the `simplemde` or `contenttools` folder into `/your/site/grav/user/plugins/editable/editors`.


## Front-end User Accounts

To enable users to edit content in the front-end they must be able to login. Follow the instructions for setting up a login page as described with the [Grav Login plugin](https://github.com/getgrav/grav-plugin-login) or the [Private Grav Plugin](https://github.com/Diyzzuf/grav-plugin-private).

Add the required authorization to each user in the user account file:

```
access:
  site:
    login: 'true'
    front-end: 'true'
```

## Usage

### Getting Started

The easiest way to get started is to use SimpleMDE as editor. For that make sure the `user/config/plugins/editable.yaml` file contains this:

```
enabled: true
active_admin: false
editor: simplemde
editable_self: true
```
This will allow front-end users to edit page content right on the page.

### Editable Content Modes

Editable Content mode is about what content is configured as editable and how. There are two modes: Editable Self and Editable Regions. Note that different editors may support all or just some of the available modes.

#### Editable Self

The main scope of front-end editing possibilities is determined by the `editable_self` configuration variable.

The default setting `editable_self: false` prevents complete pages to be editable.
When `editable_self: true` is set in the `user/config/plugins/editable.yaml` configuration file all pages will be editable in the front-end.

On a per page basis, setting `editable_self` in a page's frontmatter makes that entire page content editable in the front-end. The correct YAML to do so in page frontmatter is:

```
editable:
    editable_self: true
```

#### Editable Regions
A very flexible and poweful feature is defining editable regions. By using shortcodes one or more parts of a webpage can be made editable. Here the term 'webpage' is introduced to distinguish the page a regular visitor sees and the Grav page being an object or file.

A webpage can have one or more `editable regions` or parts, each of which is editable in the front-end by inserting one or more shortcodes in the page. Each shortcode links to another Grav page of which the page content is inserted in the 'host' page and can be edited right there.

##### Using Shortcodes
A typical editable region shortcode looks like:

`[editable name="introduction" /]`

The shortcode will be replaced by the content of the page `introduction` wrapped in a `div` or `textarea` element to make it editable by the editor in use. Note that in this example there is no preceding `/` in the name value, which indicates that the page `introduction` is and must be a child page. To refer to non child pages an absolute path must be specified, e.g. `/about-us`.

> Note: The page the shortcode refers to with the name parameter must exist !

### Editors

What an editor can do depends on the editor's features and the way it is configured. The editor specific configuration is done by editing the Javascript config file for that editor, for example `user/plugins/editable/editors/simplemde/js/simplemde_config.js`.   
The configuration per bundled editor is fairly basic and just enough for a decsent demonstration of it's use with the Editable plugin in Grav.

#### ContentTools
ContentTools is a WYSIWYG "in context" editor where content is edited right on the page with the same layout and style as the regular page looks. Content is saved in HTML. ContentTools can be used in Editable Self and in Editable Regions mode.

A configuration file for ContentTools is for example:

```
enabled: true
active_admin: false
editor: contenttools
editable_self: false
```

#### Simple MDE <a id="simplemde"></a>
Simple Markdown Editor is a WYSIWYG-like editor where writers can edit content using markdown syntax either by entering markdown or by using the toolbar buttons. SimpleMDE saves content in markdown format making it very suitable for further content handling in the Grav back-end. Or even beyond, for instance with the [Git Sync](https://github.com/trilbymedia/grav-plugin-git-sync) plugin.   
SimpleMDE can only be used in Editable Self mode. The Editable Regions mode is not supported in the current version of Editable.

To use SimpleMDE start with this configuration:

```
enabled: true
active_admin: false
editor: simplemde
editable_self: true
```

## Todo

Improvements:

- error handling
- create page from shortcode
- code optimalisation
- language handling

## Credits / Thanks

Thanks go to Team Grav and everyone on the [Grav Forum](https://getgrav.org/forum) for creating and supporting Grav. Special thanks to [Patrick Taylor](http://www.patricktaylor.com/) for inspiring me with his [la.plume micro CMS](http://www.mini-print.com/) to go the Flat File CMS path.
