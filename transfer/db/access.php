<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 01/03/2017
 * Time: 16:31
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'gradereport/transfer:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )

);