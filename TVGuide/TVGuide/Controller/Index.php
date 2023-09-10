<?php
declare(strict_types=1);

namespace TVGuide\Controller;

use DateTimeImmutable;
use Exception;
use TVGuide\Contract\Renderable;
use TVGuide\Module\Channel\Repository\ChannelRepository;
use TVGuide\Module\Database\DbConnection;
use TVGuide\Module\Program\Repository\ProgramRepository;
use TVGuide\Module\TemplateEngine\Exception\TemplateEngineException;
use TVGuide\Module\TemplateEngine\Template;
use TVGuide\Module\TemplateEngine\View;
use TVGuide\View\Model\NavigationBar;
use function _;
use function http_response_code;

final class Index
{
    private $db;
    private $cfg;
    private $pageTitle;

    public function __construct(DbConnection $db, array $cfg)
    {
        $this->db = $db;
        $this->cfg = $cfg;
    }

    /**
     * @return Renderable
     * @throws TemplateEngineException
     */
    public function index(): Renderable
    {
        $datetime = new DateTimeImmutable();
        $this->pageTitle = _('TV-programs') . ' ' . $datetime->format('j.n.Y');
        $programLists = (new ProgramRepository($this->db))->getProgramListsByDate($datetime->format('Y-m-d'));

        return $this->renderProgramLists($datetime, $programLists, false);
    }

    /**
     * @param string $date
     * @return Renderable
     * @throws TemplateEngineException
     * @throws Exception
     */
    public function date(string $date): Renderable
    {
        try {
            $datetime = new DateTimeImmutable($date);
        } catch (Exception $e) {
            http_response_code(404);
            die();
        }

        $this->pageTitle = _('TV-programs') . ' ' . $datetime->format('j.n.Y');
        $programLists = (new ProgramRepository($this->db))->getProgramListsByDate($date);

        return $this->renderProgramLists($datetime, $programLists, true);
    }

    /**
     * @param DateTimeImmutable $date
     * @param array $programLists
     * @param bool $customDateActive
     * @return Renderable
     * @throws TemplateEngineException
     */
    private function renderProgramLists(DateTimeImmutable $date, array $programLists, bool $customDateActive): Renderable
    {
        date_default_timezone_set('Europe/Helsinki');
        $programRepository = new ProgramRepository($this->db);
        [$first, $last] = $programRepository->getStoredProgramTimeRange();
        $groups = (new ChannelRepository($this->db))->getChannelGroups();
        $radio = false;
        $data = [
            'navigationBar' => new NavigationBar(
                $date,
                $customDateActive,
                $first,
                $last
            ),
            'programLists' => $programLists,
            'staticUrl' => $this->cfg['staticUrl'],
            'radio' => $radio,
            'groups' => $groups,
        ];

        $view = new View(__DIR__ . '/../View/BasicProgramView.phtml', $data);
        $view = new Template(__DIR__ . '/../View/Template.phtml', $view, $this->pageTitle, [
            'staticUrl' => $this->cfg['staticUrl'],
        ]);

        return $view;
    }
}