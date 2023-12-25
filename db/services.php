<?php

defined('MOODLE_INTERNAL') || die();

// for XMLRPC
/*
$functions = array(
    'mod_lticontainer_write_nbdata' => array(               // Service Function Name
        'classname'     => 'mod_lticontainer_external',     // 
        'methodname'    => 'write_nbdata',                  // External Function Name
        'description'   => 'Write Jupyter Notebook data to DB',
        'type'          => 'write',
        'capabilities'  => 'mod/lticontainer:db_write',
    ),
);


$services = array(
    'Jupyter Notebook Data' => array(                       // Service Name
        'functions' => array(
            'mod_lticontainer_write_nbdata',                // Service Function Name
        ),
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'moodle_nbdata'
    )
);
*/


// for REST
$functions = array(
    'mod_lticontainer_write_nblogs' => array(               // Service Function Name
        //'classname'     => 'mod_lticontainer\\external\\write_nblogs',    
        'classname'     => 'mod_lticontainer_external',    
        'methodname'    => 'write_nblogs',
        'classpath'     => 'mod/lticontainer/externallib.php',
        'description'   => 'Write JupyterHub Notebook logs to DB by REST',
        'type'          => 'write',
        //'ajax'          => false,
        'capabilities'  => 'mod/lticontainer:db_write',
    ),
);


$services = array(
    'JupyterHub Notebook Logs' => array(                    // Service Name
        'functions' => array(
            'mod_lticontainer_write_nblogs',                // Service Function Name
        ),
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'mod_lticontainer'
    )
);

