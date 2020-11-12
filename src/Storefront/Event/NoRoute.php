<?php declare(strict_types=1);

namespace Loewenstark\Redirect\Storefront\Event;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\PlatformRequest;
use Shopware\Core\SalesChannelRequest;
use Loewenstark\Redirect\Service\Product;
use Symfony\Component\HttpFoundation\RedirectResponse;



class NoRoute implements EventSubscriberInterface
{
    /**
     * @var Product
     */
    private $productService;

    /**
     * @var SalesChannelContextServiceInterface
     */
    private $contextService;

    public function __construct(
        Product $productService,
        SalesChannelContextServiceInterface $contextService
        )
    {
        $this->productService = $productService;
        $this->contextService = $contextService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'  
        ];
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();
        $request = $event->getRequest();
        $context = $this->getSalesChannelContext($request);

        if (!$request || !$context)
        {
            return;
        }
    
        if ($exception instanceof NotFoundHttpException)
        {
            //try to find new product url
            $url = $this->productService->getRedirectUrlFromRequest($context, $request);
            if($url)
            {
                $response = new RedirectResponse($url, 301);
                $event->setResponse($response);
            }
        }

        return;
    }

    /*
        Get Sales Channel Context from Request, because at this event state the context is not loaded by shopware
    */
    public function getSalesChannelContext($request)
    {
        $context = false;
        
        $contextToken = $request->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN);
        $salesChannelId = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID);
        $language = $request->headers->get(PlatformRequest::HEADER_LANGUAGE_ID);
        $currencyId = $request->attributes->get(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID);

        $context = $this->contextService->get(
            $salesChannelId,
            $contextToken,
            $language,
            $currencyId
        );

        return $context;
    }
}
