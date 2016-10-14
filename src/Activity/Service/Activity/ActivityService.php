<?php

namespace Activity\Service\Activity;

interface ActivityService
{
    public function getActivity($id);

    public function createActivity($activity);

    public function updateActivity($id, $fields);

    public function deleteActivity($id);

}