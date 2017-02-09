<?php
namespace AppBundle\Controller;

use Biz\CloudPlatform\Service\AppService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\ThreadService;
use Biz\Search\Service\SearchService;
use Biz\System\Service\SettingService;
use Biz\Taxonomy\Service\CategoryService;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use Vip\Service\Vip\LevelService;
use Vip\Service\Vip\VipService;

class SearchController extends BaseController
{
    public function indexAction(Request $request)
    {
        $courses = $paginator = null;

        $currentUser = $this->getCurrentUser();

        $keywords = $request->query->get('q');
        $keywords = $this->filterKeyWord(trim($keywords));

        $cloud_search_setting = $this->getSettingService()->get('cloud_search', array());

        if (isset($cloud_search_setting['search_enabled']) && $cloud_search_setting['search_enabled'] && $cloud_search_setting['status'] == 'ok') {
            return $this->redirect($this->generateUrl('cloud_search', array(
                'q' => $keywords
            )));
        }

        $vip = $this->getAppService()->findInstallApp('Vip');

        $isShowVipSearch = $vip && version_compare($vip['version'], "1.0.7", ">=");

        $currentUserVipLevel = "";
        $vipLevelIds         = "";

        if ($isShowVipSearch) {
            $currentUserVip      = $this->getVipService()->getMemberByUserId($currentUser['id']);
            $currentUserVipLevel = $this->getLevelService()->getLevel($currentUserVip['levelId']);
            $vipLevels           = $this->getLevelService()->findAllLevelsLessThanSeq($currentUserVipLevel['seq']);
            $vipLevelIds         = ArrayToolkit::column($vipLevels, "id");
        }

        $parentId   = 0;
        $categories = $this->getCategoryService()->findAllCategoriesByParentId($parentId);

        $categoryIds = array();

        foreach ($categories as $key => $category) {
            $categoryIds[$key] = $category['name'];
        }

        $categoryId = $request->query->get('categoryIds');
        $filter     = $request->query->get('filter');

        $conditions = array(
            'status'     => 'published',
            'title'      => $keywords,
            'categoryId' => $categoryId,
            'parentId'   => 0
        );

        if ($filter == 'vip') {
            $conditions['vipLevelIds'] = $vipLevelIds;
        } elseif ($filter == 'live') {
            $conditions['type'] = 'live';
        } elseif ($filter == 'free') {
            $conditions['price'] = '0.00';
        }

        $count     = $this->getCourseService()->searchCourseCount($conditions);
        $paginator = new Paginator(
            $this->get('request'),
            $count
            , 12
        );
        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            'latest',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('search/index.html.twig', array(
            'courses'             => $courses,
            'paginator'           => $paginator,
            'keywords'            => $keywords,
            'isShowVipSearch'     => $isShowVipSearch,
            'currentUserVipLevel' => $currentUserVipLevel,
            'categoryIds'         => $categoryIds,
            'filter'              => $filter,
            'count'               => $count
        ));
    }

    public function cloudSearchAction(Request $request)
    {
        $pageSize = 10;
        $keywords = $request->query->get('q');
        $keywords = $this->filterKeyWord(trim($keywords));

        $type = $request->query->get('type', 'course');
        $page = $request->query->get('page', '1');

        if (empty($keywords)) {
            return $this->render('search/cloud-search-failure.html.twig', array(
                'keywords'     => $keywords,
                'type'         => $type,
                'errorMessage' => '在上方搜索框输入关键词进行搜索.'
            ));
        }
        $conditions = array(
            'type'  => $type,
            'words' => $keywords,
            'page'  => $page
        );

        if ($type == 'teacher') {
            $pageSize              = 9;
            $conditions['type']    = 'user';
            $conditions['num']     = $pageSize;
            $conditions['filters'] = json_encode(array('role' => 'teacher'));
        } elseif ($type == 'thread') {
            $conditions['filters'] = json_encode(array('targetType' => 'group'));
        }

        try {
            list($resultSet, $counts) = $this->getSearchService()->cloudSearch($type, $conditions);
        } catch (\Exception $e) {
            return $this->render('search/cloud-search-failure.html.twig', array(
                'keywords'     => $keywords,
                'type'         => $type,
                'errorMessage' => '搜索失败，请稍候再试.'
            ));
        }

        $paginator = new Paginator($this->get('request'), $counts, $pageSize);

        return $this->render('search/cloud-search.html.twig', array(
            'keywords'  => $keywords,
            'type'      => $type,
            'resultSet' => $resultSet,
            'counts'    => $counts,
            'paginator' => $paginator
        ));
    }

    private function filterKeyWord($keyword)
    {
        $keyword = str_replace('<', '', $keyword);
        $keyword = str_replace('>', '', $keyword);
        $keyword = str_replace("'", '', $keyword);
        $keyword = str_replace("\"", '', $keyword);
        $keyword = str_replace('=', '', $keyword);
        $keyword = str_replace('&', '', $keyword);
        $keyword = str_replace('/', '', $keyword);
        return $keyword;
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    /**
     * @return ThreadService
     */
    protected function getThreadService()
    {
        return $this->getBiz()->service('Course:ThreadService');
    }

    /**
     * @return AppService
     */
    protected function getAppService()
    {
        return $this->getBiz()->service('CloudPlatform:AppService');
    }

    /**
     * @return LevelService
     */
    protected function getLevelService()
    {
        return $this->getBiz()->service('VipPlugin:Vip:LevelService');
    }

    /**
     * @return VipService
     */
    protected function getVipService()
    {
        return $this->getBiz()->service('VipPlugin:Vip:VipService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->getBiz()->service('Taxonomy:CategoryService');
    }

    /**
     * @return SearchService
     */
    protected function getSearchService()
    {
        return $this->getBiz()->service('Search:SearchService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }
}
