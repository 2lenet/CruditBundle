<?= "<?php" ?>
<?php if ($strictType): ?>


declare(strict_types=1);
<?php endif; ?>

namespace <?= $namespace; ?>;

use App\Crudit\Config\<?= $entityClass ?>CrudConfig;
use Lle\CruditBundle\Provider\ConfigProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lle\CruditBundle\Controller\AbstractCrudController;

/**
 * @Route("/<?= strtolower($entityClass) ?>")
 */
class <?= $entityClass ?>Controller extends AbstractCrudController
{
    /** @var ConfigProvider  */
    private $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @Route("/")
     */
    public function index(Request $request): Response
    {
        $views = $this->getBrickBuilder()->build($this->configProvider->get(<?= $entityClass ?>CrudConfig::class), $request);
        return $this->render('@LleCrudit/crud/index.html.twig', ['views' => $views]);
    }

    /**
     * @Route("/api")
     */
    public function api(Request $request): Response
    {
        $views = $this->getBrickBuilder()->build($this->configProvider->get(<?= $entityClass ?>CrudConfig::class), $request);
        return new JsonResponse($this->getSerializer()->normalize($views));
    }

}