<?php declare(strict_types=1);

namespace Shopware\Storefront\Controller;

use Shopware\Core\Content\Product\SalesChannel\ProductReviewService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Shopware\Storefront\Framework\Seo\SeoUrlPlaceholderHandler;
use Shopware\Storefront\Page\Product\Configurator\ProductCombinationFinder;
use Shopware\Storefront\Page\Product\ProductPageLoader;
use Shopware\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class ProductController extends StorefrontController
{
    /**
     * @var ProductPageLoader
     */
    private $productPageLoader;

    /**
     * @var ProductCombinationFinder
     */
    private $combinationFinder;

    /**
     * @var MinimalQuickViewPageLoader
     */
    private $minimalQuickViewPageLoader;

    /**
     * @var ProductReviewService
     */
    private $productReviewService;

    /**
     * @var SeoUrlPlaceholderHandler
     */
    private $seoUrlPlaceholderHandler;

    public function __construct(
        ProductPageLoader $productPageLoader,
        ProductCombinationFinder $combinationFinder,
        MinimalQuickViewPageLoader $minimalQuickViewPageLoader,
        ProductReviewService $productReviewService,
        SeoUrlPlaceholderHandler $seoUrlPlaceholderHandler
    ) {
        $this->productPageLoader = $productPageLoader;
        $this->combinationFinder = $combinationFinder;
        $this->minimalQuickViewPageLoader = $minimalQuickViewPageLoader;
        $this->productReviewService = $productReviewService;
        $this->seoUrlPlaceholderHandler = $seoUrlPlaceholderHandler;
    }

    /**
     * @HttpCache()
     * @Route("/detail/{productId}", name="frontend.detail.page", methods={"GET"})
     */
    public function index(SalesChannelContext $context, Request $request): Response
    {
        $page = $this->productPageLoader->load($request, $context);

        $ratingSuccess = $request->get('success');

        return $this->renderStorefront('@Storefront/page/product-detail/index.html.twig', ['page' => $page, 'ratingSuccess' => $ratingSuccess]);
    }

    /**
     * @HttpCache()
     * @Route("/detail/{productId}/switch", name="frontend.detail.switch", methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function switch(string $productId, Request $request, SalesChannelContext $context): JsonResponse
    {
        $switchedOption = $request->query->get('switched');

        $newOptions = json_decode($request->query->get('options'), true);

        $redirect = $this->combinationFinder->find($productId, $switchedOption, $newOptions, $context);

        $url = $this->seoUrlPlaceholderHandler->generateResolved(
            $request,
            'frontend.detail.page',
            ['productId' => $redirect->getVariantId()]
        );

        return new JsonResponse(['url' => $url]);
    }

    /**
     * @Route("/quickview/{productId}", name="widgets.quickview.minimal", methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    public function quickviewMinimal(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->minimalQuickViewPageLoader->load($request, $context);

        return $this->renderStorefront('@Storefront/component/product/quickview/minimal.html.twig', ['page' => $page]);
    }

    /**
     * @Route("/product/{productId}/rating", name="frontend.detail.review.save", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function saveReview(string $productId, RequestDataBag $data, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();

        try {
            $this->productReviewService->save($productId, $data, $context);
        } catch (ConstraintViolationException $formViolations) {
            return $this->forward('Shopware\Storefront\Controller\ProductController::loadReviews', [
                'productId' => $productId,
                'success' => -1,
                'formViolations' => $formViolations,
                'data' => $data,
            ], ['productId' => $productId]);
        }

        $forwardParams = [
            'productId' => $productId,
            'success' => 1,
            'data' => $data,
        ];

        if ($data->has('id')) {
            $forwardParams['success'] = 2;
        }

        return $this->forward('Shopware\Storefront\Controller\ProductController::loadReviews', $forwardParams);
    }

    /**
     * @Route("/product/{productId}/reviews", name="frontend.product.reviews", methods={"GET","POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function loadReviews(Request $request, RequestDataBag $data, SalesChannelContext $context): Response
    {
        $page = $this->productPageLoader->load($request, $context);

        return $this->renderStorefront('page/product-detail/review/review.html.twig', [
            'page' => $page,
            'ratingSuccess' => $request->get('success'),
        ]);
    }
}
