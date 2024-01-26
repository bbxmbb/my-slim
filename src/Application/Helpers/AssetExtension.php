<?php
namespace App\Application\Helpers;

// Your Twig extension class
class AssetExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    private $rootUrl;

    public function __construct($rootUrl)
    {
        $this->rootUrl = $rootUrl;
    }

    public function getGlobals(): array
    {
        return [
            'rootUrl' => $this->rootUrl,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('assetUrl', [$this, 'assetUrl']),
        ];
    }

    public function assetUrl(string $path): string
    {
        // Assuming $this->rootUrl is the root URL of your application
        return $this->rootUrl . $path;
    }
}