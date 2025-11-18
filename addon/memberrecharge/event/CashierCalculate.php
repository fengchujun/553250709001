<?php

namespace addon\memberrecharge\event;

use addon\memberrecharge\model\cashier\Calculate;

class CashierCalculate
{
    public function handle($params=[])
    {
        $calculate_model = new Calculate();
        $res = $calculate_model->calculate($params);
        return $res;
    }
}