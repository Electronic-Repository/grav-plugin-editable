<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Uri;
use RocketTheme\Toolbox\Event\Event;
use Grav\Common\Filesystem\Folder;
use RocketTheme\Toolbox\File\File;
use Symfony\Component\Yaml\Yaml;


class EditablePlugin extends Plugin
{
    protected $route = 'edtblapi';

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize configuration.
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        // Enable the events we are interested in
        $this->enable([
            'onTwigExtensions' => ['onTwigExtensions', 0],
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0],
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onPagesInitialized' => ['onPagesInitialized', 0],
            'onPageInitialized' => ['onPageInitialized', 0],
            'onPageContentRaw' => ['onPageContentRaw', 0]
        ]);
    }

    /**
     * Add current directory to twig lookup paths.
     */
    public function onTwigTemplatePaths()
    {
        // Add local templates folder to the Twig templates search path
        $editor = $this->config->get('plugins.editable.editor');
        $this->grav['twig']->twig_paths[] = __DIR__ . '/editors/'.$editor.'/templates';
    }


    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/EditableTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new EditableTwigExtension());
    }


    public function onShortcodeHandlers()
    {
        $editor = $this->config->get('plugins.editable.editor');
        // Register the selected editor's shortcode
        $this->grav['shortcode']->registerShortcode($editor.'shortcode.php' , __DIR__.'/editors/'.$editor.'/shortcodes/');
    }


    public function onPageInitialized()
    {
        $page = $this->grav['page'];
        $assets = $this->grav['assets'];
        $assets->addJs('plugin://editable/js/check_browser_close.js', 1);
        // Get editor to use before merging configs (so it can not be changed by the page frontmatter)
        $editor = $this->config->get('plugins.editable.editor');
        $config = $this->mergeConfig($page);
        $editable_self = $config->get('editable_self');
        // Check for a logged in user
        $userAuthorized = $this->grav['user']->authorize('site.front-end');
        if ($userAuthorized) {
            $editor = $this->config->get('plugins.editable.editor');
            // Output some debug info
            $the_username = $this->grav['user']->get('username');
            // Include the editor specific initialisation code block
            $file = __DIR__.'/editors/'.$editor.'/classes/' . $editor . 'processing.php';
            if (file_exists($file)) {
                require_once $file;
                $resourceClassName = '\Grav\Plugin\Editable\\' . ucfirst($editor) . 'Processing';
                $resource = new $resourceClassName($this->grav);
                $resource->addAssets();
            }
            else {
                throw new \Exception('Missing class file "' . $file . '"');
            }
        }
    }


    public function onPageContentRaw(Event $event)
    {
        $page = $this->grav['page'];
        $twig = $this->grav['twig'];
        // Get editor to use before merging configs (so it can not be changed by the page frontmatter)
        $editor = $this->config->get('plugins.editable.editor');
        $config = $this->mergeConfig($page);
        $editable_self = $config->get('editable_self');
        // Get raw content
        $content = $page->getRawContent();
        if (($editable_self === 1) || ($editable_self === "true") || ($editable_self === true)) {
            $userAuthorized = $this->grav['user']->authorize('site.front-end');
            if ($userAuthorized) {
                $file = __DIR__.'/editors/'.$editor.'/classes/' . $editor . 'processing.php';
                if (file_exists($file)) {
                    require_once $file;
                    $resourceClassName = '\Grav\Plugin\Editable\\' . ucfirst($editor) . 'Processing';
                    $resource = new $resourceClassName($this->grav);
                    $name = 'editable' . str_replace('/', '___', $page->route());
                    $this->config->set('plugins.editable.id', $name);
                    $content = $resource->processTemplate($content, $editor . '.html.twig');
                }
            }
        }
        else {
            $content = $page->getRawContent();
        }
        $page->setRawContent($content);
    }

    /**
     * Pass valid actions (via AJAX requests) on to the editor resource to handle
     *
     * @return the output of the editor resource
     */
    public function onPagesInitialized()
    {
        $pages = $this->grav['pages'];
        $uri = $this->grav['uri'];
        // Determine whether passing on should even be considered
        if (strpos($uri->path(), $this->config->get('plugins.editable.route') . '/' . $this->route) === false) {
            return;
        }
        $paths = $this->grav['uri']->paths();
        $paths = array_splice($paths, 1);
        $target = $paths[0];
        $page = $pages->dispatch(str_replace("/edtblapi/pages","",$this->grav['uri']->route()), false);
        if ($page == null) {
            // Page does not exist
            $this->setErrorCode(404);
            $message = $this->buildReturnMessage('Page does not exist.');
            return $message;
        }
        $editor = $this->config->get('plugins.editable.editor');
        $file = __DIR__.'/editors/'.$editor.'/classes/' . $editor . 'processing.php';
        if (file_exists($file)) {
            require_once $file;
            $resourceClassName = '\Grav\Plugin\Editable\\' . ucfirst($editor) . 'Processing';
            $resource = new $resourceClassName($this->grav);
            switch ($target) {
            case 'approve': // unneeded when saveContent can do batch save
                $output = $resource->approveContent($page);
                $this->setHeaders();
                //echo $output;
                echo json_encode($output);
                break;
            case 'images':
                $output = $resource->saveImage($page);
                $this->setHeaders();
                echo json_encode($output);
                break;
            case 'files':
                $output = $resource->saveFile($page);
                $this->setHeaders();
                echo json_encode($output);
                break;
            default:
                $output = $resource->saveContent($page);
                $this->setHeaders();
                echo json_encode($output);
                break;
            }
        }
        else {
            throw new \Exception('Missing class file "' . $file . '"');
        }
        exit();
    }


    // Return shortcode examples
    static function showShortcodeExamples($sc_name)
    {
        switch ($sc_name) {
            case 'example-1':
                $result = "[editable name=\"example\" /]\n";
                break;
            case 'example-2':
                $result = "[editable name=\"example\"]Placeholder text[/editable]\n";
                break;
            case 'example-3':
                $result = "[editable name=\"example\"]\nPlaceholder text\n[/editable]\n";
                break;
            default:
                $result = "Invalid Editable shortcode \"example.\"\n";
        }
        return nl2br($result);
    }


    public function setHeaders()
    {
        header('Content-type: application/json');

        // Calculate Expires Headers if set to > 0
        $expires = $this->grav['config']->get('system.pages.expires');
        if ($expires > 0) {
            $expires_date = gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT';
            header('Cache-Control: max-age=' . $expires);
            header('Expires: '. $expires_date);
        }
    }

    /**
     * Get the identifier name
     *
     * @return string the resource identifier name
     */
    public function getIdentifier()
    {
        $paths = $this->grav['uri']->paths();
        $paths = array_splice($paths, 2);

        $identifier = join('/', $paths);
        return $identifier;
    }

}
