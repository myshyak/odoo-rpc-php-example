<?php
require_once('../ripcordr/ripcord.php');

function insert_registration($server, $r) { 
    $info = ripcord::client($server['url'])->start();
    $common = ripcord::client($server['url'].'common');
    $uid = $common->authenticate($server['db'], $server['username'], $server['password'], array());
    echo("uid: \n");
    var_dump($uid);

    // отримання ID замовника -- початок
    echo("partner_id start \n");
    $models = ripcord::client($server['url'].'object');
    // пошук замовника за параметрами можливо id, name, email, phone тощо
    // $partner = $models->execute_kw($server['db'], $uid, $server['password'],
    //     'res.partner', 'search', array(
    //         array(array('email', '=', $r['customer']['email']),)));
    // var_dump($partner);

    // але в прикладі спочатку спробуємо шукати по зовнішньому ID
    $external_ids = $models->execute_kw($server['db'], $uid, $server['password'],
        'ir.model.data', 'search', array(
            array(
                array('module', '=', "site_customer_code"),
                array('model', '=', "res.partner"),
                array('name', '=', $r['customer']['id']))));
    var_dump($external_ids);
    if ($external_ids) {
        $external_id = $models->execute_kw($server['db'], $uid, $server['password'],
            'ir.model.data', 'read', array($external_ids));
        $partner_id = $external_id[0]['res_id'];                        
    } else {
        // якщо з пошуком за ID нас спіткала невдачка
        // повертаємось до пошуку за телефоном та ємейлом
        $partner = $models->execute_kw($server['db'], $uid, $server['password'],
            'res.partner', 'search', array(
                array(
                    array('email', '=', $r['customer']['email']),
                    array('phone', '=', $r['customer']['phone']),)));

        if(count($partner)) {
            $partner_id = $partner[0];
        }
        else {
            $partner_id = $models->execute_kw($server['db'], $uid, $server['password'],
                'res.partner', 'create', array(array(
                    'name'=>$r['customer']['name'], 
                    'email'=>$r['customer']['email'],
                    'phone'=>$r['customer']['phone']),
                ));
            }
        // збережемо ID для наступних поколінь
        $models->execute_kw($server['db'], $uid, $server['password'],
            'ir.model.data', 'create', array(
                array(
                    'module'=>"site_customer_code", 
                    'model'=>"res.partner", 
                    'name'=>$r['customer']['id'],
                    'res_id'=>$partner_id
                )));   
    }
    echo("partner_id: \n");
    var_dump($partner_id);
    // отримання ID замовника -- завершення

    // отримання ID мови туру -- початок
    if ($r['jaunt']['language'] and is_array($r['jaunt']['language'])){

        $models = ripcord::client($server['url'].'object');

        // повторюємо фокус пошуку по ID
        $external_ids = $models->execute_kw($server['db'], $uid, $server['password'],
                'ir.model.data', 'search', array(
                    array(
                        array('module', '=', "site_language_code"),
                        array('model', '=', "jaunt.language"),
                        array('name', '=', $r['jaunt']['language']['id']))));

        if ($external_ids) {
            $external_id = $models->execute_kw($server['db'], $uid, $server['password'],
                'ir.model.data', 'read', array($external_ids));
            $lang_id = $external_id[0]['res_id'];                        
        } else {
            // ну ми намагалися, спробуємо за назвою
            $lang = $models->execute_kw($server['db'], $uid, $server['password'],
                'jaunt.language', 'search', array(
                    array(array('name', '=', $r['jaunt']['language']['name']))));        
            if(count($lang)) {
                $lang_id = $lang[0];
            } 
            else {
                $lang_id = $models->execute_kw($server['db'], $uid, $server['password'],
                    'jaunt.language', 'create', array(
                        array('name'=>$r['jaunt']['language']['name'])));
            }
            $models->execute_kw($server['db'], $uid, $server['password'],
                'ir.model.data', 'create', array(
                    array(
                        'module'=>"site_language_code", 
                        'model'=>"jaunt.language",
                        'name'=>$r['jaunt']['language']['id'],
                        'res_id'=>$lang_id)));  
        }    
    } 
    else {
        // якщо мову не задано, но ID не передаємо
        $lang_id = false;
    }

    var_dump($lang_id);
    // отримання ID мови туру  -- завершення


    // отримання ID гіду туру -- початок
    if ($r['jaunt']['guide'] and is_array($r['jaunt']['guide'])){
        $models = ripcord::client($server['url'].'object');
        $external_ids = $models->execute_kw($server['db'], $uid, $server['password'],
                'ir.model.data', 'search', array(
                    array(
                        array('module', '=', "site_guide_code"),
                        array('model', '=', "res.partner"),
                        array('name', '=', $r['jaunt']['guide']['id']))));
        if ($external_ids) {
            $external_id = $models->execute_kw($server['db'], $uid, $server['password'],
                'ir.model.data', 'read', array($external_ids));
            $guide_id = $external_id[0]['res_id'];                        
        } else { 
            $guide = $models->execute_kw($server['db'], $uid, $server['password'],
                'res.partner', 'search', array(
                    array(array('name', '=', $r['jaunt']['guide']['name']))));        
            if(count($lang)) {
                $guide_id = $guide[0];
            } 
            else {
                $guide_id = $models->execute_kw($server['db'], $uid, $server['password'],
                    'res.partner', 'create', array(
                        array('name'=>$r['jaunt']['guide']['name'])));
            }
            $models->execute_kw($server['db'], $uid, $server['password'],
                'ir.model.data', 'create', array(
                    array(
                        'module'=>"site_guide_code", 
                        'model'=>"res.partner",
                        'name'=>$r['jaunt']['guide']['id'],
                        'res_id'=>$guide_id)));  
        }    
    } 
    else {
        // якщо гіда не задано, но ID не передаємо
        $guide_id = false;
    }

    var_dump($guide_id);
    // отримання ID гіду туру  -- завершення


    // отримання ID туру у каталозі -- початок
    $models = ripcord::client($server['url'].'object');

    $external_ids = $models->execute_kw($server['db'], $uid, $server['password'],
        'ir.model.data', 'search', array(
            array(
                array('module', '=', "site_catalog_code"),
                array('model', '=', "jaunt.catalog"),
                array('name', '=', $r['jaunt']['catalog']['id']))));
    if ($external_ids) {
        $external_id = $models->execute_kw($server['db'], $uid, $server['password'],
            'ir.model.data', 'read', array($external_ids));
        $catalog_id = $external_id[0]['res_id'];                        
    } else {
        $catalog = $models->execute_kw($server['db'], $uid, $server['password'],
            'jaunt.catalog', 'search', array(
                array(array('name', '=', $r['jaunt']['catalog']['name']))));;
        //var_dump($catalog);
        if(count($catalog)) {
            $catalog_id = $catalog[0];
        }
        else {
            $catalog_id = $models->execute_kw($server['db'], $uid, $server['password'],
            'jaunt.catalog', 'create', array(
                array('name'=>$r['jaunt']['catalog']['name'],
                    'language_ids'=>array(4,$lang_id),
                    'guide_ids'=>array(4,$lang_id) ),
            ));
        }
        $models->execute_kw($server['db'], $uid, $server['password'],
            'ir.model.data', 'create', array(
                array(
                    'module'=>"site_catalog_code", 
                    'model'=>"jaunt.catalog",
                    'name'=>$r['jaunt']['catalog']['id'],
                    'res_id'=>$catalog_id)));  
    }
    var_dump($catalog_id);
    // отримання ID туру у каталозі  -- завершення


    # отримання ID туру у розкладі -- початок
    $models = ripcord::client($server['url'].'object');

    $external_ids = $models->execute_kw($server['db'], $uid, $server['password'],
        'ir.model.data', 'search', array(
            array(
                array('module', '=', "site_jaunt_code"),
                array('model', '=', "jaunt.jaunt"),
                array('name', '=', $r['jaunt']['id']))));
    if ($external_ids) {
        $external_id = $models->execute_kw($server['db'], $uid, $server['password'],
            'ir.model.data', 'read', array($external_ids));
        $jaunt_id = $external_id[0]['res_id'];                        
    } else {

        $jaunt = $models->execute_kw($server['db'], $uid, $server['password'],
            'jaunt.jaunt', 'search', array(
                array(
                    array('catalog_id', '=', $catalog_id),
                    array('date_begin', '=', $r['jaunt']['date_begin']))));
        //var_dump($partner);
        if(count($jaunt)) {
            $jaunt_id = $jaunt[0];
        }
        else {
            $jaunt_id = $models->execute_kw($server['db'], $uid, $server['password'],
            'jaunt.jaunt', 'create', array(array(
                'catalog_id'=>$catalog_id, 
                'date_begin'=>$r['jaunt']['date_begin'],
                'is_individual'=>false,
                'language_id'=>$lang_id,
                'guide_id'=>$guide_id),
            ));
        } 

        $models->execute_kw($server['db'], $uid, $server['password'],
            'ir.model.data', 'create', array(
                array(
                    'module'=>"site_catalog_code", 
                    'model'=>"jaunt.catalog",
                    'name'=>$r['jaunt']['id'],
                    'res_id'=>$jaunt_id)));  
    }
    var_dump($jaunt_id);
    // отримання ID туру у розкладі  -- завершення


    // отримання ID реєстраціі на тур у розкладі -- початок
    $models = ripcord::client($server['url'].'object');
    // для запису контакту стільки разів, склільки він заповнив форму 
    // використовуємо пошук по ID

    $registration = $models->execute_kw($server['db'], $uid, $server['password'],
        'ir.model.data', 'search', array(
            array(array('model', '=', "jaunt.registration"),array('name', '=', "33311"))));
    //var_dump($registration);
    if(count($registration)) {
        $registration_id = $registration[0];
    }
    else {
        // $ticket_partner == 'bodo' значення поля партнер
        if ($ticket_partner){
            $external_ids = $models->execute_kw($server['db'], $uid, $server['password'],
                'ir.model.data', 'search', array(
                    array(array('model', '=', "res.partner"),
                    array('name', '=', $ticket_partner))));
            if ($external_ids) {
                $ticket_partner_id = $external_ids[0]['res_id'];                        
            } else {
                $ticket_partner_id = $models->execute_kw($server['db'], $uid, $server['password'],
                    'res.partner', 'create', array(array(
                        'name'=>$ticket_partner, 
                        'company'=>1,
                        'jaunt_is_partner'=>1
                    )));
                $models->execute_kw($server['db'], $uid, $server['password'],
                    'ir.model.data', 'create', array(array(
                        'model'=>"res.partner", 
                        'name'=>$ticket_partner
                    )));    
            }
        }
        else {
            $ticket_partner_id = false;
        }

        $tp_ids = $models->execute_kw($server['db'], $uid, $server['password'],
        'jaunt.site.ticket.preset', 'search', array(
            array(
                array('is_paid', '=', 1),
                array('ticket_type', '=', 'standart'),
                array('coupon_type', '=', ''),
                array('partner_company_id', '=', $ticket_partner_id)
            )));


        $registration_id = $models->execute_kw($server['db'], $uid, $server['password'],
        'jaunt.registration', 'create', array(array(
            'jaunt_id'=>$jaunt_id, 
            'contact_id'=>$partner_id,
            'is_paid'=>1,
            'visitors_qty'=>'1'),
        ));
        $external_ids = $models->execute_kw($server['db'], $uid, $server['password'],
        'ir.model.data', 'create', array(array(
            'model'=>"jaunt.registration", 
            'name'=>"33311",
            'res_id'=>$registration_id),
        ));
    }
    var_dump($registration_id);
    // отримання ID реєстраціі на тур у розкладі  -- завершення
}