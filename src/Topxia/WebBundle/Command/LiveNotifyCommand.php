<?php
namespace Topxia\WebBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Topxia\Service\Common\ServiceKernel;
use Topxia\Service\User\CurrentUser;
use Topxia\Common\ArrayToolkit;

class LiveNotifyCommand extends BaseCommand
{

	protected function configure()
	{
		$this->setName ( 'topxia:live-notify' )
			->setDescription('直播通知');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->initServiceKernel();
		$connection = $this->getContainer()->get('database_connection');
	    $connection->beginTransaction();

		$sendTime = date("Y-m-d",time());

		$startDate = $sendTime." 0:00";
		$endDate = $sendTime." 24:00";

		$conditions['startTimeLessThan'] = strtotime($endDate);
		$conditions['startTimeGreaterThan'] = strtotime($startDate);

		$total = $this->getCourseService()->searchLessonCount($conditions);
	    $liveLessons = $this->getCourseService()->searchLessons(
	    	$conditions, array('startTime', 'ASC'), 0, $total
	    );

	    $courseIds = ArrayToolkit::column($liveLessons,'courseId');
	    $courseIds = array_unique($courseIds);
	    $courseIds = array_values($courseIds);
	    
	    if ($courseIds) {

	        $marks = str_repeat('?,', count($courseIds) - 1) . '?';

	        $courseMembers =  $connection->fetchAll("SELECT * FROM `course_member` WHERE courseId IN ({$marks})", $courseIds);

		    foreach ($courseMembers as $key => $value) {

		      $minStartTime = $connection->fetchAll("select min(`startTime`) as startTime from `course_lesson` where courseId ={$value['courseId']};");

		      $minStartTime = date("Y-m-d H:i:s",$minStartTime[0]['startTime']);

		      $this->getNotificationService()->notify($value['userId'], $type="default",  $content = "【直播】您正在学习的《课程名称课程名称》即将于 明天{$minStartTime} 开始直播，请安排好时间准时参加。");
		    }

		  	$output->writeln('<info>消息发布完成</info>');
	    } else {
			$output->writeln('<info>没有消息可以发布</info>');
	    }
	   

	}
	
    protected function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    protected function getKernel()
    {
        return ServiceKernel::instance();
    }

    public function getCurrentUser()
    {
        return $this->getKernel()->getCurrentUser();
    }


	protected function getUserService()
	{
		return $this->getServiceKernel()->createService('User.UserService');
	}

	private function initServiceKernel()
	{
		$serviceKernel = ServiceKernel::create('dev', false);
		$serviceKernel->setConnection($this->getContainer()->get('database_connection'));
		$currentUser = new CurrentUser();
		$currentUser->fromArray(array(
		    'id' => 1,
		    'nickname' => '测试管理员',
		    'currentIp' =>  '127.0.0.1',
		    'roles' => array("ROLE_SUPER_ADMIN"),
		));
		$serviceKernel->setCurrentUser($currentUser);
	}

}