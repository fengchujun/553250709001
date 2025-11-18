<?php

namespace app\job;

use app\model\member\Register;
use think\facade\Log;
use think\queue\Job;

class MemberRegisterAfter
{
    public function fire(Job $job, $data)
    {
        $job->delete();
        try {
            (new Register())->memberRegisterAfter($data);
        } catch (\Exception $e) {
            Log::write($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

}
