<?php

namespace Biz\Crontab\Dao\Impl;

use Biz\Crontab\Dao\JobDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class JobDaoImpl extends GeneralDaoImpl implements JobDao
{
    protected $table = 'crontab_job';

    public function deleteByTargetTypeAndTargetId($targetType, $targetId)
    {
        return $this->db()->delete($this->table, array('targetType' => $targetType, 'targetId' => $targetId));
    }

    public function findByTargetTypeAndTargetId($targetType, $targetId)
    {
        return $this->findByFields(array('targetType' => $targetType, 'targetId' => $targetId));
    }

    public function findByNameAndTargetTypeAndTargetId($jobName, $targetType, $targetId)
    {
        return $this->findByFields(array('name' => $jobName, 'targetType' => $targetType, 'targetId' => $targetId));
    }

    public function declares()
    {
        return array(
            'serializes' => array(
                'jobParams' => 'json'
            ),
            'orderbys'   => array(
                'createdTime',
                'nextExcutedTime'
            ),
            'conditions' => array(
                "name LIKE :name",
                "cycle = :cycle",
                'jobClass = :jobClass',
                'executing = :executing',
                'nextExcutedTime <= :nextExcutedTime',
                'nextExcutedTime <= :nextExcutedEndTime',
                'nextExcutedTime >= :nextExcutedStartTime',
                'creatorId = :creatorId'
            )
        );
    }
}
