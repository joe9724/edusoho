<?php

namespace Biz\File\FireWall;

use Topxia\Service\Common\ServiceKernel;

class QuestionFileFireWall extends BaseFireWall implements FireWallInterface
{
    public function canAccess($attachment)
    {
        $user = $this->getCurrentUser();
        if ($user->isAdmin()) {
            return true;
        }
        $question = $this->getQuestionService()->get($attachment['targetId']);
        if ($user['id'] == $question['userId']) {
            return true;
        }

        return false;
    }

    protected function getKernel()
    {
        return ServiceKernel::instance();
    }

    protected function getQuestionService()
    {
        return $this->getKernel()->createService('Question:QuestionService');
    }
}
