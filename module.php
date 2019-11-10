<?php

namespace MyCustomNamespace;

use Fisharebest\Webtrees\Carbon;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\WebtreesTheme;
use Fisharebest\Webtrees\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Support\Str;
use function response;
use function route;
use function view;

/**
 * Example theme.  Here we are extending an existing theme.
 * Instead, you could extend AbstractModule and implement ModuleThemeInterface directly.
 */
return new class extends WebtreesTheme implements ModuleCustomInterface {
    use ModuleCustomTrait;

    public function customModuleAuthorName(): string {
      return 'Richard Cissée';
    }

    public function customModuleVersion(): string {
      return '2.0.0-beta.5.1';
    }

    public function customModuleLatestVersionUrl(): string {
      return 'https://cissee.de';
    }

    public function customModuleSupportUrl(): string {
      return 'https://cissee.de';
    }
  
    /**
     * @return string
     */
    public function title(): string
    {
        return I18N::translate('webtrees compact');
    }

    /**
     * Bootstrap the module
     */
    public function boot(): void
    {
        // Register a namespace for our views.
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');

        // Replace an existing view with our own version.
        View::registerCustomView('::individual-page', $this->name() . '::individual-page');
    }

    /**
     * Where does this module store its resources
     *
     * @return string
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

    /**
     * Add our own stylesheet to the existing stylesheets.
     *
     * @return array
     */
    public function stylesheets(): array
    {
        $stylesheets = parent::stylesheets();

        // NOTE - a future version of webtrees will allow the modules to be stored in a private folder.
        // Only files in the /public/ folder will be accessible via the webserver.
        // Since modules cannot copy their files to the /public/ folder, they need to provide them via a callback.
        $stylesheets[] = $this->assetUrl('css/theme.css');

        return $stylesheets;
    }
    
    public function assetsViaViews(): array {
      return [
          'css/theme.css' => 'css/theme'];
    }
    
    //adapted from ModuleCustomTrait

    /**
     * Create a URL for an asset.
     *
     * @param string $asset e.g. "css/theme.css" or "img/banner.png"
     *
     * @return string
     */
    public function assetUrl(string $asset): string {
      $assetFile = $asset;
      $assetsViaViews = $this->assetsViaViews();
      if (array_key_exists($asset, $assetsViaViews)) {
        $assetFile = 'views/' . $assetsViaViews[$asset] . '.phtml';
      }

      $file = $this->resourcesFolder() . $assetFile;

      // Add the file's modification time to the URL, so we can set long expiry cache headers.
      //[RC] assume this is also ok for views (i.e. assume the rendered content isn't dynamic)
      $hash = filemtime($file);

      return route('module', [
          'module' => $this->name(),
          'action' => 'asset',
          'asset' => $asset,
          'hash' => $hash,
      ]);
    }
        
    //adapted from ModuleCustomTrait

    /**
     * Serve a CSS/JS file.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function getAssetAction(ServerRequestInterface $request): ResponseInterface {
      // The file being requested.  e.g. "css/theme.css"
      $asset = $request->getQueryParams()['asset'];

      // Do not allow requests that try to access parent folders.
      if (Str::contains($asset, '..')) {
        throw new AccessDeniedHttpException($asset);
      }

      $assetsViaViews = $this->assetsViaViews();
      if (array_key_exists($asset, $assetsViaViews)) {
        $assetFile = $assetsViaViews[$asset];
        $assertRouter = function (string $asset) {
          return $this->assetUrl($asset);
        };
        $content = view($this->name() . '::' . $assetFile, ['assetRouter' => $assertRouter]);
      } else {
        $file = $this->resourcesFolder() . $asset;

        if (!file_exists($file)) {
          throw new NotFoundHttpException($file);
        }

        $content = file_get_contents($file);
      }

      $expiry_date = Carbon::now()->addYears(10)->toDateTimeString();

      $extension = pathinfo($asset, PATHINFO_EXTENSION);

      $mime_types = [
          'css' => 'text/css',
          'gif' => 'image/gif',
          'js' => 'application/javascript',
          'jpg' => 'image/jpg',
          'jpeg' => 'image/jpg',
          'json' => 'application/json',
          'png' => 'image/png',
          'txt' => 'text/plain',
      ];

      $mime_type = $mime_types[$extension] ?? 'application/octet-stream';

      $headers = [
          'Content-Type' => $mime_type,
          'Expires' => $expiry_date,
      ];
      return response($content, 200, $headers);
    }
};
