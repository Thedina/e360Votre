<?php
    require_once APP_PATH . '/eprocess360/v3core/src/Configuration.php';
    use eprocess360\v3core\Configuration;
    global $pool;

    $dir = mod_dir();

    switch($dir[2]) {
        case 'ssl-opt':
            Configuration::initPool();
            Configuration::initTwig();
            $form = Configuration::getSSLOptForm();
            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                Configuration::acceptForm($form, 'processSSLOptForm');
            }

            echo $form->render();
            break;
        case 'ssl':
            if($dir[3] == 'get-csr') {
                Configuration::sslServeCSR();
            }
            elseif($dir[3] == 'interstitial') {
                Configuration::initPool();
                Configuration::initTwig();

                echo Configuration::renderInterstitial(['Download CSR'=>'/setup/ssl/get-csr', 'Next'=>'/']);
            }
            elseif($dir[3] == 'sign') {
                $form = Configuration::getFileUploadForm('ssl/sign', 'Upload CRT File');

                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    Configuration::acceptForm($form, 'processSSLSign');
                }

                echo $form->render();
            }
            else {
                Configuration::initPool();
                Configuration::initTwig();

                $form = Configuration::getSSLForm();

                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    Configuration::acceptForm($form, 'processSSLForm');
                }

                echo $form->render();
            }
            break;
        case 'conn':
            Configuration::initPool();
            Configuration::initTwig();

            $form = Configuration::getConnForm();

            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                Configuration::acceptForm($form, 'processConnForm');
            }

            echo $form->render();
            break;
        case 'db-reset':
            Configuration::initPool();
            Configuration::initTwig();

            $form = Configuration::getDBResetForm();

            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                Configuration::acceptForm($form, 'processDBResetForm');
            }

            echo $form->render();
            break;
        case 'config':
            Configuration::initPool();
            Configuration::initTwig();
            Configuration::initSysVar();

            $configStep = $pool->SysVar->get('configStep');

            if($dir[3] != $configStep) {
                header('Location: /setup/config/'.$configStep);
            }
            $form = Configuration::getConfigForm((int)$dir[3]);

            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                Configuration::acceptForm($form, 'processConfigForm');
            }

            echo $form->render();
            break;
        case 'user':
            Configuration::initPool();
            Configuration::initTwig();
            Configuration::initSysVar();

            $form = Configuration::getUserForm();

            if($_SERVER['REQUEST_METHOD'] == 'POST') {
                Configuration::acceptForm($form, 'processUserForm');
            }

            echo $form->render();
            break;
    }