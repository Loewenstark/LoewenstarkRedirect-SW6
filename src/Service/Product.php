<?php declare(strict_types=1);

namespace Loewenstark\Redirect\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;

class Product
{
    /**
     * @var string
     */
    private $csv_path = 'var/loewenstark/redirect/url_export.csv';

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SeoUrlPlaceholderHandlerInterface
     */
    private $seoUrlReplacer;
    
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        SeoUrlPlaceholderHandlerInterface $seoUrlReplacer,
        string $projectDir
    )
    {
        $this->productRepository = $productRepository;
        $this->seoUrlReplacer = $seoUrlReplacer;
        $this->projectDir = $projectDir;
    }

    /*
        Try to find new shopware url by old url
    */
    public function getRedirectUrlFromRequest($context, $request)
    {
        $sku = $this->getSkuByNoRouteUrl($request);

        if(!$sku)
        {
            return false;
        }

        $url = $this->getProductUrlByNumber($sku, $context, $request);

        /*
            TODO:
            Add Event at this point
        */

        return $url;
    }

    /*
        Search in csv generated URL for current no router URL and return the mapped sku
    */
    public function getSkuByNoRouteUrl($request)
    {
        $urls = $this->getProductUrls();
        $noRouteUrl = $request->getPathInfo();
        $noRouteUrlWithoutTrailingSlash = rtrim($noRouteUrl,"/");

        foreach($urls as $url)
        {
            if (strpos($url['url'], $noRouteUrl) !== false || strpos($url['url'], $noRouteUrlWithoutTrailingSlash) !== false) {
                return $url['sku'];
            }
        }

        return false;
    }

    /*
        Read csv with old urls with sku mapping
    */
    public function getProductUrls()
    {
        $out = array();

        $path = $this->projectDir . '/' . $this->csv_path;
        if (($handle = fopen($path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $out[] = array(
                    'sku' => $data[1],
                    'url' => $data[2]
                );
            }
            fclose($handle);
        }

        return $out;
    }

    /*
        Get Shopware Product Url by Product Number
    */
    public function getProductUrlByNumber($number, $salesChannelContext, $request)
    {
        $product = $this->findProductByNumber($number, $salesChannelContext);

        if(!$product)
        {
            return false;
        }
        
        $host = $request->attributes->get(RequestTransformer::STOREFRONT_URL);
        $parameter = ['productId' => $product->getId()];
        $raw = $this->seoUrlReplacer->generate('frontend.detail.page', $parameter);
        
        return $this->seoUrlReplacer->replace($raw, $host, $salesChannelContext);
    }

    /*
        Returns Shopware Product Entity by Product Number
    */
    public function findProductByNumber($number, $salesChannelContext)
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('productNumber', $number));

        $entities = $this->productRepository->search(
            $criteria,
            $salesChannelContext->getContext()
        )->getEntities();

        /** @var ProductEntity */
        foreach ($entities as $entity) {
            return $entity;
        }

        return false;
    }
}
